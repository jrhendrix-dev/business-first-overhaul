import { Injectable, NgZone } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { environment } from '@/environments/environment';

declare global { interface Window { google?: any; } }

function whenGoogleReady(): Promise<void> {
  return new Promise((resolve, reject) => {
    if (window.google?.accounts?.id) return resolve();
    const max = 60; let tries = 0;
    const t = setInterval(() => {
      tries++;
      if (window.google?.accounts?.id) { clearInterval(t); resolve(); }
      else if (tries >= max) { clearInterval(t); reject(new Error('GIS script not ready')); }
    }, 100);
  });
}

@Injectable({ providedIn: 'root' })
export class GoogleAuthService {
  private initialized = false;
  private currentClientId: string | null = null;

  constructor(private http: HttpClient, private zone: NgZone) {}

  /** Safe to call multiple times; will initialize exactly once. */
  async init(clientId: string, cb: (idToken: string) => void): Promise<void> {
    await whenGoogleReady();
    if (this.initialized) {
      // If clientId changed across builds, re-init explicitly.
      if (this.currentClientId !== clientId) {
        window.google!.accounts.id.cancel();
        this.initialized = false;
      } else {
        return;
      }
    }

    this.currentClientId = clientId;
    // Useful one-time log to catch accidental prod/dev mixups
    console.info('[GSI] init client:', clientId);

    window.google!.accounts.id.initialize({
      client_id: clientId,
      callback: (resp: any) => this.zone.run(() => cb(resp.credential)),
      auto_select: false,
      cancel_on_tap_outside: true,
      ux_mode: 'popup',   // avoid redirects and “no user activation” oddities
      itp_support: true
    });

    this.initialized = true;
  }

  async renderButton(host: HTMLElement): Promise<void> {
    await whenGoogleReady();
    window.google!.accounts.id.renderButton(host, {
      type: 'standard',
      theme: 'filled',
      size: 'large',
      shape: 'pill'
    });
  }

  exchange(idToken: string) {
    const url = `${environment.apiBase}/api/auth/google`;
    return this.http.post<{ token: string }>(url, { idToken });
  }
}
