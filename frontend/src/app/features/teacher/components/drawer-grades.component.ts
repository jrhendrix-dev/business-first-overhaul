import { Component, EventEmitter, Input, Output, inject, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ReactiveFormsModule, NonNullableFormBuilder, Validators } from '@angular/forms';
import { DrawerComponent } from '@/app/core/ui/drawer/drawer.component';
import { TeacherService, GradeItem, RosterStudent } from '../data/teacher.service';
import { ToastService } from '@/app/core/ui/toast/toast.service';
import { HttpErrorResponse } from '@angular/common/http';

type GradeComponent = 'QUIZ' | 'HOMEWORK' | 'MIDTERM' | 'FINAL' | 'PROJECT';

@Component({
  standalone: true,
  selector: 'app-teacher-drawer-grades',
  imports: [CommonModule, ReactiveFormsModule, DrawerComponent],
  template: `
    <bf-drawer
      [open]="open"
      [heading]="'Calificaciones — ' + studentName()"
      [offsetVar]="'--admin-navbar-h'"
      (close)="handleClose()">

      <!-- Controls row -->
      <form class="space-y-3" (ngSubmit)="save()" [formGroup]="form">
        <!-- 4 columns: Component | Score | Max | Add -->
        <div class="grid grid-cols-[auto_auto_auto_auto] items-end gap-x-5 gap-y-2">
          <!-- Component -->
          <div class="min-w-0">
            <label class="block text-xs text-slate-600 mb-1">Componente</label>
            <select class="border rounded px-2 h-9 w-40" formControlName="component">
              <option value="QUIZ">Quiz</option>
              <option value="HOMEWORK">Homework</option>
              <option value="EXAM">Exam</option>
              <option value="PROJECT">Project</option>
            </select>
          </div>

          <!-- Score -->
          <div>
            <label class="block text-xs text-slate-600 mb-1">Puntuación</label>
            <input type="number" class="border rounded px-2 h-9 w-24 no-spin" formControlName="score" />
          </div>

          <!-- Max -->
          <div>
            <label class="block text-xs text-slate-600 mb-1">Máx.</label>
            <input type="number" class="border rounded px-2 h-9 w-24 no-spin" formControlName="maxScore" />
          </div>

          <!-- Add button aligned with inputs -->
          <div class="pb-[1.125rem]">
            <button type="submit" class="btn btn-primary shrink-0" [disabled]="saving() || form.invalid">
              {{ editingId() ? 'Guardar' : 'Añadir' }}
            </button>
          </div>
        </div>
      </form>


      <!-- Add a clear visual gap before the list -->
      <div class="mt-4 space-y-2">
        <div *ngFor="let g of grades" class="rounded border p-2 flex items-center justify-between">
          <div>
            <div class="font-medium">{{ g.title || g.component }}</div>
            <div class="text-xs text-slate-500">
              {{ g.score }}<span *ngIf="g.maxScore"> / {{ g.maxScore }}</span>
              · {{ g.gradedAt | date:'short' }}
            </div>
          </div>
          <div class="flex gap-2">
            <button class="px-2 py-1 rounded border" (click)="edit(g)">Editar</button>
            <button class="px-2 py-1 rounded bg-red-600 text-white" (click)="remove(g)">Borrar</button>
          </div>
        </div>

        <p *ngIf="!grades?.length" class="text-sm text-slate-500">Sin calificaciones todavía.</p>
      </div>
    </bf-drawer>
  `,
})
export class TeacherDrawerGradesComponent {
  private fb = inject(NonNullableFormBuilder);
  private api = inject(TeacherService);
  private toast = inject(ToastService);

  @Input() open = false;
  @Input() classId!: number;
  @Input() student: RosterStudent | null = null;
  @Input() grades: GradeItem[] = [];

  @Output() close = new EventEmitter<void>();
  @Output() changed = new EventEmitter<void>();

  saving = signal(false);
  editingId = signal<number | null>(null);

  form = this.fb.group({
    component: this.fb.control<GradeComponent>('QUIZ', { validators: [Validators.required] }),
    score: this.fb.control(0, { validators: [Validators.required] }),
    maxScore: this.fb.control(10, { validators: [Validators.required] }),
  });

  /** Build "First Last" safely */
  studentName(): string {
    const s = this.student?.student;
    if (!s) return '';
    return `${s.firstName ?? ''} ${s.lastName ?? ''}`.trim();
  }

  save() {
    if (!this.student || this.form.invalid || this.saving()) return;
    this.saving.set(true);

    const payload = {
      component: this.form.value.component!,
      score: Number(this.form.value.score!),
      maxScore: Number(this.form.value.maxScore!),
    };

    const editing = this.editingId();
    if (editing) {
      this.api.updateGrade(editing, payload).subscribe({
        next: () => {
          this.toast.success('Nota actualizada');
          this.saving.set(false);
          this.editingId.set(null);
          this.changed.emit();
        },
        error: (err: HttpErrorResponse) => {
          this.saving.set(false);
          this.toast.error('No se pudo actualizar la nota');
          console.error(err);
        }
      });
    } else {
      this.api.createGrade(this.classId, this.student.student.id, payload).subscribe({
        next: () => {
          this.toast.success('Nota añadida');
          this.saving.set(false);
          this.form.reset({ component: 'QUIZ', score: 0, maxScore: 10 });
          this.changed.emit();
        },
        error: (err: HttpErrorResponse) => {
          this.saving.set(false);
          this.toast.error('No se pudo crear la nota');
          console.error(err);
        }
      });
    }
  }

  edit(g: GradeItem) {
    this.editingId.set(g.id);
    this.form.patchValue({
      component: (g.component as GradeComponent) ?? 'QUIZ',
      score: g.score,
      maxScore: g.maxScore ?? 10
    });
  }

  remove(g: GradeItem) {
    if (!confirm('¿Borrar esta nota?')) return;
    this.api.deleteGrade(g.id).subscribe({
      next: () => { this.toast.success('Nota eliminada'); this.changed.emit(); },
      error: (err: HttpErrorResponse) => { this.toast.error('No se pudo borrar la nota'); console.error(err); }
    });
  }

  handleClose() {
    this.editingId.set(null);
    this.form.reset({ component: 'QUIZ', score: 0, maxScore: 10 });
    this.close.emit();
  }
}
