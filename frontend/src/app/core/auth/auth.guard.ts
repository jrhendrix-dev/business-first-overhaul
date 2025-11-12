import { inject } from '@angular/core';
import { CanActivateFn, Router, ActivatedRouteSnapshot, RouterStateSnapshot } from '@angular/router';
import { AuthStateService } from './auth.service';

/**
 * Redirects unauthenticated users to /auth (choice page),
 * preserving the protected URL so we can come back after login/register.
 */
export const authGuard: CanActivateFn = (_route: ActivatedRouteSnapshot, state: RouterStateSnapshot) => {
  const auth   = inject(AuthStateService);
  const router = inject(Router);

  if (auth.isAuthenticated()) return true;

  return router.createUrlTree(['/auth'], {
    queryParams: { returnUrl: state.url, reason: 'forbidden' }
  });
};

// Optional: backward-compat so imports using `AuthGuard` keep working
export const AuthGuard = authGuard;
