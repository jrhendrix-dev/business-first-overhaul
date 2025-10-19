import { UserRole } from './user-role';

export interface CreateUserDto {
  firstName: string;
  lastName: string;
  email: string;
  userName: string;
  password: string;
  role: UserRole;          // backend expects string role (good)
}

/** Partial fields only; send what changes */
export type UpdateUserDto = Partial<{
  firstName: string;
  lastName: string;
  email: string;
  userName: string;
  password: string;        // only include if changing
  role: UserRole | null;   // set if changing; omit otherwise
}>;
