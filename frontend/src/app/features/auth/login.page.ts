import { Component, ChangeDetectionStrategy, inject, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ReactiveFormsModule, NonNullableFormBuilder, Validators } from '@angular/forms';
import { Router, RouterLink } from '@angular/router';
import { AuthStateService, LoginResponse, TwoFactorChallenge } from '@/app/core/auth/auth.service';
import { ToastService } from '@/app/core/ui/toast/toast.service';
import { TotpVerifyDialogComponent } from '@/app/core/auth/ui/totp-verify-dialog.component';

@Component({
  standalone: true,
  selector: 'app-login',
  imports: [CommonModule, ReactiveFormsModule, RouterLink, TotpVerifyDialogComponent],
  changeDetection: ChangeDetectionStrategy.OnPush,
  templateUrl: './login.page.html',
})
export class LoginPage {
  private fb = inject(NonNullableFormBuilder);
  private router = inject(Router);
  private auth = inject(AuthStateService);
  private toast = inject(ToastService);

  loading = signal(false);
  submitted = signal(false);
  error = signal<string | null>(null);

  show2fa = signal(false);
  preToken = signal<string>('');

  form = this.fb.group({
    email: this.fb.control('', [Validators.required, Validators.email]),
    password: this.fb.control('', [Validators.required]),
  });

  submit(): void {
    this.submitted.set(true);
    this.error.set(null);

    if (this.form.invalid) {
      this.toast.error('Introduce email y contraseña');
      return;
    }

    this.loading.set(true);
    const { email, password } = this.form.getRawValue();

    this.auth.login(email!, password!).subscribe({
      next: (res) => {
        this.loading.set(false);

        if (this.auth.isTwoFactor(res)) {
          this.preToken.set(res.preToken);
          this.show2fa.set(true);
          return;
        }

        // res has { token } or { accessToken }
        this.auth.persist(res);
        this.router.navigateByUrl('/post-login');
      },
      error: (err) => {
        this.loading.set(false);
        const e = err?.error?.error;
        if (e?.code === 'VALIDATION_FAILED') {
          this.error.set(Object.values(e.details).join(', '));
        } else {
          this.error.set('Login incorrecto');
        }
      },
    });
  }

  on2faSuccess(finalJwt: string): void {
    this.auth.persistFinalToken(finalJwt);
    this.show2fa.set(false);
    this.router.navigateByUrl('/post-login');
  }
}
