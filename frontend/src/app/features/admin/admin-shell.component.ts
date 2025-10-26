import { Component } from '@angular/core';
import { RouterLink, RouterOutlet } from '@angular/router';
import { CommonModule } from '@angular/common';

@Component({
  standalone: true,
  selector: 'app-admin-shell',
  imports: [CommonModule, RouterLink, RouterOutlet],
  template: `
  <div class="min-h-screen grid grid-cols-[16rem_1fr] grid-rows-[3.5rem_1fr]">
    <header class="col-span-2 row-start-1 flex items-center justify-between px-4 border-b bg-white">
      <div class="font-semibold">Business First • Admin</div>
      <nav class="flex items-center gap-4 text-sm">
        <a routerLink="/admin/users" class="hover:underline">Users</a>
        <a routerLink="/admin/classes" class="hover:underline">Classes</a>
        <a routerLink="/admin/grades" class="hover:underline">Grades</a>
      </nav>
    </header>

    <aside class="row-start-2 bg-slate-50 border-r p-3">
      <div class="text-xs uppercase text-slate-500 mb-2">Navigation</div>
      <ul class="space-y-1">
        <li><a routerLink="/admin/users" class="block rounded px-2 py-1 hover:bg-slate-100">Users</a></li>
        <li><a routerLink="/admin/classes" class="block rounded px-2 py-1 hover:bg-slate-100">Classes</a></li>
        <li><a routerLink="/admin/grades" class="block rounded px-2 py-1 hover:bg-slate-100">Grades</a></li>
      </ul>
    </aside>

    <main class="row-start-2 p-6">
      <router-outlet />
    </main>
  </div>
  `
})
export class AdminShellComponent {}
