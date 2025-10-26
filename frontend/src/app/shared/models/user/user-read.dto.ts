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

  /** Optional list of classes (only when include=classes) */
  classes?: { id: number; name: string }[];
}
