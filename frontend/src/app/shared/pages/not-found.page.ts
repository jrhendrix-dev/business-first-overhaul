import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink } from '@angular/router';

@Component({
  standalone: true,
  selector: 'app-not-found',
  imports: [CommonModule, RouterLink],
  template: `
    <div class="min-h-[50vh] grid place-items-center text-center">
      <div>
        <div class="text-6xl font-black text-slate-300">404</div>
        <p class="mt-2 text-slate-500">Page not found.</p>
        <a routerLink="/" class="inline-block mt-4 px-4 py-2 rounded bg-slate-800 text-white">Go home</a>
      </div>
    </div>
  `,
})
export class NotFoundPage {}
