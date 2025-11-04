import { Component, Input, ChangeDetectionStrategy } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink } from '@angular/router';

export interface MiniClass { id: number; name: string; }

@Component({
  standalone: true,
  selector: 'app-class-list-cell',
  imports: [CommonModule, RouterLink],
  changeDetection: ChangeDetectionStrategy.OnPush,
  template: `
    <div class="relative flex items-center gap-2">
      <!-- Empty -->
      <ng-container *ngIf="!classes?.length; else hasData">—</ng-container>

      <ng-template #hasData>
        <!-- first chip -->
        <span class="inline-block px-2 py-0.5 rounded-full border text-xs max-w-40 truncate"
              [title]="classes[0]?.name">
          {{ classes[0]?.name }}
        </span>

        <!-- +N with popover (only when > 1) -->
        <ng-container *ngIf="classes.length > 1">
          <div class="relative group">
            <button type="button"
                    class="text-xs px-2 py-0.5 rounded-full border hover:bg-slate-50"
                    aria-haspopup="true"
                    aria-expanded="false">
              +{{ classes.length - 1 }}
            </button>
            <div
              class="invisible opacity-0 group-hover:visible group-hover:opacity-100 transition
                     absolute left-0 top-[120%] z-10 w-64 rounded-lg border bg-white shadow-lg p-2">
              <div class="max-h-56 overflow-auto">
                <div *ngFor="let c of classes" class="px-2 py-1 text-sm hover:bg-slate-50 rounded">
                  {{ c.name }}
                </div>
              </div>
            </div>
          </div>
        </ng-container>
      </ng-template>

      <!-- Always-visible Manage action -->
      <a
        [routerLink]="['/admin/classes']"
        [queryParams]="manageQuery"
        class="ml-auto inline-flex items-center justify-center rounded-md border px-2 py-1 text-xs
               text-slate-600 hover:bg-slate-50"
        aria-label="Manage classes">
        <svg class="w-3.5 h-3.5 mr-1" viewBox="0 0 24 24" fill="none" stroke="currentColor">
          <path d="M12 20h9" stroke-width="2" stroke-linecap="round"/>
          <path d="M16.5 3.5a2.12 2.12 0 1 1 3 3L7 19l-4 1 1-4 12.5-12.5z"
                stroke-width="2" stroke-linejoin="round"/>
        </svg>
        Manage
      </a>
    </div>
  `
})
export class ClassListCellComponent {
  @Input() classes: MiniClass[] = [];
  @Input() manageQuery: any;
}
