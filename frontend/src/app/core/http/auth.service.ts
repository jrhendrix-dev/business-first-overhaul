// src/app/core/http/auth.service.ts
import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';

@Injectable({ providedIn: 'root' })
export class AuthService {
  constructor(private http: HttpClient) {}

  /** Backend always returns 200 with a generic message to avoid enumeration. */
  requestPasswordReset(email: string) {
    return this.http.post<{ message: string }>(`/api/password/forgot`, { email });
  }

  /** For the reset page you added later (token + newPassword) */
  confirmPasswordReset(payload: { token: string; newPassword: string }) {
    return this.http.post<{ message: string }>(`/api/password/reset`, payload);
  }
}
