import { Injectable, inject, isDevMode } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { Observable, catchError, map, throwError, tap } from 'rxjs';
import { environment } from '@/environments/environment';
import { UserItemDto } from '@/app/shared/models/user/user-read.dto';
import { CreateUserDto, UpdateUserDto } from '@/app/shared/models/user/user-write.dto';

const API = environment.apiBase; // currently 'http://localhost:8000'

export interface UsersQuery {
  q?: string;
  role?: string;
  page?: number;
  size?: number;
}

export interface UsersPageDto {
  items: UserItemDto[];
  total: number;
  page: number;
  size: number;
}

@Injectable({ providedIn: 'root' })
export class UsersService {
  private http = inject(HttpClient);


  /** List users (supports array or paginated object responses) */
  list(query: UsersQuery): Observable<UsersPageDto> {
    // Ask backend to embed classrooms relation (single, not array param; switch to with[] if your controller expects arrays)
    let params = new HttpParams().set('with', 'classrooms');

    if (query.q)          params = params.set('q', query.q);
    if (query.role)       params = params.set('role', query.role);
    if (query.page != null) params = params.set('page', String(query.page));
    if (query.size != null) params = params.set('size', String(query.size));

    const url = `${API}/api/admin/users`;

    if (isDevMode()) console.log('[UsersService] GET', url, 'params=', params.toString());

    return this.http.get<any>(url, { params }).pipe(
      tap(res => isDevMode() && console.log('[UsersService] response:', res)),

      map((res: any) => {
        // Normalize payload to always expose .items and .classes[]
        const rawItems: UserItemDto[] = Array.isArray(res) ? (res as UserItemDto[]) : (res?.items ?? []);

        const items = (rawItems ?? []).map((u: any) => {
          // prefer `classes`, otherwise map `classrooms` -> `classes`
          const classes = Array.isArray(u?.classes) ? u.classes
            : Array.isArray(u?.classrooms) ? u.classrooms
              : [];
          return { ...u, classes };
        });

        if (Array.isArray(res)) {
          return {
            items,
            total: items.length,
            page: query.page ?? 1,
            size: query.size ?? items.length,
          };
        }

        return {
          items,
          total: Number(res?.total ?? items.length ?? 0),
          page: Number(res?.page ?? query.page ?? 1),
          size: Number(res?.size ?? query.size ?? items.length ?? 0),
        };
      }),

      catchError(err => {
        console.error('[UsersService] list() failed', err);
        return throwError(() => err);
      })
    );
  }


  /** Create new user */
  create(dto: CreateUserDto): Observable<UserItemDto> {
    const url = `${API}/api/admin/users`;           // ✅
    if (isDevMode()) console.log('[UsersService] POST', url, dto);
    return this.http.post<UserItemDto>(url, dto).pipe(
      catchError(err => {
        console.error('[UsersService] create() failed', err);
        return throwError(() => err);
      })
    );
  }

  /** Update existing user */
  update(id: number, dto: UpdateUserDto): Observable<UserItemDto> {
    const url = `${API}/api/admin/users/${id}`;     // ✅
    if (isDevMode()) console.log('[UsersService] PATCH', url, dto);
    return this.http.patch<UserItemDto>(url, dto).pipe(
      catchError(err => {
        console.error('[UsersService] update() failed', err);
        return throwError(() => err);
      })
    );
  }

  /** Delete user by ID */
  remove(id: number): Observable<void> {
    const url = `${API}/api/admin/users/${id}`;     // ✅
    if (isDevMode()) console.log('[UsersService] DELETE', url);
    return this.http.delete<void>(url).pipe(
      catchError(err => {
        console.error('[UsersService] remove() failed', err);
        return throwError(() => err);
      })
    );
  }
}
