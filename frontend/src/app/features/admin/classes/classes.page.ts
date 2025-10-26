import { Component, OnInit, inject, signal, computed } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ReactiveFormsModule, NonNullableFormBuilder, Validators } from '@angular/forms';
import { ActivatedRoute, Router, NavigationEnd } from '@angular/router';
import { debounceTime, distinctUntilChanged, filter, forkJoin } from 'rxjs';
import { ClassroomsService } from './classrooms.service';
import { ClassroomItemDto, ClassroomDetailDto } from '@/app/shared/models/classrooms/classroom-read.dto';
import { ToastContainerComponent } from '@/app/core/ui/toast-container.component';
import { ToastService } from '@/app/core/ui/toast.service';
import type { TeacherOption, EnrollmentMini } from './classrooms.service';
import { of } from 'rxjs';
import { catchError } from 'rxjs/operators';



@Component({
  standalone: true,
  selector: 'app-admin-classes',
  imports: [CommonModule, ReactiveFormsModule, ToastContainerComponent],
  templateUrl: './classes.page.html',
})
export class ClassesPage implements OnInit {
  private api   = inject(ClassroomsService);
  private fb    = inject(NonNullableFormBuilder);
  private route = inject(ActivatedRoute);
  private router= inject(Router);
  private toast = inject(ToastService);

  roster = signal<EnrollmentMini[]>([]);

  /** list + filters */
  items   = signal<ClassroomItemDto[]>([]);
  loading = signal(false);
  filters = this.fb.group({
    q: this.fb.control<string>(''),
    onlyUnassigned: this.fb.control<boolean>(false),
  });

  /** “filtered” guard (to show the “All classes” button) */
  teacherId: number | null = null;
  studentId: number | null = null;
  isFiltered = computed(() =>
    !!this.teacherId || !!this.studentId ||
    !!this.filters.controls.q.value || !!this.filters.controls.onlyUnassigned.value
  );

  /** create/rename drawer */
  drawerOpen = signal(false);
  editMode   = signal(false);
  editingId: number | null = null;
  form = this.fb.group({
    name: this.fb.control('', { validators: [Validators.required, Validators.minLength(2)] }),
  });

  /** assign-teacher drawer */
  assignOpen   = signal(false);
  assignClassId: number | null = null;
  assignForm = this.fb.group({
    teacherId: this.fb.control<number | null>(null, { validators: [Validators.required] }),
    onlyVacant: this.fb.control<boolean>(false),
  });
  teachers = signal<TeacherOption[]>([]);
  teacherLoading = signal(false);

  /** students / roster drawer */
  rosterOpen  = signal(false);
  rosterClass: ClassroomDetailDto | null = null;
  rosterEnrollForm = this.fb.group({
    studentId: this.fb.control<number | null>(null, { validators: [Validators.required] }),
  });

  ngOnInit(): void {
    // initial + refresh when the “Classes” nav is clicked again
    const readQp = () => {
      const qp = this.route.snapshot.queryParamMap;
      this.teacherId = qp.get('teacherId') ? Number(qp.get('teacherId')) : null;
      this.studentId = qp.get('studentId') ? Number(qp.get('studentId')) : null;
    };
    readQp();
    this.router.events.pipe(filter(e => e instanceof NavigationEnd)).subscribe(() => {
      readQp();
      this.load();
    });

    this.load();

    this.filters.valueChanges
      .pipe(debounceTime(300), distinctUntilChanged())
      .subscribe(() => this.load());

    // react to the “only vacant” toggle inside assign drawer
    this.assignForm.controls.onlyVacant.valueChanges.subscribe(() => this.loadTeachers());
  }

  /** list loader */
  load(): void {
    this.loading.set(true);
    const q = this.filters.controls.q.value?.trim();
    const unassigned = this.filters.controls.onlyUnassigned.value;

    if (q) {
      this.api.list({ name: q }).subscribe({
        next: res => { this.items.set(res); this.loading.set(false); },
        error: () => { this.items.set([]); this.loading.set(false); this.toast.add('Search failed', 'error'); },
      });
      return;
    }
    if (this.teacherId != null) {
      this.api.list({ teacherId: this.teacherId }).subscribe({
        next: res => { this.items.set(res); this.loading.set(false); },
        error: () => { this.items.set([]); this.loading.set(false); this.toast.add('Load failed', 'error'); },
      });
      return;
    }
    if (unassigned) {
      this.api.list({ unassigned: true }).subscribe({
        next: res => { this.items.set(res); this.loading.set(false); },
        error: () => { this.items.set([]); this.loading.set(false); this.toast.add('Load failed', 'error'); },
      });
      return;
    }

    this.api.list().subscribe({
      next: res => { this.items.set(res); this.loading.set(false); },
      error: () => { this.items.set([]); this.loading.set(false); this.toast.add('Load failed', 'error'); },
    });
  }

  /** helpers */
  resetToAll() {
    this.filters.reset({ q: '', onlyUnassigned: false }, { emitEvent: false });
    this.teacherId = null;
    this.studentId = null;
    this.router.navigate([], { relativeTo: this.route, queryParams: {}, replaceUrl: true });
    this.load();
  }
  isDropped(c: ClassroomItemDto)  { return c.status === 'DROPPED'; }

  /** create / rename */
  openCreate() {
    this.editMode.set(false);
    this.editingId = null;
    this.form.reset({ name: '' });
    this.drawerOpen.set(true);
  }
  openRename(c: ClassroomItemDto) {
    this.editMode.set(true);
    this.editingId = c.id;
    this.form.reset({ name: c.name });
    this.drawerOpen.set(true);
  }
  saveName() {
    const name = this.form.value.name!.trim();
    if (!this.editMode()) {
      this.api.create(name).subscribe({
        next: () => { this.toast.add('Classroom created', 'success'); this.closeDrawer(); this.load(); },
        error: () => this.toast.add('Create failed', 'error'),
      });
    } else {
      this.api.rename(this.editingId!, name).subscribe({
        next: () => { this.toast.add('Classroom renamed', 'success'); this.closeDrawer(); this.load(); },
        error: () => this.toast.add('Rename failed', 'error'),
      });
    }
  }
  closeDrawer(){ this.drawerOpen.set(false); }

  /** assign / unassign teacher */
  openAssign(c: ClassroomItemDto) {
    this.assignClassId = c.id;
    this.assignForm.reset({
      teacherId: c.teacher ? c.teacher.id : null,
      onlyVacant: false,
    });
    this.assignOpen.set(true);
    this.loadTeachers();
  }

  private loadTeachers() {
    if (!this.assignOpen()) return;
    this.teacherLoading.set(true);
    this.api.listTeachers({ onlyVacant: !!this.assignForm.value.onlyVacant }).subscribe({
      next: list => { this.teachers.set(list); this.teacherLoading.set(false); },
      error: () => { this.teacherLoading.set(false); this.toast.add('Failed to load teachers', 'error'); }
    });
  }

  assign() {
    const teacherId = this.assignForm.value.teacherId!;
    this.api.assignTeacher(this.assignClassId!, teacherId).subscribe({
      next: () => {
        this.toast.add('Teacher assigned', 'success');
        this.assignOpen.set(false);
        this.load(); // now runs
      },
      error: () => this.toast.add('Assign failed', 'error'),
    });
  }

  unassign(c: ClassroomItemDto) {
    this.api.unassignTeacher(c.id).subscribe({
      next: () => { this.toast.add('Teacher unassigned', 'success'); this.load(); },
      error: () => this.toast.add('Unassign failed', 'error'),
    });
  }

  /** reactivate / delete */
  reactivate(c: ClassroomItemDto) {
    this.api.reactivate(c.id).subscribe({
      next: () => { this.toast.add('Classroom reactivated', 'success'); this.load(); },
      error: () => this.toast.add('Reactivate failed', 'error'),
    });
  }
  remove(c: ClassroomItemDto) {
    const ok = window.confirm(`Delete classroom "${c.name}"? If it has active students, it will be dropped instead.`);
    if (!ok) return;
    this.api.delete(c.id).subscribe({
      next: () => { this.toast.add('Request processed', 'success'); this.load(); },
      error: () => this.toast.add('Delete failed', 'error'),
    });
  }

  /** students / roster */
  openRoster(c: ClassroomItemDto) {
    // optimistic open, clear previous data
    this.rosterOpen.set(true);
    this.rosterClass = null;
    this.roster.set([]);

    forkJoin({
      detail: this.api.getOne(c.id).pipe(
        catchError((_err) => {
          this.toast.add('Failed to load class detail', 'error');
          return of(null as unknown as ClassroomDetailDto);
        })
      ),
      active: this.api.listActiveEnrollments(c.id).pipe(
        catchError((_err) => {
          this.toast.add('Failed to load students', 'error');
          return of([]);
        })
      ),
    }).subscribe(({ detail, active }) => {
      if (detail) this.rosterClass = detail;
      this.roster.set(active);
    });
  }
  closeRoster(){ this.rosterOpen.set(false); }

  enrollStudent() {
    const classId = this.rosterClass?.id!;
    const sid = this.rosterEnrollForm.value.studentId!;
    this.api.enrollStudent(classId, sid).subscribe({
      next: () => { this.toast.add('Student enrolled', 'success'); this.rosterEnrollForm.reset({ studentId: null }); this.reloadRoster(); },
      error: () => this.toast.add('Enroll failed', 'error'),
    });
  }
  dropStudent(studentId: number) {
    const classId = this.rosterClass?.id!;
    this.api.dropStudent(classId, studentId).subscribe({
      next: () => { this.toast.add('Student dropped', 'success'); this.reloadRoster(); },
      error: () => this.toast.add('Drop failed', 'error'),
    });
  }
  private reloadRoster() {
    if (!this.rosterClass) return;
    forkJoin({
      detail: this.api.getOne(this.rosterClass.id),
      active: this.api.listActiveEnrollments(this.rosterClass.id),
    }).subscribe({
      next: ({ detail, active }) => { this.rosterClass = detail; this.roster.set(active); },
    });
  }
}
