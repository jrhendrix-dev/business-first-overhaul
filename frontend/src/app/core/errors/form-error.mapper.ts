import { Injectable } from '@angular/core';
import { FormGroup } from '@angular/forms';
import { HttpErrorResponse } from '@angular/common/http';
import { ToastService } from '@app/core/ui/toast/toast.service';

@Injectable({ providedIn: 'root' })
export class FormErrorMapper {
  normalize(raw: unknown): any {
    let e: any = (raw as any)?.error ?? raw;
    if (typeof e === 'string') { try { e = JSON.parse(e); } catch { e = { message: e }; } }
    if (e?.error && typeof e.error === 'object') e = e.error;
    return e ?? {};
  }

  private isDuplicateErr(text: string) {
    return /(duplicate entry|sqlstate\[23000\]|1062)/i.test(text);
  }
  private fieldFromDuplicate(text: string): 'email' | 'userName' | null {
    const t = text.toLowerCase();
    if (/(?:for key .*email|`email`|\bemail\b|uniq.*email)/i.test(t)) return 'email';
    if (/(?:for key .*user|`user_name`|`username`|\busername\b|uniq.*user)/i.test(t)) return 'userName';
    return null;
  }
  private detectTokens(text: string) {
    const t = text.toLowerCase();
    const emailTaken =
      /(email|correo)[^\n]*?(ya\s*est[aá]\s*en\s*uso|ocupado|en\s*uso|taken|in\s*use|exist[es]|used)/.test(t) ||
      t.includes('email_taken');
    const usernameTaken =
      /(usuario|nombre\s*de\s*usuario|username|user\s*name)[^\n]*?(ya\s*existe|ocupado|en\s*uso|taken|in\s*use|exist[es]|used)/.test(t) ||
      t.includes('username_taken');
    const emailGeneric = /\b(email|correo)\b/.test(t);
    return { emailTaken, usernameTaken, emailGeneric };
  }

  applyToForm<T extends string>(
    form: FormGroup,
    fieldMap: Record<string, T>,
    err: HttpErrorResponse,
    onSticky?: (ctrl: T) => void,
  ): { applied: boolean; norm: any } {
    const norm = this.normalize(err);
    let applied = false;
    const setField = (key: T, message: string) => {
      const c = form.get(key as string);
      if (!c) return;
      c.setErrors({ ...(c.errors ?? {}), server: message });
      c.markAsTouched(); c.markAsDirty();
      c.updateValueAndValidity({ onlySelf: true, emitEvent: false });
      onSticky?.(key);
      applied = true;
    };

    // details
    const details = norm.details;
    if (details && typeof details === 'object') {
      for (const [field, raw] of Object.entries(details as Record<string, string | string[]>)) {
        if (field === 'message') continue;
        const msgs = Array.isArray(raw) ? raw : [raw];
        for (const m of msgs) setField(fieldMap[field] ?? (field as T), String(m || 'Valor inválido.'));
      }
      const dm = (details as any).message;
      const dmMsgs = typeof dm === 'string' ? [dm] : Array.isArray(dm) ? dm : [];
      for (const m of dmMsgs) {
        const { emailTaken, usernameTaken, emailGeneric } = this.detectTokens(String(m));
        if (usernameTaken) setField(fieldMap['userName'] ?? ('userName' as T), 'Este nombre de usuario ya existe.');
        if (emailTaken)    setField(fieldMap['email']    ?? ('email'    as T), 'Este email ya está en uso.');
        if (!emailTaken && emailGeneric) setField(fieldMap['email'] ?? ('email' as T), 'Email inválido.');
      }
    }

    // violations
    if (Array.isArray(norm.violations)) {
      for (const v of norm.violations) {
        const key = fieldMap[v?.propertyPath ?? ''] ?? (v?.propertyPath as T);
        if (key) setField(key, v?.message || 'Valor inválido.');
      }
    }

    // errors
    const obj = norm.errors;
    if (obj && typeof obj === 'object') {
      for (const [field, raw] of Object.entries(obj as Record<string, string[] | string>)) {
        const msgs = Array.isArray(raw) ? raw : [raw];
        for (const m of msgs) setField(fieldMap[field] ?? (field as T), String(m || 'Valor inválido.'));
      }
    }

    // —— robust fallbacks (covers Edit case returning plain SQLSTATE) ——
    const parts: string[] = [];
    if (norm.code) parts.push(String(norm.code));
    if (norm.message) parts.push(String(norm.message));
    if (err?.message) parts.push(String(err.message));         // 👈 include HttpErrorResponse.message
    try { parts.push(JSON.stringify(err?.error ?? err ?? {})); } catch {}

    const combined = parts.join(' | ');
    if (combined) {
      const { emailTaken, usernameTaken, emailGeneric } = this.detectTokens(combined);
      if (usernameTaken) setField(fieldMap['userName'] ?? ('userName' as T), 'Este nombre de usuario ya existe.');
      if (emailTaken)    setField(fieldMap['email']    ?? ('email'    as T), 'Este email ya está en uso.');
      if (!emailTaken && emailGeneric) setField(fieldMap['email'] ?? ('email' as T), 'Email inválido.');
      if (this.isDuplicateErr(combined)) {
        const fld = this.fieldFromDuplicate(combined);
        if (fld === 'email')    setField(fieldMap['email']    ?? ('email'    as T), 'Este email ya está en uso.');
        if (fld === 'userName') setField(fieldMap['userName'] ?? ('userName' as T), 'Este nombre de usuario ya existe.');
      }
    }

    return { applied, norm };
  }

  toastFromDetails(norm: any, toast: ToastService, raw?: HttpErrorResponse) {
    let emitted = false;
    const d = norm.details;

    if (d && typeof d === 'object') {
      for (const [field, val] of Object.entries(d as Record<string, string | string[]>)) {
        if (field === 'message') continue;
        const msgs = Array.isArray(val) ? val : [val];
        for (const m of msgs) { if (m) { toast.add(String(m), 'error'); emitted = true; } }
      }
      const dm = (d as any).message;
      const dmMsgs = typeof dm === 'string' ? [dm] : Array.isArray(dm) ? dm : [];
      for (const m of dmMsgs) { if (m) { toast.add(String(m), 'error'); emitted = true; } }
    }

    const pool = [
      String(norm?.message ?? ''),
      String(norm?.code ?? ''),
      String(raw?.message ?? ''),
      (() => { try { return JSON.stringify(raw?.error ?? raw ?? {}); } catch { return ''; } })(),
    ].join(' | ');

    if (!emitted) {
      if (this.isDuplicateErr(pool)) {
        const fld = this.fieldFromDuplicate(pool);
        toast.add(fld === 'email' ? 'Este email ya está en uso.' : 'Este nombre de usuario ya existe.', 'error');
        emitted = true;
      }
    }
    if (!emitted) {
      const t = pool.toLowerCase();
      if (t.includes('username')) { toast.add('Este nombre de usuario ya existe.', 'error'); emitted = true; }
      if (t.includes('email'))    { toast.add('Este email ya está en uso.', 'error'); emitted = true; }
    }
    if (!emitted) toast.add('No se pudo completar la operación.', 'error');
  }
}
