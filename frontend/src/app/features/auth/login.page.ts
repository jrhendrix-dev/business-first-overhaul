// src/app/features/auth/login.page.ts
import { Component, inject } from '@angular/core';
import { ReactiveFormsModule, NonNullableFormBuilder, Validators } from '@angular/forms';
import {Router, RouterLink} from '@angular/router';
import { NgIf } from '@angular/common';
import { AuthService } from '@/app/core/auth.service';

@Component({
  standalone: true,
  selector: 'app-login-page',
  imports: [ReactiveFormsModule, NgIf, RouterLink],
  template: `
    <section class="min-h-[calc(100vh-6rem)] w-full grid place-items-center bg-brand-paper">
      <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl ring-1 ring-black/5">
        <h1 class="mb-6 text-center text-3xl font-extrabold text-brand-navy">Iniciar sesión</h1>

        <form [formGroup]="form" (ngSubmit)="onSubmit()" class="space-y-4" novalidate>
          <div>
            <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
            <input id="email" type="email" autocomplete="username"
                   class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm
                        focus:border-brand-crimson focus:outline-none focus:ring-2 focus:ring-brand-crimson/30"
                   formControlName="email" />
            <p *ngIf="submitted && form.controls.email.invalid"
               class="mt-1 text-sm text-red-600">Introduce un email válido.</p>
          </div>

          <div>
            <label for="password" class="block text-sm font-medium text-gray-700">Contraseña</label>
            <input id="password" type="password" autocomplete="current-password"
                   class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm
                        focus:border-brand-crimson focus:outline-none focus:ring-2 focus:ring-brand-crimson/30"
                   formControlName="password" />
            <p *ngIf="submitted && form.controls.password.invalid"
               class="mt-1 text-sm text-red-600">La contraseña es obligatoria.</p>
          </div>

          <!-- in src/app/features/auth/login.page.html (or inline template) -->
          <div class="flex items-center justify-between">
            <a class="text-sm text-[color:var(--brand)] hover:underline" routerLink="/password/forgot">
              ¿Olvidaste tu contraseña?
            </a>
          </div>


          <button type="submit"
                  class="mt-2 w-full rounded-md bg-brand-crimson px-4 py-2 font-semibold text-white
                       transition hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-60"
                  [disabled]="auth.loading()">
            <span *ngIf="!auth.loading(); else loadingTpl">Entrar</span>
            <ng-template #loadingTpl>Entrando…</ng-template>
          </button>

          <p *ngIf="error" role="alert" class="text-center text-sm text-red-600">{{ error }}</p>
        </form>
      </div>
    </section>
  `
})
export class LoginPage {
  private fb = inject(NonNullableFormBuilder);
  private router = inject(Router);
  auth = inject(AuthService);

  error = '';
  submitted = false;

  form = this.fb.group({
    email: ['', [Validators.required, Validators.email]],
    password: ['', [Validators.required]],
  });



  onSubmit() {
    this.submitted = true;
    if (this.form.invalid) return;

    this.error = '';
    this.auth.login(this.form.getRawValue()).subscribe({
      next: () => this.router.navigateByUrl('/'),
      error: (e) => {
        this.error =
          e?.error?.error?.code === 'INVALID_CREDENTIALS'
            ? 'Credenciales inválidas'
            : 'No se pudo iniciar sesión';
      },
    });
  }
}
