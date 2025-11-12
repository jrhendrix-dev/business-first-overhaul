// src/app/features/student/pages/student-classes.page.ts
import { Component, inject, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ActivatedRoute, Router } from '@angular/router';
import { ToastContainerComponent } from '@/app/core/ui/toast/toast-container.component';
import { ToastService } from '@/app/core/ui/toast/toast.service';
import { StudentService, StudentClassroomMini, StudentGradeItem } from '../data/student.service';
import { StudentDrawerGradesComponent } from '../components/student-drawer-grades.component';

@Component({
  standalone: true,
  selector: 'app-student-classes',
  imports: [CommonModule, ToastContainerComponent, StudentDrawerGradesComponent],
  template: `
    <app-toast-container />

    <h1 class="text-2xl font-bold text-brand-navy mb-4">Mis clases</h1>

    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
      @for (c of classes(); track c.id) {
        <button
          type="button"
          class="block w-full text-left rounded-2xl bg-white ring-1 ring-black/5 p-4 shadow hover:shadow-md focus:outline-none focus:ring-2 focus:ring-[color:var(--brand)]"
          (click)="openGrades(c)"
          [attr.aria-label]="'Ver calificaciones de ' + c.name">
          <h3 class="font-semibold text-slate-800">{{ c.name }}</h3>
          <p class="text-xs text-slate-500" *ngIf="c.teacher as t">Profesor: {{ t.name }}</p>
        </button>
      }
    </div>

    <app-student-drawer-grades
      [open]="drawerOpen()"
      [className]="currentClass()?.name || ''"
      [grades]="grades()"
      (close)="closeDrawer()">
    </app-student-drawer-grades>
  `,
})
export class StudentClassesPage {
  private api = inject(StudentService);
  private toast = inject(ToastService);
  private route = inject(ActivatedRoute);
  private router = inject(Router);

  classes = signal<StudentClassroomMini[]>([]);
  grades  = signal<StudentGradeItem[]>([]);
  currentClass = signal<StudentClassroomMini | null>(null);
  drawerOpen = signal(false);

  constructor() {
    // initial load
    this.loadClasses();

    // if we come from /payment/success?…&refresh=1, reload once after a tick
    const refresh = this.route.snapshot.queryParamMap.get('refresh') === '1';
    if (refresh) {
      setTimeout(() => this.loadClasses(), 800);
      // clean the URL (remove refresh=1) without reloading the page
      this.router.navigate([], { relativeTo: this.route, queryParams: { refresh: null }, queryParamsHandling: 'merge' });
    }
  }

  private loadClasses() {
    this.api.myClasses().subscribe({
      next: rows => this.classes.set(rows),
      error: () => this.toast.error('No se pudieron cargar tus clases'),
    });
  }

  openGrades(cls: StudentClassroomMini) {
    this.currentClass.set(cls);
    this.api.gradesForClass(cls.id).subscribe({
      next: rows => { this.grades.set(rows); this.drawerOpen.set(true); },
      error: () => this.toast.error('No se pudieron cargar tus calificaciones'),
    });
  }

  closeDrawer() {
    this.drawerOpen.set(false);
  }
}
