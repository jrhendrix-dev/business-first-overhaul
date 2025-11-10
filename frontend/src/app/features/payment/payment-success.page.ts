import { Component, inject, signal, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { HttpClient, HttpParams } from '@angular/common/http';
import { ToastContainerComponent } from '@app/core/ui/toast/toast-container.component';
import { ToastService } from '@app/core/ui/toast/toast.service';
import { environment } from '@/environments/environment';

type VerifyOkShape =
  | { ok: true; orderId: number }                                  // new controller
  | { ok: true; status: 'paid' | 'already_paid' | 'not_paid' };    // legacy controller

type VerifyErrShape = { ok?: false; error?: string };

@Component({
  standalone: true,
  selector: 'app-payment-success',
  imports: [CommonModule, RouterLink, ToastContainerComponent],
  templateUrl: './payment-success.page.html',
})
export default class PaymentSuccessPage implements OnInit {
  private http  = inject(HttpClient);
  private route = inject(ActivatedRoute);
  private toast = inject(ToastService);

  checking  = signal(true);
  confirmed = signal<boolean | null>(null);
  orderId   = signal<number | null>(null);
  attempts  = 0;

  ngOnInit() {
    const sessionId = this.route.snapshot.queryParamMap.get('session_id') || '';
    if (!sessionId) {
      this.checking.set(false);
      this.confirmed.set(false);
      return;
    }
    this.verify(sessionId);
  }

  private verify(sessionId: string) {
    const url = `${environment.apiBase}/api/payment/verify`;
    const params = new HttpParams().set('session_id', sessionId);

    this.http.get<VerifyOkShape | VerifyErrShape>(url, { params }).subscribe({
      next: (res: any) => {
        if (res?.ok === true) {
          if (typeof res.orderId === 'number') {
            this.orderId.set(res.orderId);
            this.confirmed.set(true);
          } else if (res.status === 'paid' || res.status === 'already_paid') {
            this.confirmed.set(true);
          } else if (res.status === 'not_paid') {
            this.retry(sessionId); return;
          } else {
            this.retry(sessionId); return;
          }
        } else {
          this.retry(sessionId); return;
        }

        this.checking.set(false);
        if (this.confirmed()) this.toast.success('Pago confirmado. ¡Gracias!');
      },
      error: () => this.retry(sessionId),
    });
  }

  private retry(sessionId: string) {
    if (++this.attempts <= 4) {
      setTimeout(() => this.verify(sessionId), 900);
      return;
    }
    this.checking.set(false);
    this.confirmed.set(false);
    this.toast.info('No se pudo confirmar con el proveedor. Si ves el cargo, contacta soporte.');
  }
}
