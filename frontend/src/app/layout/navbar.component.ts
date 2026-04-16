// src/app/layout/navbar.component.ts
import { Component, ElementRef, HostListener, OnDestroy, inject } from '@angular/core';
import { Router, NavigationEnd, RouterLink, RouterLinkActive } from '@angular/router';
import { NgIf } from '@angular/common';
import { Subject, takeUntil } from 'rxjs';
import { AuthStateService } from '@/app/core/auth/auth.service';

@Component({
  selector: 'app-navbar',
  standalone: true,
  imports: [RouterLink, RouterLinkActive, NgIf],
  template: `
    <nav class="sticky [top:var(--demo-bar-h,0px)] z-40 w-full bg-brand-navy text-white shadow">
      <!-- Desktop (logo | links | account) -->
      <div class="mx-auto hidden max-w-6xl grid-cols-[300px_1fr_220px] items-center gap-4 px-4 py-5 md:grid">

        <!-- Logo + optional portfolio return -->
        <div class="flex flex-col items-start gap-1">
          <a routerLink="/" class="flex items-center" (click)="goHome($event)">
            <img src="assets/pics/logoNew.png" alt="Business First"
                 class="h-12 w-auto md:h-[64px] lg:h-[72px] xl:h-[80px] object-contain" />
          </a>
          <a *ngIf="isPortfolioDemo"
             href="https://jonathan-hendrix.dev"
             class="flex items-center gap-1 text-xs text-white/70 hover:text-white transition"
             title="Volver al portfolio de Jonathan Hendrix">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none"
                 viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Volver al portfolio
          </a>
        </div>

        <!-- Center links -->
        <ul class="flex items-center justify-center gap-14 text-[15.5px] font-medium tracking-wide uppercase">
          <li class="group relative">
            <a routerLink="/"
               routerLinkActive="text-brand-white"
               class="relative px-2 py-1 transition-colors hover:text-brand-crimson"
               (click)="goHome($event)">
              Inicio
              <span class="absolute left-0 -bottom-0.5 block h-0.5 w-0 bg-brand-crimson transition-all duration-200 group-hover:w-full"></span>
            </a>
          </li>

          <!-- Cursos -->
          <li class="group relative">
            <button class="relative flex items-center gap-2 px-2 py-1 transition-colors hover:text-brand-crimson">
              Cursos
              <span class="mt-1 inline-block border-x-transparent border-t-8 border-b-0 border-l-8 border-r-8 border-t-brand-crimson"></span>
            </button>
            <div class="invisible absolute left-1/2 z-20 mt-3 w-72 -translate-x-1/2 overflow-hidden
                        rounded-md bg-brand-navy opacity-0 shadow-lg ring-1 ring-black/10
                        transition-all group-hover:visible group-hover:opacity-100">
              <a routerLink="/ingles-corporativo" class="block px-4 py-2 hover:bg-brand-crimson">Inglés corporativo</a>
              <a routerLink="/examenes" class="block px-4 py-2 hover:bg-brand-crimson">Exámenes oficiales</a>
              <a routerLink="/clases-espanol" class="block px-4 py-2 hover:bg-brand-crimson">Español para extranjeros</a>
              <a routerLink="/catalog" class="block px-4 py-2 hover:bg-brand-crimson">Catálogo de clases</a>
            </div>
          </li>

          <!-- Contacto -->
          <li class="group relative">
            <button class="relative px-2 py-1 transition-colors hover:text-brand-crimson">
              Contacto <span class="sr-only">abrir menú de contacto</span>
            </button>
            <div class="invisible absolute left-1/2 z-20 mt-3 w-80 -translate-x-1/2 overflow-hidden rounded-md bg-brand-navy opacity-0 shadow-lg ring-1 ring-black/10 transition-all group-hover:visible group-hover:opacity-100">
              <div class="px-4 py-3">
                <a href="tel:+34635507365" class="block hover:text-brand-crimson">Tlf: +34 635 507 365</a>
                <a href="mailto:jrhendrixdev@gmail.com" class="block hover:text-brand-crimson">jrhendrixdev&#64;gmail.com</a>
                <span class="block text-gray-200">Rota (Cádiz)</span>
              </div>
            </div>
          </li>
        </ul>

        <!-- Right side: Portfolio button + CTA / Account -->
        <div class="flex items-center justify-end gap-3">

          <!-- Logged out -->
          <ng-container *ngIf="!auth.loggedIn()">
            <a routerLink="/register"
               class="rounded-md border border-white/60 px-3 py-2 font-semibold text-white/90 transition
                      hover:text-white hover:border-white hover:bg-white/10">
              Registrarse
            </a>
            <a routerLink="/login"
               class="rounded-md bg-brand-crimson px-3 py-2 font-semibold text-white transition hover:bg-red-700 whitespace-nowrap">
              Iniciar sesión
            </a>
          </ng-container>

          <!-- Logged in account -->
          <div *ngIf="auth.loggedIn()" class="relative group">
            <button
              class="relative flex items-center gap-2 px-2 py-1 transition-colors hover:text-brand-crimson"
              (click)="toggleAccountMenu()"
              aria-haspopup="menu"
              [attr.aria-expanded]="accountOpen">
              <span class="opacity-90">{{ fullName() }}</span>
              <span class="mt-1 inline-block border-x-transparent border-t-8 border-b-0 border-l-8 border-r-8 border-t-brand-crimson"></span>
            </button>

            <div *ngIf="accountOpen"
                 class="absolute right-0 z-20 mt-3 w-72 overflow-hidden rounded-md bg-brand-navy text-white shadow-lg ring-1 ring-black/10">
              <div class="px-4 py-3 text-sm">
                <p class="font-semibold">{{ fullName() }}</p>
                <p class="truncate text-white/70">{{ userEmail() }}</p>
              </div>
              <div class="border-t border-white/10"></div>
              <a routerLink="/me" class="block px-4 py-2 hover:bg-brand-crimson" (click)="closeMenus()">Mi perfil</a>
              <a *ngIf="panelRoute() as pr" [routerLink]="pr" class="block px-4 py-2 hover:bg-brand-crimson" (click)="closeMenus()">
                {{ panelLabel() }}
              </a>
              <button class="block w-full px-4 py-2 text-left hover:bg-brand-crimson" (click)="closeMenus(); logout()">
                Cerrar sesión
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Mobile header -->
      <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-3 md:hidden">
        <a routerLink="/" class="flex items-center" (click)="goHome($event)">
          <img src="assets/pics/logoNew.png" alt="Business First" class="h-10 w-auto" />
        </a>
        <button class="rounded p-2 focus:outline-none focus:ring-2 focus:ring-white/50"
                (click)="open = !open" aria-label="Abrir menú">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
          </svg>
        </button>
      </div>

      <!-- Mobile menu -->
      <div *ngIf="open" class="border-t border-white/10 md:hidden">

        <!-- Portfolio return link — mobile -->
        <a *ngIf="isPortfolioDemo"
           href="https://jonathan-hendrix.dev"
           class="flex items-center gap-2 px-4 py-3 hover:bg-white/10 border-b border-white/10">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
               viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
          </svg>
          Volver al portfolio
        </a>

        <a routerLink="/" class="block px-4 py-3 hover:bg-white/10" (click)="open=false; goHome($event)">Inicio</a>
        <a routerLink="/ingles-corporativo" class="block px-4 py-3 hover:bg-white/10" (click)="open=false">Inglés corporativo</a>
        <a routerLink="/examenes" class="block px-4 py-3 hover:bg-white/10" (click)="open=false">Exámenes oficiales</a>
        <a routerLink="/clases-espanol" class="block px-4 py-3 hover:bg-white/10" (click)="open=false">Español para extranjeros</a>
        <a routerLink="/catalog" class="block px-4 py-3 hover:bg-white/10" (click)="open=false">Catálogo de clases</a>

        <a *ngIf="!auth.loggedIn()" routerLink="/login" class="block px-4 py-3 hover:bg-white/10" (click)="open=false">Iniciar sesión</a>
        <a *ngIf="!auth.loggedIn()" routerLink="/register" class="block px-4 py-3 hover:bg-white/10" (click)="open=false">Registrarse</a>
        <a *ngIf="auth.loggedIn()" routerLink="/me" class="block px-4 py-3 hover:bg-white/10" (click)="open=false">Mi perfil</a>
        <a *ngIf="auth.loggedIn() && panelRoute() as pr" [routerLink]="pr" class="block px-4 py-3 hover:bg-white/10" (click)="open=false">
          {{ panelLabel() }}
        </a>
        <button *ngIf="auth.loggedIn()" class="block w-full text-left px-4 py-3 hover:bg-white/10" (click)="open=false; logout()">
          Cerrar sesión
        </button>
      </div>
    </nav>
  `
})
export class NavbarComponent implements OnDestroy {
  auth = inject(AuthStateService);
  private router = inject(Router);
  private host = inject(ElementRef<HTMLElement>);
  private destroy$ = new Subject<void>();

  open = false;
  accountOpen = false;

  /**
   * True when the app is mounted at /businessfirst/ (portfolio demo mode).
   * Drives the "Volver al portfolio" button visibility.
   */
  readonly isPortfolioDemo = document.querySelector('base')
    ?.getAttribute('href')
    ?.includes('/businessfirst') ?? false;

  constructor() {
    // Swap favicon when running as portfolio sub-path
    if (this.isPortfolioDemo) {
      this.swapFavicon('favicon.ico');
    }

    this.router.events
      .pipe(takeUntil(this.destroy$))
      .subscribe(e => {
        if (e instanceof NavigationEnd && e.urlAfterRedirects === '/') {
          window.scrollTo({ top: 0, behavior: 'smooth' });
        }
      });
  }

  ngOnDestroy(): void {
    this.destroy$.next();
    this.destroy$.complete();
  }

  /**
   * Swaps the browser tab favicon.
   *
   * @param href path to the favicon asset
   */
  private swapFavicon(href: string): void {
    let link = document.querySelector<HTMLLinkElement>('link[rel~="icon"]');
    if (!link) {
      link = document.createElement('link');
      link.rel = 'icon';
      document.head.appendChild(link);
    }
    link.href = href;
  }

  @HostListener('window:keydown', ['$event'])
  onKeydown(ev: KeyboardEvent) {
    if (ev.key === 'Escape') this.closeMenus();
  }

  @HostListener('document:click', ['$event'])
  onDocClick(ev: MouseEvent) {
    if (!this.accountOpen) return;
    const target = ev.target as Node | null;
    if (target && !this.host.nativeElement.contains(target)) this.accountOpen = false;
  }

  goHome(ev: MouseEvent) {
    const path = this.router.url.replace(/[?#].*$/, '');
    if (path === '/') {
      ev.preventDefault();
      window.scrollTo({ top: 0, behavior: 'smooth' });
      if (this.open) this.open = false;
    }
  }

  toggleAccountMenu() { this.accountOpen = !this.accountOpen; }
  closeMenus() { this.accountOpen = false; this.open = false; }

  private decodePayload(): any {
    const token = this.auth.getAccessToken();
    if (!token) return null;
    try {
      const first = token.indexOf('.');
      const second = token.indexOf('.', first + 1);
      if (first <= 0 || second <= first + 1) return null;
      const b64url = token.slice(first + 1, second);
      const pad = '==='.slice((b64url.length + 3) % 4);
      const json = atob((b64url + pad).replace(/-/g, '+').replace(/_/g, '/'));
      return JSON.parse(json);
    } catch { return null; }
  }

  userEmail(): string { return this.decodePayload()?.email ?? ''; }

  userRoles(): string[] {
    const r = this.decodePayload()?.roles;
    return Array.isArray(r) ? r : [];
  }

  fullName(): string {
    const p = this.decodePayload();
    const name = [p?.firstName, p?.lastName].filter(Boolean).join(' ').trim();
    return name || p?.email || 'Cuenta';
  }

  panelRoute(): string | null {
    const roles = this.userRoles();
    if (roles.includes('ROLE_ADMIN'))   return '/admin';
    if (roles.includes('ROLE_TEACHER')) return '/teacher';
    if (roles.includes('ROLE_STUDENT')) return '/student';
    return null;
  }

  panelLabel(): string {
    const roles = this.userRoles();
    if (roles.includes('ROLE_ADMIN'))   return 'Panel de administrador';
    if (roles.includes('ROLE_TEACHER')) return 'Panel de profesor';
    if (roles.includes('ROLE_STUDENT')) return 'Mi panel';
    return 'Panel';
  }

  logout() {
    this.auth.clearAuth();
    void this.router.navigateByUrl('/login');
  }
}
