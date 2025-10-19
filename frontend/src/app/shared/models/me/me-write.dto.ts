// /api/me (PATCH) – use only fields you allow to change
export type MeUpdateDto = Partial<{
  firstName: string;
  lastName: string;
  userName: string;
  email: string; // only if you really allow direct change; you have a separate flow below
}>;
export interface ChangeEmailStartDto {
  email: string;
  password: string;
}
