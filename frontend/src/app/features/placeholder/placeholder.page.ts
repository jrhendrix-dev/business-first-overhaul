import { Component, inject } from '@angular/core';
import { ActivatedRoute } from '@angular/router';

@Component({
  standalone: true,
  template: `
    <section class="mx-auto max-w-6xl px-4 py-12">
      <h1 class="mb-3 text-3xl font-bold text-brand-navy">{{ title }}</h1>
      <p class="text-lg text-brand-navy/80">Contenido en construcción. Esta sección se migrará desde el sitio legacy.</p>
    </section>
  `
})
export class PlaceholderPage {
  title = inject(ActivatedRoute).snapshot.data?.['title'] ?? 'Sección';
}
