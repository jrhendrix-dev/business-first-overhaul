import { Component, ChangeDetectionStrategy, inject, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink } from '@angular/router';
import { ClassCatalogApi, PublicClassItem } from './class-catalog.api';

@Component({
  standalone: true,
  selector: 'app-class-catalog',
  imports: [CommonModule, RouterLink],
  changeDetection: ChangeDetectionStrategy.OnPush,
  template: `
  <section class="max-w-5xl mx-auto px-4 my-10">
    <h1 class="text-3xl font-semibold text-[#0c145a] mb-3">Catálogo de clases</h1>
    <p class="text-slate-600 mb-6">Elige tu clase y compra tu plaza en un minuto.</p>

    <div class="grid md:grid-cols-2 gap-5">
      <article *ngFor="let c of classes()"
               class="rounded-2xl bg-white shadow ring-1 ring-black/5 p-5 flex flex-col justify-between">
        <div>
          <h2 class="text-xl font-semibold text-[#0c145a]">{{ c.name }}</h2>
          <p class="text-sm text-slate-600 mt-1" *ngIf="c.teacher">Profesor: {{ c.teacher }}</p>

          <p class="mt-3 text-lg" *ngIf="c.priceCents !== null; else noPrice">
            <span class="font-semibold">
              {{ (c.priceCents / 100) | currency:'EUR':'symbol':'1.0-0' }}
            </span>
          </p>
          <ng-template #noPrice>
            <p class="mt-3 text-sm text-slate-500">Precio: consultar</p>
          </ng-template>
        </div>

        <div class="mt-5 flex gap-3">
          <a class="px-4 py-2 rounded-2xl bg-[#0c145a] text-white"
             [routerLink]="['/catalog', c.id]">Ver detalles</a>
          <a class="px-4 py-2 rounded-2xl border border-[#0c145a] text-[#0c145a]"
             [routerLink]="['/catalog', c.id]" [queryParams]="{ action: 'buy' }">Comprar</a>
        </div>
      </article>
    </div>
  </section>
  `
})
export default class ClassCatalogPage {
  private api = inject(ClassCatalogApi);
  classes = signal<PublicClassItem[]>([]);

  ngOnInit() {
    this.api.list().subscribe({ next: cs => this.classes.set(cs) });
  }
}
