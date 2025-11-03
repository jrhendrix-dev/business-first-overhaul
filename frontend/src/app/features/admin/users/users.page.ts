import { Component, OnInit, inject, signal, computed } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ReactiveFormsModule, NonNullableFormBuilder } from '@angular/forms';
import { debounceTime, distinctUntilChanged } from 'rxjs/operators';
import { ToastContainerComponent } from '@app/core/ui/toast/toast-container.component';
import { ToastService } from '@app/core/ui/toast/toast.service';
import { UsersService, UsersQuery } from './users.service';
import { UserItemDto } from '@/app/shared/models/user/user-read.dto';
import { RouterLink } from '@angular/router';


// new drawers
import { DrawerCreateUserComponent } from './components/drawer-create-user.component';
import { DrawerEditUserComponent } from './components/drawer-edit-user.component';
import {AdminHeaderComponent} from '@app/core/ui/admin-header.component';

@Component({
  standalone: true,
  selector: 'app-admin-users',
  imports: [
    CommonModule,
    ReactiveFormsModule,
    ToastContainerComponent,
    RouterLink,
    DrawerCreateUserComponent,
    AdminHeaderComponent,
    DrawerEditUserComponent
  ],
  templateUrl: './users.page.html',
})
export class UsersPage implements OnInit {
  private fb    = inject(NonNullableFormBuilder);
  private api   = inject(UsersService);
  private toast = inject(ToastService);

  // ====== Data / paging ======
  items = signal<UserItemDto[]>([]);
  total = signal(0);
  page  = signal(1);
  size  = signal(10);

  // ====== Filters ======
  filters = this.fb.group({
    q:    this.fb.control<string>(''),
    role: this.fb.control<string>(''),
  });

  // ====== Drawer state ======
  createOpen = signal(false);
  editOpen   = signal(false);
  editing    = signal<UserItemDto | null>(null);

  // ====== Computed view ======
  viewItems = computed(() => {
    const q    = (this.filters.controls.q.value || '').toLowerCase().trim();
    const role = this.filters.controls.role.value || '';
    let data   = [...this.items()];
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

  ngOnInit(): void {
    this.load();
    this.filters.valueChanges
      .pipe(debounceTime(300), distinctUntilChanged())
      .subscribe(() => { this.page.set(1); this.load(); });
  }

  /** Build Classes page query params for a given user row */
  manageQuery(u: UserItemDto) {
    return u.role === 'ROLE_TEACHER' ? { teacherId: u.id } : { studentId: u.id };
  }

  // ====== Data ======
  load(): void {
    const query: UsersQuery = {
      ...(this.filters.controls.q.value?.trim() ? { q: this.filters.controls.q.value!.trim() } : {}),
      ...(this.filters.controls.role.value?.trim() ? { role: this.filters.controls.role.value!.trim() } : {}),
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
        this.items.set([]); this.total.set(0);
        this.toast.add('No se pudieron cargar los usuarios.', 'error');
      }
    });
  }
  next(){ this.page.update(p => p + 1); this.load(); }
  prev(){ this.page.update(p => Math.max(1, p - 1)); this.load(); }

  visibleStart = computed(() => (this.total() === 0 ? 0 : (this.page() - 1) * this.size() + 1));
  visibleEnd   = computed(() => Math.min(this.page() * this.size(), this.total()));

  classesOf(u: UserItemDto): Array<{ id: number; name: string }> {
    const anyU = u as any;
    return (anyU.classes ?? anyU.classrooms ?? []) as Array<{ id: number; name: string }>;
  }
  nameOf(u: UserItemDto): string {
    return (u.fullName?.trim() || `${u.firstName ?? ''} ${u.lastName ?? ''}`.trim()) || '—';
  }
  formatDate(iso: string | undefined): string {
    if (!iso) return '—';
    const d = new Date(iso);
    return isNaN(d.getTime()) ? iso : d.toLocaleString();
  }

  // ====== Drawer actions ======
  openCreate(){ this.createOpen.set(true); }
  openEdit(u: UserItemDto){ this.editing.set(u); this.editOpen.set(true); }

  onDrawerClosed(){ this.createOpen.set(false); this.editOpen.set(false); this.editing.set(null); }
  onSaved(){ this.onDrawerClosed(); this.load(); }

  // ====== Delete ======
  confirmDelete(u: UserItemDto){
    const ok = window.confirm(`Delete user "${this.nameOf(u)}"?`);
    if (!ok) return;
    this.api.remove(u.id).subscribe({
      next: () => {
        this.toast.add('User deleted', 'success');
        const wasLast = this.items().length === 1 && this.page() > 1;
        if (wasLast) this.page.update(p => p - 1);
        this.load();
      },
      error: () => this.toast.add('No se pudo eliminar el usuario.', 'error')
    });
  }


}
