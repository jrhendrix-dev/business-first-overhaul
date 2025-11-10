import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { environment } from '@/environments/environment';
import { Observable } from 'rxjs';

type CheckoutOut = { checkoutUrl: string };

@Injectable({ providedIn: 'root' })
export class PaymentApi {
  private base = `${environment.apiBase}/api/student/payments`;

  constructor(private http: HttpClient) {}

  startCheckout(classroomId: number): Observable<CheckoutOut> {
    return this.http.post<CheckoutOut>(`${this.base}/checkout-session`, { classroomId });
  }

  // used by /payment/success page
  confirm(sessionId: string) {
    return this.http.post<{ ok: boolean; status: 'paid'|'already_paid'|'not_paid' }>(
      `${this.base}/confirm`, { sessionId }
    );
  }
}
