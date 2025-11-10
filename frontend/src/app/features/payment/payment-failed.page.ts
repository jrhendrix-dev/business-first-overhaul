import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink } from '@angular/router';
import { ToastContainerComponent } from '@app/core/ui/toast/toast-container.component';

@Component({
  standalone: true,
  selector: 'app-payment-failed',
  imports: [CommonModule, RouterLink, ToastContainerComponent],
  template: `
    <section class="max-w-xl mx-auto p-6 text-center">
      <app-toast-container />
      <h1 class="text-2xl font-semibold text-red-700">Payment failed</h1>
      <p class="mt-2">We couldn’t confirm your payment.</p>
      <a routerLink="/catalog" class="inline-block mt-6 underline">Try again</a>
    </section>
  `,
})
export default class PaymentFailedPage {}
