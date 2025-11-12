// src/app/features/auth/register.page.ts
import { Component, HostListener, PLATFORM_ID, inject, signal } from '@angular/core';
import { CommonModule, isPlatformBrowser } from '@angular/common';
import { ReactiveFormsModule, FormBuilder, Validators } from '@angular/forms';
import { Router, RouterLink, ActivatedRoute } from '@angular/router';
import { HttpErrorResponse } from '@angular/common/http';
import { AuthApiService, RegisterDto } from './auth-api.service';
import { FormErrorMapper } from '@app/core/errors/form-error.mapper';
import { ToastService } from '@app/core/ui/toast/toast.service';
import { ToastContainerComponent } from '@app/core/ui/toast/toast-container.component';

type FormKey = 'firstName' | 'lastName' | 'userName' | 'email' | 'password' | 'hp';

@Component({
  standalone: true,
  selector: 'app-register',
  imports: [CommonModule, ReactiveFormsModule, RouterLink, ToastContainerComponent],
  templateUrl: './register.page.html',
})
export class RegisterPage {
  private fb         = inject(FormBuilder);
  private auth       = inject(AuthApiService);
  private router     = inject(Router);
  private toast      = inject(ToastService);
  private platformId = inject(PLATFORM_ID);
  private isBrowser  = isPlatformBrowser(this.platformId);
  private errmap     = inject(FormErrorMapper);
  private route      = inject(ActivatedRoute);

  private readonly baseInput =
    'w-full rounded-2xl bg-white text-[color:var(--brand)] p-2.5 ring-1 focus:outline-none focus:ring-2 scroll-mt-32';

  submitted = signal(false);
  loading   = signal(false);
  error     = signal<string | null>(null);
  success   = signal(false);

  private pulse = signal<Set<FormKey>>(new Set());
  private formStartAt = this.isBrowser ? performance.now() : 0;

  form = this.fb.group({
    firstName: ['', [Validators.maxLength(255)]],
    lastName:  ['', [Validators.maxLength(255)]],
    userName:  ['', [Validators.maxLength(255)]],
    email:     ['', [Validators.required, Validators.email]],
    password:  ['', [Validators.required, Validators.minLength(12)]],
    hp:        [''],
  });

  constructor() {
    (['firstName','lastName','userName','email','password','hp'] as const).forEach(k => {
      const c = this.form.get(k)!;
      c.valueChanges.subscribe(() => {
        const errs = c.errors || {};
        if ('server' in errs) {
          const { server, ...rest } = errs;
          c.setErrors(Object.keys(rest).length ? rest : null);
        }
        if (k !== 'hp') this.pulseOff(k as Exclude<FormKey,'hp'>);
      });
    });
  }

  /* ---------------------- UI helpers ---------------------- */
  private pulseOn(ctrl: Exclude<FormKey,'hp'>) {
    const s = new Set(this.pulse()); s.add(ctrl); this.pulse.set(s);
  }
  private pulseOff(ctrl: Exclude<FormKey,'hp'>) {
    const s = new Set(this.pulse()); if (s.delete(ctrl)) this.pulse.set(s);
  }
  private pulseClearAll() { this.pulse.set(new Set()); }

  inputClass(ctrl: Exclude<FormKey,'hp'>) {
    const c = this.form.get(ctrl)!;
    const showRed = this.hasServerError(ctrl) || (this.submitted() && c.invalid);
    const base = showRed
      ? `${this.baseInput} ring-2 ring-rose-500 focus:ring-rose-500 border border-rose-300 bg-rose-50/40`
      : `${this.baseInput} ring-[color:var(--brand)]/25`;
    const pulsing = this.pulse().has(ctrl) ? ' ring-3 ring-rose-400 ring-offset-1' : '';
    return base + pulsing;
  }
  ariaInvalid(ctrl: Exclude<FormKey,'hp'>) {
    const c = this.form.get(ctrl)!;
    return this.hasServerError(ctrl) || (this.submitted() && c.invalid);
  }
  msg(ctrl: Exclude<FormKey,'hp'>) {
    return this.form.get(ctrl)?.errors?.['server'] as string | undefined;
  }
  hasServerError(ctrl: Exclude<FormKey,'hp'>) {
    return !!this.form.get(ctrl)?.errors?.['server'];
  }

  private setFieldError(ctrl: string, message: string): boolean {
    const c = this.form.get(ctrl);
    if (!c) return false;
    c.setErrors({ ...(c.errors ?? {}), server: message });
    c.markAsTouched();
    c.markAsDirty();
    c.updateValueAndValidity({ onlySelf: true, emitEvent: false });
    if (['firstName','lastName','userName','email','password'].includes(ctrl)) {
      this.pulseOn(ctrl as Exclude<FormKey,'hp'>);
    }
    return true;
  }

  private clearServerErrors() {
    (['firstName','lastName','userName','email','password','hp'] as const).forEach(k => {
      const c = this.form.get(k);
      if (!c) return;
      const { server, ...rest } = c.errors || {};
      c.setErrors(Object.keys(rest).length ? rest : null);
      c.updateValueAndValidity({ onlySelf: true, emitEvent: false });
    });
    this.pulseClearAll();
  }

  private highlightAllErrors() {
    const order: Array<Exclude<FormKey,'hp'>> = ['userName','email','password','firstName','lastName'];
    let focused = false;
    for (const k of order) {
      if (this.hasServerError(k)) {
        this.pulseOn(k);
        if (!focused && this.isBrowser) {
          focused = true;
          const el = document.querySelector<HTMLElement>(`[formControlName="${k}"]`);
          el?.scrollIntoView({ behavior: 'smooth', block: 'center' });
          (el as HTMLInputElement)?.focus?.({ preventScroll: true });
        }
      }
    }
  }

  /* ---------------- Error normalization & toasts ---------------- */
  private normalizeError(raw: unknown): any {
    let e: any = (raw as any)?.error ?? raw;
    if (typeof e === 'string') { try { e = JSON.parse(e); } catch { e = { message: e }; } }
    if (e?.error && typeof e.error === 'object') e = e.error;
    return e;
  }

  private detect(text: string) {
    const t = text.toLowerCase();
    const emailTaken =
      /(email|correo)[^\n]*?(ya\s*est[aá]\s*en\s*uso|ocupado|en\s*uso|taken|in\s*use|exist[es]|used)/.test(t) ||
      t.includes('email_taken');
    const usernameTaken =
      /(usuario|nombre\s*de\s*usuario|username|user\s*name)[^\n]*?(ya\s*existe|ocupado|en\s*uso|taken|in\s*use|exist[es]|used)/.test(t) ||
      t.includes('username_taken');
    const emailGeneric = /\b(email|correo)\b/.test(t);
    return { emailTaken, usernameTaken, emailGeneric };
  }

  private applyServerErrors(err: HttpErrorResponse): boolean {
    const norm = this.normalizeError(err);
    let applied = false;

    const map: Record<string,string> = {
      firstName: 'firstName',
      lastName:  'lastName',
      userName:  'userName',
      username:  'userName',
      email:     'email',
      password:  'password',
    };

    const details = norm.details;
    if (details && typeof details === 'object') {
      for (const [field, raw] of Object.entries(details as Record<string, string | string[]>)) {
        if (field === 'message') continue;
        const msgs = Array.isArray(raw) ? raw : [raw];
        for (const m of msgs) {
          applied ||= this.setFieldError(map[field] ?? field, String(m || 'Valor inválido.'));
        }
      }
      const dm = (details as any).message;
      const dmMsgs = typeof dm === 'string' ? [dm] : Array.isArray(dm) ? dm : [];
      for (const m of dmMsgs) {
        const { emailTaken, usernameTaken, emailGeneric } = this.detect(String(m));
        if (usernameTaken) this.setFieldError('userName', 'Este nombre de usuario ya existe.');
        if (emailTaken)    this.setFieldError('email',    'Este email ya está en uso.');
        if (!emailTaken && emailGeneric) this.setFieldError('email', 'Email inválido.');
      }
    }

    if (Array.isArray(norm.violations)) {
      for (const v of norm.violations) {
        const field = map[v?.propertyPath ?? ''] ?? v?.propertyPath;
        if (field) applied ||= this.setFieldError(field, v?.message || 'Valor inválido.');
      }
    }

    const obj = norm.errors;
    if (obj && typeof obj === 'object') {
      for (const [field, raw] of Object.entries(obj as Record<string, string[] | string>)) {
        const msgs = Array.isArray(raw) ? raw : [raw];
        for (const m of msgs) {
          applied ||= this.setFieldError(map[field] ?? field, String(m || 'Valor inválido.'));
        }
      }
    }

    const globalText = [norm.code, norm.message].filter(Boolean).join(' ');
    if (globalText) {
      const { emailTaken, usernameTaken, emailGeneric } = this.detect(String(globalText));
      if (usernameTaken) this.setFieldError('userName', 'Este nombre de usuario ya existe.');
      if (emailTaken)    this.setFieldError('email',    'Este email ya está en uso.');
      if (!emailTaken && emailGeneric) this.setFieldError('email', 'Email inválido.');
    }

    try {
      const rawText = JSON.stringify(err?.error ?? err ?? {}, null, 0);
      if (rawText) {
        const { emailTaken, usernameTaken, emailGeneric } = this.detect(rawText);
        if (usernameTaken) this.setFieldError('userName', 'Este nombre de usuario ya existe.');
        if (emailTaken)    this.setFieldError('email',    'Este email ya está en uso.');
        if (!emailTaken && emailGeneric) this.setFieldError('email', 'Email inválido.');
      }
    } catch {}

    return applied;
  }

  public forwardBuyParams() {
    // reuse your helper; make it public or call it here
    return this.buyParams(); // returns { returnUrl?, action?, classroomId? }
  }

  private toastFromDetails(norm: any) {
    const d = norm.details;
    let emitted = false;

    if (d && typeof d === 'object') {
      for (const [field, raw] of Object.entries(d as Record<string, string | string[]>)) {
        if (field === 'message') continue;
        const msgs = Array.isArray(raw) ? raw : [raw];
        for (const m of msgs) { if (m) { this.toast.add(String(m), 'error'); emitted = true; } }
      }
      const dm = (d as any).message;
      const dmMsgs = typeof dm === 'string' ? [dm] : Array.isArray(dm) ? dm : [];
      for (const m of dmMsgs) { if (m) { this.toast.add(String(m), 'error'); emitted = true; } }
    }
    if (!emitted) {
      const token = String(norm.code ?? norm.message ?? '').toLowerCase();
      if (token.includes('username')) this.toast.add('Este nombre de usuario ya existe.', 'error');
      if (token.includes('email'))    this.toast.add('Este email ya está en uso.', 'error');
      if (!token && !d)               this.toast.add('No se pudo crear la cuenta.', 'error');
    }
  }

  /* ---------------- Buy params (kept inside class) ---------------- */
  private buyParams(): Record<string, any> {
    const q = this.route.snapshot.queryParamMap;
    const action      = q.get('action');
    const classroomId = q.get('classroomId');
    const returnUrl   = q.get('returnUrl');
    const out: Record<string, any> = {};
    if (returnUrl)   out['returnUrl']   = returnUrl;
    if (action)      out['action']      = action;
    if (classroomId) out['classroomId'] = classroomId;
    return out;
  }

  /* ---------------- Submit ---------------- */
  submit() {
    if (this.loading()) return;

    this.submitted.set(true);
    if (this.form.invalid) return;

    const elapsedMs = this.isBrowser ? Math.round(performance.now() - this.formStartAt) : 0;
    if (elapsedMs > 0 && elapsedMs < 1200) {
      this.toast.add('No se pudo crear la cuenta.', 'error');
      return;
    }

    this.loading.set(true);
    this.error.set(null);
    this.success.set(false);

    const v = this.form.value;

    // Honeypot: pretend success and forward params to login
    if ((v.hp ?? '').trim() !== '') {
      this.loading.set(false);
      this.success.set(true);
      this.toast.add('Cuenta creada correctamente. Redirigiendo…', 'success', 1500);
      const queryParams = this.buyParams();
      setTimeout(() => this.router.navigate(['/login'], { queryParams }), 1500);
      return;
    }

    const userFromForm = (v.userName ?? '').trim();
    const fallbackUser = (v.email ?? '').split('@')[0] || 'user';

    const dto: RegisterDto = {
      firstName: v.firstName ?? '',
      lastName:  v.lastName  ?? '',
      email:     v.email     ?? '',
      userName:  userFromForm || fallbackUser,
      password:  v.password  ?? '',
      role:      'ROLE_STUDENT',
    };

    const payload = { ...dto, elapsedMs };

    this.auth.registerUser(payload as any).subscribe({
      next: () => {
        this.loading.set(false);
        this.success.set(true);

        const cameFromBuy = !!this.route.snapshot.queryParamMap.get('action');
        this.toast.add(
          cameFromBuy ? 'Cuenta creada. Inicia sesión para continuar la compra.'
            : 'Cuenta creada correctamente. Redirigiendo…',
          'success',
          1500
        );

        this.clearServerErrors();

        const queryParams = this.buyParams();
        setTimeout(() => this.router.navigate(['/login'], { queryParams }), 1500);
      },
      error: (err: HttpErrorResponse) => {
        this.loading.set(false);

        const fieldMap = {
          firstName: 'firstName',
          lastName:  'lastName',
          userName:  'userName',
          username:  'userName',
          email:     'email',
          password:  'password',
        } as const;

        const { applied, norm } = this.errmap.applyToForm(
          this.form,
          fieldMap,
          err,
          (ctrl) => this.pulseOn(ctrl)
        );

        this.errmap.toastFromDetails(norm, this.toast);
        this.highlightAllErrors();

        if (!applied && !norm.details) {
          this.error.set('No se pudo crear la cuenta.');
        }
      }
    });
  }

  @HostListener('window:resize')
  @HostListener('window:scroll')
  @HostListener('window:orientationchange')
  repositionToast() {}
}
