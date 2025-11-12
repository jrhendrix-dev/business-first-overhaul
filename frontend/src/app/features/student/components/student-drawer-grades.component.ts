import { Component, EventEmitter, Input, Output } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DrawerComponent } from '@/app/core/ui/drawer/drawer.component';
import { StudentGradeItem } from '../data/student.service';

@Component({
  standalone: true,
  selector: 'app-student-drawer-grades',
  imports: [CommonModule, DrawerComponent],
  template: `
    <bf-drawer
      [open]="open"
      [heading]="'Calificaciones — ' + (className || '')"
      [offsetVar]="'--admin-navbar-h'"
      (close)="close.emit()">

      <div class="space-y-3 mt-2">
        <div
          *ngFor="let g of grades"
          class="rounded-2xl border ring-1 ring-black/5 bg-white p-3"
        >
          <!-- Top row: title + big score -->
          <div class="flex items-start justify-between gap-3">
            <div>
              <div class="font-semibold text-slate-800">
                {{ g.componentLabel || 'Componente' }}
              </div>
            </div>

            <div class="text-right leading-none">
              <div class="text-2xl font-extrabold text-slate-900 tracking-tight">
                {{ g.score }}
                <span *ngIf="g.maxScore != null" class="text-slate-400 text-base font-semibold">/ {{ g.maxScore }}</span>
              </div>
              <div *ngIf="g.percent != null"
                   class="inline-block mt-1 px-2 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-700">
                {{ g.percent }}%
              </div>
            </div>
          </div>

          <!-- Date with extra spacing -->
          <div class="text-xs text-slate-500 mt-3">
            {{ g.gradedAt | date:'short' }}
          </div>
        </div>

        <p *ngIf="!grades?.length" class="text-sm text-slate-500">Sin calificaciones todavía.</p>
      </div>
    </bf-drawer>
  `,
})
export class StudentDrawerGradesComponent {
  @Input() open = false;
  @Input() className = '';
  @Input() grades: StudentGradeItem[] = [];
  @Output() close = new EventEmitter<void>();
}
