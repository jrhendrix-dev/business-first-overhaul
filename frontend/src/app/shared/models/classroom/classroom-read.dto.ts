import { ClassroomStatus } from './classroom-status';

/** Matches ClassroomResponseMapper::teacherMini() */
export interface TeacherMiniDto {
  id: number;
  name: string;
}

/** Matches ClassroomResponseMapper::toItem() */
export interface ClassroomItemDto {
  id: number;
  name: string;
  teacher: TeacherMiniDto | null;
  status: ClassroomStatus;
}

/** Matches ClassroomResponseMapper::toDetail() */
export interface ClassroomDetailDto {
  id: number;
  name: string;
  teacher: TeacherMiniDto | null;
  activeStudents: number;     // mapper always returns activeCount (int)
  status: ClassroomStatus;
}
