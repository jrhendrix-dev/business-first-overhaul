import { Component, OnInit, inject, signal, computed } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ReactiveFormsModule, NonNullableFormBuilder, Validators } from '@angular/forms';
import { debounceTime, distinctUntilChanged } from 'rxjs/operators';
import { ToastContainerComponent } from '@/app/core/ui/toast-container.component';
import { ToastService } from '@/app/core/ui/toast.service';
import { UsersService, UsersQuery } from './users.service';
import { UserItemDto } from '@/app/shared/models/user/user-read.dto';
import { CreateUserDto, UpdateUserDto } from '@/app/shared/models/user/user-write.dto';
import { UserRole } from '@/app/shared/models/user/user-role';
import { RouterLink } from '@angular/router';

@Component({
  standalone: true,
  selector: 'app-admin-users',
  imports: [CommonModule, ReactiveFormsModule, ToastContainerComponent, RouterLink],
  templateUrl: './users.page.html',
})
export class UsersPage implements OnInit {
  private fb = inject(NonNullableFormBuilder);
  private api = inject(UsersService);
  private toast = inject(ToastService);

  // Raw data from API
  items = signal<UserItemDto[]>([]);
  total = signal(0);
  page = signal(1);
  size = signal(10);

  // Filters (debounced)
  filters = this.fb.group({
    q: this.fb.control<string>(''),
    role: this.fb.control<string>(''),
  });

  // Drawer/form state
  drawerOpen = signal(false);
  editMode = signal(false);
  editingId: number | null = null;

  form = this.fb.group({
    firstName: this.fb.control('', { validators: [Validators.required, Validators.minLength(2)] }),
    lastName:  this.fb.control('', { validators: [Validators.required, Validators.minLength(2)] }),
    email:     this.fb.control('', { validators: [Validators.required, Validators.email] }),
    userName:  this.fb.control('', { validators: [Validators.required, Validators.minLength(2)] }),
    password:  this.fb.control('', { validators: [] }), // only for create
    role:      this.fb.control<UserRole | null>(null, { validators: [Validators.required] }),
  });

  // Client-side filtered view (fallback if backend ignores q/role)
  viewItems = computed(() => {
    const q = (this.filters.controls.q.value || '').toLowerCase().trim();
    const role = this.filters.controls.role.value || '';
    let data = [...this.items()];
    if (q) {
      data = data.filter(u =>
        (u.userName || '').toLowerCase().includes(q) ||
        (u.email || '').toLowerCase().includes(q) ||
        (u.firstName || '').toLowerCase().includes(q) ||
        (u.lastName || '').toLowerCase().includes(q) ||
        (u.fullName || '').toLowerCase().includes(q)
      );
    }
    if (role) data = data.filter(u => u.role === role);
    return data;
  });
  filteredTotal = computed(() => this.viewItems().length);

  manageQuery(u: UserItemDto) {
    // Send them to Classes page filtered by the person in context
    return u.role === 'ROLE_TEACHER'
      ? { teacherId: u.id }
      : { studentId: u.id };
  }

  ngOnInit(): void {
    this.load();



    // Debounce filter changes to avoid spamming server
    this.filters.valueChanges
      .pipe(debounceTime(300), distinctUntilChanged())
      .subscribe(() => {
        this.page.set(1);
        this.load(); // server-side filtering if supported
      });
  }

  // ---- Data ----
  load(): void {
    const qVal = this.filters.controls.q.value?.trim();
    const roleVal = this.filters.controls.role.value?.trim();

    const query: UsersQuery = {
      ...(qVal ? { q: qVal } : {}),
      ...(roleVal ? { role: roleVal } : {}),
      page: this.page(),
      size: this.size(),
    };

    this.api.list(query).subscribe({
      next: (res) => {
        this.items.set(res.items);
        this.total.set(res.total ?? res.items.length);
        this.page.set(res.page ?? this.page());
        this.size.set(res.size ?? this.size());
      },
      error: () => {
        this.items.set([]);
        this.total.set(0);
      }
    });
  }
  next(){ this.page.update(p => p + 1); this.load(); }
  prev(){ this.page.update(p => Math.max(1, p - 1)); this.load(); }

  // after total/page/size signals
  visibleStart = computed(() => (this.total() === 0 ? 0 : (this.page() - 1) * this.size() + 1));
  visibleEnd   = computed(() => Math.min(this.page() * this.size(), this.total()));


  // ---- Helpers for view ----
  nameOf(u: UserItemDto): string {
    return (u.fullName?.trim() || `${u.firstName ?? ''} ${u.lastName ?? ''}`.trim()) || '—';
  }
  formatDate(iso: string | undefined): string {
    if (!iso) return '—';
    const d = new Date(iso);
    return isNaN(d.getTime()) ? iso : d.toLocaleString();
  }

  // ---- CRUD ----
  openCreate(){
    this.editMode.set(false);
    this.editingId = null;
    this.form.reset({
      firstName: '',
      lastName: '',
      email: '',
      userName: '',
      password: '',
      role: null,
    });
    this.drawerOpen.set(true);
  }

  openEdit(u: UserItemDto){
    this.editMode.set(true);
    this.editingId = u.id;
    this.form.reset({
      firstName: u.firstName ?? '',
      lastName:  u.lastName ?? '',
      email:     u.email ?? '',
      userName:  u.userName ?? '',
      password:  '',
      role:      (u.role as UserRole) ?? null,
    });
    this.drawerOpen.set(true);
  }

  close(){ this.drawerOpen.set(false); }

  submit(){
    if (this.editMode()) {
      const dto: UpdateUserDto = {
        firstName: this.form.value.firstName!,
        lastName:  this.form.value.lastName!,
        email:     this.form.value.email!,
        userName:  this.form.value.userName!,
        role:      this.form.value.role!,
        ...(this.form.value.password ? { password: this.form.value.password! } : {}),
      };
      this.api.update(this.editingId!, dto).subscribe(() => {
        this.toast.add('User updated', 'success');
        this.close(); this.load();
      });
    } else {
      const dto: CreateUserDto = {
        firstName: this.form.value.firstName!,
        lastName:  this.form.value.lastName!,
        email:     this.form.value.email!,
        userName:  this.form.value.userName!,
        password:  this.form.value.password!,
        role:      this.form.value.role!,
      };
      this.api.create(dto).subscribe(() => {
        this.toast.add('User created', 'success');
        this.close(); this.load();
      });
    }
  }

  confirmDelete(u: UserItemDto){
    const ok = window.confirm(`Delete user "${this.nameOf(u)}"?`);
    if (!ok) return;
    this.api.remove(u.id).subscribe(() => {
      this.toast.add('User deleted', 'success');
      const wasLast = this.items().length === 1 && this.page() > 1;
      if (wasLast) this.page.update(p => p - 1);
      this.load();
    });
  }
}
