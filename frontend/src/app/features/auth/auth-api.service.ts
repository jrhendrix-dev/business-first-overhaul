// src/app/features/auth/auth-api.service.ts
import { HttpClient } from '@angular/common/http';
import { inject, Injectable } from '@angular/core';
import { environment } from '@environments/environment';

export interface LoginResponse {
  accessToken: string;
  refreshToken?: string;
  expiresAt: string;
}

export interface RegisterDto {
  firstName: string;
  lastName:  string;
  email:     string;
  userName:  string;
  password:  string;
  role:      'ROLE_STUDENT' | 'ROLE_TEACHER' | 'ROLE_ADMIN';
}

@Injectable({ providedIn: 'root' })
export class AuthApiService {
  private http = inject(HttpClient);
  private api = environment.apiBase;

  /** Symfony API path helper */
  private apiPath(path: string) {
    return `${this.api}/api/${path}`;
  }

  login(email: string, password: string) {
    return this.http.post<LoginResponse>(this.apiPath('auth/login'), { email, password });
  }

  requestPasswordReset(email: string) {
    return this.http.post<{ message?: string }>(this.apiPath('password/forgot'), { email });
  }

  confirmPasswordReset(token: string, newPassword: string) {
    return this.http.post<{ message?: string }>(this.apiPath('password/reset'), { token, newPassword });
  }

  registerUser(dto: RegisterDto) {
    return this.http.post(this.apiPath('auth/register'), dto);
  }
}
