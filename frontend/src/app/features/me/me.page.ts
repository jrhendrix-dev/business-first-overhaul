// src/app/features/me/me.page.ts
import {
  Component, OnInit, AfterViewInit, OnDestroy,
  ViewChild, ElementRef, inject, signal
} from '@angular/core';
import { CommonModule } from '@angular/common';
import { NonNullableFormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { animate, state, style, transition, trigger } from '@angular/animations';

import type { MeResponse } from '@/app/shared/models/me/me-read.dto';
import { matchFields } from '@app/shared/validators/match-fields.validator';

import { ToastService } from '@app/core/ui/toast/toast.service';
import { ToastContainerComponent } from '@app/core/ui/toast/toast-container.component';

import { TwoFactorSettingsComponent } from '@app/features/me/components/two-factor-settings.component';
import { SectionCardComponent } from '@shared/ui/section-card.component';
import { SectionSeparatorComponent } from '@shared/ui/section-separator.component';

import { GoogleAuthService } from '@/app/core/auth/google-auth.service';
import { environment } from '@/environments/environment';
import { MeService } from '@app/features/me/me.service';

@Component({
  selector: 'app-me-page',
  standalone: true,
  imports: [
    CommonModule,
    ReactiveFormsModule,
    ToastContainerComponent,
    SectionCardComponent,
    SectionSeparatorComponent,
    TwoFactorSettingsComponent,
  ],
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
      <app-toast-container></app-toast-container>

      <!-- ======================= DATOS BÁSICOS ======================= -->
      <app-section-card
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
                         class="w-full rounded-2xl bg-white text-[color:var(--brand)]
                                placeholder-slate-400 ring-1 ring-[color:var(--brand)]/25
                                focus:outline-none focus:ring-2 focus:ring-[color:var(--brand)] p-2.5"/>
                </div>

                <div>
                  <label for="lastName" class="block text-sm text-black/70 mb-1">Apellidos</label>
                  <input id="lastName" formControlName="lastName"
                         class="w-full rounded-2xl bg-white text-[color:var(--brand)]
                                placeholder-slate-400 ring-1 ring-[color:var(--brand)]/25
                                focus:outline-none focus:ring-2 focus:ring-[color:var(--brand)] p-2.5"/>
                </div>
              </div>

              <div class="text-sm text-black/70">
                Email (solo lectura):
                <strong class="font-semibold text-[color:var(--brand)]">{{ me()?.email }}</strong>
              </div>

              <div class="flex items-center gap-3 pt-2">
                <button class="px-4 py-2 rounded-xl bg-[color:var(--brand)] text-white
                               hover:bg-[color:var(--brand-strong)]
                               disabled:opacity-50 disabled:cursor-not-allowed"
                        [disabled]="profileForm.invalid || saving()">
                  Guardar cambios
                </button>
                <span *ngIf="saved()" class="text-sm text-black/70">Guardado ✔</span>
              </div>
            </form>
          </div>
        </ng-template>
      </app-section-card>

      <app-section-separator></app-section-separator>

      <!-- ======================= CONTRASEÑA ======================= -->
      <app-section-card
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
      </app-section-card>

      <app-section-separator></app-section-separator>

      <!-- ======================= CORREO ELECTRÓNICO ======================= -->
      <app-section-card
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
      </app-section-card>

      <app-section-separator></app-section-separator>

      <!-- ======================= ACCESO CON GOOGLE (LINK/UNLINK) ======================= -->
      <app-section-card
        class="mt-8"
        [title]="'Acceso con Google'"
        [subtitle]="me()?.hasGoogleLink ? 'Tu cuenta está vinculada a Google' : 'Vincula tu cuenta para iniciar sesión con Google'">

        <div header-right class="flex items-center gap-2">
          <button *ngIf="me()?.hasGoogleLink"
                  type="button"
                  class="rounded-xl px-3 py-2 text-sm border border-rose-300 text-rose-700 hover:bg-rose-50 disabled:opacity-50"
                  [disabled]="linkLoading()"
                  (click)="onUnlinkGoogle()">
            Desvincular
          </button>

          <!-- GIS button renders here when NOT linked -->
          <div *ngIf="!me()?.hasGoogleLink"
               #googleBtnContainer
               class="min-w-[240px] h-[40px] flex items-center justify-end"></div>
        </div>

        <div class="px-6 py-5">
          <div class="flex items-center justify-between">
            <div>
              <div class="text-sm text-black/60">Estado</div>
              <div class="mt-0.5 font-medium">
                {{ me()?.hasGoogleLink ? 'Vinculada ✅' : 'No vinculada' }}
              </div>
            </div>
            <div *ngIf="linkLoading()" class="text-sm text-black/60">Procesando…</div>
          </div>
          <p class="mt-3 text-sm text-black/60">
            Puedes vincular tu cuenta para iniciar sesión con Google. Si la desvinculas, seguirás pudiendo entrar con tu contraseña.
          </p>
        </div>
      </app-section-card>

      <app-section-separator></app-section-separator>

      <!-- ======================= 2FA ======================= -->
      <app-two-factor-settings-card
        [initialEnabled]="me()?.twoFactorEnabled">
      </app-two-factor-settings-card>
    </section>
  `,
})
export class MePage implements OnInit, AfterViewInit, OnDestroy {
  private meSvc = inject(MeService);
  private fb = inject(NonNullableFormBuilder);
  private toast = inject(ToastService);
  private google = inject(GoogleAuthService);

  @ViewChild('googleBtnContainer', { static: false }) googleBtnContainer?: ElementRef<HTMLDivElement>;

  linkLoading = signal(false);
  me = signal<MeResponse | null>(null);

  // toggles
  editProfile = signal(false);
  changePwOpen = signal(false);
  changeEmailOpen = signal(false);

  // flags
  saving = signal(false);
  saved = signal(false);
  pwLoading = signal(false);
  emailLoading = signal(false);

  private destroyed = false;

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

  // ---------------- lifecycle ----------------

  ngOnInit(): void {
    this.meSvc.getMe().subscribe({
      next: (m) => {
        this.me.set(m);
        this.profileForm.patchValue({
          firstName: m.firstName ?? '',
          lastName:  m.lastName ?? '',
        });
        // If GIS is already mounted, ensure the button reflects current link state
        queueMicrotask(() => this.renderGoogleButtonIfNeeded());
      },
    });
  }

  ngAfterViewInit(): void {
    if (!environment.googleClientId) {
      this.toast.add('Falta GOOGLE_CLIENT_ID', 'error');
      return;
    }

    // Mirror login flow: init → then renderButton
    this.google
      .init(environment.googleClientId, (idToken?: string) => {
        if (!idToken) {
          this.toast.add('No se recibió el token de Google', 'error');
          return;
        }
        this.linkLoading.set(true);
        this.meSvc.linkGoogle(idToken).subscribe({
          next: () => {
            this.linkLoading.set(false);
            this.me.update(m => (m ? ({ ...m, hasGoogleLink: true, googleLinkedAt: new Date().toISOString() }) : m));
            this.toast.add('Cuenta vinculada con Google', 'success');
            this.clearGoogleButton();
          },
          error: (e) => {
            this.linkLoading.set(false);
            const msg = this.handleApiValidationError(e) || 'No se pudo vincular';
            this.toast.add(msg, 'error');
          },
        });
      })
      .then(() => this.renderGoogleButtonIfNeeded())
      .catch(() => this.toast.add('Google script failed to load', 'error'));
  }

  ngOnDestroy(): void {
    this.destroyed = true;
  }

  // ---------------- Google button helpers ----------------

  private renderGoogleButtonIfNeeded(): void {
    if (this.destroyed) return;
    if (this.me()?.hasGoogleLink) { this.clearGoogleButton(); return; }
    const container = this.googleBtnContainer?.nativeElement;
    if (!container) return;

    try {
      container.replaceChildren();
      (window as any)?.google?.accounts?.id?.renderButton?.(container, {
        theme: 'filled',
        size: 'large',
        type: 'standard',
        shape: 'pill',
        text: 'continue_with',
        logo_alignment: 'left',
      });
      // Keep One Tap policy centralized in GoogleAuthService; no prompt() here
    } catch {
      this.toast.add('No se pudo mostrar el botón de Google', 'error');
    }
  }

  private clearGoogleButton(): void {
    const container = this.googleBtnContainer?.nativeElement;
    if (container) container.replaceChildren();
  }

  onUnlinkGoogle(): void {
    this.linkLoading.set(true);
    this.meSvc.unlinkGoogle().subscribe({
      next: () => {
        this.linkLoading.set(false);
        this.me.update(m => (m ? ({ ...m, hasGoogleLink: false, googleLinkedAt: null }) : m));
        this.toast.add('Cuenta desvinculada de Google', 'success');
        this.renderGoogleButtonIfNeeded();
      },
      error: (e) => {
        this.linkLoading.set(false);
        const msg = this.handleApiValidationError(e) || 'No se pudo desvincular';
        this.toast.add(msg, 'error');
      },
    });
  }

  // ---------------- toggles ----------------
  toggleEditProfile()    { this.editProfile.update(v => !v); }
  toggleChangePassword() { this.changePwOpen.update(v => !v); }
  toggleChangeEmail()    { this.changeEmailOpen.update(v => !v); }

  // ---------------- API helpers ----------------
  private handleApiValidationError(e: unknown): string {
    const err: any = e as any;

    const topCode    = err?.error?.error?.code    ?? err?.error?.code    ?? '';
    const topMessage = err?.error?.error?.message ?? err?.error?.message ?? '';
    const details    = err?.error?.error?.details ?? err?.error?.details ?? {};

    const domainToField: Record<string, { control?: string; msg: string }> = {
      bad_credentials:        { control: 'currentPassword', msg: 'Contraseña actual incorrecta.' },
      same_password:          { control: 'newPassword',     msg: 'La nueva contraseña no puede ser igual a la actual.' },
      invalid_email:          { control: 'newEmail',        msg: 'Formato de email inválido.' },
      email_taken:            { control: 'newEmail',        msg: 'Este email ya está en uso.' },
      same_email:             { control: 'newEmail',        msg: 'Introduce un email distinto al actual.' },
      email_taken_or_invalid: { control: 'newEmail',        msg: 'Email inválido o ya está en uso.' },
      google_already_linked:  { msg: 'Esta cuenta de Google ya está vinculada a otro usuario.' },
      must_set_password_before_unlink: { msg: 'Configura una contraseña antes de desvincular Google.' },
      validation_failed:      { msg: 'Error' },
      missing_sub:            { msg: 'Token de Google inválido: falta “sub”. Prueba de nuevo o revisa el Client ID.' },
    };

    let friendlyFromToken = '';

    const summaryPairs: string[] = [];
    if (details && typeof details === 'object') {
      for (const [field, raw] of Object.entries(details as Record<string, string | string[]>)) {
        const msgs = Array.isArray(raw) ? raw : [raw];

        if (field === 'message') {
          const token = String(msgs[0] ?? '').toLowerCase().replace(/\s+/g, '_');
          const map = domainToField[token];
          if (map?.msg) friendlyFromToken ||= map.msg;
          continue;
        }
        const text = msgs.filter(Boolean).join(', ');
        if (text) summaryPairs.push(`${field}: ${text}`);
      }
    }

    const token = String((topMessage || topCode) ?? '').toLowerCase().replace(/\s+/g, '_');
    if (!friendlyFromToken && token && domainToField[token]) {
      const map = domainToField[token];
      if (map?.msg) friendlyFromToken = map.msg;
    }

    if (friendlyFromToken) return friendlyFromToken;
    if (summaryPairs.length > 0) return summaryPairs.join(' · ');
    if (token) return token;
    return 'Error';
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
      error: () => {
        this.saving.set(false);
        this.toast.add('No se pudieron guardar los cambios', 'error');
      },
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
