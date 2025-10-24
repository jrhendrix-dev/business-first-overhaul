import { Component } from '@angular/core';
import { RouterOutlet } from '@angular/router';
import { NavbarComponent } from './layout/navbar.component';
import { FooterComponent } from './layout/footer.component';
import { DemoNoticeComponent } from './layout/demo-notice.component';

@Component({
  selector: 'app-root',
  standalone: true,
  imports: [RouterOutlet, NavbarComponent, FooterComponent, DemoNoticeComponent],
  template: `
    <app-demo-notice />
    <app-navbar />
    <main class="min-h-[70vh]">
      <router-outlet />
    </main>
    <app-footer />
  `
})
export class AppComponent {}
