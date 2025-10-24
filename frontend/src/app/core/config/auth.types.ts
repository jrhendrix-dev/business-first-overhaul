export type LoginDto = { email: string; password: string };

export type LoginResponse = { token: string };

export type User = {
  id: number;
  email: string;
  firstName: string;
  lastName: string;
  roles: string[];
};
