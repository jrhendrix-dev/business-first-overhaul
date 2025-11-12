// features/admin/classrooms/models/enrollment-mini.model.ts
export type EnrollmentMini = {
  student: {
    id: number;
    firstName: string;
    lastName: string;
    email?: string | null;
  };
  status: string;
  enrolledAt?: string | null;
};

export type TeacherOption = { id: number; name: string; email?: string | null };
export type StudentOption = { id: number; name: string; email?: string | null };
