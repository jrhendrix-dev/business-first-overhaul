import { Component, inject, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ToastContainerComponent } from '@/app/core/ui/toast/toast-container.component';
import { ToastService } from '@/app/core/ui/toast/toast.service';
import { TeacherService, ClassroomMini, RosterStudent, GradeItem } from '../data/teacher.service';
import { TeacherDrawerRosterComponent } from '../components/drawer-roster.component';
import { TeacherDrawerGradesComponent } from '../components/drawer-grades.component';

@Component({
  standalone: true,
  selector: 'app-teacher-classes',
  imports: [CommonModule, ToastContainerComponent, TeacherDrawerRosterComponent, TeacherDrawerGradesComponent],
  template: `
    <app-toast-container />

    <h1 class="text-2xl font-bold text-brand-navy mb-4">Mis clases</h1>

    <!-- Make the entire card area clickable -->
    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
      <button
        *ngFor="let c of classes()"
        type="button"
        class="w-full h-full text-left rounded-2xl bg-white ring-1 ring-black/5 p-4 shadow
               flex flex-col justify-between hover:shadow-md focus:outline-none
               focus:ring-2 focus:ring-[color:var(--brand)]"
        (click)="openRoster(c)"
        [attr.aria-label]="'Ver alumnos de ' + c.name"
      >
        <h3 class="font-semibold text-slate-800">{{ c.name }}</h3>
        <p class="text-xs text-slate-500" *ngIf="c.schedule">{{ c.schedule }}</p>
        <span class="sr-only">Abrir listado de alumnos</span>
      </button>
    </div>

    <app-teacher-drawer-roster
      [open]="rosterOpen()"
      [className]="currentClass()?.name || ''"
      [students]="roster()"
      (close)="closeRoster()"
      (viewGrades)="onViewGrades($event)"
      (addGrade)="onAddGrade($event)">
    </app-teacher-drawer-roster>

    <app-teacher-drawer-grades
      [open]="gradesOpen()"
      [classId]="currentClass()?.id || 0"
      [student]="selectedStudent()"
      [grades]="grades()"
      (close)="closeGrades()"
      (changed)="reloadGrades()">
    </app-teacher-drawer-grades>
  `,
})
export class TeacherClassesPage {
  private api = inject(TeacherService);
  private toast = inject(ToastService);

  classes = signal<ClassroomMini[]>([]);
  roster  = signal<RosterStudent[]>([]);
  grades  = signal<GradeItem[]>([]);

  rosterOpen   = signal(false);
  gradesOpen   = signal(false);
  currentClass = signal<ClassroomMini | null>(null);
  selectedStudent = signal<RosterStudent | null>(null);

  constructor() {
    this.api.myClasses().subscribe({
      next: rows => this.classes.set(rows),
      error: () => this.toast.error('No se pudieron cargar tus clases'),
    });
  }

  openRoster(cls: ClassroomMini) {
    this.currentClass.set(cls);
    this.api.roster(cls.id).subscribe({
      next: list => { this.roster.set(list); this.rosterOpen.set(true); },
      error: () => this.toast.error('No se pudo cargar el listado de alumnos'),
    });
  }
  closeRoster() { this.rosterOpen.set(false); }

  onViewGrades(s: RosterStudent) { this.selectedStudent.set(s); this.loadGrades(); }
  onAddGrade(s: RosterStudent)  { this.selectedStudent.set(s); this.loadGrades(); }
  closeGrades(){ this.gradesOpen.set(false); }

  private loadGrades() {
    const cls = this.currentClass(); const s = this.selectedStudent();
    if (!cls || !s) return;
    this.api.gradesFor(cls.id, s.student.id).subscribe({
      next: rows => { this.grades.set(rows); this.gradesOpen.set(true); },
      error: () => this.toast.error('No se pudieron cargar las calificaciones'),
    });
  }

  reloadGrades() { this.loadGrades(); }
}
