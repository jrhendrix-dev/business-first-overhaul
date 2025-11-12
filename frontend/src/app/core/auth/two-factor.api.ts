import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '@/environments/environment';

const API = environment.apiBase;

export type SetupInitiate = {
  secret: string;
  otpauthUri: string;
  qrPng: string;  // data:image/svg+xml;base64,....
};


@Injectable({ providedIn: 'root' })
export class TwoFactorApi {
  private http = inject(HttpClient);

  initiate(): Observable<SetupInitiate> {
    return this.http.get<SetupInitiate>(`${API}/api/auth/2fa/setup/initiate`);
  }

  confirm(code: string): Observable<{ enabled: true; recoveryCodes: string[] }> {
    return this.http.post<{ enabled: true; recoveryCodes: string[] }>(
      `${API}/api/auth/2fa/setup/confirm`,
      { code }
    );
  }

  verify(preToken: string, code: string): Observable<{ token: string }> {
    return this.http.post<{ token: string }>(`${API}/api/auth/2fa/verify`, { preToken, code });
  }

  verifyWithRecovery(preToken: string, recoveryCode: string): Observable<{ token: string }> {
    return this.http.post<{ token: string }>(`${API}/api/auth/2fa/verify`, { preToken, recoveryCode });
  }

  disable(): Observable<{ enabled: false }> {
    return this.http.post<{ enabled: false }>(`${API}/api/auth/2fa/disable`, {});
  }

  regenerate(): Observable<{ recoveryCodes: string[] }> {
    return this.http.post<{ recoveryCodes: string[] }>(`${API}/api/auth/2fa/recovery/regenerate`, {});
  }
}
