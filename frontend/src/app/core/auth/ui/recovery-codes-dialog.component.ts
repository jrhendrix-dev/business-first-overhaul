import { Component, ChangeDetectionStrategy, Input, Output, EventEmitter } from '@angular/core';
import { CommonModule } from '@angular/common';

@Component({
  standalone: true,
  selector: 'app-recovery-codes-dialog',
  imports: [CommonModule],
  changeDetection: ChangeDetectionStrategy.OnPush,
  template: `
  <div class="fixed inset-0 bg-black/30 grid place-items-center z-50">
    <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl ring-1 ring-black/5">
      <h2 class="text-xl font-extrabold text-[#0c145a]">Códigos de recuperación</h2>
      <p class="mt-1 text-sm text-slate-600">
        Guarda estos códigos en un lugar seguro. Cada código se puede usar una sola vez.
      </p>

      <ul class="mt-4 grid grid-cols-2 gap-2 font-mono text-sm">
        <li *ngFor="let c of codes" class="rounded border px-3 py-2 bg-slate-50">{{ c }}</li>
      </ul>

      <div class="mt-6 flex items-center justify-end gap-3">
        <button class="btn btn-outline-muted btn-raise" (click)="copy()">Copiar</button>
        <button class="btn btn-primary btn-raise" (click)="close.emit()">Cerrar</button>
      </div>
    </div>
  </div>
  `
})
export class RecoveryCodesDialogComponent {
  @Input() codes: string[] = [];
  @Output() close = new EventEmitter<void>();

  copy(): void {
    navigator.clipboard.writeText(this.codes.join('\n')).catch(() => {});
  }
}
