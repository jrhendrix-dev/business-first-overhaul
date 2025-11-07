import { Injectable, inject } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '@environments/environment';

const API = environment.apiBase;
const BASE = `${API}/api/me`;

export type StudentClassroomMini = {
  id: number;
  name: string;
  teacher?: { id: number; name: string } | null;
  status?: 'ACTIVE' | 'INACTIVE';
};

export type StudentGradeItem = {
  id: number;
  componentLabel?: string | null;   // e.g. "Quiz"
  score: number;
  maxScore?: number | null;
  percent?: number | null;
  gradedAt: string;
  enrollmentId: number;
  classrooms?: { id: number; name: string } | null;
};

@Injectable({ providedIn: 'root' })
export class StudentService {
  private http = inject(HttpClient);

  /** GET /api/me/classrooms */
  myClasses(): Observable<StudentClassroomMini[]> {
    return this.http.get<StudentClassroomMini[]>(`${BASE}/classrooms`);
  }

  /** GET /api/me/grades?classId=… (omit param to get all) */
  gradesForClass(classId: number): Observable<StudentGradeItem[]> {
    const params = new HttpParams().set('classId', String(classId));
    return this.http.get<StudentGradeItem[]>(`${BASE}/grades`, { params });
  }
}
