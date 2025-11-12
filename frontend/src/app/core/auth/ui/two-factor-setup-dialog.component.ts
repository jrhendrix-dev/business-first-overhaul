import { Component, ChangeDetectionStrategy, EventEmitter, Output, inject, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ReactiveFormsModule, NonNullableFormBuilder, Validators } from '@angular/forms';
import { TwoFactorApi, SetupInitiate } from '@app/core/auth/two-factor.api';
import { ToastService } from '@/app/core/ui/toast/toast.service';

@Component({
  standalone: true,
  selector: 'app-two-factor-setup-dialog',
  imports: [CommonModule, ReactiveFormsModule],   // ⬅️ removed NgOptimizedImage
  changeDetection: ChangeDetectionStrategy.OnPush,
  template: `
    <div class="fixed inset-0 bg-black/30 grid place-items-center z-50">
      <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl ring-1 ring-black/5">
        <h2 class="text-xl font-extrabold text-[#0c145a]">Activar verificación en dos pasos</h2>
        <p class="mt-1 text-sm text-slate-600">
          Escanea el código QR con tu app de autenticación (Google Authenticator, Authy, etc.)
          y escribe el código de 6 dígitos para confirmar.
        </p>

        <div class="mt-4 grid md:grid-cols-[14rem_1fr] gap-4">
          <div class="rounded-lg border p-2 bg-white">
            <img
              *ngIf="init() as i"
              [src]="i.qrPng"
              alt="QR"
              class="mx-auto"
              width="224"
              height="224" />
          </div>

          <div>
            <div class="text-xs text-slate-600">Secreto</div>
            <div class="font-mono text-sm break-all">{{ init()?.secret }}</div>

            <div class="mt-2">
              <a class="btn btn-link"
                 [attr.href]="init()?.otpauthUri || null"
                 target="_blank"
                 rel="noopener noreferrer">Abrir en app compatible</a>
            </div>

            <form class="mt-4" [formGroup]="form" (ngSubmit)="confirm()">
              <label class="block text-xs text-slate-600">Código</label>
              <input
                formControlName="code"
                class="border rounded w-full px-3 py-2"
                placeholder="123456"
                inputmode="numeric"
                autocomplete="one-time-code" />

              <div class="mt-4 flex items-center justify-end gap-3">
                <button type="button" class="btn btn-outline-muted btn-raise" (click)="cancel.emit()">Cancelar</button>
                <button type="submit" class="btn btn-primary btn-raise" [disabled]="submitting()">Confirmar</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  `

})
export class TwoFactorSetupDialogComponent {
  @Output() success = new EventEmitter<string[]>();
  @Output() cancel  = new EventEmitter<void>();

  private api   = inject(TwoFactorApi);
  private toast = inject(ToastService);
  private fb    = inject(NonNullableFormBuilder);

  init = signal<SetupInitiate | null>(null);
  submitting = signal(false);

  form = this.fb.group({
    code: this.fb.control('', [Validators.required, Validators.minLength(6)]),
  });

  constructor() {
    this.api.initiate().subscribe({
      next: (i) => this.init.set(i),
      error: () => this.toast.error('No se pudo iniciar la configuración de 2FA'),
    });
  }

  confirm(): void {
    if (this.form.invalid) return;
    this.submitting.set(true);
    this.api.confirm(this.form.controls.code.value!).subscribe({
      next: (res) => {
        this.submitting.set(false);
        this.toast.success('2FA activado');
        this.success.emit(res.recoveryCodes);
      },
      error: (err) => {
        this.submitting.set(false);
        const m = err?.error?.error?.code === 'VALIDATION_FAILED' ? 'Código inválido' : 'No se pudo confirmar 2FA';
        this.toast.error(m);
      },
    });
  }
}
