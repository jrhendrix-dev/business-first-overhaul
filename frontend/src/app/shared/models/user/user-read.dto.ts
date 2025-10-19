import { UserRole } from './user-role';

export interface UserItemDto {
  id: number;
  userName: string;
  email: string;
  firstName: string;
  lastName: string;
  role: UserRole | null;
  isActive: boolean;
  createdAt: string;   // ISO 8601
  fullName: string;
}
