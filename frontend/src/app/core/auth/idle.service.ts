// src/app/core/auth/idle.service.ts
import { Injectable, NgZone } from '@angular/core';
import { Router } from '@angular/router';
import { fromEvent, merge, Subject, timer } from 'rxjs';
import { switchMap, startWith, takeUntil } from 'rxjs/operators';
import { AuthStateService } from './auth.service';
import { ToastService } from '@/app/core/ui/toast/toast.service';

const IDLE_MINUTES = 20;

// hoist helper so it’s not recreated each tick
const isProtectedPath = (path: string): boolean =>
  /^\/(admin|me|teacher|student)(\/|$)/.test(path);

@Injectable({ providedIn: 'root' })
export class IdleService {
  private stop$ = new Subject<void>();

  constructor(
    private zone: NgZone,
    private router: Router,
    private auth: AuthStateService,
    private toast: ToastService
  ) {}

  start(): void {
    const activity$ = merge(
      fromEvent(window, 'mousemove'),
      fromEvent(window, 'mousedown'),
      fromEvent(window, 'keydown'),
      fromEvent(window, 'scroll'),
      fromEvent(window, 'touchstart')
    );

    this.zone.runOutsideAngular(() => {
      activity$
        .pipe(
          startWith(null),
          switchMap(() => timer(IDLE_MINUTES * 60 * 1000)),
          takeUntil(this.stop$)
        )
        .subscribe(() => {
          this.zone.run(() => {
            // logout + conditional redirect
            this.auth.clearAuth();

            // ✅ always a string
            const current = ((this.router.url ?? '/').split('?')[0]) ?? '/';

            if (isProtectedPath(current)) {
              // ✅ silence the “Promise returned from navigate” lint
              void this.router.navigate(['/login'], { queryParams: { reason: 'timeout' } });
            }

            this.toast.info('Se cerró tu sesión por inactividad. Inicia sesión de nuevo.');
          });
        });
    });
  }

  stop(): void {
    this.stop$.next();
  }
}
