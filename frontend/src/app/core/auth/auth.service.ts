// src/app/core/auth/auth.service.ts
import { Injectable, signal } from '@angular/core';
import { HttpClient, HttpRequest } from '@angular/common/http';
import { environment } from '@/environments/environment';
import { Observable, tap } from 'rxjs';

/** Accept both shapes coming from backend(s) */
export type LoginResponse = {
  token?: string;          // <-- new (backend current)
  accessToken?: string;    // <-- keep BC just in case
  refreshToken?: string;
  expiresAt?: string | null;
};

/** 2FA challenge branch */
export type TwoFactorChallenge = {
  preToken: string;        // <-- presence of preToken == needs 2FA
};

@Injectable({ providedIn: 'root' })
export class AuthStateService {
  private readonly ACCESS = 'auth.access';
  private readonly REFRESH = 'auth.refresh';
  private readonly EXPIRES = 'auth.expires';

  readonly loggedIn = signal<boolean>(false);

  constructor(private http: HttpClient) {
    this.loggedIn.set(this.isAuthenticated());
  }

  /** POST /api/login -> { token? , preToken? } */
  login(email: string, password: string): Observable<LoginResponse | TwoFactorChallenge> {
    return this.http.post<LoginResponse | TwoFactorChallenge>(
      `${environment.apiBase}/api/login`,
      { email, password }
    );
  }

  addAuthHeader<T>(req: HttpRequest<T>): HttpRequest<T> {
    const token = this.getAccessToken();
    return token ? req.clone({ setHeaders: { Authorization: `Bearer ${token}` } }) : req;
  }

  /** Persist final JWT (handles token or accessToken) */
  persist(res: LoginResponse): void {
    const token = (res.token ?? res.accessToken) || '';
    if (!token) return; // nothing to do; caller should have handled preToken

    localStorage.setItem(this.ACCESS, token);
    if (res.refreshToken) localStorage.setItem(this.REFRESH, res.refreshToken);

    const iso = res.expiresAt && res.expiresAt.trim() !== ''
      ? res.expiresAt
      : this.decodeJwtExpIso(token);

    if (iso) localStorage.setItem(this.EXPIRES, iso);
    this.loggedIn.set(this.isAuthenticated());
  }

  /** Convenience for the /auth/2fa/verify final JWT */
  persistFinalToken(finalJwt: string): void {
    this.persist({ token: finalJwt, expiresAt: null });
  }

  /** Detects if the response is the 2FA branch */
  isTwoFactor(res: LoginResponse | TwoFactorChallenge): res is TwoFactorChallenge {
    return typeof (res as any)?.preToken === 'string' && (res as any).preToken.length > 0;
  }

  clearAuth(): void {
    localStorage.removeItem(this.ACCESS);
    localStorage.removeItem(this.REFRESH);
    localStorage.removeItem(this.EXPIRES);
    this.loggedIn.set(false);
  }

  canRefresh(): boolean { return !!localStorage.getItem(this.REFRESH); }

  isAuthenticated(): boolean {
    const token = this.getAccessToken();
    if (!token) return false;

    let expires = this.getExpiresAt();
    if (!expires) {
      expires = this.decodeJwtExpIso(token);
      if (expires) localStorage.setItem(this.EXPIRES, expires);
    }
    if (!expires) return true;
    return new Date(expires).getTime() > Date.now();
  }

  getAccessToken(): string | null { return localStorage.getItem(this.ACCESS); }
  getExpiresAt(): string | null { return localStorage.getItem(this.EXPIRES); }

  private base64UrlDecode(b64url: string): string {
    const pad = '==='.slice((b64url.length + 3) % 4);
    const b64 = (b64url + pad).replace(/-/g, '+').replace(/_/g, '/');
    return atob(b64);
  }

  private decodeJwtExpIso(token: string | null): string | null {
    if (!token) return null;
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
    } catch { return null; }
  }

  // (Optional) refresh flow kept as-is
  refreshTokens(): Observable<LoginResponse> {
    const refreshToken = localStorage.getItem(this.REFRESH);
    return this.http
      .post<LoginResponse>(`${environment.apiBase}/api/auth/refresh`, { refreshToken })
      .pipe(tap((r) => this.persist(r)));
  }
}
