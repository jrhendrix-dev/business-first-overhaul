// src/app/features/catalog/class-catalog.api.ts
import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { environment } from '@/environments/environment';
import { map } from 'rxjs/operators';

const API = environment.apiBase;
const BASE = `${API}/api/classrooms`; // if API already ends with /api, use `${API}/classrooms`

export type PublicClassItem = {
  id: number;
  name: string;
  priceCents: number | null;
  currency: string;
  teacher: string | null;
  status?: 'ACTIVE' | 'INACTIVE';
  meta?: Record<string, unknown> | null;
};

function toItem(raw: any): PublicClassItem {
  // backend now supplies camelCase directly
  const teacher =
    typeof raw?.teacher === 'string'
      ? raw.teacher
      : (raw?.teacher?.name ?? null);

  return {
    id: Number(raw.id),
    name: String(raw.name),
    priceCents: raw?.priceCents != null ? Number(raw.priceCents) : null,
    currency: (raw?.currency ?? 'EUR') as string,
    teacher,
    status: raw?.status ?? 'ACTIVE',
    meta: raw?.meta ?? null,
  };
}

@Injectable({ providedIn: 'root' })
export class ClassCatalogApi {
  private http = inject(HttpClient);

  list() {
    return this.http.get<any[]>(BASE).pipe(map(rows => rows.map(toItem)));
  }
  get(id: number) {
    return this.http.get<any>(`${BASE}/${id}`).pipe(map(toItem));
  }
}
