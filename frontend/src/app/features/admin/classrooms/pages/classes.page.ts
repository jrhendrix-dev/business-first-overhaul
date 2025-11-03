// src/app/features/admin/classrooms/pages/classes.page.ts
import { Component, OnInit, inject, signal, computed } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ReactiveFormsModule, NonNullableFormBuilder, Validators } from '@angular/forms';
import { ActivatedRoute, Router, NavigationEnd} from '@angular/router';
import { debounceTime, distinctUntilChanged, filter, forkJoin } from 'rxjs';
import { HttpErrorResponse } from '@angular/common/http';
import { ClassroomsService } from '../services/classrooms.service';
import { ClassroomItemDto, ClassroomDetailDto } from '@/app/shared/models/classrooms/classroom-read.dto';
import { ToastContainerComponent } from '@app/core/ui/toast/toast-container.component';
import { ToastService } from '@app/core/ui/toast/toast.service';

import { DrawerAssignTeacherComponent } from '../components/drawer-assign-teacher.component';
import { DrawerCreateClassComponent } from '../components/drawer-create-class.component';
import { DrawerRosterComponent } from '../components/drawer-roster.component';

import type { EnrollmentMini } from '../models/enrollment-mini.model';
import {AdminHeaderComponent} from '@app/core/ui/admin-header.component';

@Component({
  standalone: true,
  selector: 'app-admin-classes',
  imports: [
    CommonModule,
    ReactiveFormsModule,
    AdminHeaderComponent,
    ToastContainerComponent,
    DrawerAssignTeacherComponent,
    DrawerCreateClassComponent,
    DrawerRosterComponent,
  ],
  templateUrl: './classes.page.html',
})
export class ClassesPage implements OnInit {
  private api    = inject(ClassroomsService);
  private fb     = inject(NonNullableFormBuilder);
  private route  = inject(ActivatedRoute);
  private router = inject(Router);
  private toast  = inject(ToastService);
  private studentOptionsReqSeq = 0;

  private assigning = false;
  private creating = false;

  // roster list inside drawer
  roster = signal<EnrollmentMini[]>([]);

  // list + filters
  items   = signal<ClassroomItemDto[]>([]);
  loading = signal(false);
  filters = this.fb.group({
    q: this.fb.control<string>(''),
    onlyUnassigned: this.fb.control<boolean>(false),
  });

  teacherId: number | null = null;
  studentId: number | null = null;

  isFiltered = computed(() =>
    !!this.teacherId ||
    !!this.studentId ||
    !!this.filters.controls.q.value ||
    this.filters.controls.onlyUnassigned.value
  );

  // create/rename
  drawerOpen = signal(false);
  editMode   = signal(false);
  editingId: number | null = null;

  form = this.fb.group({
    name: this.fb.control('', { validators: [Validators.required, Validators.minLength(2)] }),
  });

  // assign teacher
  assignOpen    = signal(false);
  assignClassId: number | null = null;

  assignForm = this.fb.group({
    teacherId: this.fb.control<number | null>(null, { validators: [Validators.required] }),
    onlyVacant: this.fb.control<boolean>(false),
  });

  teachers        = signal<{ id: number; name: string; email?: string | null }[]>([]);
  teacherLoading  = signal(false);

  // roster
  rosterOpen  = signal(false);
  rosterClass: ClassroomDetailDto | null = null;

  // picker in roster drawer
  rosterEnrollForm = this.fb.group({
    studentId: this.fb.control<number | null>(null, { validators: [Validators.required] }),
    onlyFree:  this.fb.control<boolean>(false),
  });

  studentOptions   = signal<{ id: number; name: string; email?: string | null }[]>([]);
  studentsLoading  = signal(false);

  ngOnInit(): void {
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

    this.assignForm.controls.onlyVacant.valueChanges
      .subscribe((onlyVacant) => this.loadTeachers(onlyVacant ?? false));

    this.rosterEnrollForm.controls.onlyFree.valueChanges
      .subscribe((onlyFree) => this.loadStudentOptions(onlyFree ?? false));
  }

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

    if (this.studentId != null) {
      this.api.list({ studentId: this.studentId }).subscribe({
        next: res => { this.items.set(res); this.loading.set(false); },
        error: ()   => { this.items.set([]); this.loading.set(false); this.toast.add('Load failed', 'error'); },
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

  resetToAll(): void {
    this.filters.reset({ q: '', onlyUnassigned: false }, { emitEvent: false });
    this.teacherId = null;
    this.studentId = null;
    this.router.navigate([], { relativeTo: this.route, queryParams: {}, replaceUrl: true });
    this.load();
  }

  /** True if the classroom is in dropped/archived state */
  isDropped(c: ClassroomItemDto) { return c.status === 'DROPPED'; }

  // create / rename
  openCreate(): void {
    this.editMode.set(false);
    this.editingId = null;
    this.creating = false;
    this.form.reset({ name: '' });
    this.drawerOpen.set(true);
  }

  openRename(c: ClassroomItemDto): void {
    this.editMode.set(true);
    this.editingId = c.id;
    this.creating = false;
    this.form.reset({ name: c.name });
    this.drawerOpen.set(true);
  }

  saveName(): void {
    if (this.creating) return;
    const name = (this.form.value.name ?? '').trim();
    if (!name) { this.toast.add('Please enter a name', 'error'); return; }

    this.creating = true;

    if (!this.editMode()) {
      this.api.create(name).subscribe({
        next: () => {
          this.toast.add('Classroom created', 'success');
          this.drawerOpen.set(false);
          this.creating = false;
          this.load();
        },
        error: (err) => {
          this.creating = false;
          this.showFriendlyNameError(err, 'create', name);
        },
      });
    } else {
      this.api.rename(this.editingId!, name).subscribe({
        next: () => {
          this.toast.add('Classroom renamed', 'success');
          this.drawerOpen.set(false);
          this.creating = false;
          this.load();
        },
        error: (err) => {
          this.creating = false;
          this.showFriendlyNameError(err, 'rename', name);
        },
      });
    }
  }



  /** Map server errors to human messages for create/rename */
  private humanizeSaveError(err: unknown, attemptedName: string): string | null {
    const http = err as HttpErrorResponse;
    const payload: any = http?.error ?? {};
    const envelope = payload?.error ?? payload; // support {error:{...}} or flat

    const code    = envelope?.code;
    const details = envelope?.details ?? {};

    // 1) Most explicit: API signals duplicate or 409
    if (http?.status === 409 || code === 'DUPLICATE_CLASSROOM_NAME') {
      return `The name "${attemptedName}" is already in use. Please choose another.`;
    }

    // 2) Validation envelope with a name field error
    const nameDetail = details?.name ?? details?.Name ?? details?.classroomName;
    if (nameDetail) {
      // allow array or string
      const msg = Array.isArray(nameDetail) ? nameDetail.join(', ') : String(nameDetail);
      return msg || `Invalid classroom name.`;
    }

    // 3) Raw message hints (e.g., SQL unique/duplicate)
    const rawMsg = envelope?.message ?? payload?.message ?? http?.message ?? '';
    if (typeof rawMsg === 'string' && /unique|duplicate|already exists/i.test(rawMsg)) {
      return `The name "${attemptedName}" is already in use.`;
    }

    return null; // let caller fall back
  }

  /** Quick local check to give instant feedback if the name is already on the list */
  private nameAlreadyUsed(name: string): boolean {
    const want = (name ?? '').trim().toLowerCase();
    return this.items().some(c => (c.name ?? '').trim().toLowerCase() === want);
  }

  /** Show a friendly error message for create/rename name issues (and other errors) */
  private showFriendlyNameError(err: any, intent: 'create' | 'rename', name: string): void {
    const generic = intent === 'create' ? 'Could not create classroom' : 'Could not rename classroom';

    // 0) Client-side instant duplicate check
    if (this.nameAlreadyUsed(name)) {
      this.toast.add(`“${name}” is already used by another classroom. Choose a different name.`, 'error');
      return;
    }

    // 1) Try to understand the server error
    const status = err?.status;
    const body   = err?.error ?? err;

    // Common fields some backends use
    const bodyMsg =
      (typeof body === 'string' ? body : null) ??
      body?.message ??
      body?.detail ??
      body?.error ??
      null;

    // Symfony-style validation errors
    const violations = Array.isArray(body?.violations) ? body.violations : null;

    // Custom codes you might emit server-side
    const code = body?.code;

    let msg: string | null = null;

    // 409 Conflict or explicit duplicate codes/messages
    if (status === 409 || code === 'DUPLICATE_CLASS_NAME' || /already\s*exist/i.test(bodyMsg ?? '')) {
      msg = `A classroom named “${name}” already exists. Try a different name.`;
    }

    // 400/422 with validation details
    if (!msg && (status === 400 || status === 422)) {
      if (violations) {
        const v = violations.find((x: any) =>
          /name/i.test(String(x?.propertyPath ?? '')) || /name/i.test(String(x?.property ?? ''))
        );
        if (v?.message) msg = v.message;
      } else if (typeof bodyMsg === 'string' && bodyMsg.trim()) {
        msg = bodyMsg;
      } else if (typeof body?.['hydra:description'] === 'string') {
        msg = body['hydra:description'];
      }
    }

    this.toast.add(msg ?? generic, 'error');
  }


  private showSaveError(err: unknown, action: 'create' | 'rename', attemptedName: string): void {
    const specific = this.humanizeSaveError(err, attemptedName);
    const fallback = action === 'create' ? 'Create failed' : 'Rename failed';

    this.toast.add(specific ?? fallback, 'error');

    // decorate the field so template can render an inline hint if desired
    if (specific) {
      this.form.controls.name.setErrors({ server: specific });
    }
  }

  closeDrawer(): void { this.drawerOpen.set(false); }

  // Helpers for assignments
  canAssign(c: ClassroomItemDto) { return c.status === 'ACTIVE'; }
  canOpenRoster(_: ClassroomItemDto) { return true; }
  canEnrollHere(detail?: ClassroomDetailDto | null) {
    return !!detail && detail.status === 'ACTIVE';
  }

  // assign / unassign teacher
  openAssign(c: ClassroomItemDto): void {
    if (!this.canAssign(c)) {
      this.toast.add('Classroom is dropped. Reactivate to assign a teacher.', 'info');
      return;
    }
    this.assignClassId = c.id;
    this.assignForm.reset({
      teacherId: c.teacher ? c.teacher.id : null,
      onlyVacant: false,
    });
    this.assignOpen.set(true);
    this.loadTeachers(false);
  }

  private loadTeachers(onlyVacantFromEvent?: boolean): void {
    if (!this.assignOpen()) return;
    this.teacherLoading.set(true);

    const onlyVacant =
      (onlyVacantFromEvent ?? this.assignForm.controls.onlyVacant.value ?? false);

    this.api.listTeachers({ onlyVacant }).subscribe({
      next: list => { this.teachers.set(list); this.teacherLoading.set(false); },
      error: ()   => { this.teacherLoading.set(false); this.toast.add('Failed to load teachers', 'error'); },
    });
  }

  assign(): void {
    if (this.assigning) return;
    this.assigning = true;

    const teacherId = this.assignForm.value.teacherId!;
    this.api.assignTeacher(this.assignClassId!, teacherId).subscribe({
      next: () => {
        this.toast.add('Teacher assigned', 'success');
        this.assignOpen.set(false);

        this.api.getOne(this.assignClassId!).subscribe({
          next: updated => {
            const updatedItems = this.items().map(c =>
              c.id === updated.id ? { ...c, teacher: updated.teacher } : c
            );
            this.items.set(updatedItems);
            this.assigning = false;
          },
          error: () => { this.assigning = false; }
        });
      },
      error: () => { this.assigning = false; this.toast.add('Assign failed', 'error'); },
    });
  }

  unassign(c: ClassroomItemDto): void {
    this.api.unassignTeacher(c.id).subscribe({
      next: () => { this.toast.add('Teacher unassigned', 'success'); this.load(); },
      error: () => this.toast.add('Unassign failed', 'error'),
    });
  }

  // students / roster
  droppedCount = signal(0);
  droppedList = signal<any[]>([]);
  bannerDismissed = signal(false);

  openRoster(c: ClassroomItemDto) {
    // open drawer
    this.rosterOpen.set(true);

    // reset visible state
    this.rosterClass = null;
    this.roster.set([]);
    this.studentOptions.set([]);
    this.droppedList.set([]);
    this.droppedCount.set(0);

    forkJoin({
      detail:  this.api.getOne(c.id),
      all:     this.api.listEnrollments(c.id, { includeDropped: true }),
      dropped: this.api.listDroppedEnrollments(c.id),
    }).subscribe(({ detail, all, dropped }) => {
      this.rosterClass = detail;

      // ACTIVE class → only ACTIVE rows; DROPPED → full history
      if (detail.status === 'ACTIVE') {
        this.roster.set(all.filter(e => (e.status || 'ACTIVE').toUpperCase() === 'ACTIVE'));
        this.loadStudentOptions(this.rosterEnrollForm.controls.onlyFree.value ?? false);
      } else {
        this.roster.set(all);
      }

      this.droppedList.set(dropped);
      this.droppedCount.set(dropped.length);
    });
  }

  dismissRestoreBanner(): void {
    if (!this.rosterClass?.id) return;
    this.api.dismissRestoreBanner(this.rosterClass.id).subscribe({
      next: () => { this.droppedCount.set(0); this.toast.add('Notice dismissed', 'info'); },
      error: () => this.toast.add('Could not dismiss notice', 'error'),
    });
  }

  discardDropped(id: number): void {
    this.api.discardEnrollment(id).subscribe({
      next: () => {
        this.toast.add('Discarded enrollment', 'info');
        this.api.listDroppedEnrollments(this.rosterClass!.id).subscribe(list => {
          this.droppedList.set(list);
          this.droppedCount.set(list.length);
        });
      },
      error: () => this.toast.add('Discard failed', 'error'),
    });
  }

  restoreRoster(): void {
    if (!this.rosterClass?.id) return;

    const classId = this.rosterClass.id;

    this.api.restoreRoster(classId).subscribe({
      next: (res) => {
        const n = res?.restored ?? 0;
        this.toast.add(n ? `Restored ${n} enrollment(s)` : 'No enrollments to restore', 'info');

        // 1) Refresh the active roster immediately
        this.reloadRoster();

        // 2) Hide the banner right away in the UI
        this.droppedCount.set(0);
        this.droppedList.set([]);

        // 3) Persist the dismissal on the server so it won’t reappear
        this.api.dismissRestoreBanner(classId).subscribe({
          next: () => {/* no-op */},
          error: () => {/* ignore — UI already cleared */},
        });
      },
      error: (err) => {
        if (err?.code === 'CLASSROOM_INACTIVE') {
          this.toast.add('Classroom is dropped. Reactivate to restore enrollments.', 'error');
          return;
        }
        this.toast.add('Restore failed', 'error');
      },
    });
  }

  closeRoster(): void { this.rosterOpen.set(false); }

  enrollStudent(): void {
    if (!this.canEnrollHere(this.rosterClass)) {
      this.toast.add('Classroom is dropped. Reactivate to enroll students.', 'info');
      return;
    }
    const classId = this.rosterClass!.id;
    const sid = this.rosterEnrollForm.value.studentId!;
    this.api.enrollStudent(classId, sid).subscribe({
      next: () => {
        this.toast.add('Student enrolled', 'success');
        this.rosterEnrollForm.reset({
          studentId: null,
          onlyFree: this.rosterEnrollForm.value.onlyFree ?? false,
        });
        this.reloadRoster();
        this.loadStudentOptions();
      },
      error: (err) => {
        if (err?.code === 'CLASSROOM_INACTIVE') {
          this.toast.add('Classroom is dropped. Reactivate to enroll students.', 'error');
          return;
        }
        this.toast.add('Enroll failed', 'error');
      },
    });
  }

  dropStudent(studentId: number): void {
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
      next: ({ detail, active }) => {
        this.rosterClass = detail;
        this.roster.set(active);
        this.loadStudentOptions();
      },
    });
  }

  private loadStudentOptions(onlyFreeFromEvent?: boolean): void {
    if (!this.rosterOpen() || !this.rosterClass) return;
    this.studentsLoading.set(true);

    const excludeIds = this.roster().map(e => e.student.id);
    const onlyFree = (onlyFreeFromEvent ?? this.rosterEnrollForm.controls.onlyFree.value ?? false);

    const reqId = ++this.studentOptionsReqSeq;

    this.api
      .listStudentsForClass(this.rosterClass.id, {
        onlyNotEnrolled: true,
        onlyWithoutAnyEnrollment: onlyFree,
        excludeIds,
      })
      .subscribe({
        next: list => {
          if (reqId === this.studentOptionsReqSeq) {
            this.studentOptions.set(list);
          }
          this.studentsLoading.set(false);
        },
        error: () => {
          if (reqId === this.studentOptionsReqSeq) {
            this.studentsLoading.set(false);
          }
          this.toast.add('Failed to load students list', 'error');
        },
      });
  }

  /** Reactivate a dropped classroom. */
  reactivate(c: ClassroomItemDto): void {
    if (!c?.id) return;
    this.api.reactivate(c.id).subscribe({
      next: () => { this.toast.add('Classroom reactivated', 'success'); this.load(); },
      error: () => this.toast.add('Could not reactivate classroom', 'error'),
    });
  }

  /** Delete classroom; if backend soft-deletes (DROPPED), reflect immediately without a page reload. */
  remove(c: ClassroomItemDto): void {
    if (!c?.id) return;

    this.api.delete(c.id).subscribe({
      next: () => {
        // Optimistically mark as DROPPED so UI updates instantly
        this.items.set(
          this.items().map(x =>
            x.id === c.id ? { ...x, status: 'DROPPED', teacher: null } : x
          )
        );

        // Verify with server: if 404 → hard-deleted, remove from list; otherwise sync the row.
        this.api.getOne(c.id).subscribe({
          next: detail => {
            // Exists (likely soft-deleted) → sync status/teacher from backend
            this.items.set(
              this.items().map(x =>
                x.id === detail.id ? { ...x, status: detail.status, teacher: detail.teacher ?? null } : x
              )
            );
            this.toast.add(
              detail.status === 'DROPPED' ? 'Classroom dropped' : 'Classroom deleted',
              'info'
            );
          },
          error: () => {
            // Not found → it was hard-deleted; remove from the list
            this.items.set(this.items().filter(x => x.id !== c.id));
            this.toast.add('Classroom deleted', 'info');
          },
        });
      },
      error: () => this.toast.add('Could not delete classroom', 'error'),
    });
  }
}
