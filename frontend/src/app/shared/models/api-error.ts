export interface ApiErrorPayload {
  error: {
    code: string; // e.g. "VALIDATION_FAILED"
    details: Record<string, string>; // e.g. { email: "Invalid" }
  };
}
