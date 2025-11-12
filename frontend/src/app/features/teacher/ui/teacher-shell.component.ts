import { Component } from '@angular/core';
import { RouterLink, RouterOutlet } from '@angular/router';
import { CommonModule } from '@angular/common';

@Component({
  standalone: true,
  selector: 'app-teacher-shell',
  imports: [CommonModule, RouterLink, RouterOutlet],
  template: `
    <!-- No inline --admin-navbar-h here -->
    <div class="min-h-screen grid grid-rows-[3.5rem_1fr] bg-slate-50">
      <header class="row-start-1 flex items-center justify-between px-4 border-b bg-white">
        <div class="font-semibold text-brand-navy">Business First • Profesor</div>
        <nav class="flex items-center gap-4 text-sm">
          <a routerLink="/teacher/classes" class="hover:underline">Mis clases</a>
        </nav>
      </header>
      <main class="p-6">
        <router-outlet />
      </main>
    </div>
  `,
})
export class TeacherShellComponent {}
