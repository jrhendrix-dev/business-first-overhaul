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
      [heading]="editMode ? 'Rename classroom' : 'Create classroom'"
      [offsetVar]="'--admin-navbar-h'"
      (close)="cancel.emit()">

      <form [formGroup]="form" class="space-y-3" (ngSubmit)="submit.emit()">
        <div>
          <label class="block text-xs text-slate-600 mb-1">Name</label>
          <input class="w-full border rounded px-2 py-1" formControlName="name">
        </div>

        <div class="pt-2 flex justify-end gap-2">
          <button type="button" class="btn btn-outline" (click)="cancel.emit()">Cancel</button>
          <button class="btn btn-success" [disabled]="form.invalid">
            {{ editMode ? 'Save' : 'Create' }}
          </button>
        </div>
      </form>
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
