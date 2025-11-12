import { Component, Input } from '@angular/core';
import { CommonModule } from '@angular/common';

/**
 * Lightweight page header for the admin area.
 * Renders a title on the left and projects action content on the right.
 *
 * Usage:
 * <app-admin-header title="Users">
 *   <button class="btn btn-success">+ Add user</button>
 * </app-admin-header>
 */
@Component({
  standalone: true,
  selector: 'app-admin-header',
  imports: [CommonModule],
  template: `
    <header class="px-6 py-4 border-b border-gray-200 bg-white flex items-center justify-between"
            role="banner" [attr.aria-labelledby]="headerId">
      <h2 class="text-2xl font-bold text-[#0c145a]" [id]="headerId">{{ title }}</h2>
      <div class="flex items-center gap-2">
        <ng-content />
      </div>
    </header>
  `,
})
export class AdminHeaderComponent {
  /** Main page title */
  @Input({ required: true }) title!: string;

  /** Stabilize the heading id for a11y */
  headerId = `admin-header-${Math.random().toString(36).slice(2, 8)}`;
}
