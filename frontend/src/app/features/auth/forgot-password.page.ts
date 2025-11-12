// src/app/features/auth/forgot-password.page.ts
import { Component, inject, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { AuthApiService } from './auth-api.service';


@Component({
  standalone: true,
  selector: 'app-forgot-password',
  imports: [CommonModule, ReactiveFormsModule],
  template: `
    <section class="mx-auto w-full max-w-5xl px-4 py-16">
      <h1 class="text-5xl font-extrabold tracking-tight mb-10">Recuperar contraseña</h1>

      <article class="mx-auto max-w-2xl rounded-2xl border border-black/5 bg-white shadow-[0_10px_30px_-15px_rgba(0,0,0,0.25)]">
        <header class="px-6 py-5 border-b border-black/5">
          <h2 class="text-2xl font-semibold">¿No recuerdas tu contraseña?</h2>
          <p class="text-sm text-black/60">Te enviaremos un enlace para restablecerla</p>
        </header>

        <form [formGroup]="form" (ngSubmit)="submit()" class="px-6 py-6 grid gap-4">
          <div>
            <label for="email" class="block text-sm text-black/70 mb-1">Email</label>
            <input id="email" type="email" formControlName="email"
                   class="w-full rounded-2xl bg-white text-[color:var(--brand)] placeholder-slate-400 p-2.5
                          ring-1 ring-[color:var(--brand)]/25 focus:outline-none focus:ring-2 focus:ring-[color:var(--brand)]"/>
          </div>

          <div *ngIf="error()" class="text-rose-600 text-sm">{{ error() }}</div>
          <div *ngIf="sent()" class="text-emerald-700 text-sm">
            ¡Listo! Si el email existe, te hemos enviado un enlace para restablecer la contraseña.
          </div>

          <button class="mt-1 w-full px-4 py-3 rounded-2xl bg-[color:var(--brand)] text-white
                         hover:bg-[color:var(--brand-strong)]
                         disabled:opacity-50 disabled:cursor-not-allowed"
                  [disabled]="form.invalid || loading()">
            {{ loading() ? 'Enviando…' : 'Enviar enlace' }}
          </button>
        </form>
      </article>
    </section>
  `,
})
export class ForgotPasswordPage {
  private fb = inject(FormBuilder);
  private auth = inject(AuthApiService);

  form = this.fb.group({
    email: ['', [Validators.required, Validators.email]],
  });

  loading = signal(false);
  error   = signal<string | null>(null);
  sent    = signal(false);

  submit() {
    if (this.form.invalid || this.loading()) return;

    this.loading.set(true);
    this.error.set(null);
    this.sent.set(false);

    const email = this.form.value.email as string;

    this.auth.requestPasswordReset(email).subscribe({
      next: () => {
        this.loading.set(false);
        this.sent.set(true);       // ✅ success even if email doesn't exist
      },
      error: (e) => {
        this.loading.set(false);
        if (e?.status === 429) {
          this.error.set('Has hecho demasiados intentos. Inténtalo de nuevo en unos minutos.');
        } else if (e?.status === 0) {
          this.error.set('No se pudo contactar con el servidor.');
        } else {
          this.error.set('No se pudo enviar el email.');
        }
      },
    });
  }
}
