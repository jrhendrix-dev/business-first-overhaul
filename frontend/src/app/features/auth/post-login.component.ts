import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { Router } from '@angular/router';
import { MeService } from '@/app/features/me/me.service';

@Component({
  standalone: true,
  selector: 'app-post-login',
  imports: [CommonModule],
  template: `
    <div class="min-h-[60vh] grid place-items-center">
      <div class="text-center">
        <div class="animate-pulse text-3xl font-semibold">Entrando…</div>
        <p class="mt-2 text-sm text-slate-600">Comprobando tu rol y preparando tu panel.</p>
      </div>
    </div>
  `
})
export class PostLoginComponent implements OnInit {
  private router = inject(Router);
  private me = inject(MeService);

  ngOnInit(): void {
    this.me.getMe().subscribe({
      next: (m) => {
        const roles = m.roles ?? [];
        if (roles.includes('ROLE_ADMIN')) {
          this.router.navigateByUrl('/admin');
        } else if (roles.includes('ROLE_TEACHER')) {
          this.router.navigateByUrl('/teacher');
        } else {
          // default for students / basic users
          this.router.navigateByUrl('/student');
        }
      },
      error: () => this.router.navigateByUrl('/login?reason=unauthenticated'),
    });
  }
}
