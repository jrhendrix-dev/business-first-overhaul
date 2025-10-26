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
