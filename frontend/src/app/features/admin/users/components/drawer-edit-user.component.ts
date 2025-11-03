import { Component, EventEmitter, Input, Output, OnChanges, SimpleChanges, inject, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ReactiveFormsModule, NonNullableFormBuilder, Validators } from '@angular/forms';
import { ToastService } from '@app/core/ui/toast/toast.service';
import { UsersService } from '../users.service';
import { UpdateUserDto } from '@/app/shared/models/user/user-write.dto';
import { UserRole } from '@/app/shared/models/user/user-role';
import { UserItemDto } from '@/app/shared/models/user/user-read.dto';
import { HttpErrorResponse } from '@angular/common/http';
import { DrawerComponent } from '@/app/core/ui/drawer/drawer.component';

type FormKey = 'firstName'|'lastName'|'email'|'userName'|'password'|'role';

@Component({
  standalone: true,
  selector: 'app-drawer-edit-user',
  imports: [CommonModule, ReactiveFormsModule, DrawerComponent],
  template: `
    <bf-drawer
      [open]="open"
      [heading]="'Edit user'"
      [offsetVar]="'--admin-navbar-h'"
      (close)="handleClose()">

      <form class="space-y-3" (ngSubmit)="submit()" [formGroup]="form">
        <div>
          <label class="block text-sm mb-1">First name</label>
          <input type="text" formControlName="firstName" [class]="inputClass('firstName')" [attr.aria-invalid]="ariaInvalid('firstName')" />
          <p *ngIf="msg('firstName')" class="text-xs text-rose-600 mt-1">{{ msg('firstName') }}</p>
        </div>

        <div>
          <label class="block text-sm mb-1">Last name</label>
          <input type="text" formControlName="lastName" [class]="inputClass('lastName')" [attr.aria-invalid]="ariaInvalid('lastName')" />
          <p *ngIf="msg('lastName')" class="text-xs text-rose-600 mt-1">{{ msg('lastName') }}</p>
        </div>

        <div>
          <label class="block text-sm mb-1">Username</label>
          <input type="text" formControlName="userName" [class]="inputClass('userName')" [attr.aria-invalid]="ariaInvalid('userName')" />
          <p *ngIf="msg('userName')" class="text-xs text-rose-600 mt-1">{{ msg('userName') }}</p>
        </div>

        <div>
          <label class="block text-sm mb-1">Email</label>
          <input type="email" formControlName="email" [class]="inputClass('email')" [attr.aria-invalid]="ariaInvalid('email')" />
          <p *ngIf="msg('email')" class="text-xs text-rose-600 mt-1">{{ msg('email') }}</p>
        </div>

        <div>
          <label class="block text-sm mb-1">Password (leave blank to keep)</label>
          <input type="password" formControlName="password" [class]="inputClass('password')" [attr.aria-invalid]="ariaInvalid('password')" />
          <p *ngIf="msg('password')" class="text-xs text-rose-600 mt-1">{{ msg('password') }}</p>
        </div>

        <div>
          <label class="block text-sm mb-1">Role</label>
          <select formControlName="role" [class]="inputClass('role')" [attr.aria-invalid]="ariaInvalid('role')">
            <option [ngValue]="null">— Select —</option>
            <option value="ROLE_ADMIN">Admin</option>
            <option value="ROLE_TEACHER">Teacher</option>
            <option value="ROLE_STUDENT">Student</option>
          </select>
          <p *ngIf="msg('role')" class="text-xs text-rose-600 mt-1">{{ msg('role') }}</p>
        </div>

        <div class="pt-4 flex justify-end gap-2">
          <button type="button" class="btn btn-outline-muted" (click)="handleClose()">Cancel</button>
          <button type="submit" class="btn btn-primary" [disabled]="loading()">Save</button>
        </div>
      </form>
    </bf-drawer>
  `,
})
export class DrawerEditUserComponent implements OnChanges {
  private fb    = inject(NonNullableFormBuilder);
  private api   = inject(UsersService);
  private toast = inject(ToastService);

  @Input() open = false;
  @Input() user: UserItemDto | null = null;
  @Input() users: UserItemDto[] = [];
  @Output() close = new EventEmitter<void>();
  @Output() saved = new EventEmitter<void>();

  loading   = signal(false);
  submitted = signal(false);
  private pulse = signal<Set<FormKey>>(new Set());

  form = this.fb.group({
    firstName: this.fb.control('', { validators: [Validators.required, Validators.minLength(2)] }),
    lastName:  this.fb.control('', { validators: [Validators.required, Validators.minLength(2)] }),
    email:     this.fb.control('', { validators: [Validators.required, Validators.email] }),
    userName:  this.fb.control('', { validators: [Validators.required, Validators.minLength(2)] }),
    password:  this.fb.control('', { validators: [] }), // optional
    role:      this.fb.control<UserRole | null>(null, { validators: [Validators.required] }),
  });

  constructor() {
    (['firstName','lastName','email','userName','password','role'] as const).forEach(k => {
      const c = this.form.get(k)!;
      c.valueChanges.subscribe(() => {
        const errs = c.errors || {};
        if ('server' in errs) {
          const { server, ...rest } = errs;
          c.setErrors(Object.keys(rest).length ? rest : null);
        }
        this.pulseOff(k);
      });
    });
  }

  ngOnChanges(ch: SimpleChanges): void {
    if (ch['user'] && this.user) {
      this.form.reset({
        firstName: this.user.firstName ?? '',
        lastName:  this.user.lastName  ?? '',
        email:     this.user.email     ?? '',
        userName:  this.user.userName  ?? '',
        password:  '',
        role:      (this.user.role as UserRole) ?? null,
      }, { emitEvent: false });
      this.submitted.set(false);
      this.pulse.set(new Set());
      this.clearServerErrors();
    }
  }

  // ===== UI helpers
  private readonly baseOk =
    'border rounded px-2 py-1 w-full focus:outline-none ring-1 ring-slate-300 ring-offset-1 ring-offset-white focus:ring-2 focus:ring-[color:var(--brand)] focus:ring-offset-1';
  private readonly baseErr =
    'border rounded px-2 py-1 w-full focus:outline-none ring-2 ring-rose-500 ring-offset-1 ring-offset-white border-rose-400 bg-rose-50/40 focus:ring-2 focus:ring-rose-500 focus:ring-offset-1';

  inputClass(ctrl: FormKey) {
    const c = this.form.get(ctrl)!;
    const showRed = this.hasServerError(ctrl) || (this.submitted() && c.invalid);
    const sticky  = this.pulse().has(ctrl);
    return (showRed || sticky) ? this.baseErr : this.baseOk;
  }
  ariaInvalid(ctrl: FormKey) {
    const c = this.form.get(ctrl)!;
    return this.hasServerError(ctrl) || this.pulse().has(ctrl) || (this.submitted() && c.invalid);
  }
  hasServerError(ctrl: FormKey){ return !!this.form.get(ctrl)?.errors?.['server']; }
  msg(ctrl: FormKey){ return this.form.get(ctrl)?.errors?.['server'] as string | undefined; }
  private pulseOn(ctrl: FormKey){ const s = new Set(this.pulse()); s.add(ctrl); this.pulse.set(s); }
  private pulseOff(ctrl: FormKey){ const s = new Set(this.pulse()); if (s.delete(ctrl)) this.pulse.set(s); }
  private pulseClearAll(){ this.pulse.set(new Set()); }

  private setFieldError(ctrl: FormKey, message: string): boolean {
    const c = this.form.get(ctrl);
    if (!c) return false;
    c.setErrors({ ...(c.errors ?? {}), server: message });
    c.markAsTouched(); c.markAsDirty();
    c.updateValueAndValidity({ onlySelf: true, emitEvent: false });
    this.pulseOn(ctrl);
    return true;
  }
  private clearServerErrors() {
    (['firstName','lastName','email','userName','password','role'] as const).forEach(k => {
      const c = this.form.get(k);
      if (!c) return;
      const { server, ...rest } = c.errors || {};
      c.setErrors(Object.keys(rest).length ? rest : null);
      c.updateValueAndValidity({ onlySelf: true, emitEvent: false });
    });
  }

  // ===== Normalization + detection
  private normalizeError(raw: unknown): any {
    let e: any = (raw as any)?.error ?? raw;
    if (typeof e === 'string') { try { e = JSON.parse(e); } catch { e = { message: e }; } }
    if (e?.error && typeof e.error === 'object') e = e.error;
    return e;
  }
  private detect(text: string) {
    const t = text.toLowerCase();
    const emailTaken =
      /(email|correo)[^\n]*?(ya\s*est[aá]\s*en\s*uso|ocupado|in\s*use|taken|exist[es]|used)/.test(t) ||
      t.includes('email_taken');
    const usernameTaken =
      /(usuario|nombre\s*de\s*usuario|username|user\s*name)[^\n]*?(ya\s*existe|en\s*uso|taken|in\s*use|exist[es]|used)/.test(t) ||
      t.includes('username_taken');
    const emailGeneric = /\b(email|correo)\b/.test(t);
    return { emailTaken, usernameTaken, emailGeneric };
  }

  /** Map common API shapes (details/violations/errors) to form fields. */
  private applyServerErrors(err: HttpErrorResponse): boolean {
    const norm = this.normalizeError(err);
    let applied = false;

    const map: Record<string, FormKey> = {
      firstName: 'firstName',
      lastName:  'lastName',
      userName:  'userName',
      username:  'userName',
      email:     'email',
      password:  'password',
      role:      'role',
    };

    // { error: { code, details: { field: string|string[] } } }
    const details = norm.details;
    if (details && typeof details === 'object') {
      for (const [field, raw] of Object.entries(details as Record<string, string|string[]>)) {
        if (field === 'message') continue;
        const msgs = Array.isArray(raw) ? raw : [raw];
        for (const m of msgs) applied ||= this.setFieldError(map[field] ?? (field as FormKey), String(m || 'Valor inválido.'));
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

    // API-Platform style { violations: [{ propertyPath, message }] }
    if (Array.isArray(norm.violations)) {
      for (const v of norm.violations) {
        const field = map[v?.propertyPath ?? ''] ?? v?.propertyPath;
        if (field) applied ||= this.setFieldError(field as FormKey, v?.message || 'Valor inválido.');
      }
    }

    // alt shape { errors: { field: ["..."] } }
    const obj = norm.errors;
    if (obj && typeof obj === 'object') {
      for (const [field, raw] of Object.entries(obj as Record<string, string[] | string>)) {
        const msgs = Array.isArray(raw) ? raw : [raw];
        for (const m of msgs) applied ||= this.setFieldError(map[field] ?? (field as FormKey), String(m || 'Valor inválido.'));
      }
    }

    // global text sniffing
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

  /** Parse SQLSTATE[23000] / 1062 duplicates and set field errors. */
  private tryHandleSqlDuplicatesStrict(err: HttpErrorResponse): boolean {
    const parts: string[] = [];
    const norm = this.normalizeError(err);
    if (norm?.message) parts.push(String(norm.message));
    if (norm?.code)     parts.push(String(norm.code));
    if (err?.message)   parts.push(String(err.message));
    try { parts.push(JSON.stringify(err?.error ?? err ?? {})); } catch {}

    const blob = parts.join(' | ');
    if (!/(duplicate entry|sqlstate\[23000\]|1062)/i.test(blob)) return false;

    const m = blob.match(/duplicate entry\s+'([^']+)'/i);
    const dupVal = (m?.[1] ?? '').toLowerCase();

    const emailVal = (this.form.value.email ?? '').toLowerCase();
    const userVal  = (this.form.value.userName ?? '').toLowerCase();

    const emailKey =
      /(for key\s*['"`]?(?:.*\bemail\b)['"`]?|`email`|\bemail\b|uniq[^"'`]*\bemail\b)/i.test(blob);
    const userKey  =
      /(for key\s*['"`]?(?:.*\b(user_name|username)\b)['"`]?|`user_name`|`username`|\busername\b|uniq[^"'`]*\b(user_name|username)\b)/i
        .test(blob);

    // 👇 NEW: local inference so we can flag BOTH even if DB reports only one
    const { emailDupLocal, userDupLocal } = this.checkLocalDupes(emailVal, userVal);

    const isEmailDup = emailKey || (!!dupVal && dupVal === emailVal) || emailDupLocal;
    const isUserDup  = userKey  || (!!dupVal && dupVal === userVal)  || userDupLocal;

    let applied = false;
    if (isEmailDup) { this.setFieldError('email', 'Este email ya está en uso.'); applied = true; }
    if (isUserDup)  { this.setFieldError('userName', 'Este nombre de usuario ya existe.'); applied = true; }

    // Emit both toasts without clearing
    if (applied) {
      if (isUserDup)  this.toast.error('Este nombre de usuario ya existe.');
      if (isEmailDup) this.toast.error('Este email ya está en uso.');
    }
    return applied;
  }


  /** Local duplicate check (case-insensitive), excluding the current user id */
  private checkLocalDupes(emailVal: string, userVal: string) {
    const id = this.user?.id;
    const emailDupLocal = !!this.users.find(
      u => u.id !== id && (u.email ?? '').toLowerCase() === emailVal.toLowerCase()
    );
    const userDupLocal  = !!this.users.find(
      u => u.id !== id && (u.userName ?? '').toLowerCase() === userVal.toLowerCase()
    );
    return { emailDupLocal, userDupLocal };
  }



  /** Make all offending fields sticky/red & focus the first one. */
  private highlightAllErrors() {
    const order: FormKey[] = ['userName','email','password','firstName','lastName','role'];
    let focused = false;
    for (const k of order) {
      if (this.hasServerError(k)) {
        this.pulseOn(k);
        if (!focused) {
          focused = true;
          const el = document.querySelector<HTMLElement>(`[formControlName="${k}"]`);
          el?.scrollIntoView({ behavior: 'smooth', block: 'center' });
          (el as HTMLInputElement)?.focus?.({ preventScroll: true });
        }
      }
    }
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
      if (!token && !d)               this.toast.add('No se pudo guardar el usuario.', 'error');
    }
  }

  // ===== Submit
  submit(){
    if (this.loading() || !this.user) return;

    this.submitted.set(true);
    if (this.form.invalid) return;

    this.loading.set(true);
    this.clearServerErrors();

    const dto: UpdateUserDto = {
      firstName: this.form.value.firstName!,
      lastName:  this.form.value.lastName!,
      email:     this.form.value.email!,
      userName:  this.form.value.userName!,
      role:      this.form.value.role!,
      ...(this.form.value.password ? { password: this.form.value.password! } : {}),
    };

    this.api.update(this.user.id, dto).subscribe({
      next: () => {
        this.loading.set(false);
        this.toast.add('User updated', 'success');
        this.saved.emit();
      },
      error: (err: HttpErrorResponse) => {
        this.loading.set(false);

        // 1) Map structured shapes
        const appliedByMapper = this.applyServerErrors(err);

        // 2) Parse raw SQLSTATE/1062 & infer the offending field(s)
        const handledSql = this.tryHandleSqlDuplicatesStrict(err);

        // 3) Prefer friendly per-field toasts when duplicates are on screen
        const emittedFriendly = this.emitDuplicateToasts();

        // 4) Sticky highlight + focus
        this.highlightAllErrors();

        // 5) Fallback toast
        const norm = this.normalizeError(err);
        if (!handledSql && !appliedByMapper && !norm?.details && !emittedFriendly) {
          this.toastFromDetails(norm);
          this.toast.add('No se pudo actualizar el usuario.', 'error');
        }
      }
    });
  }

  private emitDuplicateToasts() {
    const emailErr    = this.hasServerError('email');
    const usernameErr = this.hasServerError('userName');
    if (!emailErr && !usernameErr) return false;
    this.toast.clear();
    if (usernameErr) this.toast.add('Este nombre de usuario ya existe.', 'error');
    if (emailErr)    this.toast.add('Este email ya está en uso.', 'error');
    return true;
  }

  handleClose(){
    this.submitted.set(false);
    this.pulseClearAll();
    this.clearServerErrors();
    this.close.emit();
  }
}
