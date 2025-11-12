import { UserRole } from './user-role';

export interface CreateUserDto {
  firstName: string;
  lastName: string;
  email: string;
  userName: string;
  password: string;
  role: UserRole;
  classId?: number | null;   // optional: assign a class on create
}

/** Partial fields only; send what changes */
export type UpdateUserDto = Partial<{
  firstName: string;
  lastName: string;
  email: string;
  userName: string;
  password: string;        // only include if changing
  role: UserRole;          // include only if changing
  isActive: boolean;       // admin enable/disable
  classId: number | null;  // reassign or clear (null)
}>;
