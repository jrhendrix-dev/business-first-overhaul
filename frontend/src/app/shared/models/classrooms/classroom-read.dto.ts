// src/app/shared/models/classrooms/classroom-read.dto.ts
export interface TeacherMini {
  id: number;
  name: string;
}

export type ClassroomStatus = 'ACTIVE' | 'DROPPED';

export interface ClassroomItemDto {
  id: number;
  name: string;
  status: ClassroomStatus;
  teacher: TeacherMini | null;

  /** pricing (backend may send either) */
  price?: number | null;        // decimal (e.g. 15.0)
  priceCents?: number | null;   // minor units (e.g. 1500)
  currency?: string | null;     // e.g. 'EUR'
}

export interface ClassroomDetailDto extends ClassroomItemDto {
  activeStudents: number;
}

export interface EnrollmentStudentMini {
  id: number;
  firstName: string;
  lastName: string;
  email: string;
}

export interface EnrollmentItemDto {
  id: number;
  classId: number;
  status: 'ACTIVE' | 'DROPPED';
  enrolledAt?: string;
  droppedAt?: string;
  student: EnrollmentStudentMini;
}
