import { CommonModule } from '@angular/common';
import { Component } from '@angular/core';
import { ToastService } from './toast.service';
import { Toast } from './toast.model';

@Component({
  standalone: true,
  selector: 'app-toast-container',
  imports: [CommonModule],
  template: `
    <div class="fixed left-3/4 bottom-1/4 z-[10000] flex flex-col-reverse items-end space-y-2 space-y-reverse">
      <div
        *ngFor="let t of toasts; trackBy: track"
        (click)="dismiss(t.id)"
        class="cursor-pointer select-none min-w-[260px] max-w-sm rounded-xl px-4 py-3 mb-2 shadow-lg text-white
               transition-all duration-300 ease-out transform hover:scale-[1.02]"
        [class.bg-emerald-600]="t.kind==='success'"
        [class.bg-rose-600]="t.kind==='error'"
        [class.bg-slate-700]="t.kind==='info'">
        {{ t.message }}
      </div>
    </div>
  `
})
export class ToastContainerComponent {
  toasts: Toast[] = [];
  constructor(private toast: ToastService) {
    this.toast.toasts$.subscribe(list => (this.toasts = list));
  }
  track(_: number, t: Toast) { return t.id; }
  dismiss(id: number) { this.toast.remove(id); }
}
