// Functional interceptor version
import { HttpInterceptorFn, HttpErrorResponse, HttpRequest } from '@angular/common/http';
import { inject } from '@angular/core';
import { Router } from '@angular/router';
import { AuthStateService } from './auth.service';
import { ToastService } from '@/app/core/ui/toast/toast.service';
import { BehaviorSubject, throwError } from 'rxjs';
import { catchError, filter, switchMap, take } from 'rxjs/operators';

const refreshing$ = new BehaviorSubject<boolean>(false);

// Simple checker – tweak as needed if you add more protected roots
function isProtectedUrl(path: string): boolean {
  return /^\/(admin|me|teacher|student)(\/|$)/.test(path);
}


export const authInterceptor: HttpInterceptorFn = (req, next) => {
  const auth = inject(AuthStateService);
  const router = inject(Router);
  const toast = inject(ToastService);

  const url = req.url || '';
  if (url.endsWith('/api/login') || url.endsWith('/api/auth/login') || url.endsWith('/api/auth/refresh')) {
    return next(req);
  }

  const withAuth: HttpRequest<any> = auth.addAuthHeader(req);

  return next(withAuth).pipe(
    catchError((err: HttpErrorResponse) => {
      if (err.status !== 401 && err.status !== 403) {
        return throwError(() => err);
      }

      // Clear local auth first (token invalid/expired)
      auth.clearAuth();

      const code = err.error?.error?.code as string | undefined;

      // Optional refresh flow (only if you implement it)
      if (auth.canRefresh() && (code === 'TOKEN_EXPIRED' || code === 'UNAUTHENTICATED')) {
        if (!refreshing$.value) {
          refreshing$.next(true);
          return auth.refreshTokens().pipe(
            switchMap(() => {
              refreshing$.next(false);
              return next(auth.addAuthHeader(req));
            }),
            catchError((e) => {
              refreshing$.next(false);
              auth.clearAuth();
              toast.info('Session expired. Please log in again.');
              router.navigate(['/login'], { queryParams: { reason: 'expired' } });
              return throwError(() => e);
            })
          );
        }

        // Wait for in-flight refresh
        return refreshing$.pipe(
          filter(v => !v),
          take(1),
          switchMap(() => next(auth.addAuthHeader(req)))
        );
      }

      // No refresh path
      auth.clearAuth();
      if (err.status === 403) {
        toast.error('Access denied.');
        router.navigate(['/login'], { queryParams: { reason: 'forbidden' } });
      } else {
        toast.info('Please log in to continue.');
        router.navigate(['/login'], { queryParams: { reason: 'unauthenticated' } });
      }
      return throwError(() => err);
    })
  );
};
