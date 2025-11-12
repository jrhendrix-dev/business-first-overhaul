/** Payload to create a grade */
export interface AddGradeDto {
  enrollmentId: number;             // carried in URL for create; still kept in form for UX
  component: string;                // enum value (e.g. "QUIZ", "MIDTERM", "FINAL")
  score: number;
  maxScore: number;
}

/** Payload to update a grade (partial) */
export interface UpdateGradeDto {
  component?: string;
  score?: number;
  maxScore?: number;
}
