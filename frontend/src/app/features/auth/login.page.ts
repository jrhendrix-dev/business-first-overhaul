import { Component, ChangeDetectionStrategy, inject, signal, ViewChild, ElementRef, AfterViewInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ReactiveFormsModule, NonNullableFormBuilder, Validators } from '@angular/forms';
import { Router, RouterLink, ActivatedRoute } from '@angular/router';

import { GoogleAuthService } from '@/app/core/auth/google-auth.service';
import { AuthStateService } from '@/app/core/auth/auth.service';
import { TotpVerifyDialogComponent } from '@/app/core/auth/ui/totp-verify-dialog.component';
import { ToastService } from '@/app/core/ui/toast/toast.service';
import { environment } from '@/environments/environment';
import {ToastContainerComponent} from '@app/core/ui/toast/toast-container.component';

@Component({
  standalone: true,
  selector: 'app-login',
  imports: [CommonModule, ReactiveFormsModule, RouterLink, TotpVerifyDialogComponent, ToastContainerComponent],
  changeDetection: ChangeDetectionStrategy.OnPush,
  templateUrl: './login.page.html',
})
export class LoginPage implements AfterViewInit  {
  @ViewChild('googleBtn', { static: true }) googleBtn!: ElementRef<HTMLDivElement>;

  private fb     = inject(NonNullableFormBuilder);
  private router = inject(Router);
  private route  = inject(ActivatedRoute);
  private auth   = inject(AuthStateService);
  private toast  = inject(ToastService);
  private google = inject(GoogleAuthService);

  loading  = signal(false);
  submitted = signal(false);
  error     = signal<string | null>(null);

  show2fa  = signal(false);
  preToken = signal<string>('');

  // ---- helpers -------------------------------------------------------------

  /** Where to send the user after a successful login (supports ?returnUrl=) */
  private nextUrlAfterLogin(): string {
    const q = this.route.snapshot.queryParamMap;
    return q.get('returnUrl') || this.auth.roleHome();
  }

  // ---- lifecycle -----------------------------------------------------------

  ngAfterViewInit(): void {
    // Optional UX: if redirected due to guard
    if (this.route.snapshot.queryParamMap.get('reason') === 'forbidden') {
      this.toast.info('Please log in to continue.');
    }

    if (!environment.googleClientId) {
      this.toast.error('Missing Google Client ID');
      return;
    }

    this.google.init(environment.googleClientId, (idToken) => {
      this.loading.set(true);
      this.google.exchange(idToken).subscribe({
        next: ({ token }) => {
          this.loading.set(false);
          this.auth.persist({ token });
          this.router.navigateByUrl(this.nextUrlAfterLogin());
        },
        error: (err) => {
          this.loading.set(false);
          console.error('Google exchange failed', err);
          this.toast.error('GOOGLE_LOGIN_FAILED');
        }
      });
    }).then(() => {
      this.google.renderButton(this.googleBtn.nativeElement);
    }).catch(() => {
      this.toast.error('Google script failed to load');
    });
  }

  // ---- form ---------------------------------------------------------------

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

        this.auth.persist(res); // { token } or { accessToken }
        this.router.navigateByUrl(this.nextUrlAfterLogin());
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
    this.router.navigateByUrl(this.nextUrlAfterLogin());
  }
}
