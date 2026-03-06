export type PaymentStatus = 'PENDING' | 'PROCESSING' | 'COMPLETED' | 'FAILED' | 'REFUNDED' | 'PARTIALLY_REFUNDED';
export type PaymentMethodType = 'CREDIT_CARD' | 'DEBIT_CARD' | 'BANK_TRANSFER';
export interface PaymentMethod {
    type: PaymentMethodType;
    last4Digits?: string;
    bankCode?: string;
}
export interface Refund {
    refundId: string;
    amount: number;
    reason: string;
    refundedAt: Date;
}
export interface Payment {
    _id: string;
    tenantId: string;
    sagaId: string;
    orderId: string;
    customerId: string;
    amount: number;
    currency: string;
    status: PaymentStatus;
    paymentMethod: PaymentMethod;
    transactionId?: string;
    refunds: Refund[];
    errorCode?: string;
    errorMessage?: string;
    processedAt?: Date;
    refundedAt?: Date;
    createdAt: Date;
    updatedAt: Date;
}
export interface ProcessPaymentParams {
    tenantId: string;
    sagaId: string;
    orderId: string;
    customerId: string;
    amount: number;
    currency?: string;
    paymentMethod: PaymentMethod;
}
export interface PaymentResult {
    success: boolean;
    paymentId: string;
    transactionId?: string;
    errorCode?: string;
    errorMessage?: string;
}
export interface RefundResult {
    success: boolean;
    refundId: string;
    paymentId: string;
    amount: number;
    errorCode?: string;
    errorMessage?: string;
}
export interface PaymentFilters {
    status?: PaymentStatus;
    orderId?: string;
    customerId?: string;
    page?: number;
    limit?: number;
}
export interface PaginatedResult<T> {
    data: T[];
    total: number;
    page: number;
    limit: number;
    totalPages: number;
}
export interface IPaymentService {
    processPayment(params: ProcessPaymentParams): Promise<PaymentResult>;
    refundPayment(paymentId: string, reason: string): Promise<RefundResult>;
    getPayment(paymentId: string): Promise<Payment | null>;
    listPayments(tenantId: string, filters: PaymentFilters): Promise<PaginatedResult<Payment>>;
}
//# sourceMappingURL=IPaymentService.d.ts.map