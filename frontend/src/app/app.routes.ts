import { RouterModule, Routes, ExtraOptions } from '@angular/router';
import { NgModule } from '@angular/core';
import { authGuard } from '@app/core/auth/auth.guard';
import { PostLoginComponent } from '@app/features/auth/post-login.component';
import { TeacherShellComponent } from '@app/features/teacher/ui/teacher-shell.component';
import { StudentShellComponent } from '@app/features/student/ui/student-shell.component';
import {guestOnlyGuard} from '@app/core/auth/guest-only.guard';

export const routes: Routes = [
  { path: '', pathMatch: 'full', loadComponent: () => import('./features/home/home.page').then(m => m.HomePage) },
  { path: 'ingles-corporativo', loadComponent: () => import('./features/placeholder/placeholder.page').then(m => m.PlaceholderPage), data: { title: 'Inglés corporativo' } },
  { path: 'examenes',           loadComponent: () => import('./features/placeholder/placeholder.page').then(m => m.PlaceholderPage), data: { title: 'Exámenes oficiales' } },
  { path: 'clases-espanol',     loadComponent: () => import('./features/placeholder/placeholder.page').then(m => m.PlaceholderPage), data: { title: 'Español para extranjeros' } },

  { path: 'login',    loadComponent: () => import('./features/auth/login.page').then(m => m.LoginPage) },
  { path: '2fa',      loadComponent: () => import('./features/auth/2fa/two-factor.page').then(m => m.TwoFactorPage) },
  { path: 'me',       loadComponent: () => import('./features/me/me.page').then(m => m.MePage) },
  { path: 'email/confirm',  loadComponent: () => import('./features/me/email-confirm.page').then(m => m.EmailConfirmPage) },
  { path: 'password/forgot', loadComponent: () => import('./features/auth/forgot-password.page').then(m => m.ForgotPasswordPage) },
  { path: 'password/reset',  loadComponent: () => import('./features/auth/reset-password.page').then(m => m.ResetPasswordPage) },

  { path: 'catalog',     loadComponent: () => import('./features/catalog/class-catalog.page') },
  { path: 'catalog/:id', loadComponent: () => import('./features/catalog/class-details.page') },

  { path: 'register', canActivate: [guestOnlyGuard], loadComponent: () => import('./features/auth/register.page').then(m => m.RegisterPage) },
  { path: 'auth',     loadComponent: () => import('./features/auth/auth-choice.page').then(m => m.AuthChoicePage) },

  // ✅ single lazy group for payment pages (success / cancel / failed)
  { path: 'payment', loadChildren: () => import('./features/payment/payment.routes').then(m => m.PAYMENT_ROUTES) },

  // --- Student/Teacher areas ---
  { path: 'student', loadChildren: () => import('./features/student/student.routes').then(m => m.STUDENT_ROUTES) },
  { path: 'teacher', loadChildren: () => import('./features/teacher/teacher.routes').then(m => m.TEACHER_ROUTES) },

  // --- Admin shell + children ---
  {
    path: 'admin',
    canActivate: [authGuard],
    loadComponent: () => import('./features/admin/admin-shell.component').then(m => m.AdminShellComponent),
    children: [
      { path: '', redirectTo: 'users', pathMatch: 'full' },
      { path: 'users',   title: 'Admin • Users',   loadComponent: () => import('./features/admin/users/users.page').then(m => m.UsersPage) },
      { path: 'classes', title: 'Admin • Classes', loadComponent: () => import('./features/admin/classrooms/pages/classes.page').then(m => m.ClassesPage) },
      { path: 'grades',  title: 'Admin • Grades',  loadComponent: () => import('./features/admin/grades/grades.page').then(m => m.GradesPage) },
    ],
  },

  { path: 'post-login', component: PostLoginComponent },
  { path: 'teacher', component: TeacherShellComponent },
  { path: 'student', component: StudentShellComponent },

  // 404
  { path: '**', loadComponent: () => import('./shared/pages/not-found.page').then(m => m.NotFoundPage) },
];

const routerOptions: ExtraOptions = {
  scrollPositionRestoration: 'top',
  anchorScrolling: 'enabled',
  scrollOffset: [0, 0],
};

@NgModule({
  imports: [RouterModule.forRoot(routes, routerOptions)],
  exports: [RouterModule],
})
export class AppRoutingModule {}
