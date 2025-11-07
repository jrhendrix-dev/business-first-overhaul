import { Injectable, NgZone } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { environment } from '@/environments/environment';

declare global { interface Window { google?: any; } }

function whenGoogleReady(): Promise<void> {
  return new Promise((resolve, reject) => {
    if (window.google?.accounts?.id) return resolve();
    const max = 40; let tries = 0;
    const t = setInterval(() => {
      tries++;
      if (window.google?.accounts?.id) { clearInterval(t); resolve(); }
      else if (tries >= max) { clearInterval(t); reject(new Error('GIS script not ready')); }
    }, 100);
  });
}

@Injectable({ providedIn: 'root' })
export class GoogleAuthService {
  constructor(private http: HttpClient, private zone: NgZone) {}

  async init(clientId: string, cb: (idToken: string) => void) {
    await whenGoogleReady();
    window.google.accounts.id.initialize({
      client_id: clientId,
      callback: (resp: any) => this.zone.run(() => cb(resp.credential)),
      auto_select: false,
      cancel_on_tap_outside: true,
    });
  }

  async renderButton(host: HTMLElement) {
    await whenGoogleReady();
    window.google.accounts.id.renderButton(host, { theme: 'filled', size: 'large' });
  }

  exchange(idToken: string) {
    const url = `${environment.apiBase}/api/auth/google`; // <-- use API base
    return this.http.post<{ token: string }>(url, { idToken });
  }
}
