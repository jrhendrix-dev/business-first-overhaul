import { Component, inject } from '@angular/core';
import { RouterLink, Router } from '@angular/router';
import { AuthStateService } from '@/app/core/auth/auth.service';

@Component({
  standalone: true,
  selector: 'app-home',
  imports: [RouterLink],
  templateUrl: './home.page.html'
})

export class HomePage {
  private router = inject(Router);
  private auth   = inject(AuthStateService);

  startNow(): void {
    if (this.auth.loggedIn()) {
      this.router.navigateByUrl('/catalog'); // or '/account'
    } else {
      this.router.navigateByUrl('/register');
    }
  }



}
