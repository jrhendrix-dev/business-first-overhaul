// src/app/core/ui/drawer/drawer.component.ts
import {
  Component, Input, Output, EventEmitter,
  ChangeDetectionStrategy, ElementRef, AfterViewInit, ViewChild, OnChanges, SimpleChanges,
} from '@angular/core';
import { CommonModule } from '@angular/common';
import { CdkTrapFocus } from '@angular/cdk/a11y';
import {
  trigger, transition, style, animate, state,
} from '@angular/animations';

let nextId = 0;

@Component({
  standalone: true,
  selector: 'bf-drawer',
  imports: [CommonModule, CdkTrapFocus],
  changeDetection: ChangeDetectionStrategy.OnPush,
  animations: [
    // Backdrop fade
    trigger('backdrop', [
      state('void', style({ opacity: 0 })),
      state('*', style({ opacity: 0.3 })),
      transition('void => *', animate('150ms ease-out')),
      transition('* => void', animate('150ms ease-in')),
    ]),

    // Panel slides from the right
    trigger('panel', [
      transition(':enter', [
        style({ transform: 'translateX(100%)' }),
        animate('250ms cubic-bezier(0.2, 0, 0, 1)', style({ transform: 'translateX(0%)' })),
      ]),
      transition(':leave', [
        animate('220ms cubic-bezier(0.4, 0, 1, 1)', style({ transform: 'translateX(100%)' })),
      ]),
    ]),
  ],
  template: `
    <!-- Backdrop -->
    <div *ngIf="open"
         @backdrop
         class="fixed inset-0 bg-black z-[9000]"
         [style.opacity]="0.3"
         [style.top]="styleTop"
         [style.height]="styleHeight"
         (click)="close.emit()">
    </div>

    <!-- Sliding panel -->
    <div *ngIf="open"
         @panel
         #panel
         class="fixed right-0 w-[28rem] bg-white shadow-2xl z-[9001]
                overflow-y-auto border-l border-slate-200 will-change-transform"
         [style.top]="styleTop"
         [style.height]="styleHeight"
         [ngClass]="panelClass"
         role="dialog"
         aria-modal="true"
         [attr.aria-labelledby]="computedHeadingId || null"
         (keydown.escape)="close.emit()"
         cdkTrapFocus
         [cdkTrapFocusAutoCapture]="true">

      <header class="px-6 pt-5 pb-3 border-b bg-white sticky top-0 z-10">
        <h2 class="text-lg font-semibold leading-6 text-slate-800"
            [attr.id]="computedHeadingId"
            tabindex="-1">
          {{ heading }}
        </h2>
      </header>

      <div class="px-6 py-5">
        <ng-content></ng-content>
      </div>
    </div>
  `,
})
export class DrawerComponent implements AfterViewInit, OnChanges {
  @Input() heading = '';
  @Input() headingId?: string;
  @Input() open = false;
  @Input() panelClass = '';
  @Input() offset: string = '4.5rem';
  @Input() offsetVar?: string;

  @Output() close = new EventEmitter<void>();
  @ViewChild('panel') panelRef!: ElementRef<HTMLElement>;

  private defaultId = `bf-drawer-title-${++nextId}`;
  get computedHeadingId() { return (this.headingId || this.defaultId) || null; }

  get styleTop(): string { return this.offsetVar ? `var(${this.offsetVar})` : this.offset; }
  get styleHeight(): string {
    return this.offsetVar ? `calc(100% - var(${this.offsetVar}))` : `calc(100% - ${this.offset})`;
  }

  ngAfterViewInit() { this.focusHeading(); }

  ngOnChanges(ch: SimpleChanges) {
    if (ch['open']?.currentValue) queueMicrotask(() => this.focusHeading());
  }

  private focusHeading() {
    const el = this.panelRef?.nativeElement?.querySelector<HTMLElement>('h2[id]');
    el?.focus?.();
  }
}
