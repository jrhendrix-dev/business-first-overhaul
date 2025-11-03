import { Injectable, signal } from '@angular/core';
import { HttpClient, HttpRequest } from '@angular/common/http';
import { environment } from '@/environments/environment';
import { Observable, tap } from 'rxjs';

export type LoginResponse = {
  accessToken: string;
  refreshToken?: string;
  /** May be null/undefined from backend; we’ll derive it from exp. */
  expiresAt?: string | null;
};

@Injectable({ providedIn: 'root' })
export class AuthStateService {
  private readonly ACCESS = 'auth.access';
  private readonly REFRESH = 'auth.refresh';
  private readonly EXPIRES = 'auth.expires';

  /** Handy signal for navbars etc. */
  readonly loggedIn = signal<boolean>(false);

  constructor(private http: HttpClient) {
    this.loggedIn.set(this.isAuthenticated());
  }

  addAuthHeader<T>(req: HttpRequest<T>): HttpRequest<T> {
    const token = this.getAccessToken();
    return token ? req.clone({ setHeaders: { Authorization: `Bearer ${token}` } }) : req;
  }

  /** Persist tokens; if expiresAt missing, decode from JWT exp. */
  persist(res: LoginResponse): void {
    const token = res.accessToken;
    localStorage.setItem(this.ACCESS, token);

    if (res.refreshToken) localStorage.setItem(this.REFRESH, res.refreshToken);

    const iso = res.expiresAt && res.expiresAt.trim() !== ''
      ? res.expiresAt
      : this.decodeJwtExpIso(token);

    if (iso) localStorage.setItem(this.EXPIRES, iso);

    this.loggedIn.set(this.isAuthenticated());
  }

  clearAuth(): void {
    localStorage.removeItem(this.ACCESS);
    localStorage.removeItem(this.REFRESH);
    localStorage.removeItem(this.EXPIRES);
    this.loggedIn.set(false);
  }

  canRefresh(): boolean {
    return !!localStorage.getItem(this.REFRESH);
  }

  isAuthenticated(): boolean {
    const token = this.getAccessToken();
    if (!token) return false;

    let expires = this.getExpiresAt();
    if (!expires) {
      // derive once and store for faster checks later
      expires = this.decodeJwtExpIso(token);
      if (expires) localStorage.setItem(this.EXPIRES, expires);
    }

    // If still no expiry (non-standard token), consider presence == logged in.
    if (!expires) return true;

    return new Date(expires).getTime() > Date.now();
  }

  getAccessToken(): string | null {
    return localStorage.getItem(this.ACCESS);
  }
  getExpiresAt(): string | null {
    return localStorage.getItem(this.EXPIRES);
  }

  private base64UrlDecode(b64url: string): string {
    // pad to multiple of 4
    const pad = '==='.slice((b64url.length + 3) % 4);
    const b64 = (b64url + pad).replace(/-/g, '+').replace(/_/g, '/');
    return atob(b64);
  }


  private decodeJwtExpIso(token: string | null): string | null {
    if (!token) return null;

    // find the 2 dots; avoid direct parts[1] to satisfy TS
    const firstDot = token.indexOf('.');
    if (firstDot <= 0) return null;

    const secondDot = token.indexOf('.', firstDot + 1);
    if (secondDot <= firstDot + 1) return null;

    const payloadB64 = token.slice(firstDot + 1, secondDot);
    try {
      const json = this.base64UrlDecode(payloadB64);
      const payload: any = JSON.parse(json);

      const expSec = Number(payload?.exp);
      if (!Number.isFinite(expSec)) return null;

      return new Date(expSec * 1000).toISOString();
    } catch {
      return null;
    }
  }

  // Only if/when you implement refresh on backend:
  refreshTokens(): Observable<LoginResponse> {
    const refreshToken = localStorage.getItem(this.REFRESH);
    return this.http
      .post<LoginResponse>(`${environment.apiBase}/api/auth/refresh`, { refreshToken })
      .pipe(tap((r) => this.persist(r)));
  }


}

