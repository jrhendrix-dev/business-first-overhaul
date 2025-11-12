import { HttpErrorResponse, HttpInterceptorFn } from '@angular/common/http';
import { catchError, throwError } from 'rxjs';
import { ApiErrorPayload } from '@/app/shared/models/api-error';

export const errorInterceptor: HttpInterceptorFn = (req, next) =>
  next(req).pipe(
    catchError((err: unknown) => {
      if (err instanceof HttpErrorResponse) {
        const api = err.error as Partial<ApiErrorPayload> | undefined;

        // Surface a normalized error object to the app
        const normalized = {
          status: err.status,
          code: api?.error?.code ?? 'HTTP_ERROR',
          details: api?.error?.details ?? {},
          url: err.url ?? undefined,
        } as const;

        return throwError(() => normalized);
      }
      return throwError(() => ({ status: 0, code: 'NETWORK_ERROR', details: {} }));
    })
  );
