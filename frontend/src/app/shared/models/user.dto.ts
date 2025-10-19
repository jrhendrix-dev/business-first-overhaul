export type UserRole = 'ROLE_ADMIN' | 'ROLE_TEACHER' | 'ROLE_STUDENT' | 'ROLE_USER';

export interface UserItemDto {
  id: number;
  firstName: string;
  lastName: string;
  email: string;
  userName: string;
  role: UserRole;
}
