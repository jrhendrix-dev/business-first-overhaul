import { Component, inject, signal } from '@angular/core';
import { AsyncPipe, NgFor, NgIf } from '@angular/common';
import {
  AbstractControl,
  NonNullableFormBuilder,
  ReactiveFormsModule,
  Validators,
} from '@angular/forms';
import { toObservable } from '@angular/core/rxjs-interop';
import { combineLatest, firstValueFrom, of, startWith, switchMap } from 'rxjs';
import { ClassroomService } from './classroom.service';
import {
  ClassroomItemDto,
  ClassroomDetailDto,
} from '@/app/shared/models/classroom/classroom-read.dto';

/**
 * Admin Classrooms Page (standalone component)
 *
 * What you can do here:
 *  - View the admin classroom list
 *  - Create a new classroom
 *  - Select a classroom, then rename it
 *  - Assign or unassign a teacher by teacherId
 *
 * Notes:
 *  - We rely on the global error interceptor to normalize backend errors into:
 *    { status, code, details }, e.g. { error: { code: "VALIDATION_FAILED", details: {...} } }
 *  - Forms are typed via NonNullableFormBuilder. We let Angular infer types.
 *  - We use signals for simple state (selected classroom id, isLoading, error text).
 */
@Component({
  standalone: true,
  selector: 'bfe-admin-classrooms-page',
  imports: [NgIf, NgFor, AsyncPipe, ReactiveFormsModule],
  templateUrl: './admin-classrooms.page.html',
})
export class AdminClassroomsPage {
  // DI: feature service + typed form builder
  private readonly svc = inject(ClassroomService);
  private readonly fb = inject(NonNullableFormBuilder);

  // ============ UI state (signals) ============
  // selected classroom id in the list (null when none selected)
  readonly selectedId = signal<number | null>(null);
  // top-level error text to show (non-validation)
  readonly error = signal<string | null>(null);
  // simple loading indicator for actions
  readonly busy = signal(false);

  // ============ Forms ============
  /** Create a classroom (POST /admin/classrooms) */
  readonly createForm = this.fb.group({
    name: ['', [Validators.required, Validators.maxLength(255)]],
  });

  /** Rename selected classroom (PUT /admin/classrooms/{id}) */
  readonly renameForm = this.fb.group({
    name: ['', [Validators.required, Validators.maxLength(255)]],
  });

  /** Assign/unassign teacher for selected classroom (PUT/DELETE) */
  readonly assignForm = this.fb.group({
    teacherId: this.fb.control<number | null>(null, { validators: [Validators.required, Validators.min(1)] }),
  });

  /**
   * Helper: pull "server" validation message set by us when backend returns
   * { error: { code: 'VALIDATION_FAILED', details: { field: 'message' } } }
   */
  serverError(ctrl: AbstractControl | null) {
    const e = ctrl?.errors as any;
    return e?.server as string | undefined;
  }

  /**
   * Small utility to set server validation errors onto a form group.
   * `details` expected shape: { [fieldName]: message }
   */
  private applyServerValidationErrors(
    form: typeof this.createForm | typeof this.renameForm | typeof this.assignForm,
    details: Record<string, string>
  ) {
    Object.entries(details).forEach(([field, message]) => {
      // Using controls indexing avoids the typed `get()` overload mismatch
      const ctrl = (form.controls as Record<string, AbstractControl | undefined>)[field];
      ctrl?.setErrors({ server: message });
    });
  }

  // ============ Data streams ============

  /**
   * Trigger signal: bump to force a refresh of list/detail.
   */
  private readonly refreshTick = signal(0);

  /**
   * List stream as an Observable:
   * - Convert `refreshTick` (Signal) to an Observable.
   * - Start with an initial tick to load immediately.
   * - On each tick, call the admin list endpoint.
   */
  readonly classrooms$ = toObservable(this.refreshTick).pipe(
    startWith(0),
    switchMap(() => this.svc.adminList())
  );

  /**
   * Selected classroom details as an Observable:
   * - Whenever `selectedId` OR `refreshTick` changes, re-fetch detail.
   * - If nothing selected, emit null.
   */
  readonly selected$ = combineLatest([
    toObservable(this.selectedId),
    toObservable(this.refreshTick),
  ]).pipe(
    switchMap(([id]) => (id == null ? of(null) : this.svc.adminGet(id))),
    startWith(null)
  );

  // ============ UI actions ============
  /** Select an item from list; also preload rename form with current name. */
  async select(id: number) {
    this.selectedId.set(id);
    const detail = await firstValueFrom(this.svc.adminGet(id));
    this.renameForm.reset({ name: detail.name });
    this.assignForm.reset({ teacherId: detail.teacher?.id ?? null });
  }

  /** Create a classroom, then refresh the list. */
  async create() {
    this.error.set(null);
    if (this.createForm.invalid) { this.createForm.markAllAsTouched(); return; }

    this.busy.set(true);
    try {
      await firstValueFrom(this.svc.create(this.createForm.getRawValue()));
      this.createForm.reset({ name: '' });
      // trigger reload
      this.refreshTick.set(this.refreshTick() + 1);
    } catch (e: any) {
      if (e?.code === 'VALIDATION_FAILED') {
        this.applyServerValidationErrors(this.createForm, e.details ?? {});
      } else {
        this.error.set(e?.code ?? 'UNKNOWN_ERROR');
      }
    } finally {
      this.busy.set(false);
    }
  }

  /** Rename currently selected classroom. */
  async rename() {
    this.error.set(null);
    const id = this.selectedId();
    if (id == null) return;
    if (this.renameForm.invalid) { this.renameForm.markAllAsTouched(); return; }

    this.busy.set(true);
    try {
      await firstValueFrom(this.svc.rename(id, this.renameForm.getRawValue()));
      this.refreshTick.set(this.refreshTick() + 1);
    } catch (e: any) {
      if (e?.code === 'VALIDATION_FAILED') {
        this.applyServerValidationErrors(this.renameForm, e.details ?? {});
      } else {
        this.error.set(e?.code ?? 'UNKNOWN_ERROR');
      }
    } finally {
      this.busy.set(false);
    }
  }

  /** Assign teacherId to the selected classroom (PUT). */
  async assignTeacher() {
    this.error.set(null);
    const id = this.selectedId();
    if (id == null) return;
    if (this.assignForm.invalid) { this.assignForm.markAllAsTouched(); return; }

    this.busy.set(true);
    try {
      await firstValueFrom(this.svc.assignTeacher(id, { teacherId: this.assignForm.value.teacherId! }));
      this.refreshTick.set(this.refreshTick() + 1);
    } catch (e: any) {
      if (e?.code === 'VALIDATION_FAILED') {
        this.applyServerValidationErrors(this.assignForm, e.details ?? {});
      } else {
        this.error.set(e?.code ?? 'UNKNOWN_ERROR');
      }
    } finally {
      this.busy.set(false);
    }
  }

  /** Remove the teacher from the selected classroom (DELETE). */
  async unassignTeacher() {
    this.error.set(null);
    const id = this.selectedId();
    if (id == null) return;

    this.busy.set(true);
    try {
      await firstValueFrom(this.svc.unassignTeacher(id));
      // After unassign, clear current teacherId in the form and refresh detail/list
      this.assignForm.patchValue({ teacherId: null });
      this.refreshTick.set(this.refreshTick() + 1);
    } catch (e: any) {
      this.error.set(e?.code ?? 'UNKNOWN_ERROR');
    } finally {
      this.busy.set(false);
    }
  }

  /** Utility for *ngFor trackBy to avoid re-render churn */
  trackById = (_: number, c: ClassroomItemDto) => c.id;
}
