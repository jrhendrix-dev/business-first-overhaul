import { Injectable, inject, isDevMode } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { Observable, catchError, map, throwError, forkJoin } from 'rxjs';
import { environment } from '@/environments/environment';
import { ClassroomItemDto, ClassroomDetailDto } from '@/app/shared/models/classrooms/classroom-read.dto';
import { UserItemDto } from '@/app/shared/models/user/user-read.dto';

const API  = environment.apiBase;
const BASE = `${API}/api/admin/classrooms`;
const ENR  = `${API}/api/admin/enrollments`;
const USERS= `${API}/api/admin/users`;
const USERS_TEACHERS = `${API}/api/admin/users/teachers`;
const USERS_TEACHERS_WITHOUT   = `${USERS}/teachers/without-classroom`;
const USERS_STUDENTS           = `${USERS}/students`;
const USERS_STUDENTS_WITHOUT   = (classId: number) => `${USERS}/students/without-classroom/${classId}`;

const mapEnrollments = (res: any): EnrollmentMini[] =>
  extractArray<any>(res).map(toEnrollmentMini);

function isActive(e: EnrollmentMini) {
  return (e.status || 'ACTIVE').toUpperCase() === 'ACTIVE';
}

/** UI picklist option for assigning teachers (avoid name clash with DTO TeacherMini). */
export type TeacherOption = { id: number; name: string; email?: string | null };

/** UI picklist for assigning students */
export type StudentOption = { id: number; name: string; email?: string | null };

/** Normalized enrollment item shape for the roster drawer. */
export type EnrollmentMini = {
  student: { id: number; firstName: string; lastName: string; email?: string | null };
  status: string;
  enrolledAt?: string | null;
};

/** Service-level error format with a user-facing message. */
type ServiceError = { code: string; userMessage: string; cause?: any };

/* ------------- mappers ------------- */
const toTeacherOption = (t: UserItemDto): TeacherOption => ({
  id: t.id,
  name: t.fullName || `${t.firstName ?? ''} ${t.lastName ?? ''}`.trim(),
  email: t.email ?? null,
});
const toStudentOption = (s: UserItemDto): StudentOption => ({
  id: s.id,
  name: s.fullName || `${s.firstName ?? ''} ${s.lastName ?? ''}`.trim(),
  email: s.email ?? null,
});

/* ----------------------- helpers ----------------------- */

/** Extract arrays from common paginated shapes. */
function extractArray<T>(payload: unknown): T[] {
  if (Array.isArray(payload)) return payload as T[];
  if (payload && typeof payload === 'object') {
    const obj = payload as any;
    if (Array.isArray(obj.items))   return obj.items as T[];
    if (Array.isArray(obj.data))    return obj.data as T[];
    if (Array.isArray(obj.content)) return obj.content as T[];
  }
  return [];
}



function toEnrollmentMini(raw: any): EnrollmentMini {
  const s = raw?.student ?? raw?.user ?? raw ?? {};
  return {
    student: {
      id: Number(s.id),
      firstName: String(s.firstName ?? s.firstname ?? ''),
      lastName:  String(s.lastName  ?? s.lastname  ?? ''),
      email: (s.email ?? null) as string | null,
    },
    status: String(raw?.status ?? raw?.enrollmentStatus ?? 'ACTIVE'),
    enrolledAt: (raw?.enrolledAt ?? raw?.enrolled_at ?? null) as string | null,
  };
}


function hasTeacherRole(u: any, assumeFiltered: boolean): boolean {
  if (Array.isArray(u?.roles)) return u.roles.includes('ROLE_TEACHER');
  if (typeof u?.role === 'string') return u.role === 'ROLE_TEACHER';
  // If server was asked to filter but didn't include a per-item role,
  // assume the list is already teachers.
  return assumeFiltered;
}

/* ----------------------- service ----------------------- */

@Injectable({ providedIn: 'root' })
export class ClassroomsService {
  private http = inject(HttpClient);

  list(params?: { name?: string; unassigned?: boolean; teacherId?: number }): Observable<ClassroomItemDto[]> {
    if (params?.name) {
      const httpParams = new HttpParams().set('name', params.name);
      return this.http.get<ClassroomItemDto[]>(`${BASE}/search`, { params: httpParams });
    }
    if (params?.unassigned) {
      return this.http.get<ClassroomItemDto[]>(`${BASE}/unassigned`);
    }
    if (params?.teacherId != null) {
      return this.http.get<ClassroomItemDto[]>(`${BASE}/taught-by/${params.teacherId}`);
    }
    return this.http.get<ClassroomItemDto[]>(BASE);
  }

  /** Load a single classroom detail. Emits a precise userMessage on failure. */
  getOne(id: number): Observable<ClassroomDetailDto> {
    return this.http.get<ClassroomDetailDto>(`${BASE}/${id}`).pipe(
      catchError((cause): Observable<never> =>
        this.fail({ code: 'CLASS_DETAIL_LOAD_FAILED', userMessage: 'Failed to load class detail', cause })
      )
    );
  }

  create(name: string) {
    return this.http.post<ClassroomDetailDto>(BASE, { name }).pipe(
      catchError((cause): Observable<never> =>
        this.fail({ code: 'CLASS_CREATE_FAILED', userMessage: 'Create failed', cause })
      )
    );
  }

  rename(id: number, name: string) {
    return this.http.put<ClassroomDetailDto>(`${BASE}/${id}`, { name }).pipe(
      catchError((cause): Observable<never> =>
        this.fail({ code: 'CLASS_RENAME_FAILED', userMessage: 'Rename failed', cause })
      )
    );
  }

  /** Backend returns 204 -> avoid JSON parse error by using text-as-json. */
  assignTeacher(id: number, teacherId: number) {
    return this.http.put<void>(`${BASE}/${id}/teacher`, { teacherId }, { responseType: 'text' as 'json' }).pipe(
      catchError((cause): Observable<never> =>
        this.fail({ code: 'ASSIGN_TEACHER_FAILED', userMessage: 'Failed to assign teacher', cause })
      )
    );
  }

  unassignTeacher(id: number) {
    return this.http.delete<void>(`${BASE}/${id}/teacher`, { responseType: 'text' as 'json' }).pipe(
      catchError((cause): Observable<never> =>
        this.fail({ code: 'UNASSIGN_TEACHER_FAILED', userMessage: 'Failed to unassign teacher', cause })
      )
    );
  }

  reactivate(id: number) {
    return this.http.post<ClassroomDetailDto>(`${BASE}/${id}/reactivate`, {}).pipe(
      catchError((cause): Observable<never> =>
        this.fail({ code: 'CLASS_REACTIVATE_FAILED', userMessage: 'Reactivate failed', cause })
      )
    );
  }

  delete(id: number) {
    return this.http.delete(`${BASE}/${id}`).pipe(
      catchError((cause): Observable<never> =>
        this.fail({ code: 'CLASS_DELETE_FAILED', userMessage: 'Delete failed', cause })
      )
    );
  }


  /* ----------------------- enrollments ----------------------- */

  /** List ACTIVE enrollments with robust fallback:
   *  - try /active-enrollments
   *  - on ANY error (404/500/etc) try /enrollments and filter ACTIVE client-side
   */
  listActiveEnrollments(classId: number): Observable<EnrollmentMini[]> {
    const primary  = `${ENR}/class/${classId}/active-enrollments`;
    const fallback = `${ENR}/class/${classId}/enrollments`;

    return this.http.get<any>(primary).pipe(
      map(mapEnrollments),
      catchError(() =>
        this.http.get<any>(fallback).pipe(
          map(mapEnrollments),
          map(list => list.filter(isActive)),
          catchError((err): Observable<never> =>
            this.fail({
              code: 'ENROLLMENTS_LOAD_FAILED',
              userMessage: 'Failed to load students',
              cause: err,
            })
          )
        )
      )
    );
  }

  enrollStudent(classId: number, studentId: number) {
    return this.http.put(`${ENR}/class/${classId}/student/${studentId}`, {}).pipe(
      catchError((cause): Observable<never> =>
        this.fail({ code: 'ENROLL_STUDENT_FAILED', userMessage: 'Enroll failed', cause })
      )
    );
  }

  dropStudent(classId: number, studentId: number) {
    return this.http.delete(`${ENR}/class/${classId}/student/${studentId}`).pipe(
      catchError((cause): Observable<never> =>
        this.fail({ code: 'DROP_STUDENT_FAILED', userMessage: 'Drop failed', cause })
      )
    );
  }

  hardDropStudent(classId: number, studentId: number) {
    return this.http.delete(`${ENR}/class/${classId}/student/${studentId}/hard`).pipe(
      catchError((cause): Observable<never> =>
        this.fail({ code: 'HARD_DROP_STUDENT_FAILED', userMessage: 'Drop failed', cause })
      )
    );
  }

  /* ----------------------- teachers (picklist) ----------------------- */


  /** Teachers picklist.
   * when onlyVacant=true uses `/users/teachers/without-classroom` (server-side filter),
   * otherwise `/users/teachers`.
   */
  listTeachers(opts?: { onlyVacant?: boolean }): Observable<TeacherOption[]> {
    const url = opts?.onlyVacant ? USERS_TEACHERS_WITHOUT : USERS_TEACHERS;
    return this.http.get<UserItemDto[]>(url).pipe(
      map(list => list.map(toTeacherOption)),
      catchError((cause): Observable<never> =>
        this.fail({ code: 'TEACHERS_LOAD_FAILED', userMessage: 'Failed to load teachers', cause })
      )
    );
  }

  /* ----------------------- students (picklist) ----------------------- */

  /** Students picklist for roster enrollment.
   * Tries `/users/students/without-classroom/{classId}` when onlyVacant=true.
   * Falls back to `/users/students` and client-side filtering if backend route is absent.
   */
  listStudentsForClass(
    classId: number,
    opts?: { onlyVacant?: boolean }
  ): Observable<StudentOption[]> {
    if (opts?.onlyVacant) {
      // try server-side “not enrolled in the class”
      return this.http.get<UserItemDto[]>(USERS_STUDENTS_WITHOUT(classId)).pipe(
        map(list => list.map(toStudentOption)),
        // fallback: load all students and subtract current active roster
        catchError(() =>
          this.http.get<UserItemDto[]>(USERS_STUDENTS).pipe(
            map(list => list.map(toStudentOption))
          )
        ),
        catchError((cause): Observable<never> =>
          this.fail({ code: 'STUDENTS_LOAD_FAILED', userMessage: 'Failed to load students', cause })
        )
      );
    }

    // plain all students
    return this.http.get<UserItemDto[]>(USERS_STUDENTS).pipe(
      map(list => list.map(toStudentOption)),
      catchError((cause): Observable<never> =>
        this.fail({ code: 'STUDENTS_LOAD_FAILED', userMessage: 'Failed to load students', cause })
      )
    );
  }

  /* ----------------------- shared fail helper ----------------------- */

  private fail<T>(err: ServiceError): Observable<T> {
    if (isDevMode()) console.error('[ClassroomsService]', err.code, err.cause ?? err);
    return throwError(() => err);
  }
}
