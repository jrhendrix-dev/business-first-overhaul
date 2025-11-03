import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { environment } from '@environments/environment';
import { Observable, tap } from 'rxjs';
import { AuthService } from '@/app/core/auth.service';

const API = environment.apiBase;

export type MeResponse = {
  id: number;
  email: string;
  roles: string[];
  firstName: string | null;
  lastName: string | null;
  fullName: string;
  role: string | null;
};

export type UpdateMeDto = {
  userName?: string | null;
  firstName?: string | null;
  lastName?: string | null;
};

export type ChangePasswordDto = {
  currentPassword: string;
  newPassword: string;
  confirmPassword: string;
};

export type StartChangeEmailDto = {
  newEmail: string;
  password: string;
};

export type ForgotPasswordDto = { email: string };

export type ResetPasswordDto = { token: string; newPassword: string };

@Injectable({ providedIn: 'root' })
export class MeService {
  private http = inject(HttpClient);
  private auth = inject(AuthService);

  getMe(): Observable<MeResponse> {
    return this.http.get<MeResponse>(`${API}/api/me`).pipe(
      tap(m => {
        // keep AuthApiService reactive user in sync if fields exist
        const u = this.auth.user();
        if (u) this.auth.user.set({ ...u, firstName: m.firstName ?? '', lastName: m.lastName ?? '' } as any);
      })
    );
  }

  updateMe(dto: UpdateMeDto): Observable<any> {
    return this.http.patch(`${API}/api/me`, dto).pipe(
      tap((updated: any) => {
        // backend returns full UserResponseDto; hydrate auth state
        const u = this.auth.user();
        this.auth.user.set({ ...(u ?? {}), ...updated } as any);
      })
    );
  }

  changePassword(dto: ChangePasswordDto): Observable<{ message: string }> {
    return this.http.post<{ message: string }>(`${API}/api/me/change-password`, dto);
  }

  startChangeEmail(dto: StartChangeEmailDto): Observable<{ message: string }> {
    return this.http.post<{ message: string }>(`${API}/api/me/change-email`, dto);
  }

  confirmEmailChange(token: string) {
    return this.http.get<{ message: string; email: string }>(`${API}/api/me/change-email/confirm`, {
      params: { token }
    });
  }

  startPasswordReset(dto: ForgotPasswordDto): Observable<{ message: string }> {
    return this.http.post<{ message: string }>(`${API}/api/me/password/forgot`, dto);
  }

  confirmPasswordReset(dto: ResetPasswordDto): Observable<{ message: string }> {
    return this.http.post<{ message: string }>(`${API}/api/me/password/reset`, dto);
  }

}
