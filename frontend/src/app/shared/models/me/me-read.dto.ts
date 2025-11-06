export interface MeResponseDto {
  id: number;
  email: string;

  // New model you’re already using in the template
  roles: string[];
  firstName: string | null;
  lastName: string | null;
  fullName: string;

  /** Primary role kept for backward compatibility with older UI bits */
  role: string | null;

  /** <-- Needed for the 2FA card */
  twoFactorEnabled: boolean;
}
