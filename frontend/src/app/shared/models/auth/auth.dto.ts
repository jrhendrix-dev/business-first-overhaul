export interface RegisterDto {
  firstName: string;
  lastName: string;
  email: string;
  userName: string;
  password: string;
  role?: 'ROLE_STUDENT' | 'ROLE_USER'; // whatever you allow at register
}

export interface LoginDto {
  username: string;  // or email, match your /api/login
  password: string;
}

export interface LoginResponse {
  token: string;     // whatever your API returns
}
