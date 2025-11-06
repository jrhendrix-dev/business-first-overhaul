import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';

@Component({
  standalone: true,
  selector: 'app-student-shell',
  imports: [CommonModule],
  template: `
    <section class="mx-auto max-w-5xl px-4 py-10">
      <h1 class="text-3xl font-extrabold tracking-tight">Panel de Estudiante</h1>
      <p class="mt-2 text-slate-600">(Placeholder) Aquí verás tus clases, tareas y notas.</p>
    </section>
  `
})
export class StudentShellComponent {}
