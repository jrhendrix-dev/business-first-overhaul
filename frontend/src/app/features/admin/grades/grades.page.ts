import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';

@Component({
  standalone: true,
  selector: 'app-admin-grades',
  imports: [CommonModule],
  template: `
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-xl font-semibold">Grades</h1>
      <button class="px-3 py-1.5 rounded bg-slate-800 text-white">+ Add grade</button>
    </div>

    <div class="rounded border p-4 text-slate-500">
      Grades module stub. Plug in classroom/student grade management here.
    </div>
  `,
})
export class GradesPage {}
