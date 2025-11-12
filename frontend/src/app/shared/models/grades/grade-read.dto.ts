export interface GradeItemDto {
  id: number;
  component: string;         // raw enum value from backend (if included)
  componentLabel: string;    // human label
  score: number;
  maxScore: number;
  percent: number;           // 0..100
  gradedAt: string;          // ISO string
  enrollmentId: number;
  // Backend key is "classrooms" (plural). Normalize to "classroom".
  classroom: { id: number; name: string };
  student?: { id: number; firstName: string; lastName: string; email: string };
}
