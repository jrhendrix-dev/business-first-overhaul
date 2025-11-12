export interface UserItemDto {
  id: number;
  firstName?: string;
  lastName?: string;
  fullName?: string;
  userName?: string;
  email?: string;
  role?: string;
  isActive?: boolean;
  createdAt?: string;

  /** Optional list of classrooms (only when include=classrooms) */
  classes?: { id: number; name: string }[];
}
