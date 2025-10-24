import { HttpClient } from '@angular/common/http';
import { inject, Injectable } from '@angular/core';
import {environment} from '@environments/environment';

export interface LoginResponse {
  success?: boolean;
  // legacy responses might also return wait or message
  wait?: number;
  message?: string;
  error?: { code: string; details?: Record<string, string> };
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
export class AuthService {
  private http = inject(HttpClient);
  private base = '/api/password';
  private api = environment.apiBase;


  /** helper to build Symfony API paths */
  private apiPath(path: string) {
    // always hit /api/... on the same host
    return `${this.api}/api/${path}`;
  }

  /**
   * Logs in against the legacy PHP or Symfony endpoint.
   * For legacy, POST to '/login.php' with form fields.
   * For Symfony, adjust to '/api/auth/login' JSON body.
   */
  async login(username: string, password: string): Promise<LoginResponse> {
    // LEGACY: form-encoded to login.php
    const res = await fetch('/login.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      credentials: 'include', // allow cookies (PHPSESSID)
      body: new URLSearchParams({ username, password }).toString(),
    });

    const data = await res.json().catch(() => ({}));
    return data as LoginResponse;
  }


  /** /api/password/forgot */
  requestPasswordReset(email: string) {
    return this.http.post<{ message?: string }>(
      this.apiPath('password/forgot'),
      { email }
    );
  }

  /** /api/password/reset */
  confirmPasswordReset(token: string, newPassword: string) {
    return this.http.post<{ message?: string }>(
      this.apiPath('password/reset'),
      { token, newPassword }
    );
  }

  /** /api/auth/register */
  registerUser(dto: RegisterDto) {

    const payload: RegisterDto = {
      firstName: dto.firstName ?? '',
      lastName:  dto.lastName  ?? '',
      email:     dto.email,
      userName:  (dto.userName ?? '').trim() || (dto.email.split('@')[0] || 'user'), // simple default
      password:  dto.password,
      role:      'ROLE_STUDENT',                       // default role
    };
    return this.http.post(this.apiPath('auth/register'), payload);
  }


}
