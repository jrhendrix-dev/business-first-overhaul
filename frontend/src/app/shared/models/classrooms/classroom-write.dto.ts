/** Payload for POST /admin/classrooms  */
export interface CreateClassroomDto {
  name: string;
}

/** Payload for PUT /admin/classrooms/{id}  */
export interface RenameClassroomDto {
  name: string;
}

/** Payload for PUT /admin/classrooms/{id}/teacher  */
export interface AssignTeacherDto {
  teacherId: number;
}
