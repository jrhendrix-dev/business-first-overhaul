// src/app/features/admin/classrooms/components/drawer-create-class.component.ts
import { Component, EventEmitter, Input, Output, ChangeDetectionStrategy } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ReactiveFormsModule, FormGroup } from '@angular/forms';
import { DrawerComponent } from '@/app/core/ui/drawer/drawer.component';

@Component({
  standalone: true,
  selector: 'bf-drawer-create-class',
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [CommonModule, ReactiveFormsModule, DrawerComponent],
  template: `
    <bf-drawer
      [open]="open"
      [heading]="editMode ? 'Edit classroom' : 'Create classroom'"
      [offsetVar]="'--admin-navbar-h'"
      (close)="cancel.emit()">

      <div class="px-3 py-4">
        <form
          [formGroup]="form"
          class="space-y-4"
          (ngSubmit)="submit.emit()">

          <div>
            <label class="block text-xs text-slate-600 mb-1">Name</label>
            <input
              class="w-full border rounded px-3 py-2 text-sm"
              formControlName="name">
          </div>

          <div>
            <label class="block text-xs text-slate-600 mb-1">Price (EUR)</label>
            <input
              type="number"
              step="0.01"
              min="0"
              placeholder="e.g. 39.99"
              class="w-full border rounded px-3 py-2 text-sm"
              formControlName="price">
            <p class="text-xs text-slate-500 mt-1">Leave empty for free classes.</p>
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
              {{ editMode ? 'Save' : 'Create' }}
            </button>
          </div>
        </form>
      </div>
    </bf-drawer>
  `,
})
export class DrawerCreateClassComponent {
  @Input() open = false;
  @Input() editMode = false;
  @Input({ required: true }) form!: FormGroup;

  @Output() cancel = new EventEmitter<void>();
  @Output() submit = new EventEmitter<void>();
}
