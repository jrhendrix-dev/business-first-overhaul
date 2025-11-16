import {
  ChangeDetectionStrategy,
  Component,
  inject,
} from '@angular/core';
import { CommonModule } from '@angular/common';
import {
  FormBuilder,
  ReactiveFormsModule,
  Validators,
} from '@angular/forms';
import { RouterLink } from '@angular/router';

@Component({
  standalone: true,
  selector: 'app-contact',
  templateUrl: './contact.page.html',
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [CommonModule, ReactiveFormsModule, RouterLink],
})
export class ContactPage {
  private fb = inject(FormBuilder);

  readonly form = this.fb.group({
    name: ['', [Validators.required, Validators.minLength(2)]],
    email: ['', [Validators.required, Validators.email]],
    phone: [''],
    message: ['', [Validators.required, Validators.minLength(10)]],
  });

  isSubmitting = false;
  submitted = false;
  submitError: string | null = null;

  onSubmit(): void {
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }

    this.isSubmitting = true;
    this.submitError = null;

    const { name, email, phone, message } = this.form.value;

    // Simple, no-backend implementation: open email client
    const bodyLines = [
      `Nombre: ${name}`,
      `Email: ${email}`,
      phone ? `Teléfono: ${phone}` : '',
      '',
      'Mensaje:',
      message ?? '',
    ].filter(Boolean);

    const subject = encodeURIComponent('Consulta desde la web Business First');
    const body = encodeURIComponent(bodyLines.join('\n'));

    // Uses the same email you show in the navbar
    window.location.href =
      `mailto:jrhendrixdev@gmail.com?subject=${subject}&body=${body}`;

    this.isSubmitting = false;
    this.submitted = true;
  }

  hasError(control: 'name' | 'email' | 'phone' | 'message', error: string): boolean {
    const c = this.form.get(control);
    return !!c && c.touched && c.hasError(error);
  }
}
