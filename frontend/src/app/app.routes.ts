import { RouterModule, Routes, ExtraOptions } from '@angular/router';
import { NgModule } from '@angular/core';
import { authGuard } from '@app/core/auth/auth.guard';
import { PostLoginComponent } from '@app/features/auth/post-login.component';
import { guestOnlyGuard } from '@app/core/auth/guest-only.guard';

export const routes: Routes = [
  { path: '', pathMatch: 'full', loadComponent: () => import('./features/home/home.page').then(m => m.HomePage) },

  { path: 'login',    loadComponent: () => import('./features/auth/login.page').then(m => m.LoginPage) },
  { path: '2fa',      loadComponent: () => import('./features/auth/2fa/two-factor.page').then(m => m.TwoFactorPage) },
  { path: 'me',       loadComponent: () => import('./features/me/me.page').then(m => m.MePage) },
  { path: 'email/confirm',  loadComponent: () => import('./features/me/email-confirm.page').then(m => m.EmailConfirmPage) },
  { path: 'password/forgot', loadComponent: () => import('./features/auth/forgot-password.page').then(m => m.ForgotPasswordPage) },
  { path: 'password/reset',  loadComponent: () => import('./features/auth/reset-password.page').then(m => m.ResetPasswordPage) },

  // Marketing
  { path: 'ingles-corporativo', loadComponent: () => import('@/app/features/marketing/corporate-english.page').then(m => m.CorporateEnglishPage), },
  { path: 'examenes', loadComponent: () => import('@/app/features/marketing/exam-prep.page').then(m => m.ExamPrepPage), },
  { path: 'clases-espanol', loadComponent: () => import('@/app/features/marketing/spanish-classes.page').then(m => m.SpanishClassesPage), },
  { path: 'contacto', loadComponent: () => import('@/app/features/contact/contact.page').then(m => m.ContactPage), },

  // Catalog (default exports)
  { path: 'catalog',     loadComponent: () => import('./features/catalog/class-catalog.page').then(m => m.default) },
  { path: 'catalog/:id', loadComponent: () => import('./features/catalog/class-details.page').then(m => m.default) },

  { path: 'register', canActivate: [guestOnlyGuard], loadComponent: () => import('./features/auth/register.page').then(m => m.RegisterPage) },
  { path: 'auth',     loadComponent: () => import('./features/auth/auth-choice.page').then(m => m.AuthChoicePage) },

  // Payments
  { path: 'payment', loadChildren: () => import('./features/payment/payment.routes').then(m => m.PAYMENT_ROUTES) },

  // Student/Teacher areas (lazy)
  { path: 'student', loadChildren: () => import('./features/student/student.routes').then(m => m.STUDENT_ROUTES) },
  { path: 'teacher', loadChildren: () => import('./features/teacher/teacher.routes').then(m => m.TEACHER_ROUTES) },

  // Admin
  {
    path: 'admin',
    canActivate: [authGuard],
    loadComponent: () => import('./features/admin/admin-shell.component').then(m => m.AdminShellComponent),
    children: [
      { path: '', redirectTo: 'users', pathMatch: 'full' },
      { path: 'users',   title: 'Admin • Users',   loadComponent: () => import('./features/admin/users/users.page').then(m => m.UsersPage) },
      { path: 'classes', title: 'Admin • Classes', loadComponent: () => import('./features/admin/classrooms/pages/classes.page').then(m => m.ClassesPage) },
      { path: 'grades',  title: 'Admin • Grades',  loadComponent: () => import('./features/admin/grades/grades.page').then(m => m.GradesPage) },
      { path: 'orders',  title: 'Admin • Billing', loadComponent: () => import('./features/admin/orders/orders.page').then(m => m.OrdersPage) },
    ],
  },

  { path: 'post-login', component: PostLoginComponent },

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
