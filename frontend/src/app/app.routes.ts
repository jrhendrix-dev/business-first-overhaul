import { Routes } from '@angular/router';

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
  { path: '**', redirectTo: '' }
];
