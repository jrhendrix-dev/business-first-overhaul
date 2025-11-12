import { Component, inject, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ActivatedRoute, Router } from '@angular/router';
import { MeService } from './me.service';
import { AuthService } from '@/app/core/auth.service';

@Component({
  standalone: true,
  selector: 'app-email-confirm',
  imports: [CommonModule],
  template: `
  <section class="mx-auto w-full max-w-xl px-4 py-16 text-center">
    <h1 class="text-3xl font-extrabold mb-4">Confirmación de correo</h1>

    <ng-container [ngSwitch]="state()">
      <div *ngSwitchCase="'loading'" class="text-black/70">Confirmando…</div>

      <div *ngSwitchCase="'ok'" class="rounded-2xl border border-black/5 bg-white p-6 shadow">
        <p class="text-lg mb-2">Tu correo se actualizó a</p>
        <p class="text-xl font-semibold text-[color:var(--brand)]">{{ email() }}</p>
        <p class="text-black/70 mt-4">
          Debes iniciar sesión nuevamente por seguridad.
        </p>
        <button class="mt-6 px-4 py-2 rounded-xl bg-[color:var(--brand)] text-white hover:bg-[color:var(--brand-strong)]"
                (click)="goLogin()">
          Iniciar sesión
        </button>
      </div>

      <div *ngSwitchCase="'err'" class="rounded-2xl border border-rose-200 bg-rose-50 p-6 text-rose-700">
        <p class="font-semibold mb-2">No se pudo confirmar el correo.</p>
        <p class="text-sm">{{ errorMsg() }}</p>
        <a class="inline-block mt-6 px-4 py-2 rounded-xl bg-[color:var(--brand)] text-white hover:bg-[color:var(--brand-strong)]"
           routerLink="/">
          Volver al inicio
        </a>
      </div>
    </ng-container>
  </section>
  `
})
export class EmailConfirmPage {
  private route = inject(ActivatedRoute);
  private me = inject(MeService);
  private auth = inject(AuthService);
  private router = inject(Router);

  state = signal<'loading'|'ok'|'err'>('loading');
  email = signal<string>('');
  errorMsg = signal<string>('Token inválido o expirado.');

  constructor() {
    const token = this.route.snapshot.queryParamMap.get('token') ?? '';
    if (!token) {
      this.state.set('err');
      this.errorMsg.set('Falta el token de confirmación.');
      return;
    }

    this.me.confirmEmailChange(token).subscribe({
      next: (r) => {
        this.email.set(r.email);
        this.state.set('ok');

        // por seguridad, cierra sesión y redirige a login en 3s
        setTimeout(() => this.goLogin(), 3000);
      },
      error: (e) => {
        // mapear mensaje backend si llega
        const details = e?.error?.error?.details;
        if (details?.message) this.errorMsg.set(String(details.message));
        this.state.set('err');
      }
    });
  }

  goLogin() {
    // limpia sesión si procede y navega
    try { this.auth.logout?.(); } catch {}
    this.router.navigateByUrl('/login');
  }
}
