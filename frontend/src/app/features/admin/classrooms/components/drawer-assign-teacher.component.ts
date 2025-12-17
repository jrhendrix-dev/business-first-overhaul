// src/app/features/admin/classrooms/components/drawer-assign-teacher.component.ts
import { Component, EventEmitter, Input, Output, ChangeDetectionStrategy } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ReactiveFormsModule, FormGroup } from '@angular/forms';
import { DrawerComponent } from '@/app/core/ui/drawer/drawer.component';

export type TeacherOption = { id: number; name: string; email?: string | null };

@Component({
  standalone: true,
  selector: 'bf-drawer-assign-teacher',
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [CommonModule, ReactiveFormsModule, DrawerComponent],
  template: `
    <bf-drawer
      [open]="open"
      [heading]="'Assign teacher'"
      [offsetVar]="'--admin-navbar-h'"
      (close)="cancel.emit()">

      <div class="px-3 py-4 space-y-4">
        <form
          [formGroup]="form"
          class="space-y-4"
          (ngSubmit)="submit.emit()">

          <label class="inline-flex items-center gap-2">
            <input type="checkbox" formControlName="onlyVacant">
            <span class="text-sm">Only unassigned teachers</span>
          </label>

          <div>
            <label class="block text-xs text-slate-600 mb-1">Select teacher</label>
            <select
              class="w-full border rounded px-2 py-2 text-sm"
              formControlName="teacherId">
              <option [ngValue]="null" disabled>— Select a teacher —</option>
              <option *ngFor="let t of teachers" [ngValue]="t.id">
                {{ t.name }}
              </option>
            </select>
            <div *ngIf="loading" class="text-xs text-slate-500 mt-1">Loading teachers…</div>
          </div>

          <div class="pt-2 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
            <button
              type="button"
              class="btn btn-outline w-full sm:w-auto"
              (click)="cancel.emit()">
              Cancel
            </button>
            <button
              class="btn btn-success w-full sm:w-auto"
              [disabled]="form.invalid">
              Assign
            </button>
          </div>
        </form>

        <p class="text-xs text-slate-500">
          Tip: toggle “Only unassigned teachers” to limit the list.
        </p>
      </div>
    </bf-drawer>
  `,
})
export class DrawerAssignTeacherComponent {
  @Input() open = false;
  @Input({ required: true }) form!: FormGroup;
  @Input() teachers: TeacherOption[] = [];
  @Input() loading = false;

  @Output() cancel = new EventEmitter<void>();
  @Output() submit = new EventEmitter<void>();
}
