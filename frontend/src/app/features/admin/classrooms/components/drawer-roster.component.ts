// src/app/features/admin/classrooms/components/drawer-roster.component.ts
import { Component, EventEmitter, Input, Output, ChangeDetectionStrategy } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ReactiveFormsModule, FormGroup } from '@angular/forms';
import { DrawerComponent } from '@/app/core/ui/drawer/drawer.component';
import { EnrollmentMini } from '../models/enrollment-mini.model';

type ClassStatus = 'ACTIVE' | 'DROPPED';

@Component({
  standalone: true,
  selector: 'bf-drawer-roster',
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [CommonModule, ReactiveFormsModule, DrawerComponent],
  template: `
    <bf-drawer
      [open]="open"
      [heading]="'Students — ' + className"
      [offsetVar]="'--admin-navbar-h'"
      (close)="close.emit()">

      <!-- DROPPED notice -->
      <div *ngIf="isDropped"
           class="mb-3 rounded bg-amber-50 border border-amber-200 px-3 py-2 text-amber-900">
        Classroom is dropped. You can view the roster history but cannot enroll until it’s reactivated.
      </div>

      <!-- Restore banner (ACTIVE only) -->
      <div *ngIf="!isDropped && droppedCount > 0"
           class="mb-3 rounded border border-sky-200 bg-sky-50 text-sky-900 px-3 py-2">
        <div class="flex items-center justify-between">
          <span>{{ droppedCount }} previously dropped enrollment(s) found.</span>
          <div class="flex gap-2">
            <button type="button" class="px-2 py-1 rounded bg-sky-600 text-white hover:bg-sky-700" (click)="restore.emit()">Restore</button>
            <button type="button" class="px-2 py-1 rounded border hover:bg-sky-200" (click)="showDroppedList = !showDroppedList">View</button>
            <button type="button" class="px-2 py-1 rounded border hover:bg-sky-200" (click)="dismiss.emit()">Dismiss</button>
          </div>
        </div>

        <div *ngIf="showDroppedList && (droppedItems?.length || 0) > 0" class="mt-3 space-y-2">
          <div *ngFor="let d of droppedItems" class="flex items-center justify-between border rounded p-2">
            <div class="text-sm">
              {{ d.student?.name }}
              <span class="text-xs text-slate-600">— {{ d.student?.email }}</span>
              <div class="text-xs text-slate-500" *ngIf="d.droppedAt">Dropped: {{ d.droppedAt | date:'short' }}</div>
            </div>
            <button type="button" class="btn btn-danger" (click)="discard.emit(d.id)">Discard</button>
          </div>
        </div>
      </div>

      <!-- Add a student (hidden when DROPPED) -->
      <h3 *ngIf="!isDropped" class="text-sm font-medium mt-1 mb-2">Add a student</h3>
      <form *ngIf="!isDropped" [formGroup]="form" class="space-y-2 mb-3" (ngSubmit)="onSubmit($event)">
        <label class="inline-flex items-center gap-2">
          <input type="checkbox" formControlName="onlyFree">
          <span class="text-sm">Only students with no active enrollment</span>
        </label>

        <div>
          <label class="block text-xs text-slate-600 mb-1">Select student</label>
          <select class="w-full border rounded px-2 py-1" formControlName="studentId">
            <option [ngValue]="null" disabled>Choose a student</option>
            <option *ngFor="let s of studentOptions" [ngValue]="s.id">
              {{ s.name }} <span *ngIf="s.email"></span>
            </option>
          </select>
          <div *ngIf="studentsLoading" class="text-xs text-slate-500 mt-1">Loading students…</div>
        </div>

        <div class="flex justify-end">
          <button type="submit" class="btn btn-success" [disabled]="form?.invalid">Enroll</button>
        </div>
      </form>

      <!-- Roster -->
      <div class="space-y-2">
        <div *ngFor="let e of roster" class="border rounded p-3 flex items-center justify-between">
          <div>
            <div class="font-medium">{{ e.student.firstName }} {{ e.student.lastName }}</div>
            <div class="text-xs text-slate-600">{{ e.student.email }}</div>
            <div class="text-xs mt-1">
              Status:
              <span class="font-mono"
                    [class.text-emerald-700]="e.status==='ACTIVE'"
                    [class.text-slate-600]="e.status!=='ACTIVE'">
                {{ e.status }}
              </span>
              <span *ngIf="e.enrolledAt" class="ml-2">Enrolled: {{ e.enrolledAt | date:'short' }}</span>
            </div>
          </div>

          <button *ngIf="!isDropped && e.status==='ACTIVE'"
                  type="button"
                  class="btn btn-danger"
                  (click)="drop.emit(e.student.id)">
            Drop
          </button>
        </div>

        <div *ngIf="(roster?.length || 0) === 0" class="text-sm text-slate-500">No students.</div>
      </div>
    </bf-drawer>
  `,
})
export class DrawerRosterComponent {
  @Input() open = false;
  @Input() className = '';
  @Input() classStatus: ClassStatus = 'ACTIVE';

  @Input() form!: FormGroup;
  @Input() roster: EnrollmentMini[] = [];
  @Input() studentOptions: { id: number; name: string; email?: string | null }[] = [];
  @Input() studentsLoading = false;

  @Input() droppedCount = 0;
  @Input() droppedItems: any[] = [];

  @Output() close = new EventEmitter<void>();
  @Output() enroll = new EventEmitter<void>();
  @Output() drop = new EventEmitter<number>();
  @Output() restore = new EventEmitter<void>();
  @Output() dismiss = new EventEmitter<void>();
  @Output() discard = new EventEmitter<number>();

  showDroppedList = false;
  get isDropped(): boolean { return this.classStatus === 'DROPPED'; }

  onSubmit(evt: Event): void {
    if (this.form?.invalid) { evt.preventDefault(); return; }
    this.enroll.emit();
  }
}
