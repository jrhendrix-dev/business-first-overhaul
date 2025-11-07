// src/app/features/teacher/data/teacher.service.ts
import { Injectable, inject } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '@environments/environment';

const API = environment.apiBase;
const BASE = `${API}/api/teacher`;

export type ClassroomMini = { id: number; name: string; schedule?: string | null };

export type RosterStudent = {
  enrollmentId: number;
  status: 'ACTIVE' | 'DROPPED';
  enrolledAt?: string | null;
  droppedAt?: string | null;
  student: { id: number; firstName: string; lastName: string; email: string };
};

export type GradeItem = {
  id: number;
  enrollmentId: number;
  title?: string | null;
  component?: 'QUIZ' | 'HOMEWORK' | 'MIDTERM' | 'FINAL';
  score: number;
  maxScore?: number | null;
  gradedAt: string;
};

@Injectable({ providedIn: 'root' })
export class TeacherService {
  private http = inject(HttpClient);

  myClasses(): Observable<ClassroomMini[]> {
    return this.http.get<ClassroomMini[]>(`${BASE}/classrooms`);
  }

  roster(classId: number): Observable<RosterStudent[]> {
    return this.http.get<RosterStudent[]>(`${BASE}/classrooms/${classId}/students`);
  }

  gradesFor(classId: number, studentId: number) {
    return this.http.get<GradeItem[]>(
      `${BASE}/classrooms/${classId}/students/${studentId}/grades`
    );
  }

  createGrade(classId: number, studentId: number, payload: { component: string; score: number; maxScore: number }) {
    return this.http.post<GradeItem>(`${BASE}/classrooms/${classId}/students/${studentId}/grades`, payload);
  }

  updateGrade(id: number, patch: Partial<{ component: string; score: number; maxScore: number }>) {
    return this.http.patch<GradeItem>(`${BASE}/grades/${id}`, patch);
  }

  deleteGrade(id: number) {
    return this.http.delete<void>(`${BASE}/grades/${id}`);
  }
}
