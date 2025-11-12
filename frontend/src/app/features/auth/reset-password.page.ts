// src/app/features/auth/reset-password.page.ts
import { Component, OnDestroy, inject, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import { AuthApiService } from './auth-api.service';

@Component({
  standalone: true,
  selector: 'app-reset-password',
  imports: [CommonModule, ReactiveFormsModule, RouterLink],
  template: `
    <section class="mx-auto w-full max-w-4xl px-4 py-14">
      <h1 class="text-5xl font-extrabold tracking-tight mb-10">Establecer nueva contraseña</h1>

      <article class="mx-auto max-w-xl rounded-2xl border border-black/5 bg-white shadow-[0_10px_30px_-15px_rgba(0,0,0,0.25)]">
        <header class="px-6 py-5 border-b border-black/5">
          <h2 class="text-2xl font-semibold">Introduce tu nueva contraseña</h2>
          <p class="text-sm text-black/60">
            Mínimo 12 caracteres e incluir mayúscula, minúscula, número y símbolo.
          </p>
        </header>

        <form [formGroup]="form" (ngSubmit)="submit()" class="px-6 py-5 grid gap-3">

          <div>
            <label for="newPassword" class="block text-sm text-black/70 mb-1">Nueva contraseña</label>
            <input id="newPassword" type="password" formControlName="newPassword"
                   class="w-full rounded-xl bg-white text-[color:var(--brand)] placeholder-slate-400
                          ring-1 ring-[color:var(--brand)]/25 focus:outline-none focus:ring-2 focus:ring-[color:var(--brand)]
                          px-3 py-2" />
          </div>

          <div *ngIf="error()" class="text-rose-600 text-sm">{{ error() }}</div>

          <div *ngIf="success()" class="text-emerald-700 text-sm">
            ¡Contraseña actualizada!
            <ng-container *ngIf="countdown() > 0">
              Redirigiendo a iniciar sesión en {{ countdown() }} s…
            </ng-container>
            <a routerLink="/login" class="underline ml-1">Ir ahora</a>
          </div>

          <div class="pt-1">
            <button class="w-full max-w-sm mx-auto block rounded-xl bg-[color:var(--brand)] text-white
                           hover:bg-[color:var(--brand-strong)]
                           disabled:opacity-50 disabled:cursor-not-allowed
                           px-4 py-2.5 text-base"
                    [disabled]="form.invalid || loading()">
              {{ loading() ? 'Guardando…' : 'Guardar nueva contraseña' }}
            </button>
          </div>
        </form>
      </article>
    </section>
  `,
})
export class ResetPasswordPage implements OnDestroy {
  private fb = inject(FormBuilder);
  private auth = inject(AuthApiService);
  private route = inject(ActivatedRoute);
  private router = inject(Router);

  private token = this.route.snapshot.queryParamMap.get('token') ?? '';

  form = this.fb.group({
    newPassword: ['', [
      Validators.required,
      Validators.minLength(12),
      Validators.pattern(/[A-Z]/),
      Validators.pattern(/[a-z]/),
      Validators.pattern(/\d/),
      Validators.pattern(/[^A-Za-z0-9]/),
    ]],
  });

  loading  = signal(false);
  error    = signal<string | null>(null);
  success  = signal(false);
  countdown = signal(5);
  private timer: any;

  submit() {
    if (this.form.invalid || this.loading()) return;

    this.loading.set(true);
    this.error.set(null);
    this.success.set(false);

    const newPassword = this.form.value.newPassword as string;

    this.auth.confirmPasswordReset(this.token, newPassword).subscribe({
      next: () => {
        this.loading.set(false);
        this.success.set(true);
        // start 5s countdown then go to login
        this.timer = setInterval(() => {
          const n = this.countdown() - 1;
          this.countdown.set(n);
          if (n <= 0) {
            clearInterval(this.timer);
            this.router.navigate(['/login']);
          }
        }, 1000);
      },
      error: (e) => {
        this.loading.set(false);
        const msg = e?.error?.error?.details?.message ?? 'No se pudo actualizar la contraseña.';
        this.error.set(msg);
      },
    });
  }

  ngOnDestroy(): void {
    if (this.timer) clearInterval(this.timer);
  }
}
