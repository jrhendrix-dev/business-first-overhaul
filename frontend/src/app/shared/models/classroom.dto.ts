export interface ClassroomItemDto {
  id: number;
  name: string;
  teacher?: string | null;
  status?: 'ACTIVE' | 'DROPPED'; // keep in sync with backend enum mapping if you expose it
}

export interface ClassroomDetailDto extends ClassroomItemDto {
  studentsCount: number;
  createdAt: string; // ISO-8601
}

export interface CreateClassroomDto {
  name: string;
}
