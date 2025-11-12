import { Injectable, inject } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { environment } from '@/environments/environment';
import { OrdersListDto } from '@/app/shared/models/payments/order-read.dto';

const API = environment.apiBase;
const BASE = `${API}/api/admin/orders`;

export interface OrdersQuery {
  q?: string;
  status?: string;
  provider?: string;
  classroomId?: number;
  limit?: number;
  offset?: number;
}

@Injectable({ providedIn: 'root' })
export class OrdersService {
  private http = inject(HttpClient);

  list(params?: OrdersQuery) {
    let p = new HttpParams();
    for (const [k, v] of Object.entries(params ?? {})) {
      if (v !== undefined && v !== null && String(v) !== '') p = p.set(k, String(v));
    }
    return this.http.get<OrdersListDto>(BASE, { params: p });
  }

  getOne(id: number) {
    return this.http.get(`${BASE}/${id}`);
  }
}
