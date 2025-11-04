// src/app/features/admin/classrooms/components/drawer-student-grades.component.ts
import { Component, EventEmitter, Input, Output, ChangeDetectionStrategy, inject, signal, computed } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ReactiveFormsModule, NonNullableFormBuilder, Validators } from '@angular/forms';
import { DrawerComponent } from '@/app/core/ui/drawer/drawer.component';
import { GradesService, GradeItemDto, AddGradeDto, UpdateGradeDto } from '@/app/features/admin/grades/grades.service';
import { ToastService } from '@/app/core/ui/toast/toast.service';
import { DrawerGradeComponent } from '@/app/features/admin/grades/components/drawer-grade.component';

@Component({
  standalone: true,
  selector: 'bf-drawer-student-grades',
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [CommonModule, ReactiveFormsModule, DrawerComponent, DrawerGradeComponent],
  template: `
    <bf-drawer
      [open]="open"
      [heading]="'Grades — ' + (studentLabel || 'Student') + ' • ' + (classroomLabel || 'Classroom')"
      [offsetVar]="'--admin-navbar-h'"
      (close)="close.emit()">

      <div class="space-y-4">
        <div class="flex items-center justify-between">
          <div class="text-sm text-slate-600">
            Enrollment #{{ enrollmentId }} — {{ studentLabel }} in {{ classroomLabel }}
          </div>
          <button class="btn btn-success btn-sm" (click)="openCreate()">Add grade</button>
        </div>

        <div class="border rounded overflow-hidden">
          <table class="min-w-full text-sm">
            <thead class="bg-slate-50 border-b">
              <tr>
                <th class="text-left px-3 py-2">Component</th>
                <th class="text-left px-3 py-2">Score</th>
                <th class="text-left px-3 py-2">%</th>
                <th class="text-left px-3 py-2 w-36">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr *ngFor="let g of items(); trackBy: track" class="border-b">
                <td class="px-3 py-2">{{ g.componentLabel }}</td>
                <td class="px-3 py-2">{{ g.score }} / {{ g.maxScore }}</td>
                <td class="px-3 py-2">{{ g.percent | number:'1.0-1' }}%</td>
                <td class="px-3 py-2">
                  <div class="flex gap-2">
                    <button class="btn btn-primary btn-sm" (click)="openEdit(g)">Edit</button>
                    <button class="btn btn-danger btn-sm" (click)="remove(g)">Delete</button>
                  </div>
                </td>
              </tr>
              <tr *ngIf="items().length === 0">
                <td [attr.colspan]="4" class="px-3 py-6 text-center text-slate-500">No grades yet.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Reuse DrawerGradeComponent for add/edit -->
      <bf-drawer-grade
        [open]="childOpen()"
        [editMode]="editMode()"
        [form]="form"
        [fixedEnrollmentId]="fixedEnrollmentId"
        [fixedStudentLabel]="studentLabel"
        [fixedClassroomLabel]="classroomLabel"
        (cancel)="childOpen.set(false)"
        (save)="submit()">
      </bf-drawer-grade>
    </bf-drawer>
  `
})
export class DrawerStudentGradesComponent {
  private api = inject(GradesService);
  private fb  = inject(NonNullableFormBuilder);
  private toast = inject(ToastService);

  @Input() open = false;
  @Input({ required: true }) enrollmentId!: number;
  @Input() studentLabel = '';
  @Input() classroomLabel = '';
  @Output() close = new EventEmitter<void>();

  items = signal<GradeItemDto[]>([]);
  childOpen = signal(false);
  editMode = signal(false);
  editingId: number | null = null;

  // pin create to this enrollment
  get fixedEnrollmentId() { return this.enrollmentId ?? null; }

  form = this.fb.group({
    // student/enrollment are managed by fixedEnrollmentId, keep control for API shape compatibility
    enrollmentId: this.fb.control<number | null>(null),
    component:    this.fb.control<string>('QUIZ', { validators: [Validators.required] }),
    score:        this.fb.control<number>(0, { validators: [Validators.required, Validators.min(0)] }),
    maxScore:     this.fb.control<number>(10, { validators: [Validators.required, Validators.min(1)] }),
  });

  ngOnChanges() {
    if (this.open && this.enrollmentId) {
      this.refresh();
    }
  }

  track = (_: number, g: { id: number }) => g.id;

  refresh() {
    this.api.listByEnrollment(this.enrollmentId).subscribe({
      next: list => this.items.set(list),
      error: err => { console.error(err); this.toast.add('Failed to load grades', 'error'); }
    });
  }

  openCreate() {
    this.editMode.set(false);
    this.editingId = null;
    this.form.reset({
      enrollmentId: this.enrollmentId,
      component: 'QUIZ',
      score: 0,
      maxScore: 10,
    });
    this.childOpen.set(true);
  }

  openEdit(row: GradeItemDto) {
    this.editMode.set(true);
    this.editingId = row.id;
    this.form.reset({
      enrollmentId: this.enrollmentId,
      component: row.component,
      score: row.score,
      maxScore: row.maxScore,
    });
    this.childOpen.set(true);
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
          if (i >= 0) next[i] = g;
          this.items.set(next);
          this.toast.add('Grade updated', 'success');
          this.childOpen.set(false);
        },
        error: err => { console.error(err); this.toast.add('Could not update grade', 'error'); }
      });
    } else {
      const dto: AddGradeDto = {
        component: this.form.value.component!,
        score: this.form.value.score!,
        maxScore: this.form.value.maxScore!,
      };
      this.api.create(this.enrollmentId, dto).subscribe({
        next: g => {
          this.items.set([g, ...this.items()]);
          this.toast.add('Grade created', 'success');
          this.childOpen.set(false);
        },
        error: err => { console.error(err); this.toast.add('Could not create grade', 'error'); }
      });
    }
  }

  remove(row: GradeItemDto) {
    if (!confirm('Delete this grade?')) return;
    this.api.delete(row.id).subscribe({
      next: () => {
        this.items.set(this.items().filter(x => x.id !== row.id));
        this.toast.add('Grade deleted', 'success');
      },
      error: err => { console.error(err); this.toast.add('Could not delete grade', 'error'); }
    });
  }
}
