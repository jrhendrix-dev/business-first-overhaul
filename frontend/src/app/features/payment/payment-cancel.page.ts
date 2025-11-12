import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink } from '@angular/router';
import { ToastContainerComponent } from '@app/core/ui/toast/toast-container.component';

@Component({
  standalone: true,
  selector: 'app-payment-cancel',
  imports: [CommonModule, RouterLink, ToastContainerComponent],
  template: `
    <section class="max-w-3xl mx-auto my-16 bg-white rounded-2xl shadow p-8">
      <app-toast-container />
      <h1 class="text-2xl font-semibold text-rose-700">Payment canceled</h1>
      <p class="mt-2 text-sm text-slate-700">
        Your payment was canceled. You can try again from the
        <a routerLink="/catalog" class="underline text-[color:var(--brand)]">Classes</a> page.
      </p>
    </section>
  `,
})
export default class PaymentCancelPage {}
