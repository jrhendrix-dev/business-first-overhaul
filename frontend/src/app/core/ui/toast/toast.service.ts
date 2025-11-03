import { Injectable, isDevMode } from '@angular/core';
import { BehaviorSubject } from 'rxjs';
import { Toast, ToastKind } from './toast.model';

@Injectable({ providedIn: 'root' })
export class ToastService {
  private readonly max = 5;
  private seq = 0;
  private readonly _toasts = new BehaviorSubject<Toast[]>([]);
  readonly toasts$ = this._toasts.asObservable();

  add(message: string, kind: ToastKind = 'info', ms = 3500): void {
    const t: Toast = { id: ++this.seq, kind, message };

    const list = this._toasts.getValue().slice();
    if (list.length >= this.max) list.shift();       // remove oldest
    list.push(t);
    this._toasts.next(list);

    // auto-dismiss
    window.setTimeout(() => this.remove(t.id), ms);
  }

  remove(id: number): void {
    const list = this._toasts.getValue();
    const i = list.findIndex(x => x.id === id);
    if (i >= 0) {
      const next = list.slice();
      next.splice(i, 1);
      this._toasts.next(next);
    }
  }

  clear(): void {
    this._toasts.next([]);
  }
}
