import { PaymentService } from '../services/PaymentService';
import { IPaymentRepository } from '../interfaces/IPaymentRepository';
import { Payment } from '../interfaces/IPaymentService';
import { ILogger } from '../interfaces/ILogger';

const mockPayment: Payment = { _id: 'pay-1', tenantId: 't-1', sagaId: 's-1', orderId: 'o-1', customerId: 'c-1', amount: 100, currency: 'USD', status: 'COMPLETED', paymentMethod: { type: 'CREDIT_CARD', last4Digits: '4242' }, refunds: [], createdAt: new Date(), updatedAt: new Date() };
const mockRepo: jest.Mocked<IPaymentRepository> = { findById: jest.fn(), findByOrderId: jest.fn(), findBySagaId: jest.fn(), create: jest.fn(), update: jest.fn(), addRefund: jest.fn(), findByTenant: jest.fn() };
const mockLogger: ILogger = { info: jest.fn(), error: jest.fn(), warn: jest.fn(), debug: jest.fn() };

describe('PaymentService', () => {
  let service: PaymentService;
  beforeEach(() => { jest.clearAllMocks(); service = new PaymentService(mockRepo, mockLogger); });

  describe('processPayment', () => {
    it('returns success result on success', async () => {
      jest.spyOn(Math, 'random').mockReturnValue(0.05);
      mockRepo.create.mockResolvedValue({ ...mockPayment, status: 'PENDING' });
      mockRepo.update.mockResolvedValue({ ...mockPayment, status: 'COMPLETED' });
      const result = await service.processPayment({ tenantId: 't-1', sagaId: 's-1', orderId: 'o-1', customerId: 'c-1', amount: 100, paymentMethod: { type: 'CREDIT_CARD', last4Digits: '4242' } });
      expect(result.success).toBe(true);
      expect(result.transactionId).toBeDefined();
      expect(mockRepo.create).toHaveBeenCalledTimes(1);
    });
    it('returns failure when declined', async () => {
      jest.spyOn(Math, 'random').mockReturnValue(0.99);
      mockRepo.create.mockResolvedValue({ ...mockPayment, status: 'PENDING' });
      mockRepo.update.mockResolvedValue({ ...mockPayment, status: 'FAILED' });
      const result = await service.processPayment({ tenantId: 't-1', sagaId: 's-1', orderId: 'o-1', customerId: 'c-1', amount: 100, paymentMethod: { type: 'CREDIT_CARD' } });
      expect(result.success).toBe(false);
      expect(result.errorCode).toBe('PAYMENT_DECLINED');
    });
  });

  describe('refundPayment', () => {
    it('successfully refunds completed payment', async () => {
      mockRepo.findById.mockResolvedValue(mockPayment);
      mockRepo.addRefund.mockResolvedValue({ ...mockPayment, status: 'REFUNDED' });
      mockRepo.update.mockResolvedValue({ ...mockPayment, status: 'REFUNDED' });
      const result = await service.refundPayment('pay-1', 'Customer request');
      expect(result.success).toBe(true);
      expect(result.amount).toBe(100);
    });
    it('fails if payment not found', async () => {
      mockRepo.findById.mockResolvedValue(null);
      const result = await service.refundPayment('nope', 'test');
      expect(result.success).toBe(false);
      expect(result.errorCode).toBe('PAYMENT_NOT_FOUND');
    });
    it('fails if payment in PENDING status', async () => {
      mockRepo.findById.mockResolvedValue({ ...mockPayment, status: 'PENDING' });
      const result = await service.refundPayment('pay-1', 'test');
      expect(result.success).toBe(false);
      expect(result.errorCode).toBe('INVALID_PAYMENT_STATUS');
    });
  });

  describe('getPayment', () => {
    it('returns payment if found', async () => { mockRepo.findById.mockResolvedValue(mockPayment); expect(await service.getPayment('pay-1')).toEqual(mockPayment); });
    it('returns null if not found', async () => { mockRepo.findById.mockResolvedValue(null); expect(await service.getPayment('x')).toBeNull(); });
  });

  describe('listPayments', () => {
    it('returns paginated results', async () => {
      mockRepo.findByTenant.mockResolvedValue({ data: [mockPayment], total: 1, page: 1, limit: 20, totalPages: 1 });
      const result = await service.listPayments('t-1', { page: 1, limit: 20 });
      expect(result.data).toHaveLength(1);
    });
  });
});
