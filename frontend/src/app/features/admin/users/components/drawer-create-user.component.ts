// src/app/features/admin/users/components/drawer-create-user.component.ts
import { Component, EventEmitter, Input, Output, inject, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ReactiveFormsModule, NonNullableFormBuilder, Validators } from '@angular/forms';
import { ToastService } from '@app/core/ui/toast/toast.service';
import { UsersService } from '../users.service';
import { CreateUserDto } from '@/app/shared/models/user/user-write.dto';
import { UserRole } from '@/app/shared/models/user/user-role';
import { HttpErrorResponse } from '@angular/common/http';
import { DrawerComponent } from '@/app/core/ui/drawer/drawer.component';

type FormKey = 'firstName'|'lastName'|'email'|'userName'|'password'|'role';

@Component({
  standalone: true,
  selector: 'app-drawer-create-user',
  imports: [CommonModule, ReactiveFormsModule, DrawerComponent],
  template: `
    <bf-drawer
      [open]="open"
      [heading]="'Add a new user'"
      [offsetVar]="'--admin-navbar-h'"
      (close)="handleClose()">

      <div class="px-3 py-3 sm:py-4 text-sm">
        <form class="space-y-3" (ngSubmit)="submit()" [formGroup]="form">
          <div>
            <label class="block text-xs font-medium mb-1">First name</label>
            <input
              type="text"
              formControlName="firstName"
              [class]="inputClass('firstName')"
              [attr.aria-invalid]="ariaInvalid('firstName')" />
            <p *ngIf="msg('firstName')" class="text-xs text-rose-600 mt-1">
              {{ msg('firstName') }}
            </p>
          </div>

          <div>
            <label class="block text-xs font-medium mb-1">Last name</label>
            <input
              type="text"
              formControlName="lastName"
              [class]="inputClass('lastName')"
              [attr.aria-invalid]="ariaInvalid('lastName')" />
            <p *ngIf="msg('lastName')" class="text-xs text-rose-600 mt-1">
              {{ msg('lastName') }}
            </p>
          </div>

          <div>
            <label class="block text-xs font-medium mb-1">Username</label>
            <input
              type="text"
              formControlName="userName"
              [class]="inputClass('userName')"
              [attr.aria-invalid]="ariaInvalid('userName')" />
            <p *ngIf="msg('userName')" class="text-xs text-rose-600 mt-1">
              {{ msg('userName') }}
            </p>
          </div>

          <div>
            <label class="block text-xs font-medium mb-1">Email</label>
            <input
              type="email"
              formControlName="email"
              [class]="inputClass('email')"
              [attr.aria-invalid]="ariaInvalid('email')" />
            <p *ngIf="msg('email')" class="text-xs text-rose-600 mt-1">
              {{ msg('email') }}
            </p>
          </div>

          <div>
            <label class="block text-xs font-medium mb-1">Password</label>
            <input
              type="password"
              formControlName="password"
              [class]="inputClass('password')"
              [attr.aria-invalid]="ariaInvalid('password')" />
            <p *ngIf="msg('password')" class="text-xs text-rose-600 mt-1">
              {{ msg('password') }}
            </p>
          </div>

          <div>
            <label class="block text-xs font-medium mb-1">Role</label>
            <select
              formControlName="role"
              [class]="inputClass('role')"
              [attr.aria-invalid]="ariaInvalid('role')">
              <option [ngValue]="null">Select role</option>
              <option value="ROLE_ADMIN">Admin</option>
              <option value="ROLE_TEACHER">Teacher</option>
              <option value="ROLE_STUDENT">Student</option>
            </select>
            <p *ngIf="msg('role')" class="text-xs text-rose-600 mt-1">
              {{ msg('role') }}
            </p>
          </div>

          <div class="pt-3 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
            <button
              type="button"
              class="btn btn-outline-muted w-full sm:w-auto"
              (click)="handleClose()">
              Cancel
            </button>
            <button
              type="submit"
              class="btn btn-primary w-full sm:w-auto"
              [disabled]="loading()">
              Save
            </button>
          </div>
        </form>
      </div>
    </bf-drawer>
  `,
})
export class DrawerCreateUserComponent {
  private fb    = inject(NonNullableFormBuilder);
  private api   = inject(UsersService);
  private toast = inject(ToastService);

  @Input() open = false;
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
    password:  this.fb.control('', { validators: [Validators.required, Validators.minLength(6)] }),
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

  // ===== UI helpers (unchanged)
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

  // ===== error normalization (unchanged)
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
  private applyServerErrors(err: HttpErrorResponse): boolean {
    const norm = this.normalizeError(err);
    let applied = false;
    const map: Record<string, FormKey> = { firstName:'firstName', lastName:'lastName', userName:'userName', username:'userName', email:'email', password:'password', role:'role' };

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

    if (Array.isArray(norm.violations)) {
      for (const v of norm.violations) {
        const field = map[v?.propertyPath ?? ''] ?? v?.propertyPath;
        if (field) applied ||= this.setFieldError(field as FormKey, v?.message || 'Valor inválido.');
      }
    }

    const obj = norm.errors;
    if (obj && typeof obj === 'object') {
      for (const [field, raw] of Object.entries(obj as Record<string, string[] | string>)) {
        const msgs = Array.isArray(raw) ? raw : [raw];
        for (const m of msgs) applied ||= this.setFieldError(map[field] ?? (field as FormKey), String(m || 'Valor inválido.'));
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

  submit(){
    if (this.loading()) return;
    this.submitted.set(true);
    if (this.form.invalid) return;

    this.loading.set(true);
    this.clearServerErrors();

    const dto: CreateUserDto = {
      firstName: this.form.value.firstName!,
      lastName:  this.form.value.lastName!,
      email:     this.form.value.email!,
      userName:  this.form.value.userName!,
      password:  this.form.value.password!,
      role:      this.form.value.role!,
    };

    this.api.create(dto).subscribe({
      next: () => {
        this.loading.set(false);
        this.toast.add('User created', 'success');
        this.saved.emit();
      },
      error: (err: HttpErrorResponse) => {
        this.loading.set(false);
        const applied = this.applyServerErrors(err);
        const norm = this.normalizeError(err);
        this.toastFromDetails(norm);
        if (!applied && !norm.details) this.toast.add('No se pudo crear el usuario.', 'error');
      }
    });
  }

  handleClose(){
    this.submitted.set(false);
    this.pulseClearAll();
    this.clearServerErrors();
    this.form.reset({ firstName:'', lastName:'', email:'', userName:'', password:'', role: null });
    this.close.emit();
  }
}
