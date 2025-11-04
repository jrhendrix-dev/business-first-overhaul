// src/app/features/admin/admin-shell.component.ts
import { Component, signal } from '@angular/core';
import { CommonModule, NgIf } from '@angular/common';
import { RouterLink, RouterLinkActive, RouterOutlet } from '@angular/router';

@Component({
  standalone: true,
  selector: 'app-admin-shell',
  imports: [CommonModule, NgIf, RouterLink, RouterLinkActive, RouterOutlet],
  template: `
    <!-- Full-viewport app shell; prevent body scroll -->
    <div class="h-screen overflow-hidden grid grid-rows-[3.5rem_1fr]">

      <!-- Header -->
      <header class="row-start-1 flex items-center justify-between px-4 border-b bg-white z-10">
        <div class="flex items-center gap-3">
          <button
            class="md:hidden inline-flex items-center justify-center rounded border px-2 py-1"
            (click)="toggleSidebar()"
            aria-label="Toggle navigation">
            ☰
          </button>
          <div class="font-semibold">Business First • Admin</div>
        </div>

        <nav class="hidden md:flex items-center gap-4 text-sm">
          <a routerLink="/admin/users"   routerLinkActive="underline">Users</a>
          <a routerLink="/admin/classes" routerLinkActive="underline">Classes</a>
          <a routerLink="/admin/grades"  routerLinkActive="underline">Grades</a>
        </nav>
      </header>

      <!-- Body: sidebar + main -->
      <div class="row-start-2 grid grid-cols-1 md:grid-cols-[16rem_1fr] min-h-0">

        <!-- Sidebar (overlay on mobile) -->
        <aside
          class="bg-slate-50 border-r p-3 min-h-0 overflow-y-auto
               md:static md:translate-x-0
               fixed inset-y-14 left-0 w-64 z-20 transform transition-transform duration-200
               md:w-auto"
          [class.-translate-x-full]="!sidebarOpen()">
          <div class="text-xs uppercase text-slate-500 mb-2">Navigation</div>
          <ul class="space-y-1 text-sm">
            <li><a routerLink="/admin/users"   routerLinkActive="bg-slate-200 font-semibold"
                   class="block rounded px-2 py-1 hover:bg-slate-100">Users</a></li>
            <li><a routerLink="/admin/classes" routerLinkActive="bg-slate-200 font-semibold"
                   class="block rounded px-2 py-1 hover:bg-slate-100">Classes</a></li>
            <li><a routerLink="/admin/grades"  routerLinkActive="bg-slate-200 font-semibold"
                   class="block rounded px-2 py-1 hover:bg-slate-100">Grades</a></li>
          </ul>
        </aside>

        <!-- Backdrop on mobile -->
        <div class="md:hidden fixed inset-0 bg-black/30 z-10" *ngIf="sidebarOpen()" (click)="sidebarOpen.set(false)"></div>

        <!-- Main content scrolls -->
        <main class="min-h-0 overflow-y-auto bg-white">
          <router-outlet />
        </main>
      </div>
    </div>
  `
})
export class AdminShellComponent {
  sidebarOpen = signal(false);
  toggleSidebar() { this.sidebarOpen.update(v => !v); }
}
