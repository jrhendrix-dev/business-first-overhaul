import { Component, EventEmitter, Input, OnInit, Output, inject, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { NonNullableFormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { MeService, MeResponse } from './me.service';
import { matchFields } from '@app/shared/validators/match-fields.validator';
import { animate, state, style, transition, trigger } from '@angular/animations';

import { ToastService } from '@/app/core/ui/toast.service';
import { ToastContainerComponent } from '@/app/core/ui/toast-container.component';

/** Reusable card used by all profile sections (title + subtitle + action button + body slot). */
@Component({
  selector: 'me-section-card',
  standalone: true,
  imports: [CommonModule],
  template: `
  <article class="rounded-2xl border border-black/5 bg-white shadow-[0_10px_30px_-15px_rgba(0,0,0,0.25)]">
    <header class="flex items-center justify-between px-6 py-4 border-b border-black/5">
      <div>
        <h2 class="text-xl font-semibold">{{ title }}</h2>
        <p class="text-sm text-black/60" *ngIf="subtitle">{{ subtitle }}</p>
      </div>

      <button *ngIf="actionLabel"
              type="button"
              class="rounded-xl px-3 py-2 text-sm bg-[color:var(--brand)]/10 hover:bg-[color:var(--brand)]/15"
              (click)="action.emit()">
        {{ actionLabel }}
      </button>
    </header>

    <ng-content></ng-content>
  </article>
  `,
})
export class MeSectionCard {
  @Input() title = '';
  @Input() subtitle = '';
  @Input() actionLabel = '';
  @Output() action = new EventEmitter<void>();
}

/** Soft separator between sections. */
@Component({
  selector: 'me-section-sep',
  standalone: true,
  template: `
    <div aria-hidden="true"
         class="-mt-4 h-6 w-full rounded-b-2xl bg-gradient-to-b from-black/5 to-transparent"></div>
  `,
})
export class MeSectionSeparator {}

@Component({
  selector: 'app-me-page',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule, MeSectionCard, MeSectionSeparator, ToastContainerComponent],
  animations: [
    trigger('accordion', [
      state('closed', style({ opacity: 0, height: '0px', overflow: 'hidden', paddingTop: 0, paddingBottom: 0 })),
      state('open',   style({ opacity: 1, height: '*',   overflow: 'hidden' })),
      transition('closed <=> open', animate('220ms ease')),
    ]),
  ],
  template: `
    <section class="mx-auto w-full max-w-4xl px-4 py-8">
      <h1 class="text-4xl font-extrabold tracking-tight mb-8">Mi perfil</h1>

      <!-- ✅ Global toast container (shared style/behavior) -->
      <app-toast-container></app-toast-container>

      <!-- ======================= DATOS BÁSICOS ======================= -->
      <me-section-card
        [title]="'Datos básicos'"
        [subtitle]="'Información visible de tu perfil'"
        [actionLabel]="editProfile() ? 'Cancelar' : 'Editar'"
        (action)="toggleEditProfile()">

        <!-- LECTURA -->
        <div *ngIf="!editProfile(); else editarTpl" class="px-6 py-5 grid gap-4">
          <div class="grid md:grid-cols-2 gap-4">
            <div>
              <div class="text-xs uppercase tracking-wide text-black/60">Nombre</div>
              <div class="mt-1 text-base font-medium">{{ me()?.firstName || '—' }}</div>
            </div>
            <div>
              <div class="text-xs uppercase tracking-wide text-black/60">Apellidos</div>
              <div class="mt-1 text-base font-medium">{{ me()?.lastName || '—' }}</div>
            </div>
          </div>

          <div>
            <div class="text-xs uppercase tracking-wide text-black/60">Email</div>
            <div class="mt-1 text-base font-medium">{{ me()?.email }}</div>
          </div>

          <div class="flex flex-wrap gap-2">
            <span *ngFor="let r of me()?.roles"
                  class="inline-flex items-center rounded-full border border-black/10 px-2.5 py-0.5 text-xs text-black/70">
              {{ r }}
            </span>
          </div>
        </div>

        <!-- EDICIÓN -->
        <ng-template #editarTpl>
          <div [@accordion]="editProfile() ? 'open' : 'closed'">
            <form [formGroup]="profileForm" (ngSubmit)="saveProfile()" class="px-6 py-5 grid gap-4">
              <div class="grid md:grid-cols-2 gap-4">
                <div>
                  <label for="firstName" class="block text-sm text-black/70 mb-1">Nombre</label>
                  <input id="firstName" formControlName="firstName"
                         class="w-full rounded-2xl bg-white text-[color:var(--brand)] placeholder-slate-400
                                ring-1 ring-[color:var(--brand)]/25 focus:outline-none focus:ring-2 focus:ring-[color:var(--brand)] p-2.5"/>
                </div>

                <div>
                  <label for="lastName" class="block text-sm text-black/70 mb-1">Apellidos</label>
                  <input id="lastName" formControlName="lastName"
                         class="w-full rounded-2xl bg-white text-[color:var(--brand)] placeholder-slate-400
                                ring-1 ring-[color:var(--brand)]/25 focus:outline-none focus:ring-2 focus:ring-[color:var(--brand)] p-2.5"/>
                </div>
              </div>

              <div class="text-sm text-black/70">
                Email (solo lectura):
                <strong class="font-semibold text-[color:var(--brand)]">{{ me()?.email }}</strong>
              </div>

              <div class="flex items-center gap-3 pt-2">
                <button class="px-4 py-2 rounded-xl bg-[color:var(--brand)] text-white hover:bg-[color:var(--brand-strong)]
                               disabled:opacity-50 disabled:cursor-not-allowed"
                        [disabled]="profileForm.invalid || saving()">
                  Guardar cambios
                </button>
                <span *ngIf="saved()" class="text-sm text-black/70">Guardado ✔</span>
              </div>
            </form>
          </div>
        </ng-template>
      </me-section-card>

      <me-section-sep></me-section-sep>

      <!-- ======================= CONTRASEÑA ======================= -->
      <me-section-card
        class="mt-8"
        [title]="'Contraseña'"
        [subtitle]="'Mantén tu cuenta segura'"
        [actionLabel]="changePwOpen() ? 'Cerrar' : 'Cambiar'"
        (action)="toggleChangePassword()">

        <div [@accordion]="changePwOpen() ? 'open' : 'closed'">
          <form *ngIf="changePwOpen()" [formGroup]="passwordForm" (ngSubmit)="changePassword()" class="px-6 py-5 grid gap-4">
            <div>
              <label for="currentPassword" class="block text-sm text-black/70 mb-1">Contraseña actual</label>
              <input id="currentPassword" type="password" formControlName="currentPassword" placeholder="Contraseña actual"
                     class="w-full rounded-2xl bg-white text-[color:var(--brand)] placeholder-slate-400 p-2.5
                            ring-1 ring-[color:var(--brand)]/25 focus:outline-none focus:ring-2 focus:ring-[color:var(--brand)]"/>
              <div class="text-rose-600 text-xs mt-1"
                   *ngIf="passwordForm.controls.currentPassword.errors?.['server'] as msg">
                {{ msg }}
              </div>
            </div>

            <div>
              <label for="newPassword" class="block text-sm text-black/70 mb-1">Nueva contraseña</label>
              <input id="newPassword" type="password" formControlName="newPassword" placeholder="Nueva contraseña"
                     class="w-full rounded-2xl bg-white text-[color:var(--brand)] placeholder-slate-400 p-2.5
                            ring-1 ring-[color:var(--brand)]/25 focus:outline-none focus:ring-2 focus:ring-[color:var(--brand)]"/>
              <div class="text-black/60 text-xs mt-1" *ngIf="passwordForm.controls.newPassword.touched">
                Debe tener <strong>mínimo 12 caracteres</strong> e incluir
                <strong>mayúscula</strong>, <strong>minúscula</strong>, <strong>número</strong> y <strong>símbolo</strong>.
              </div>
              <div class="text-rose-600 text-xs mt-1"
                   *ngIf="passwordForm.controls.newPassword.errors?.['server'] as msg">
                {{ msg }}
              </div>
            </div>

            <div>
              <label for="confirmPassword" class="block text-sm text-black/70 mb-1">Confirmar nueva contraseña</label>
              <input id="confirmPassword" type="password" formControlName="confirmPassword" placeholder="Confirmar nueva contraseña"
                     class="w-full rounded-2xl bg-white text-[color:var(--brand)] placeholder-slate-400 p-2.5
                            ring-1 ring-[color:var(--brand)]/25 focus:outline-none focus:ring-2 focus:ring-[color:var(--brand)]"/>
              <div class="text-rose-600 text-xs mt-1"
                   *ngIf="passwordForm.controls.confirmPassword.errors?.['server'] as msg">
                {{ msg }}
              </div>
            </div>

            <div *ngIf="passwordForm.errors?.['fieldsMustMatch']" class="text-rose-600 text-sm">
              Las contraseñas no coinciden.
            </div>

            <div class="flex items-center gap-3 pt-1">
              <button class="px-4 py-2 rounded-xl bg-[color:var(--brand)] text-white hover:bg-[color:var(--brand-strong)]
                             disabled:opacity-50"
                      [disabled]="passwordForm.invalid || pwLoading()">
                Actualizar contraseña
              </button>
            </div>
          </form>
        </div>
      </me-section-card>

      <me-section-sep></me-section-sep>

      <!-- ======================= CORREO ELECTRÓNICO ======================= -->
      <me-section-card
        class="mt-8"
        [title]="'Correo electrónico'"
        [subtitle]="'El cambio requiere confirmación'"
        [actionLabel]="changeEmailOpen() ? 'Cerrar' : 'Cambiar'"
        (action)="toggleChangeEmail()">

        <div [@accordion]="changeEmailOpen() ? 'open' : 'closed'">
          <form *ngIf="changeEmailOpen()" [formGroup]="emailForm" (ngSubmit)="startEmailChange()" class="px-6 py-5 grid gap-4">
            <div>
              <label for="newEmail" class="block text-sm text-black/70 mb-1">Nuevo correo electrónico</label>
              <input id="newEmail" type="email" formControlName="newEmail" placeholder="nuevo@email.com"
                     class="w-full rounded-2xl bg-white text-[color:var(--brand)] placeholder-slate-400 p-2.5
                            ring-1 ring-[color:var(--brand)]/25 focus:outline-none focus:ring-2 focus:ring-[color:var(--brand)]"/>
              <div class="text-rose-600 text-xs mt-1"
                   *ngIf="emailForm.controls.newEmail.errors?.['server'] as msg">
                {{ msg }}
              </div>
            </div>

            <div>
              <label for="emailPassword" class="block text-sm text-black/70 mb-1">Contraseña actual</label>
              <input id="emailPassword" type="password" formControlName="password" placeholder="Contraseña actual"
                     class="w-full rounded-2xl bg-white text-[color:var(--brand)] placeholder-slate-400 p-2.5
                            ring-1 ring-[color:var(--brand)]/25 focus:outline-none focus:ring-2 focus:ring-[color:var(--brand)]"/>
              <div class="text-rose-600 text-xs mt-1"
                   *ngIf="emailForm.controls.password.errors?.['server'] as msg">
                {{ msg }}
              </div>
            </div>

            <div class="flex items-center gap-3 pt-1">
              <button class="px-4 py-2 rounded-xl bg-[color:var(--brand)] text-white hover:bg-[color:var(--brand-strong)]
                             disabled:opacity-50"
                      [disabled]="emailForm.invalid || emailLoading()">
                Enviar enlace de confirmación
              </button>
            </div>
          </form>
        </div>
      </me-section-card>
    </section>
  `,
})
export class MePage implements OnInit {
  private meSvc = inject(MeService);
  private fb = inject(NonNullableFormBuilder);
  private toast = inject(ToastService);

  me = signal<MeResponse | null>(null);

  // section toggles
  editProfile = signal(false);
  changePwOpen = signal(false);
  changeEmailOpen = signal(false);

  // UI flags
  saving = signal(false);
  saved = signal(false);
  pwLoading = signal(false);
  emailLoading = signal(false);

  //  Removed local toast signal and inline template

  profileForm = this.fb.group({
    userName: this.fb.control<string | null>(null),
    firstName: this.fb.control<string>('', { validators: [Validators.maxLength(255)] }),
    lastName:  this.fb.control<string>('', { validators: [Validators.maxLength(255)] }),
  });

  passwordForm = this.fb.group(
    {
      currentPassword: this.fb.control<string>('', { validators: [Validators.required] }),
      newPassword:     this.fb.control<string>('', {
        validators: [
          Validators.required,
          Validators.minLength(12),
          Validators.pattern(/[A-Z]/),
          Validators.pattern(/[a-z]/),
          Validators.pattern(/\d/),
          Validators.pattern(/[^A-Za-z0-9]/),
        ],
      }),
      confirmPassword: this.fb.control<string>('', { validators: [Validators.required] }),
    },
    { validators: [matchFields('newPassword', 'confirmPassword')] },
  );

  emailForm = this.fb.group({
    newEmail: this.fb.control<string>('', { validators: [Validators.required, Validators.email] }),
    password: this.fb.control<string>('', { validators: [Validators.required] }),
  });

  ngOnInit(): void {
    this.meSvc.getMe().subscribe({
      next: (m) => {
        this.me.set(m);
        this.profileForm.patchValue({
          firstName: m.firstName ?? '',
          lastName:  m.lastName ?? '',
        });
      },
    });
  }

  toggleEditProfile()    { this.editProfile.update(v => !v); }
  toggleChangePassword() { this.changePwOpen.update(v => !v); }
  toggleChangeEmail()    { this.changeEmailOpen.update(v => !v); }

  /** Map backend validation/domain errors into form controls and return a friendly summary. */
  private handleApiValidationError(e: unknown): string {
    const err: any = e as any;

    // common shapes we’ve seen:
    // { error: { code, message, details } }
    // { error: { details: { message: 'bad_credentials', fieldA: '...' } } }
    const topCode    = err?.error?.error?.code    ?? err?.error?.code    ?? '';
    const topMessage = err?.error?.error?.message ?? err?.error?.message ?? '';
    const details    = err?.error?.error?.details ?? err?.error?.details ?? {};

    // domain token → friendly text + optional control to mark
    const domainToField: Record<string, { control?: string; msg: string }> = {
      bad_credentials:        { control: 'currentPassword', msg: 'Contraseña actual incorrecta.' },
      same_password:          { control: 'newPassword',     msg: 'La nueva contraseña no puede ser igual a la actual.' },
      invalid_email:          { control: 'newEmail',        msg: 'Formato de email inválido.' },
      email_taken:            { control: 'newEmail',        msg: 'Este email ya está en uso.' },
      same_email:             { control: 'newEmail',        msg: 'Introduce un email distinto al actual.' },
      email_taken_or_invalid: { control: 'newEmail',        msg: 'Email inválido o ya está en uso.' },
    };

    let friendlyFromToken = ''; // prefer returning this if we detect a known token
    const addServerErr = (controlName: string, msg: string) => {
      const ctrl =
        this.passwordForm.get(controlName) ??
        this.emailForm.get(controlName);
      ctrl?.setErrors({ ...(ctrl.errors ?? {}), server: msg });
    };

    // 1) Apply per-field errors from details (skip raw 'message' in summary)
    const summaryPairs: string[] = [];
    if (details && typeof details === 'object') {
      for (const [field, raw] of Object.entries(details as Record<string, string | string[]>)) {
        const msgs = Array.isArray(raw) ? raw : [raw];

        if (field === 'message') {
          // details.message token → friendly mapping (don’t add to summary)
          const token = String(msgs[0] ?? '').toLowerCase();
          const map = domainToField[token];
          if (map?.control) addServerErr(map.control, map.msg);
          if (map?.msg) friendlyFromToken ||= map.msg;
          continue;
        }

        // normal field errors
        const text = msgs.filter(Boolean).join(', ');
        const ctrl =
          this.passwordForm.get(field) ??
          this.emailForm.get(field);
        if (ctrl && text) {
          addServerErr(field, text);
        }
        if (text) {
          // Only include real fields in the summary (not the token)
          summaryPairs.push(`${field}: ${text}`);
        }
      }
    }

    // 2) If we got a known top-level token/code, prefer that message
    const token = String((topMessage || topCode) ?? '').toLowerCase();
    if (!friendlyFromToken && token && domainToField[token]) {
      const map = domainToField[token];
      if (map?.control) addServerErr(map.control, map.msg);
      friendlyFromToken = map.msg;
    }

    // 3) Choose best summary for the toast
    if (friendlyFromToken) return friendlyFromToken;
    if (summaryPairs.length > 0) return summaryPairs.join(' · ');
    if (token) return token; // last resort (unlikely now)
    return 'Error';          // generic fallback
  }


  saveProfile() {
    if (this.profileForm.invalid) return;
    this.saving.set(true);
    this.saved.set(false);

    const { firstName, lastName, userName } = this.profileForm.getRawValue();
    const dto: any = {};
    if (userName !== null && userName !== undefined) dto.userName = userName;
    dto.firstName = firstName;
    dto.lastName  = lastName;

    this.meSvc.updateMe(dto).subscribe({
      next: (updated: any) => {
        this.me.set(updated as MeResponse);
        this.saving.set(false);
        this.saved.set(true);
        this.editProfile.set(false);
        this.toast.add('Cambios guardados', 'success');
      },
      error: () => { this.saving.set(false); this.toast.add('No se pudieron guardar los cambios', 'error'); },
    });
  }

  changePassword() {
    if (this.passwordForm.invalid) return;

    ['currentPassword','newPassword','confirmPassword']
      .forEach(k => this.passwordForm.get(k)?.setErrors(null));

    this.pwLoading.set(true);

    this.meSvc.changePassword(this.passwordForm.getRawValue()).subscribe({
      next: (r) => {
        this.pwLoading.set(false);
        this.passwordForm.reset({ currentPassword: '', newPassword: '', confirmPassword: '' });
        this.changePwOpen.set(false);
        this.toast.add(r?.message ?? 'Contraseña actualizada', 'success');
      },
      error: (e) => {
        this.pwLoading.set(false);
        const summary = this.handleApiValidationError(e);
        this.toast.add(`No se pudo cambiar la contraseña. ${summary}`, 'error');
      },
    });
  }

  startEmailChange() {
    if (this.emailForm.invalid) return;

    ['newEmail','password'].forEach(k => this.emailForm.get(k)?.setErrors(null));

    this.emailLoading.set(true);

    this.meSvc.startChangeEmail(this.emailForm.getRawValue()).subscribe({
      next: (r) => {
        this.emailLoading.set(false);
        this.changeEmailOpen.set(false);
        this.toast.add(r?.message ?? 'Correo de confirmación enviado', 'success');
      },
      error: (e) => {
        this.emailLoading.set(false);
        const summary = this.handleApiValidationError(e);
        this.toast.add(`No se pudo iniciar el cambio de correo. ${summary}`, 'error');
      },
    });
  }
}
