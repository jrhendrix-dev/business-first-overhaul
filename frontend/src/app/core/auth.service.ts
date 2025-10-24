import { Injectable, inject, signal } from '@angular/core';
import { HttpClient, HttpErrorResponse } from '@angular/common/http';
import { environment } from '@environments/environment';
import { LoginDto, LoginResponse, User } from './config/auth.types';
import { catchError, finalize, of, tap, throwError } from 'rxjs';

const TOKEN_KEY = 'auth.token';
const API = environment.apiBase;

@Injectable({ providedIn: 'root' })
export class AuthService {
  private http = inject(HttpClient);

  /** reactive state */
  private _user = signal<User | null>(null);
  user = this._user;                    // expose to components
  loading = signal(false);

  /** helpers */
  token(): string | null {
    return localStorage.getItem(TOKEN_KEY);
  }
  private setToken(token: string) {
    localStorage.setItem(TOKEN_KEY, token);
  }
  private clearToken() {
    localStorage.removeItem(TOKEN_KEY);
  }

  /**
   * Decode the payload part of a JWT (no verification, just base64url decode).
   * Returns null if malformed or not decodable.
   */
  private decodeJwtPayload(token: string): Record<string, unknown> | null {
    const parts = token.split('.');
    const payloadB64 = parts[1];
    if (!payloadB64) return null;

    try {
      const json = atob(payloadB64.replace(/-/g, '+').replace(/_/g, '/'));
      return JSON.parse(json);
    } catch {
      return null;
    }
  }

  /**
   * Very rough expiration check: considers token expired/invalid when:
   *  - payload cannot be decoded, or
   *  - 'exp' is missing/not a number, or
   *  - exp <= now (seconds).
   */
  private isTokenExpired(token: string): boolean {
    const payload = this.decodeJwtPayload(token);
    if (!payload || typeof payload['exp'] !== 'number') return true;

    const nowSeconds = Math.floor(Date.now() / 1000);
    return (payload['exp'] as number) <= nowSeconds;
  }

  /** hydrate on app start */
  hydrate() {
    const token = this.token();
    if (!token || this.isTokenExpired(token)) {
      this.clearToken();
      this._user.set(null);
      return;
    }
    // get current user profile
    this.me().subscribe({
      error: () => this._user.set(null),
    });
  }

  /** POST /api/login -> store JWT -> GET /api/me */
  login(dto: LoginDto) {
    this.loading.set(true);
    return this.http.post<LoginResponse>(`${API}/api/login`, dto).pipe(
      tap((res) => this.setToken(res.token)),
      tap(() => this.me().subscribe()),
      catchError((err: HttpErrorResponse) => throwError(() => err)),
      finalize(() => this.loading.set(false)),
    );
  }

  /** GET /api/me (must include Authorization header – done by interceptor) */
  me() {
    return this.http.get<User>(`${API}/api/me`).pipe(
      tap((u) => this._user.set(u))
    );
  }

  /** client-side logout */
  logout() {
    this.clearToken();
    this._user.set(null);
    return of(null);
  }

  /** derived helpers */
  isLoggedIn = () => this.user() !== null;
  isAdmin    = () => !!this.user()?.roles?.includes('ROLE_ADMIN');
}
