import { Component, EventEmitter, Input, Output, ContentChild, ElementRef, AfterContentInit } from '@angular/core';
import { CommonModule } from '@angular/common';

@Component({
  selector: 'app-section-card',
  standalone: true,
  imports: [CommonModule],
  template: `
    <article class="rounded-2xl border border-black/5 bg-white shadow-[0_10px_30px_-15px_rgba(0,0,0,0.25)] overflow-hidden">
      <header class="flex items-center justify-between px-6 py-4 border-b border-black/5">
        <div>
          <h2 class="text-xl font-semibold">{{ title }}</h2>
          <p class="text-sm text-black/60" *ngIf="subtitle">{{ subtitle }}</p>
        </div>

        <!-- Right header area: custom content OR fallback action button -->
        <ng-content select="[header-right]"></ng-content>
        <button *ngIf="actionLabel && !hasProjectedRight"
                type="button"
                class="rounded-xl px-3 py-2 text-sm bg-[color:var(--brand)]/10 hover:bg-[color:var(--brand)]/15"
                (click)="action.emit()">
          {{ actionLabel }}
        </button>
      </header>

      <ng-content></ng-content>
    </article>
  `,
})
export class SectionCardComponent implements AfterContentInit {
  @Input() title = '';
  @Input() subtitle = '';
  @Input() actionLabel = '';
  @Output() action = new EventEmitter<void>();

  @ContentChild('[header-right]', { read: ElementRef }) headerRight?: ElementRef;
  hasProjectedRight = false;

  ngAfterContentInit(): void {
    this.hasProjectedRight = !!this.headerRight;
  }
}
