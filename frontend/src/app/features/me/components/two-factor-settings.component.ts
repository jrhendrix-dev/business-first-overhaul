// src/app/features/me/components/two-factor-settings.component.ts
import { Component, ChangeDetectionStrategy, Input, inject, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { TwoFactorApi } from '@/app/core/auth/two-factor.api';
import { ToastService } from '@/app/core/ui/toast/toast.service';
import { TwoFactorSetupDialogComponent } from '@/app/core/auth/ui/two-factor-setup-dialog.component';
import { RecoveryCodesDialogComponent } from '@/app/core/auth/ui/recovery-codes-dialog.component';
import { SectionCardComponent } from '@shared/ui/section-card.component';

@Component({
  standalone: true,
  selector: 'app-two-factor-settings-card',
  imports: [CommonModule, SectionCardComponent, TwoFactorSetupDialogComponent, RecoveryCodesDialogComponent],
  changeDetection: ChangeDetectionStrategy.OnPush,
  template: `
    <app-section-card
      [title]="'Verificación en dos pasos (2FA)'"
      [subtitle]="'Añade una capa extra de seguridad a tu cuenta.'">

      <!-- status pill projected to header-right -->
      <span header-right
            class="text-xs rounded-full px-2 py-1"
            [class.bg-emerald-100]="enabled()" [class.text-emerald-800]="enabled()"
            [class.bg-slate-100]="!enabled()" [class.text-slate-800]="!enabled()">
        {{ enabled() ? 'ACTIVADA' : 'DESACTIVADA' }}
      </span>

      <div class="px-6 py-5">
        <ng-container *ngIf="!enabled(); else enabledBlock">
          <p class="text-sm text-slate-600 mb-4">
            Protege tu cuenta con códigos temporales de un solo uso (TOTP) desde una app autenticadora.
          </p>
          <button class="btn btn-primary btn-raise" (click)="startSetup()">Activar 2FA</button>
        </ng-container>

        <ng-template #enabledBlock>
          <div class="flex flex-wrap gap-3">
            <button class="btn btn-outline-muted btn-raise" (click)="showCodes()">Ver códigos de recuperación</button>
            <button class="btn btn-secondary btn-raise" (click)="regenerate()">Regenerar códigos</button>
            <button class="btn btn-danger btn-raise" (click)="disable()">Desactivar 2FA</button>
          </div>
        </ng-template>
      </div>
    </app-section-card>

    <app-two-factor-setup-dialog *ngIf="setupOpen()"
                                 (success)="onSetupSuccess($event)" (cancel)="setupOpen.set(false)"></app-two-factor-setup-dialog>

    <app-recovery-codes-dialog *ngIf="codesOpen()" [codes]="codes()" (close)="codesOpen.set(false)"></app-recovery-codes-dialog>
  `
})
export class TwoFactorSettingsComponent {
  private api = inject(TwoFactorApi);
  private toast = inject(ToastService);

  @Input() set initialEnabled(v: boolean | null | undefined) { this.enabled.set(!!v); }

  enabled   = signal(false);
  setupOpen = signal(false);
  codesOpen = signal(false);
  codes     = signal<string[]>([]);

  startSetup() { this.setupOpen.set(true); }
  onSetupSuccess(recoveryCodes: string[]) {
    this.setupOpen.set(false);
    this.enabled.set(true);
    this.codes.set(recoveryCodes);
    this.codesOpen.set(true);
  }

  showCodes() {
    this.toast.info('Por seguridad no guardamos los códigos. Puedes regenerarlos si los perdiste.');
  }

  regenerate() {
    this.api.regenerate().subscribe({
      next: r => { this.codes.set(r.recoveryCodes); this.codesOpen.set(true); this.toast.success('Códigos regenerados'); },
      error: () => this.toast.error('No se pudieron regenerar los códigos')
    });
  }

  disable() {
    this.api.disable().subscribe({
      next: () => { this.enabled.set(false); this.toast.success('2FA desactivado'); },
      error: () => this.toast.error('No se pudo desactivar 2FA')
    });
  }
}
