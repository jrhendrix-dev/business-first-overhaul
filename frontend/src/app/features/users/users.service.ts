import { inject, Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { ApiClient } from '@/app/core/http/api-client.service';
import { UserItemDto } from '@/app/shared/models/user/user-read.dto';
import { CreateUserDto, UpdateUserDto } from '@/app/shared/models/user/user-write.dto';

@Injectable({ providedIn: 'root' })
export class UsersService {
  private readonly api = inject(ApiClient);

  list(): Observable<UserItemDto[]> {
    return this.api.get<UserItemDto[]>('/admin/users'); // admin list
  }
  getOne(id: number): Observable<UserItemDto> {
    return this.api.get<UserItemDto>(`/admin/users/${id}`);
  }
  create(body: CreateUserDto): Observable<UserItemDto> {
    return this.api.post<UserItemDto, CreateUserDto>('/admin/users', body);
  }
  update(id: number, body: UpdateUserDto): Observable<UserItemDto> {
    return this.api.patch<UserItemDto, UpdateUserDto>(`/admin/users/${id}`, body);
  }
  delete(id: number) {
    return this.api.delete<void>(`/admin/users/${id}`);
  }
}
