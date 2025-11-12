import { Component, EventEmitter, Input, Output, ChangeDetectionStrategy, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ReactiveFormsModule, NonNullableFormBuilder, Validators } from '@angular/forms';
import { TwoFactorApi } from '../two-factor.api';
import { ToastService } from '@/app/core/ui/toast/toast.service';

@Component({
  standalone: true,
  selector: 'app-totp-verify-dialog',
  imports: [CommonModule, ReactiveFormsModule],
  changeDetection: ChangeDetectionStrategy.OnPush,
  template: `
  <div class="fixed inset-0 bg-black/30 flex items-center justify-center z-50">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm p-5">
      <h2 class="text-lg font-semibold mb-2">Two-factor verification</h2>
      <p class="text-sm text-slate-600 mb-4">Enter the 6-digit code from your authenticator app,
        or use a recovery code.</p>

      <form [formGroup]="form" (ngSubmit)="submit()" class="space-y-3">
        <div>
          <label class="block text-xs text-slate-600">TOTP code</label>
          <input class="border rounded px-3 py-2 w-full"
                 placeholder="123456" inputmode="numeric" maxlength="6"
                 formControlName="code">
        </div>

        <div class="text-center text-xs text-slate-500">or</div>

        <div>
          <label class="block text-xs text-slate-600">Recovery code</label>
          <input class="border rounded px-3 py-2 w-full"
                 placeholder="xxxx-xxxx-xxxx"
                 formControlName="recoveryCode">
        </div>

        <div class="flex items-center justify-end gap-2 pt-1">
          <button type="button" class="btn btn-outline-muted btn-raise" (click)="cancel.emit()">Cancel</button>
          <button type="submit" class="btn btn-primary btn-raise">Verify</button>
        </div>
      </form>
    </div>
  </div>
  `
})
export class TotpVerifyDialogComponent {
  private fb = inject(NonNullableFormBuilder);
  private api = inject(TwoFactorApi);
  private toast = inject(ToastService);

  @Input() preToken!: string; // required
  @Output() success = new EventEmitter<string>(); // final JWT
  @Output() cancel = new EventEmitter<void>();

  form = this.fb.group({
    code: this.fb.control<string>('', []),
    recoveryCode: this.fb.control<string>('', [])
  });

  submit(): void {
    const { code, recoveryCode } = this.form.getRawValue();

    if (!code && !recoveryCode) {
      this.toast.error('Please enter a code or a recovery code.');
      return;
    }

    const req$ = code
      ? this.api.verify(this.preToken, code)
      : this.api.verifyWithRecovery(this.preToken, recoveryCode!);

    req$.subscribe({
      next: ({ token }) => this.success.emit(token),
      error: (e) => {
        const m = e?.error?.error;
        if (m?.code === 'VALIDATION_FAILED') {
          this.toast.error(Object.values(m.details).join(', '));
        } else {
          this.toast.error('2FA verification failed');
        }
      }
    });
  }
}
