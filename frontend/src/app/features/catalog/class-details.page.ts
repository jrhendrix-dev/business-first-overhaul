// src/app/features/catalog/class-details.page.ts
import { Component, ChangeDetectionStrategy, inject, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import { ClassCatalogApi, PublicClassItem } from './class-catalog.api';
import { AuthStateService } from '@/app/core/auth/auth.service';
import { PaymentApi } from '@/app/core/payments/payment.api';

@Component({
  standalone: true,
  selector: 'app-class-details',
  imports: [CommonModule, RouterLink],
  changeDetection: ChangeDetectionStrategy.OnPush,
  template: `
    <section class="max-w-3xl mx-auto px-4 my-10" *ngIf="cls() as c">
      <a routerLink="/catalog" class="text-sm underline">&larr; Volver al catálogo</a>

      <h1 class="text-3xl font-semibold text-[#0c145a] mt-3">{{ c.name }}</h1>
      <p class="text-slate-600 mt-2" *ngIf="c.teacher">Profesor: {{ c.teacher }}</p>

      <div class="mt-6 p-5 rounded-2xl bg-white shadow ring-1 ring-black/5">
        <p class="text-lg" *ngIf="c.priceCents !== null; else noPriceD">
          Precio:
          <span class="font-semibold">
             {{ (c.priceCents / 100) | currency:'EUR':'symbol':'1.0-0' }}
          </span>
        </p>
        <ng-template #noPriceD>
          <p class="text-sm text-slate-500">Precio no disponible</p>
        </ng-template>
          <button class="px-5 py-2 rounded-2xl bg-[#0c145a] text-white disabled:opacity-50"
                  [disabled]="loading()"
                  (click)="checkout(c.id)">
            {{ loading() ? 'Redirigiendo…' : 'Comprar' }}
          </button>
        </div>
    </section>
  `
})
export default class ClassDetailsPage {
  private route = inject(ActivatedRoute);
  private router = inject(Router);
  private api = inject(ClassCatalogApi);
  private payments = inject(PaymentApi);
  private auth = inject(AuthStateService);

  cls = signal<PublicClassItem | null>(null);
  loading = signal(false);

  ngOnInit(): void {
    const id = Number(this.route.snapshot.paramMap.get('id'));
    this.api.get(id).subscribe({
      next: (c) => {
        this.cls.set(c);

        // auto-buy when coming back from login with ?action=buy
        const qp = this.route.snapshot.queryParamMap;
        if (qp.get('action') === 'buy') {
          setTimeout(() => this.checkout(c.id), 0);
        }
      }
    });
  }

  checkout(classroomId: number): void {
    if (!this.auth.isAuthenticated()) {
      void this.router.navigate(['/auth'], {
        queryParams: {
          returnUrl: this.router.url,    // e.g. /catalog/6 or /catalog/6?action=buy
          action: 'buy',
          classroomId
        }
      });
      return;
    }

    this.loading.set(true);
    this.payments.startCheckout(classroomId).subscribe({
      next: ({ checkoutUrl }) => (window.location.href = checkoutUrl),
      error: () => this.loading.set(false)
    });
  }
}
