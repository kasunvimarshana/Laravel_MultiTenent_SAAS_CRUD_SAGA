import { Payment, ProcessPaymentParams, PaymentFilters, PaginatedResult, PaymentStatus, Refund } from './IPaymentService';
export interface CreatePaymentData extends ProcessPaymentParams {
    _id: string;
    status: PaymentStatus;
}
export interface UpdatePaymentData {
    status?: PaymentStatus;
    transactionId?: string;
    errorCode?: string;
    errorMessage?: string;
    processedAt?: Date;
    refundedAt?: Date;
}
export interface IPaymentRepository {
    findById(id: string): Promise<Payment | null>;
    findByOrderId(orderId: string, tenantId: string): Promise<Payment | null>;
    findBySagaId(sagaId: string): Promise<Payment | null>;
    create(data: CreatePaymentData): Promise<Payment>;
    update(id: string, data: UpdatePaymentData): Promise<Payment | null>;
    addRefund(id: string, refund: Refund): Promise<Payment | null>;
    findByTenant(tenantId: string, filters: PaymentFilters): Promise<PaginatedResult<Payment>>;
}
//# sourceMappingURL=IPaymentRepository.d.ts.map