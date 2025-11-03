// src/app/features/admin/grades/grades.page.ts
import { Component, OnInit, inject, computed, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ReactiveFormsModule, NonNullableFormBuilder, Validators } from '@angular/forms';
import { HttpClient } from '@angular/common/http';
import { distinctUntilChanged, filter } from 'rxjs/operators';
import { Subscription } from 'rxjs';
import { GradesService, GradeItemDto, AddGradeDto, UpdateGradeDto } from './grades.service';
import { DrawerGradeComponent, StudentOption, EnrollmentOption } from './components/drawer-grade.component';
import { ToastContainerComponent } from '@/app/core/ui/toast/toast-container.component';
import { ToastService } from '@/app/core/ui/toast/toast.service';
import { AdminHeaderComponent } from '@app/core/ui/admin-header.component';
import { environment } from '@/environments/environment';

@Component({
  standalone: true,
  selector: 'app-admin-grades',
  imports: [CommonModule, ReactiveFormsModule, AdminHeaderComponent, DrawerGradeComponent, ToastContainerComponent],
  templateUrl: './grades.page.html',
})
export class GradesPage implements OnInit {
  private api   = inject(GradesService);
  private http  = inject(HttpClient);
  private fb    = inject(NonNullableFormBuilder);
  private toast = inject(ToastService);
  private studentIdSub?: Subscription;

  private API = environment.apiBase;

  // ✅ Use these endpoints (from your Postman screenshots)
  private USERS_STUDENTS = `${this.API}/api/admin/users/students`;
  private STUDENT_CLASSROOMS = (studentId: number) =>
    `${this.API}/api/admin/students/${studentId}/classrooms`;

  // UI state
  loading = signal(false);
  items   = signal<GradeItemDto[]>([]);

  // Filters
  q = signal<string>('');
  enrollmentFilter = signal<number | null>(null);

  // Drawer state
  drawerOpen = signal(false);
  editMode   = signal(false);
  editingId: number | null = null;

  // Options for drawer
  studentOptions = signal<StudentOption[]>([]);
  enrollmentOptions = signal<EnrollmentOption[]>([]);
  loadingEnrollments = signal(false);

  // Form (create/edit)
  form = this.fb.group({
    studentId:    this.fb.control<number | null>(null),
    enrollmentId: this.fb.control<number | null>(null),
    component:    this.fb.control<string>('QUIZ', { validators: [Validators.required] }),
    score:        this.fb.control<number>(0, { validators: [Validators.required, Validators.min(0)] }),
    maxScore:     this.fb.control<number>(10, { validators: [Validators.required, Validators.min(1)] }),
  });

  // Derived list with filters applied
  filtered = computed(() => {
    const term = this.q().trim().toLowerCase();
    const byEnroll = this.enrollmentFilter();

    return this.items().filter(g => {
      const classroomName = g.classrooms?.name ?? '';
      const matchesText =
        !term ||
        g.componentLabel.toLowerCase().includes(term) ||
        `${g.student?.firstName ?? ''} ${g.student?.lastName ?? ''}`.toLowerCase().includes(term) ||
        classroomName.toLowerCase().includes(term);

      const matchesEnroll = byEnroll == null ? true : g.enrollmentId === byEnroll;
      return matchesText && matchesEnroll;
    });
  });

  // --- Lifecycle ---
  ngOnInit() {
    this.refresh();
    this.loadStudentOptions(); // ✅ only students, and labels cleaned
    // react to student changes via reactive forms too (belt & suspenders)
    this.studentIdSub = this.form.get('studentId')!.valueChanges
      .pipe(
        distinctUntilChanged(),
        filter(() => !this.editMode()) // only when creating
      )
      .subscribe(val => {
        const id = Number(val);
        if (!Number.isNaN(id) && id > 0) {
          this.onStudentChange(id); // will call /api/admin/enrollments?studentId=&status=ACTIVE
        } else {
          this.enrollmentOptions.set([]);
        }
      });
  }

  // --- Template handlers ---
  onSearchInput(evt: Event) {
    const v = (evt.target as HTMLInputElement)?.value ?? '';
    this.q.set(v);
  }

  onEnrollmentFilterInput(evt: Event) {
    const raw = (evt.target as HTMLInputElement)?.value ?? '';
    const v = raw.toString().trim();
    this.enrollmentFilter.set(v === '' ? null : Number(v));
  }

  track = (_: number, g: { id: number }) => g.id;

  // --- Data ---
  refresh() {
    this.loading.set(true);
    this.api.listAll().subscribe({
      next: list => { this.items.set(list); this.loading.set(false); },
      error: err => {
        console.error(err);
        this.toast.add('Failed to load grades', 'error');
        this.loading.set(false);
      },
    });
  }

  /** ✅ Load ONLY students; label = fullName (no emails) */
  private loadStudentOptions() {
    this.http.get<any[]>(this.USERS_STUDENTS).subscribe({
      next: users => {
        // Backend already returns only students
        const opts: StudentOption[] = users.map(u => ({
          id: u.id,
          label: u.fullName ?? `${u.firstName ?? ''} ${u.lastName ?? ''}`.trim(),
        }));
        this.studentOptions.set(opts);
      },
      error: err => {
        console.error(err);
        this.toast.add('Could not load students', 'error');
      },
    });
  }

  /** Load ACTIVE enrollments for the selected student (returns enrollmentId!) */
  onStudentChange(studentId: number) {
    if (!studentId) {
      this.enrollmentOptions.set([]);
      return;
    }
    this.loadingEnrollments.set(true);

    this.http.get<any[]>(this.STUDENT_CLASSROOMS(studentId)).subscribe({
      next: list => {
        const opts = list
          .filter(e => (e.status ?? 'ACTIVE') === 'ACTIVE')
          .map(e => ({
            id: e.enrollmentId as number,                    // <-- enrollmentId from backend
            label: `${e.className}` // nice readable label
          }));
        this.enrollmentOptions.set(opts);
        this.loadingEnrollments.set(false);
      },
      error: () => {
        this.toast.add('Could not load active enrollments for this student', 'error');
        this.enrollmentOptions.set([]);
        this.loadingEnrollments.set(false);
      },
    });
  }


  // --- Drawer actions ---
  openCreate() {
    this.editMode.set(false);
    this.editingId = null;
    this.form.reset({
      studentId: null,
      enrollmentId: null,
      component: 'QUIZ',
      score: 0,
      maxScore: 10,
    });
    this.enrollmentOptions.set([]);
    this.drawerOpen.set(true);
  }

  openEdit(row: GradeItemDto) {
    this.editMode.set(true);
    this.editingId = row.id;
    this.form.reset({
      studentId: row.student?.id ?? null,
      enrollmentId: row.enrollmentId ?? null,
      component: row.component,
      score: row.score,
      maxScore: row.maxScore,
    });
    this.drawerOpen.set(true);
  }

  submit() {
    if (this.form.invalid) return;

    if (this.editMode()) {
      const dto: UpdateGradeDto = {
        component: this.form.value.component!,
        score: this.form.value.score!,
        maxScore: this.form.value.maxScore!,
      };
      this.api.update(this.editingId!, dto).subscribe({
        next: g => {
          const next = this.items().slice();
          const i = next.findIndex(x => x.id === g.id);
          if (i >= 0) next[i] = g; else next.unshift(g);
          this.items.set(next);
          this.toast.add('Grade updated', 'success');
          this.drawerOpen.set(false);
        },
        error: err => {
          console.error(err);
          this.toast.add('Could not update grade', 'error');
        },
      });
    } else {
      const enrollId = this.form.value.enrollmentId;
      if (!enrollId) {
        this.toast.add('Select an enrollment (classroom) for this student', 'error');
        return;
      }
      const dto: AddGradeDto = {
        component: this.form.value.component!,
        score: this.form.value.score!,
        maxScore: this.form.value.maxScore!,
      };
      this.api.create(enrollId, dto).subscribe({
        next: g => {
          this.items.set([g, ...this.items()]);
          this.toast.add('Grade created', 'success');
          this.drawerOpen.set(false);
        },
        error: err => {
          console.error(err);
          this.toast.add('Could not create grade', 'error');
        },
      });
    }
  }

  remove(row: GradeItemDto) {
    if (!confirm('Delete this grade?')) return;
    this.api.delete(row.id).subscribe({
      next: () => {
        this.items.set(this.items().filter(x => x.id !== row.id));
        this.toast.add('Grade deleted', 'success');
      },
      error: err => {
        console.error(err);
        this.toast.add('Could not delete grade', 'error');
      },
    });
  }
}
