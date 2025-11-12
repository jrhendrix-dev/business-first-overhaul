import { Component, HostListener, signal } from '@angular/core';
import { NgIf } from '@angular/common';

const STORAGE_KEY = 'hideDemoNoticeUntil';
const VAR_NAME     = '--demo-bar-h';
const BAR_H        = '40px'; // keep in sync with h-10/md:h-12 below

@Component({
  selector: 'app-demo-notice',
  standalone: true,
  imports: [NgIf],
  template: `
    <div
      *ngIf="visible()"
      class="sticky top-0 z-50 w-full h-10 md:h-12 bg-brand-crimson text-white shadow-md"
      role="status" aria-live="polite">
      <div class="mx-auto flex max-w-6xl items-center justify-center gap-4 px-4 h-full">
        <span class="text-center text-sm md:text-base">
          Este sitio es un proyecto ficticio con fines demostrativos.
        </span>
        <button
          type="button"
          (click)="dismiss()"
          aria-label="Cerrar aviso"
          class="rounded px-2 py-1 font-bold leading-none hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-white/50">
          ✕
        </button>
      </div>
    </div>
  `
})
export class DemoNoticeComponent {
  visible = signal(true);

  constructor() {
    const hideUntil = Number(localStorage.getItem(STORAGE_KEY) || '0');
    const now = Date.now();
    this.visible.set(!(hideUntil && now < hideUntil));
    this.applyOffset();
  }

  dismiss(): void {
    const oneHour = 60 * 60 * 1000;
    localStorage.setItem(STORAGE_KEY, String(Date.now() + oneHour));
    this.visible.set(false);
    this.applyOffset();
  }

  private applyOffset(): void {
    // Use the current value of the signal
    const height = this.visible() ? BAR_H : '0px';
    document.documentElement.style.setProperty(VAR_NAME, height);
  }

  // Allow closing with Escape for keyboard users
  @HostListener('document:keydown.escape')
  onEsc(): void {
    if (this.visible()) this.dismiss();
  }
}
