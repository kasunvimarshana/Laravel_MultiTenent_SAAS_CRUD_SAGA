import { IPaymentService, ProcessPaymentParams, PaymentResult, RefundResult, Payment, PaymentFilters, PaginatedResult } from '../interfaces/IPaymentService';
import { IPaymentRepository } from '../interfaces/IPaymentRepository';
import { ILogger } from '../interfaces/ILogger';
export declare class PaymentService implements IPaymentService {
    private readonly repo;
    private readonly logger;
    constructor(repo: IPaymentRepository, logger: ILogger);
    processPayment(params: ProcessPaymentParams): Promise<PaymentResult>;
    refundPayment(paymentId: string, reason: string): Promise<RefundResult>;
    getPayment(paymentId: string): Promise<Payment | null>;
    listPayments(tenantId: string, filters: PaymentFilters): Promise<PaginatedResult<Payment>>;
}
//# sourceMappingURL=PaymentService.d.ts.map