import { RouterModule, Routes, ExtraOptions  } from '@angular/router';
import {AuthGuard} from '@app/core/auth/auth.guard';
import { NgModule } from '@angular/core';

export const routes: Routes = [
  { path: '', pathMatch: 'full', loadComponent: () => import('./features/home/home.page').then(m => m.HomePage) },
  { path: 'ingles-corporativo', loadComponent: () => import('./features/placeholder/placeholder.page').then(m => m.PlaceholderPage), data: { title: 'Inglés corporativo' } },
  { path: 'examenes', loadComponent: () => import('./features/placeholder/placeholder.page').then(m => m.PlaceholderPage), data: { title: 'Exámenes oficiales' } },
  { path: 'clases-espanol', loadComponent: () => import('./features/placeholder/placeholder.page').then(m => m.PlaceholderPage), data: { title: 'Español para extranjeros' } },
  { path: 'login', loadComponent: () => import('./features/auth/login.page').then(m => m.LoginPage) },
  { path: 'me', loadComponent: () => import('./features/me/me.page').then(m => m.MePage) },
  { path: 'email/confirm', loadComponent: () => import('./features/me/email-confirm.page').then(m => m.EmailConfirmPage) },
  { path: 'password/forgot', loadComponent: ()  => import('./features/auth/forgot-password.page').then(m => m.ForgotPasswordPage) },
  { path: 'password/reset',  loadComponent: () => import('./features/auth/reset-password.page').then(m => m.ResetPasswordPage) },
  { path: 'register', loadComponent: () => import('./features/auth/register.page').then(m => m.RegisterPage) },
  { path: '', redirectTo: 'me', pathMatch: 'full' },

  // --- Admin area (NEW): shell + children ---
  {
    path: 'admin',
    canActivate: [AuthGuard],
    loadComponent: () =>
      import('./features/admin/admin-shell.component')
        .then(m => m.AdminShellComponent),
    // If you have an admin/auth guard, add it here:
    // canMatch: [adminGuard],
    children: [
      { path: '', redirectTo: 'users', pathMatch: 'full' },

      {
        path: 'users',
        title: 'Admin • Users',
        loadComponent: () =>
          import('./features/admin/users/users.page')
            .then(m => m.UsersPage),
      },
      {
        path: 'classes',
        title: 'Admin • Classes',
        loadComponent: () =>
          import('@app/features/admin/classrooms/pages/classes.page')
            .then(m => m.ClassesPage),
      },
      {
        path: 'grades',
        title: 'Admin • Grades',
        loadComponent: () =>
          import('./features/admin/grades/grades.page')
            .then(m => m.GradesPage),
      },
    ],
  },

  // --- 404 ---
  {
    path: '**',
    loadComponent: () =>
      import('./shared/pages/not-found.page').then(m => m.NotFoundPage),
  },
];

const routerOptions: ExtraOptions = {
  scrollPositionRestoration: 'top', // ✅ scrolls to top automatically
  anchorScrolling: 'enabled',
  scrollOffset: [0, 0],
};

@NgModule({
  imports: [RouterModule.forRoot(routes, routerOptions)],
  exports: [RouterModule],
})
export class AppRoutingModule {}
