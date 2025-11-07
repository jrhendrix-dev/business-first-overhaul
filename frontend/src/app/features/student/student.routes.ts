import { Routes } from '@angular/router';
import { StudentShellComponent } from './ui/student-shell.component';
import { StudentClassesPage } from './pages/student-classes.page';

export const STUDENT_ROUTES: Routes = [
  {
    path: '',
    component: StudentShellComponent,
    children: [
      { path: '', pathMatch: 'full', redirectTo: 'classes' },
      { path: 'classes', component: StudentClassesPage },
    ],
  },
];
