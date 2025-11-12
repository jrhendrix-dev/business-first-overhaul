import { Component, ChangeDetectionStrategy, OnInit, inject, computed, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ReactiveFormsModule, NonNullableFormBuilder, Validators } from '@angular/forms';
import { TwoFactorApi, SetupInitiate } from '@/app/core/auth/two-factor.api';
import { ToastService } from '@/app/core/ui/toast/toast.service';
import { AdminHeaderComponent } from '@app/core/ui/admin-header.component'; // you already have this

@Component({
  standalone: true,
  selector: 'app-two-factor',
  imports: [CommonModule, ReactiveFormsModule, AdminHeaderComponent],
  changeDetection: ChangeDetectionStrategy.OnPush,
  template: `
  <app-admin-header title="Two-factor authentication"></app-admin-header>

  <div class="max-w-3xl mx-auto mt-6 grid md:grid-cols-2 gap-8">
    <div class="space-y-4">
      <h3 class="font-semibold">Step 1 — Scan the QR</h3>
      <div *ngIf="state().qrPng; else startBlock">
        <img [src]="state().qrPng!" alt="TOTP QR" class="border rounded-lg p-2 bg-white">
        <div class="text-xs text-slate-600 break-all">
          <div class="mt-2"><span class="font-semibold">Secret:</span> {{ state().secret }}</div>
        </div>
      </div>
      <ng-template #startBlock>
        <p class="text-sm text-slate-600">Click “Generate QR” to begin.</p>
      </ng-template>

      <button class="px-4 py-2 rounded bg-[color:var(--brand)] text-white"
              (click)="generate()" [disabled]="loading()">Generate QR</button>
    </div>

    <div class="space-y-4">
      <h3 class="font-semibold">Step 2 — Confirm with a code</h3>
      <form [formGroup]="form" (ngSubmit)="confirm()" class="space-y-3">
        <div>
          <label class="block text-xs text-slate-600">TOTP Code</label>
          <input class="border rounded px-3 py-2 w-full" formControlName="code" placeholder="123456">
        </div>

        <div class="flex items-center gap-2">
          <button class="px-4 py-2 rounded bg-[color:var(--brand)] text-white" type="submit"
                  [disabled]="loading()">Confirm & Enable</button>
          <button type="button" class="px-4 py-2 rounded border" (click)="disable()" [disabled]="!enabled()">Disable</button>
        </div>
      </form>

      <div *ngIf="recoveryCodes().length" class="mt-4">
        <h4 class="font-semibold mb-1">Recovery codes</h4>
        <p class="text-xs text-slate-600 mb-2">Save these in a safe place. Each can be used once.</p>
        <ul class="text-sm bg-slate-50 border rounded p-3 space-y-1">
          <li *ngFor="let c of recoveryCodes()">{{ c }}</li>
        </ul>
        <button class="mt-3 px-4 py-2 rounded border" (click)="regenerate()">Generate new codes</button>
      </div>
    </div>
  </div>
  `
})
export class TwoFactorPage implements OnInit {
  private api = inject(TwoFactorApi);
  private fb = inject(NonNullableFormBuilder);
  private toast = inject(ToastService);

  form = this.fb.group({ code: this.fb.control('', [Validators.required]) });

  private _loading = signal(false);
  loading = computed(() => this._loading());
  private _enabled = signal(false);
  enabled = computed(() => this._enabled());

  private _state = signal<Partial<SetupInitiate>>({});
  state = computed(() => this._state());
  private _recoveryCodes = signal<string[]>([]);
  recoveryCodes = computed(() => this._recoveryCodes());

  ngOnInit(): void {}

  generate(): void {
    this._loading.set(true);
    this.api.initiate().subscribe({
      next: res => { this._state.set(res); this._loading.set(false); },
      error: e => { this._loading.set(false); this.toast.error('Could not generate QR'); }
    });
  }

  confirm(): void {
    if (this.form.invalid) {
      this.toast.error('Enter the 6-digit code');
      return;
    }
    this._loading.set(true);
    this.api.confirm(this.form.controls.code.value).subscribe({
      next: res => {
        this._enabled.set(true);
        this._recoveryCodes.set(res.recoveryCodes);
        this.toast.success('Two-factor enabled');
        this._loading.set(false);
      },
      error: e => {
        const m = e?.error?.error;
        if (m?.code === 'VALIDATION_FAILED') {
          this.toast.error(Object.values(m.details).join(', '));
        } else {
          this.toast.error('Invalid TOTP code');
        }
        this._loading.set(false);
      }
    });
  }

  disable(): void {
    this.api.disable().subscribe({
      next: _ => { this._enabled.set(false); this._state.set({}); this._recoveryCodes.set([]); this.toast.success('Two-factor disabled'); },
      error: _ => this.toast.error('Could not disable 2FA')
    });
  }

  regenerate(): void {
    this.api.regenerate().subscribe({
      next: res => { this._recoveryCodes.set(res.recoveryCodes); this.toast.success('New recovery codes generated'); },
      error: _ => this.toast.error('Could not regenerate codes')
    });
  }
}
