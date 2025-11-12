// src/app/core/http/api-client.service.ts
import { inject, Injectable } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { environment } from '@/environments/environment';

@Injectable({ providedIn: 'root' })
export class ApiClient {
  private readonly http = inject(HttpClient);
  private readonly base = environment.apiBase; // http://localhost:8000

  get<T>(path: string, params?: Record<string, string | number | boolean | undefined>) {
    const httpParams = new HttpParams({ fromObject: toParams(params) });
    return this.http.get<T>(`${this.base}${path}`, { params: httpParams });
  }
  post<T, B = unknown>(path: string, body: B)    { return this.http.post<T>(`${this.base}${path}`, body); }
  patch<T, B = unknown>(path: string, body: B)   { return this.http.patch<T>(`${this.base}${path}`, body); }
  put<T, B = unknown>(path: string, body: B)     { return this.http.put<T>(`${this.base}${path}`, body); }
  delete<T>(path: string)                        { return this.http.delete<T>(`${this.base}${path}`); }
}

function toParams(src?: Record<string, unknown>): Record<string, string> {
  const o: Record<string, string> = {};
  if (!src) return o;
  for (const [k, v] of Object.entries(src)) if (v != null) o[k] = String(v);
  return o;
}
