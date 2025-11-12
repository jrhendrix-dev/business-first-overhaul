import { Component, EventEmitter, Input, Output } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DrawerComponent } from '@/app/core/ui/drawer/drawer.component';
import { RosterStudent } from '../data/teacher.service';

@Component({
  standalone: true,
  selector: 'app-teacher-drawer-roster',
  imports: [CommonModule, DrawerComponent],
  template: `
    <bf-drawer
      [open]="open"
      [heading]="'Alumnos — ' + (className || '')"
      [offsetVar]="'--admin-navbar-h'"
      (close)="close.emit()">

      <div class="space-y-3">
        <div *ngFor="let s of students"
             class="rounded-xl border p-3 flex items-center justify-between">
          <div>
            <div class="font-medium">{{ s.student.firstName }} {{ s.student.lastName }}</div>
            <div class="text-xs text-slate-500">{{ s.student.email }}</div>
          </div>
          <div class="flex gap-2">
            <button class="px-2 py-1 rounded border" (click)="viewGrades.emit(s)">Ver notas</button>
            <button class="px-2 py-1 rounded bg-brand text-white" (click)="addGrade.emit(s)">Añadir nota</button>
          </div>
        </div>

        <p *ngIf="!students?.length" class="text-sm text-slate-500">
          No hay alumnos activos en esta clase.
        </p>
      </div>
    </bf-drawer>
  `,
})
export class TeacherDrawerRosterComponent {
  @Input() open = false;
  @Input() className = '';
  @Input() students: RosterStudent[] = [];

  @Output() close = new EventEmitter<void>();
  @Output() viewGrades = new EventEmitter<RosterStudent>();
  @Output() addGrade = new EventEmitter<RosterStudent>();
}
