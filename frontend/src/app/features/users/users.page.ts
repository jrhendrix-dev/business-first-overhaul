// users.page.ts
import { Component, inject, signal } from '@angular/core';
import { AsyncPipe, NgFor, NgIf } from '@angular/common';
import {
  AbstractControl,
  NonNullableFormBuilder,
  ReactiveFormsModule,
  Validators,
} from '@angular/forms';
import { Observable, firstValueFrom } from 'rxjs';
import { UsersService } from './users.service';
import { UserItemDto } from '@/app/shared/models/user/user-read.dto';
import { CreateUserDto } from '@/app/shared/models/user/user-write.dto';

type Role = 'ROLE_STUDENT' | 'ROLE_TEACHER' | 'ROLE_ADMIN';

@Component({
  standalone: true,
  selector: 'bfe-users-page',
  imports: [NgFor, NgIf, AsyncPipe, ReactiveFormsModule],
  templateUrl: './users.page.html',
})
export class UsersPage {
  private readonly svc = inject(UsersService);
  private readonly fb = inject(NonNullableFormBuilder);

  readonly users$: Observable<UserItemDto[]> = this.svc.list();

  // ⬇️ no generic here — let Angular infer the types
  readonly form = this.fb.group({
    firstName: ['', Validators.required],
    lastName: ['', Validators.required],
    email: ['', [Validators.required, Validators.email]],
    userName: ['', Validators.required],
    password: ['', [Validators.required, Validators.minLength(12)]],
    role: 'ROLE_STUDENT' as Role,
  });

  submitting = signal(false);
  error = signal<string | null>(null);

  serverError(ctrl: AbstractControl | null) {
    const e = ctrl?.errors as any;
    return e?.server as string | undefined;
  }

  async create() {
    this.error.set(null);
    if (this.form.invalid) { this.form.markAllAsTouched(); return; }

    const payload = this.form.getRawValue() as CreateUserDto;

    this.submitting.set(true);
    try {
      await firstValueFrom(this.svc.create(payload));
      // reset form and refresh list (quick & simple)
      this.form.reset({
        firstName: '', lastName: '', email: '', userName: '', password: '', role: 'ROLE_STUDENT' as Role
      });
    } catch (e: any) {
      if (e?.code === 'VALIDATION_FAILED') {
        Object.entries(e.details as Record<string, string>)
          .forEach(([field, msg]) => this.form.get(field)?.setErrors({ server: msg }));
      } else {
        this.error.set(e?.code ?? 'UNKNOWN_ERROR');
      }
    } finally {
      this.submitting.set(false);
    }
  }
}
