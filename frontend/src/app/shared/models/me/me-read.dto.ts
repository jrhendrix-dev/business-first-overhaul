export interface MeResponse {
  id: number;
  email: string;
  roles: string[];
  firstName: string | null;
  lastName: string | null;
  fullName: string;

  /** Backward-compat single role */
  role: string | null;

  /** Needed by 2FA card */
  twoFactorEnabled: boolean;

  /** NEW: safe flags for Google link state */
  hasGoogleLink: boolean;
  googleLinkedAt: string | null; // ISO 8601 or null
}
