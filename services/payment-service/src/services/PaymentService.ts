import { v4 as uuidv4 } from 'uuid';
import { IPaymentService, ProcessPaymentParams, PaymentResult, RefundResult, Payment, PaymentFilters, PaginatedResult } from '../interfaces/IPaymentService';
import { IPaymentRepository } from '../interfaces/IPaymentRepository';
import { ILogger } from '../interfaces/ILogger';
import { config } from '../config';

export class PaymentService implements IPaymentService {
  constructor(private readonly repo: IPaymentRepository, private readonly logger: ILogger) {}

  async processPayment(params: ProcessPaymentParams): Promise<PaymentResult> {
    const paymentId = uuidv4();
    this.logger.info('Processing payment', { paymentId, orderId: params.orderId, sagaId: params.sagaId, amount: params.amount });
    await this.repo.create({ _id: paymentId, status: 'PENDING', ...params });
    await this.repo.update(paymentId, { status: 'PROCESSING' });
    await new Promise((resolve) => setTimeout(resolve, config.payment.processingDelayMs));
    const isSuccess = Math.random() < config.payment.successRate;
    if (isSuccess) {
      const transactionId = `txn_${uuidv4().replace(/-/g, '')}`;
      await this.repo.update(paymentId, { status: 'COMPLETED', transactionId, processedAt: new Date() });
      this.logger.info('Payment completed', { paymentId, transactionId });
      return { success: true, paymentId, transactionId };
    } else {
      const errorCode = 'PAYMENT_DECLINED';
      const errorMessage = 'Payment declined by payment processor';
      await this.repo.update(paymentId, { status: 'FAILED', errorCode, errorMessage });
      this.logger.warn('Payment failed', { paymentId, errorCode });
      return { success: false, paymentId, errorCode, errorMessage };
    }
  }

  async refundPayment(paymentId: string, reason: string): Promise<RefundResult> {
    this.logger.info('Processing refund', { paymentId, reason });
    const payment = await this.repo.findById(paymentId);
    if (!payment) return { success: false, refundId: '', paymentId, amount: 0, errorCode: 'PAYMENT_NOT_FOUND', errorMessage: `Payment ${paymentId} not found` };
    if (payment.status !== 'COMPLETED' && payment.status !== 'PARTIALLY_REFUNDED') return { success: false, refundId: '', paymentId, amount: 0, errorCode: 'INVALID_PAYMENT_STATUS', errorMessage: `Cannot refund payment in status ${payment.status}` };
    const refundId = uuidv4();
    const now = new Date();
    await this.repo.addRefund(paymentId, { refundId, amount: payment.amount, reason, refundedAt: now });
    await this.repo.update(paymentId, { status: 'REFUNDED', refundedAt: now });
    this.logger.info('Refund completed', { paymentId, refundId, amount: payment.amount });
    return { success: true, refundId, paymentId, amount: payment.amount };
  }

  async getPayment(paymentId: string): Promise<Payment | null> { return this.repo.findById(paymentId); }
  async listPayments(tenantId: string, filters: PaymentFilters): Promise<PaginatedResult<Payment>> { return this.repo.findByTenant(tenantId, filters); }
}
