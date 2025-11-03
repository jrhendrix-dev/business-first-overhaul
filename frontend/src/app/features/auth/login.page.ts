import { Component, inject } from '@angular/core';
import { ReactiveFormsModule, NonNullableFormBuilder, Validators } from '@angular/forms';
import { Router, RouterLink, ActivatedRoute } from '@angular/router';
import { NgIf } from '@angular/common';
import { AuthApiService } from './auth-api.service';
import { AuthStateService } from '@app/core/auth/auth.service';
import { ToastService } from '@/app/core/ui/toast/toast.service';

@Component({
  standalone: true,
  selector: 'app-login-page',
  imports: [ReactiveFormsModule, NgIf, RouterLink],
  templateUrl: './login.page.html'
})
export class LoginPage {
  private fb = inject(NonNullableFormBuilder);
  private router = inject(Router);
  private route = inject(ActivatedRoute);
  private toast = inject(ToastService);
  private api = inject(AuthApiService);
  private auth = inject(AuthStateService);

  error = '';
  submitted = false;

  form = this.fb.group({
    email: ['', [Validators.required, Validators.email]],
    password: ['', [Validators.required]],
  });

  ngOnInit() {
    const reason = this.route.snapshot.queryParamMap.get('reason');
    if (reason === 'timeout') {
      this.toast.info('Session timed out. Please log in again.');
    } else if (reason === 'expired') {
      this.toast.info('Session expired. Please log in again.');
    } else if (reason === 'unauthenticated') {
      this.toast.error('You must log in to access that section.');
    } else if (reason === 'forbidden') {
      this.toast.error('Access denied.');
    }
  }

  onSubmit() {
    this.submitted = true;
    if (this.form.invalid) return;

    const { email, password } = this.form.getRawValue();

    this.api.login(email, password).subscribe({
      next: (res) => {
        this.auth.persist(res);        // <- crucial: stores accessToken/refresh/expiresAt
        this.toast.success('Bienvenido de nuevo!');
        void this.router.navigateByUrl('/');
      },
      error: (e) => {
        const code = e?.error?.error?.code;
        const msg = code === 'INVALID_CREDENTIALS' ? 'Credenciales inválidas' : 'No se pudo iniciar sesión';
        this.error = msg;
        this.toast.error(msg);
      }
    });
  }
}
