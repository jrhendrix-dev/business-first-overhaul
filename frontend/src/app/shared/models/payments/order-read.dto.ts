export type OrderItemDto = {
  id: number;
  createdAt: string;
  amountCents: number;
  currency: string;
  status: 'STATUS_PENDING' | 'STATUS_PAID' | 'STATUS_FAILED' | string;
  provider: string;
  sessionId?: string | null;
  paymentIntentId?: string | null;
  classroomId: number;
  classroomName?: string | null;
  student: { id: number; firstName: string; lastName: string; email: string };
};

export type OrdersListDto = { items: OrderItemDto[]; limit: number; offset: number };
