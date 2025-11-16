// src/app/features/admin/classrooms/services/classrooms.service.ts
import { Injectable, inject, isDevMode } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { Observable, catchError, map, throwError, of } from 'rxjs';
import { environment } from '@/environments/environment';
import { ClassroomItemDto, ClassroomDetailDto } from '@/app/shared/models/classrooms/classroom-read.dto';
import { UserItemDto } from '@/app/shared/models/user/user-read.dto';
import { EnrollmentMini, TeacherOption, StudentOption } from '../models/enrollment-mini.model';

const API  = environment.apiBase;
const BASE = `${API}/api/admin/classrooms`;
const ENR  = `${API}/api/admin/enrollments`;
const USERS = `${API}/api/admin/users`;
const USERS_TEACHERS = `${USERS}/teachers`;
const USERS_TEACHERS_WITHOUT = `${USERS}/teachers/without-classroom`;

// ----------------------- types & mappers -----------------------

/** Service-level error format with a user-facing message. */
type ServiceError = { code: string; userMessage: string; cause?: unknown };

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

function extractArray<T>(payload: unknown): T[] {
  if (Array.isArray(payload)) return payload as T[];
  if (payload && typeof payload === 'object') {
    const obj = payload as any;
    if (Array.isArray(obj.items)) return obj.items as T[];
    if (Array.isArray(obj.data)) return obj.data as T[];
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
      lastName: String(s.lastName ?? s.lastname ?? ''),
      email: (s.email ?? null) as string | null,
    },
    status: String(raw?.status ?? raw?.enrollmentStatus ?? 'ACTIVE'),
    enrolledAt: (raw?.enrolledAt ?? raw?.enrolled_at ?? null) as string | null,
  };
}

const mapEnrollments = (res: unknown): EnrollmentMini[] =>
  extractArray<any>(res).map(toEnrollmentMini);

function isActive(e: EnrollmentMini) {
  return (e.status || 'ACTIVE').toUpperCase() === 'ACTIVE';
}

function normalizeToStudentOptions(
  list: UserItemDto[] | StudentOption[]
): StudentOption[] {
  const first = (list as any[])[0];
  const shaped = first && 'id' in first && 'name' in first;
  return (list as any[]).map(
    shaped ? (x: any) => x as StudentOption : toStudentOption
  );
}

// ----------------------------- service -----------------------------

@Injectable({ providedIn: 'root' })
export class ClassroomsService {
  private http = inject(HttpClient);

  // -------- classrooms (list/search/detail/crud) --------

  list(params?: { name?: string; unassigned?: boolean; teacherId?: number; studentId?: number }): Observable<ClassroomItemDto[]> {
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
    // NEW: student filter
    if (params?.studentId != null) {
      return this.http.get<ClassroomItemDto[]>(`${BASE}/enrolled-in/${params.studentId}`);
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

  /** Create classroom (optionally with price) */
  create(name: string, price?: number | null): Observable<ClassroomDetailDto> {
    const body: any = { name };
    if (price != null) body.price = price;
    return this.http.post<ClassroomDetailDto>(BASE, body).pipe(
      catchError((cause): Observable<never> =>
        this.fail({ code: 'CLASS_CREATE_FAILED', userMessage: 'Create failed', cause })
      )
    );
  }


  /** Update classroom name and/or price */
  rename(id: number, name: string, price?: number | null): Observable<ClassroomDetailDto> {
    const body: any = { name };
    if (price != null) body.price = price;
    return this.http.put<ClassroomDetailDto>(`${BASE}/${id}`, body).pipe(
      catchError((cause): Observable<never> =>
        this.fail({ code: 'CLASS_RENAME_FAILED', userMessage: 'Rename failed', cause })
      )
    );
  }

  /** Backend may return 204: use text-as-json to avoid parse error. */
  assignTeacher(id: number, teacherId: number): Observable<void> {
    return this.http.put<void>(`${BASE}/${id}/teacher`, { teacherId }, { responseType: 'text' as 'json' }).pipe(
      catchError((cause): Observable<never> =>
        this.fail({ code: 'ASSIGN_TEACHER_FAILED', userMessage: 'Failed to assign teacher', cause })
      )
    );
  }

  unassignTeacher(id: number): Observable<void> {
    return this.http.delete<void>(`${BASE}/${id}/teacher`, { responseType: 'text' as 'json' }).pipe(
      catchError((cause): Observable<never> =>
        this.fail({ code: 'UNASSIGN_TEACHER_FAILED', userMessage: 'Failed to unassign teacher', cause })
      )
    );
  }

  /** Reactivate a dropped classroom */
  reactivate(id: number): Observable<void> {
    return this.http.patch<void>(`${BASE}/${id}/reactivate`, {});
  }

  /** Permanently delete a classroom */
  delete(id: number): Observable<void> {
    return this.http.delete<void>(`${BASE}/${id}`);
  }

  // ----------------------- enrollments -----------------------

  /**
   * List ACTIVE enrollments with a robust fallback:
   * 1) try `/active-enrollments`
   * 2) on error, try `/enrollments` and filter ACTIVE client-side
   */
  listActiveEnrollments(classId: number): Observable<EnrollmentMini[]> {
    const primary = `${ENR}/class/${classId}/active-enrollments`;
    const fallback = `${ENR}/class/${classId}/enrollments`;

    return this.http.get<unknown>(primary).pipe(
      map(mapEnrollments),
      catchError(() =>
        this.http.get<unknown>(fallback).pipe(
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

  enrollStudent(classId: number, studentId: number): Observable<unknown> {
    return this.http.put(`${ENR}/class/${classId}/student/${studentId}`, {}).pipe(
      catchError((cause): Observable<never> =>
        this.fail({ code: 'ENROLL_STUDENT_FAILED', userMessage: 'Enroll failed', cause })
      )
    );
  }

  dropStudent(classId: number, studentId: number): Observable<unknown> {
    return this.http.delete(`${ENR}/class/${classId}/student/${studentId}`).pipe(
      catchError((cause): Observable<never> =>
        this.fail({ code: 'DROP_STUDENT_FAILED', userMessage: 'Drop failed', cause })
      )
    );
  }

  hardDropStudent(classId: number, studentId: number): Observable<unknown> {
    return this.http.delete(`${ENR}/class/${classId}/student/${studentId}/hard`).pipe(
      catchError((cause): Observable<never> =>
        this.fail({ code: 'HARD_DROP_STUDENT_FAILED', userMessage: 'Drop failed', cause })
      )
    );
  }

  // ----------------------- teachers (picklist) -----------------------

  /**
   * Teachers picklist.
   * When `onlyVacant=true` uses `/users/teachers/without-classroom` (server-side filter),
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

  // ----------------------- students (picklist) -----------------------

// src/app/features/admin/classrooms/services/classrooms.service.ts


  /**
   * List candidate students for enrolling into a class.
   * - Toggle ON  (onlyWithoutAnyEnrollment = true): uses /users/students/without-classroom
   * - Toggle OFF (default): uses /users/students and filters out the current roster (excludeIds)
   */
  listStudentsForClass(
    classId: number,
    opts?: {
      /** default true: never offer students already in THIS class */
      onlyNotEnrolled?: boolean;
      /** checkbox: students with NO active enrollment ANYWHERE */
      onlyWithoutAnyEnrollment?: boolean;
      /** safety net for fallback filtering */
      excludeIds?: number[];
    }
  ): Observable<StudentOption[]> {
    // Toggle ON → global “no enrollment anywhere” endpoint (NO class id)
    if (opts?.onlyWithoutAnyEnrollment) {
      const url = `${USERS}/students/without-classroom`;
      if (isDevMode()) console.log('[ClassroomsService] GET (GLOBAL no-enrollment)', url);

      return this.http.get<UserItemDto[] | StudentOption[]>(url).pipe(
        map((list): StudentOption[] => {
          let mapped = normalizeToStudentOptions(list);
          if (opts?.excludeIds?.length) {
            const ban = new Set(opts.excludeIds);
            mapped = mapped.filter(s => !ban.has(s.id));
          }
          return mapped;
        }),
        catchError((cause): Observable<never> =>
          this.fail({ code: 'STUDENTS_LOAD_FAILED', userMessage: 'Failed to load students list', cause })
        )
      );
    }

    // Toggle OFF → fetch all students and filter out current class roster client-side
    const url = `${USERS}/students`; // ✅ no /:classId here
    if (isDevMode()) console.log('[ClassroomsService] GET (ALL students, client-filter)', url);

    return this.http.get<UserItemDto[] | StudentOption[]>(url).pipe(
      map((list): StudentOption[] => {
        let mapped = normalizeToStudentOptions(list);
        if (opts?.excludeIds?.length) {
          const ban = new Set(opts.excludeIds);
          mapped = mapped.filter(s => !ban.has(s.id));
        }
        return mapped;
      }),
      catchError((cause): Observable<never> =>
        this.fail({ code: 'STUDENTS_LOAD_FAILED', userMessage: 'Failed to load students list', cause })
      )
    );
  }

  /** List enrollments; when includeDropped=true, returns ACTIVE + DROPPED */
  listEnrollments(classId: number, opts?: { includeDropped?: boolean }) {
    const url = `${ENR}/class/${classId}/enrollments`;
    return this.http.get<unknown>(url).pipe(
      map(mapEnrollments),
      map(list => opts?.includeDropped ? list : list.filter(isActive)),
      catchError((err): Observable<never> =>
        this.fail({
          code: 'ENROLLMENTS_LOAD_FAILED',
          userMessage: 'Failed to load students',
          cause: err,
        })
      )
    );
  }

  /** Restore previously dropped enrollments; returns { restored, item } */
  restoreRoster(classId: number): Observable<{ restored: number; item: ClassroomDetailDto }> {
    return this.http.post<{ restored: number; item: ClassroomDetailDto }>(`${BASE}/${classId}/restore-roster`, {});
  }

  /** List dropped enrollments (history).
   *  - In prod, the route may not exist yet → 404 NOT_FOUND.
   *  - We treat that as "no history" instead of breaking the roster UI.
   */
  listDroppedEnrollments(
    classId: number
  ): Observable<{ id: number; student: { id: number; name: string; email?: string | null }; droppedAt?: string; status: string }[]> {
    const url = `${BASE}/${classId}/dropped-enrollments`;

    return this.http.get<{ items: any[] }>(url).pipe(
      map(r => r.items ?? []),
      catchError(err => {
        // If the endpoint doesn't exist or returns NOT_FOUND, just behave as if there are no dropped enrollments.
        if (err?.status === 404) {
          if (isDevMode()) {
            console.warn('[ClassroomsService] dropped-enrollments 404, treating as empty list', err);
          }
          return of([]);
        }

        // For other errors, degrade gracefully too — the roster should still work.
        if (isDevMode()) {
          console.error('[ClassroomsService] dropped-enrollments failed, treating as empty list', err);
        }
        return of([]);
      })
    );
  }

  /** Discard a single dropped enrollment (hard delete) */
  discardEnrollment(enrollmentId: number): Observable<{deleted: true}> {
    return this.http.delete<{deleted: true}>(`${API}/api/admin/enrollments/${enrollmentId}/discard`);
  }

  /** Dismiss the restore banner for this classroom */
  dismissRestoreBanner(classId: number): Observable<{ok: true}> {
    return this.http.post<{ok: true}>(`${BASE}/${classId}/restore-banner/dismiss`, {});
  }

  // ----------------------- shared fail helper -----------------------

  private fail<T>(err: ServiceError): Observable<T> {
    if (isDevMode()) console.error('[ClassroomsService]', err.code, err.cause ?? err);
    return throwError(() => err);
  }
}
