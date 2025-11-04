import { Component, OnInit, OnDestroy, inject, computed, signal } from '@angular/core';
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
import { ActivatedRoute } from '@angular/router';

type ClassroomOption = { id: number; name: string };

@Component({
  standalone: true,
  selector: 'app-admin-grades',
  imports: [CommonModule, ReactiveFormsModule, AdminHeaderComponent, DrawerGradeComponent, ToastContainerComponent],
  templateUrl: './grades.page.html',
})
export class GradesPage implements OnInit, OnDestroy {
  private api   = inject(GradesService);
  private http  = inject(HttpClient);
  private fb    = inject(NonNullableFormBuilder);
  private toast = inject(ToastService);
  private studentIdSub?: Subscription;
  private route = inject(ActivatedRoute);

  private API = environment.apiBase;

  private USERS_STUDENTS = `${this.API}/api/admin/users/students`;
  private STUDENT_CLASSROOMS = (studentId: number) =>
    `${this.API}/api/admin/students/${studentId}/classrooms`;

  loading = signal(false);
  items   = signal<GradeItemDto[]>([]);

  q = signal<string>('');
  enrollmentFilter = signal<number | null>(null);

  // ====== Drawer state ======
  drawerOpen = signal(false);
  editMode   = signal(false);
  editingId: number | null = null;

  studentOptions = signal<StudentOption[]>([]);
  enrollmentOptions = signal<EnrollmentOption[]>([]);
  loadingEnrollments = signal(false);

  // ====== Paging ======
  page = signal(1);
  size = signal(10);
  total = computed(() => this.items().length);

  form = this.fb.group({
    studentId:    this.fb.control<number | null>(null),
    enrollmentId: this.fb.control<number | null>(null),
    component:    this.fb.control<string>('QUIZ', { validators: [Validators.required] }),
    score:        this.fb.control<number>(0, { validators: [Validators.required, Validators.min(0)] }),
    maxScore:     this.fb.control<number>(10, { validators: [Validators.required, Validators.min(1)] }),
  });

  // ====== Filtering + Slicing ======
  /** Full filtered list (no slicing) */
  private filteredAll = computed(() => {
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

  /** Count after filters */
  filteredTotal = computed(() => this.filteredAll().length);

  /** Page-sliced view */
  viewItems = computed(() => {
    const p = this.page();
    const s = this.size();
    const start = (p - 1) * s;
    const end   = start + s;
    return this.filteredAll().slice(start, end);
  });

  /** Visible range helpers */
  visibleStart = computed(() =>
    this.filteredTotal() === 0 ? 0 : (this.page() - 1) * this.size() + 1
  );
  visibleEnd = computed(() =>
    Math.min(this.page() * this.size(), this.filteredTotal())
  );

  ngOnInit() {
    this.refresh();
    this.loadStudentOptions();

    this.studentIdSub = this.form.get('studentId')!.valueChanges
      .pipe(distinctUntilChanged(), filter(() => !this.editMode()))
      .subscribe(id => {
        const v = Number(id);
        if (v > 0) this.onStudentChange(v); else this.enrollmentOptions.set([]);
      });

    // Prefill from roster → grades
    const qp = this.route.snapshot.queryParamMap;
    const open = qp.get('open');
    const studentId = Number(qp.get('studentId'));
    const enrollmentId = Number(qp.get('enrollmentId'));
    const classId = Number(qp.get('classId'));

    if (open === 'create' && studentId > 0) {
      if (!Number.isNaN(enrollmentId) && enrollmentId > 0) {
        this.prefillCreateDrawer(studentId, enrollmentId);
      } else if (!Number.isNaN(classId) && classId > 0) {
        // Resolve classId -> enrollmentId
        this.resolveClassAndPrefill(studentId, classId);
      } else {
        this.openCreate(); // fallback
        this.form.patchValue({ studentId });
      }
    }
  }

  ngOnDestroy(): void {
    this.studentIdSub?.unsubscribe(); // avoids accidental double subscriptions -> duplicate PATCH/toasts
  }

  // ====== Filters (reset page on change) ======
  onSearchInput(evt: Event) {
    const v = (evt.target as HTMLInputElement)?.value ?? '';
    this.q.set(v);
    this.page.set(1); // reset page on filter
  }

  onEnrollmentFilterInput(evt: Event) {
    const raw = (evt.target as HTMLInputElement)?.value ?? '';
    const v = raw.toString().trim();
    this.enrollmentFilter.set(v === '' ? null : Number(v));
    this.page.set(1); // reset page on filter
  }

  // ====== Pager actions ======
  next(){ this.page.update(p => p + 1); }
  prev(){ this.page.update(p => Math.max(1, p - 1)); }

  track = (_: number, g: { id: number }) => g.id;

  refresh() {
    this.loading.set(true);
    this.api.listAll().subscribe({
      next: list => {
        this.items.set(list);
        this.loading.set(false);

        // Clamp current page if dataset shrank
        const maxPage = Math.max(1, Math.ceil(this.filteredTotal() / this.size()));
        if (this.page() > maxPage) this.page.set(maxPage);
      },
      error: err => {
        console.error(err);
        this.toast.add('Failed to load grades', 'error');
        this.loading.set(false);
      },
    });
  }

  private loadStudentOptions() {
    this.http.get<any[]>(this.USERS_STUDENTS).subscribe({
      next: users => {
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

  private resolveClassAndPrefill(studentId: number, classId: number) {
    this.editMode.set(false);
    this.editingId = null;
    this.form.reset({ studentId, enrollmentId: null, component: 'QUIZ', score: 0, maxScore: 10 });
    this.enrollmentOptions.set([]);
    this.drawerOpen.set(true);

    this.loadingEnrollments.set(true);
    this.http.get<any[]>(this.STUDENT_CLASSROOMS(studentId)).subscribe({
      next: list => {
        const opts = list
          .filter(e => (e.status ?? 'ACTIVE') === 'ACTIVE')
          .map(e => ({
            id: (e.enrollmentId ?? e.id) as number,
            classId: e.classId,
            label: `${e.className}`,
          }));
        this.enrollmentOptions.set(opts);
        this.loadingEnrollments.set(false);

        const match = opts.find(o => o.classId === classId);
        if (match) {
          this.form.patchValue({ enrollmentId: match.id });
          // Also filter the table to that enrollment
          this.enrollmentFilter.set(match.id);
          this.page.set(1);
        } else {
          this.toast.add('The selected class has no active enrollment for this student', 'error');
        }
      },
      error: () => {
        this.toast.add('Could not load active enrollments for this student', 'error');
        this.enrollmentOptions.set([]);
        this.loadingEnrollments.set(false);
      },
    });
  }

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
            id: (e.enrollmentId ?? e.id) as number,  // tolerate either shape
            label: `${e.className}`,
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

  private prefillCreateDrawer(studentId: number, enrollmentId: number) {
    this.editMode.set(false);
    this.editingId = null;
    this.form.reset({
      studentId,
      enrollmentId: null, // set after options arrive
      component: 'QUIZ',
      score: 0,
      maxScore: 10,
    });
    this.enrollmentOptions.set([]);
    this.drawerOpen.set(true);

    // Load enrollments and select the one we were given
    this.loadingEnrollments.set(true);
    this.http.get<any[]>(this.STUDENT_CLASSROOMS(studentId)).subscribe({
      next: list => {
        const opts = list
          .filter(e => (e.status ?? 'ACTIVE') === 'ACTIVE')
          .map(e => ({
            id: (e.enrollmentId ?? e.id) as number,
            label: `${e.className}`,
          }));
        this.enrollmentOptions.set(opts);
        this.loadingEnrollments.set(false);

        if (opts.some(o => o.id === enrollmentId)) {
          this.form.patchValue({ enrollmentId });
          this.enrollmentFilter.set(enrollmentId);
          this.page.set(1);
        } else {
          this.toast.add('The selected enrollment is not active for this student', 'error');
        }
      },
      error: () => {
        this.toast.add('Could not load active enrollments for this student', 'error');
        this.enrollmentOptions.set([]);
        this.loadingEnrollments.set(false);
      },
    });
  }

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
          // Optionally keep on page 1 so the new item is visible
          this.page.set(1);
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
        const next = this.items().filter(x => x.id !== row.id);
        this.items.set(next);

        // If we just emptied the current page, go back one
        const nowVisible = this.viewItems().length;
        if (nowVisible === 0 && this.page() > 1) {
          this.page.update(p => p - 1);
        }

        this.toast.add('Grade deleted', 'success');
      },
      error: err => {
        console.error(err);
        this.toast.add('Could not delete grade', 'error');
      },
    });
  }
}
