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

      <form [formGroup]="form" class="space-y-3" (ngSubmit)="submit.emit()">
        <label class="inline-flex items-center gap-2">
          <input type="checkbox" formControlName="onlyVacant">
          <span class="text-sm">Only unassigned teachers</span>
        </label>

        <div>
          <label class="block text-xs text-slate-600 mb-1">Select teacher</label>
          <select class="w-full border rounded px-2 py-1" formControlName="teacherId">
            <option [ngValue]="null" disabled>— Select a teacher —</option>
            <option *ngFor="let t of teachers" [ngValue]="t.id">
              {{ t.name }} 
            </option>
          </select>
          <div *ngIf="loading" class="text-xs text-slate-500 mt-1">Loading teachers…</div>
        </div>

        <div class="pt-2 flex justify-end gap-2">
          <button type="button" class="btn btn-outline" (click)="cancel.emit()">Cancel</button>
          <button class="btn btn-success" [disabled]="form.invalid">Assign</button>
        </div>
      </form>

      <p class="text-xs text-slate-500 mt-3">Tip: toggle “Only unassigned teachers” to limit the list.</p>
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
