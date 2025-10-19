import { inject, Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { ApiClient } from '@/app/core/http/api-client.service';
import { ClassroomDetailDto, ClassroomItemDto } from '@/app/shared/models/classroom/classroom-read.dto';
import { AssignTeacherDto, CreateClassroomDto, RenameClassroomDto } from '@/app/shared/models/classroom/classroom-write.dto';

@Injectable({ providedIn: 'root' })
export class ClassroomService {
  private readonly api = inject(ApiClient);

  // ---------- Public ----------
  /** GET /api/classrooms */
  list(): Observable<ClassroomItemDto[]> {
    return this.api.get<ClassroomItemDto[]>('/classrooms');
  }

  /** GET /api/classrooms/{id} */
  getOne(id: number): Observable<ClassroomDetailDto> {
    return this.api.get<ClassroomDetailDto>(`/classrooms/${id}`);
  }

  /** GET /api/classrooms/search?name=... */
  searchByName(name: string): Observable<ClassroomItemDto[]> {
    return this.api.get<ClassroomItemDto[]>('/classrooms/search', { name });
  }

  // ---------- Admin ----------
  /** GET /api/admin/classrooms */
  adminList(): Observable<ClassroomItemDto[]> {
    return this.api.get<ClassroomItemDto[]>('/admin/classrooms');
  }

  /** GET /api/admin/classrooms/{id} */
  adminGet(id: number): Observable<ClassroomDetailDto> {
    return this.api.get<ClassroomDetailDto>(`/admin/classrooms/${id}`);
  }

  /** POST /api/admin/classrooms */
  create(body: CreateClassroomDto): Observable<ClassroomDetailDto> {
    return this.api.post<ClassroomDetailDto, CreateClassroomDto>('/admin/classrooms', body);
  }

  /** PUT /api/admin/classrooms/{id} */
  rename(id: number, body: RenameClassroomDto): Observable<ClassroomDetailDto> {
    return this.api.put<ClassroomDetailDto, RenameClassroomDto>(`/admin/classrooms/${id}`, body);
  }

  /** PUT /api/admin/classrooms/{id}/teacher */
  assignTeacher(id: number, body: AssignTeacherDto): Observable<void> {
    return this.api.put<void, AssignTeacherDto>(`/admin/classrooms/${id}/teacher`, body);
  }

  /** DELETE /api/admin/classrooms/{id}/teacher */
  unassignTeacher(id: number): Observable<void> {
    return this.api.delete<void>(`/admin/classrooms/${id}/teacher`);
  }

  /** POST /api/admin/classrooms/{id}/reactivate */
  reactivate(id: number): Observable<void> {
    return this.api.post<void, {}>(`/admin/classrooms/${id}/reactivate`, {});
  }

  /** DELETE /api/admin/classrooms/{id} */
  remove(id: number): Observable<void> {
    return this.api.delete<void>(`/admin/classrooms/${id}`);
  }

  /** GET /api/admin/classrooms/unassigned */
  unassigned(): Observable<ClassroomItemDto[]> {
    return this.api.get<ClassroomItemDto[]>('/admin/classrooms/unassigned');
  }

  /** GET /api/admin/classrooms/taught-by/{teacherId} */
  taughtBy(teacherId: number): Observable<ClassroomItemDto[]> {
    return this.api.get<ClassroomItemDto[]>(`/admin/classrooms/taught-by/${teacherId}`);
  }

  /** GET /api/admin/classrooms/taught-by-count/{teacherId} */
  taughtByCount(teacherId: number): Observable<{ count: number }> {
    return this.api.get<{ count: number }>(`/admin/classrooms/taught-by-count/${teacherId}`);
  }
}
