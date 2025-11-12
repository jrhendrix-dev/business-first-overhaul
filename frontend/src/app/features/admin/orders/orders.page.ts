import { Component, OnInit, inject, signal, computed } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ReactiveFormsModule, NonNullableFormBuilder } from '@angular/forms';
import { OrdersService, OrdersQuery } from './orders.service';
import { AdminHeaderComponent } from '@/app/core/ui/admin-header.component';
import { ToastContainerComponent } from '@/app/core/ui/toast/toast-container.component';
import { ToastService } from '@/app/core/ui/toast/toast.service';
import { OrderItemDto } from '@/app/shared/models/payments/order-read.dto';

@Component({
  standalone: true,
  selector: 'app-admin-orders',
  imports: [CommonModule, ReactiveFormsModule, AdminHeaderComponent, ToastContainerComponent],
  templateUrl: './orders.page.html',
})
export class OrdersPage implements OnInit {
  private svc = inject(OrdersService);
  private fb  = inject(NonNullableFormBuilder);
  private toast = inject(ToastService);

  loading = signal(false);
  items   = signal<OrderItemDto[]>([]);

  filters = this.fb.group({
    q: this.fb.control<string>(''),
    status: this.fb.control<string>(''),
    provider: this.fb.control<string>(''),
  });

  isFiltered = computed(() =>
    !!this.filters.controls.q.value ||
    !!this.filters.controls.status.value ||
    !!this.filters.controls.provider.value
  );

  ngOnInit(): void {
    this.filters.valueChanges.subscribe(() => this.load());
    this.load();
  }

  reset() {
    this.filters.reset({ q: '', status: '', provider: '' }, { emitEvent: true });
  }

  private load(): void {
    this.loading.set(true);
    const f = this.filters.getRawValue();

    // Build a typed query object; omit empty values entirely
    const params: OrdersQuery = {};
    if (f.q)        params.q = f.q;
    if (f.status)   params.status = f.status;
    if (f.provider) params.provider = f.provider;
    params.limit = 100;

    this.svc.list(params).subscribe({
      next: res => { this.items.set(res.items); this.loading.set(false); },
      error: () => { this.toast.add('Failed to load orders', 'error'); this.loading.set(false); },
    });
  }

  asAmount(cents: number, curr: string) {
    return new Intl.NumberFormat(undefined, { style: 'currency', currency: curr || 'EUR' })
      .format(cents / 100);
  }
}
