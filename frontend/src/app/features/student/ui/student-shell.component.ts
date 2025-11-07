import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink, RouterOutlet } from '@angular/router';

@Component({
  standalone: true,
  selector: 'app-student-shell',
  imports: [CommonModule, RouterLink, RouterOutlet],
  template: `
    <div class="min-h-screen grid grid-rows-[3.5rem_1fr] bg-slate-50">
      <header class="row-start-1 flex items-center justify-between px-4 border-b bg-white">
        <div class="font-semibold text-brand-navy">Business First • Student</div>
        <nav class="flex items-center gap-4 text-sm">
          <a routerLink="/student/classes" class="hover:underline">Mis clases</a>
        </nav>
      </header>
      <main class="p-6">
        <router-outlet />
      </main>
    </div>
  `,
})
export class StudentShellComponent {}
