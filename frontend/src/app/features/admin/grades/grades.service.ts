import { Injectable, inject, isDevMode } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { environment } from '@/environments/environment';

const API = environment.apiBase; // e.g. http://localhost:8000
const BASE = `${API}/api/admin/grades`;

export interface GradeItemDto {
  id: number;
  component: string;         // raw enum (backend includes it for admin)
  componentLabel: string;    // friendly label
  score: number;
  maxScore: number;
  percent: number;
  gradedAt: string;          // ISO
  enrollmentId: number;
  classrooms: { id: number; name: string };
  student?: { id: number; firstName: string; lastName: string; email: string };
}

export interface AddGradeDto {
  component: string;
  score: number;
  maxScore: number;
}
export type UpdateGradeDto = Partial<AddGradeDto>;

@Injectable({ providedIn: 'root' })
export class GradesService {
  private http = inject(HttpClient);

  listAll() {
    const url = `${BASE}/all`;
    if (isDevMode()) console.log('[GradesService] GET', url);
    return this.http.get<GradeItemDto[]>(url);
  }

  listByEnrollment(enrollmentId: number) {
    const url = `${BASE}/enrollments/${enrollmentId}/grades`;
    return this.http.get<GradeItemDto[]>(url);
  }

  create(enrollmentId: number, dto: AddGradeDto) {
    const url = `${BASE}/enrollments/${enrollmentId}/grades`;
    return this.http.post<GradeItemDto>(url, dto);
  }

  update(id: number, dto: UpdateGradeDto) {
    const url = `${BASE}/${id}`;
    return this.http.patch<GradeItemDto>(url, dto);
  }

  delete(id: number) {
    const url = `${BASE}/${id}`;
    return this.http.delete<void>(url);
  }
}
