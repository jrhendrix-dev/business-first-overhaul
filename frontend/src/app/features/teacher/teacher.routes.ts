// src/app/features/teacher/teacher.routes.ts
import { Routes } from '@angular/router';
import { TeacherShellComponent } from './ui/teacher-shell.component';
import { TeacherClassesPage } from './pages/teacher-classes.page';

export const TEACHER_ROUTES: Routes = [
  {
    path: '',
    component: TeacherShellComponent,
    children: [
      { path: '', pathMatch: 'full', redirectTo: 'classes' },
      { path: 'classes', component: TeacherClassesPage },
    ],
  },
];
