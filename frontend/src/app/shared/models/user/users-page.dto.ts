import { UserItemDto } from './user-read.dto';

export interface UsersPageDto {
  items: UserItemDto[];
  total: number;
  page: number;
  size: number;
}
