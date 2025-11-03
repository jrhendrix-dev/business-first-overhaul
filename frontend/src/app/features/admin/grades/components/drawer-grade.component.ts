// src/app/features/admin/grades/components/drawer-grade.component.ts
import { Component, EventEmitter, Input, Output, ChangeDetectionStrategy } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ReactiveFormsModule, FormGroup } from '@angular/forms';
import { DrawerComponent } from '@/app/core/ui/drawer/drawer.component';

type ComponentOption  = { value: string; label: string };
export type StudentOption    = { id: number; label: string };    // e.g. "Peter Parker (peter@ex.com)"
export type EnrollmentOption = { id: number; label: string };    // e.g. "C2 A — #12"

/** Must match backend enum/DB values exactly */
const DEFAULT_COMPONENT_OPTIONS: ReadonlyArray<ComponentOption> = [
  { value: 'PROJECT',  label: 'Project'  },
  { value: 'HOMEWORK', label: 'Homework' },
  { value: 'QUIZ',     label: 'Quiz'     },
  { value: 'EXAM',     label: 'Exam'     },
];

@Component({
  standalone: true,
  selector: 'bf-drawer-grade',
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [CommonModule, ReactiveFormsModule, DrawerComponent],
  template: `
    <bf-drawer
      [open]="open"
      [heading]="editMode ? 'Edit grade' : 'Add grade'"
      [offsetVar]="'--admin-navbar-h'"
      (close)="cancel.emit()">

      <form [formGroup]="form" class="space-y-4" (ngSubmit)="save.emit()">
        <!-- Student & Enrollment (create only) -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3" *ngIf="!editMode">
          <div>
            <label class="block text-xs text-slate-600 mb-1">Student</label>
            <select
              class="w-full border rounded px-2 py-1"
              formControlName="studentId"
              (change)="onStudentChange($any($event.target).value)">
              <option [ngValue]="null">Select a student…</option>
              <option *ngFor="let s of students; trackBy: trackById" [ngValue]="s.id">{{ s.label }}</option>
            </select>
            <p class="text-[11px] text-slate-500 mt-1">Pick the student to load their active enrollments.</p>
          </div>

          <div>
            <label class="block text-xs text-slate-600 mb-1">Enrollment (classroom)</label>
            <select
              class="w-full border rounded px-2 py-1"
              formControlName="enrollmentId"
              [disabled]="!form.value.studentId || loadingEnrollments">
              <option [ngValue]="null" *ngIf="!form.value.studentId">Select a student first…</option>
              <ng-container *ngIf="form.value.studentId">
                <option [ngValue]="null" *ngIf="loadingEnrollments">Loading…</option>
                <option *ngFor="let e of enrollments; trackBy: trackById" [ngValue]="e.id">{{ e.label }}</option>
                <option [ngValue]="null" *ngIf="!loadingEnrollments && enrollments?.length === 0">
                  No active enrollments
                </option>
              </ng-container>
            </select>
          </div>
        </div>

        <div>
          <label class="block text-xs text-slate-600 mb-1">Component</label>
          <select class="w-full border rounded px-2 py-1" formControlName="component">
            <option *ngFor="let opt of componentOptions" [value]="opt.value">{{ opt.label }}</option>
          </select>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-xs text-slate-600 mb-1">Score</label>
            <input type="number" step="0.01" class="w-full border rounded px-2 py-1" formControlName="score">
          </div>
          <div>
            <label class="block text-xs text-slate-600 mb-1">Max score</label>
            <input type="number" step="0.01" class="w-full border rounded px-2 py-1" formControlName="maxScore">
          </div>
        </div>

        <div class="pt-2 flex justify-end gap-2">
          <button type="button" class="btn btn-outline" (click)="cancel.emit()">Cancel</button>
          <button type="submit" class="btn btn-success"
                  [disabled]="form.invalid || (!editMode && (!form.value.studentId || !form.value.enrollmentId))">
            {{ editMode ? 'Save' : 'Create' }}
          </button>
        </div>
      </form>
    </bf-drawer>
  `,
})
export class DrawerGradeComponent {
  @Input() open = false;
  @Input() editMode = false;
  @Input({ required: true }) form!: FormGroup;

  @Input() students: ReadonlyArray<{ id: number; label: string }> = [];
  @Input() enrollments: ReadonlyArray<{ id: number; label: string }> = [];
  @Input() loadingEnrollments = false;

  @Input() componentOptions: ReadonlyArray<{ value: string; label: string }> = [
    { value: 'PROJECT',  label: 'Project'  },
    { value: 'HOMEWORK', label: 'Homework' },
    { value: 'QUIZ',     label: 'Quiz'     },
    { value: 'EXAM',     label: 'Exam'     },
  ];

  @Output() studentChange = new EventEmitter<number>();
  @Output() cancel = new EventEmitter<void>();
  /** renamed to avoid clashing with native submit event */
  @Output() save = new EventEmitter<void>();

  onStudentChange(rawValue: string | number | null) {
    const id = Number(rawValue);
    if (this.form.get('enrollmentId')) {
      this.form.patchValue({ enrollmentId: null }, { emitEvent: false });
    }
    if (!Number.isNaN(id)) this.studentChange.emit(id);
  }

  trackById = (_: number, item: { id: number }) => item.id;
}
