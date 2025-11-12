// src/app/features/auth/auth-choice.page.ts
import { Component, inject, signal, computed } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ActivatedRoute, RouterLink } from '@angular/router';

@Component({
  standalone: true,
  selector: 'app-auth-choice',
  imports: [CommonModule, RouterLink],
  template: `
  <section class="min-h-[calc(100vh-6rem)] grid place-items-center bg-brand-paper px-4">
    <div class="w-full max-w-3xl grid md:grid-cols-2 gap-6">
      <!-- Login card -->
      <article class="rounded-2xl bg-white shadow ring-1 ring-black/5 p-6">
        <h2 class="text-xl font-semibold text-brand-navy">Ya tengo cuenta</h2>
        <p class="text-sm text-slate-600 mt-1">
          Inicia sesión para completar la compra.
        </p>
        <a class="mt-4 inline-block px-4 py-2 rounded-2xl bg-brand-navy text-white"
           [routerLink]="['/login']"
           [queryParams]="qParams()">Iniciar sesión</a>
      </article>

      <!-- Register card -->
      <article class="rounded-2xl bg-white shadow ring-1 ring-black/5 p-6">
        <h2 class="text-xl font-semibold text-brand-navy">Soy nuevo/a</h2>
        <p class="text-sm text-slate-600 mt-1">
          Crea una cuenta y vuelve automáticamente a la compra.
        </p>
        <a class="mt-4 inline-block px-4 py-2 rounded-2xl border border-brand-navy text-brand-navy"
           [routerLink]="['/register']"
           [queryParams]="qParams()">Crear cuenta</a>

        <p class="text-xs text-slate-500 mt-3">
          Al registrarte, guardaremos tu progreso y facturación.
        </p>
      </article>
    </div>
  </section>
  `
})
export class AuthChoicePage {
  private route = inject(ActivatedRoute);

  // Preserve returnUrl/action/classroomId if present
  qParams = computed(() => {
    const q = this.route.snapshot.queryParamMap;
    const returnUrl   = q.get('returnUrl') ?? '/catalog';
    const action      = q.get('action') ?? 'buy';
    const classroomId = q.get('classroomId');
    return classroomId
      ? { returnUrl, action, classroomId }
      : { returnUrl, action };
  });
}
