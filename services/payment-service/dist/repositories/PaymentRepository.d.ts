import { IPaymentRepository, CreatePaymentData, UpdatePaymentData } from '../interfaces/IPaymentRepository';
import { Payment, PaymentFilters, PaginatedResult, Refund } from '../interfaces/IPaymentService';
export declare class PaymentRepository implements IPaymentRepository {
    findById(id: string): Promise<Payment | null>;
    findByOrderId(orderId: string, tenantId: string): Promise<Payment | null>;
    findBySagaId(sagaId: string): Promise<Payment | null>;
    create(data: CreatePaymentData): Promise<Payment>;
    update(id: string, data: UpdatePaymentData): Promise<Payment | null>;
    addRefund(id: string, refund: Refund): Promise<Payment | null>;
    findByTenant(tenantId: string, filters: PaymentFilters): Promise<PaginatedResult<Payment>>;
}
//# sourceMappingURL=PaymentRepository.d.ts.map