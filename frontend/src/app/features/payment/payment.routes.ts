import { Routes } from '@angular/router';

export const PAYMENT_ROUTES: Routes = [
  {
    path: 'success',
    loadComponent: () => import('./payment-success.page').then(m => m.default),
  },
  {
    path: 'cancel',
    loadComponent: () => import('./payment-cancel.page').then(m => m.default),
  },
  {
    path: 'failed',
    loadComponent: () => import('./payment-failed.page').then(m => m.default),
  },
];
