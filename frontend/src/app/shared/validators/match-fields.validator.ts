// src/app/shared/validators/match-fields.validator.ts
import { AbstractControl, ValidationErrors, ValidatorFn } from '@angular/forms';

export function matchFields(a: string, b: string): ValidatorFn {
  return (group: AbstractControl): ValidationErrors | null => {
    const v1 = group.get(a)?.value;
    const v2 = group.get(b)?.value;
    return v1 === v2 ? null : { fieldsMustMatch: { [a]: a, [b]: b } };
  };
}
