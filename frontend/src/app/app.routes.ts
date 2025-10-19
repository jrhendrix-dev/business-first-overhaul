import { Routes } from '@angular/router';
import { UsersPage } from './features/users/users.page';
import { AdminClassroomsPage } from './features/classroom/admin-classrooms.page';

export const routes: Routes = [
  { path: '', redirectTo: 'users', pathMatch: 'full' },
  { path: 'users', loadComponent: () => Promise.resolve(UsersPage) },
  { path: 'classrooms', loadComponent: () => Promise.resolve(AdminClassroomsPage) },
];
