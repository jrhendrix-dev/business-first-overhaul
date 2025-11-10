import { inject } from '@angular/core';
import { CanActivateFn, Router } from '@angular/router';
import { AuthStateService } from '@/app/core/auth/auth.service';

//Class that redirects logged in users to the catalog if press the "empieza ahora" button
export const guestOnlyGuard: CanActivateFn = () => {
  const auth = inject(AuthStateService);
  const router = inject(Router);

  if (auth.loggedIn()) {
    router.navigateByUrl('/catalog'); // or '/account' if you prefer
    return false;
  }
  return true;
};
