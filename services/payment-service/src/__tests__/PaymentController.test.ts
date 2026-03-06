import { Response, NextFunction, Request } from 'express';
import { PaymentController } from '../http/controllers/PaymentController';
import { IPaymentService, Payment, PaginatedResult } from '../interfaces/IPaymentService';
import { ILogger } from '../interfaces/ILogger';
import { TenantRequest } from '../http/middleware/tenantMiddleware';

const mockPayment: Payment = { _id: 'pay-1', tenantId: 't-1', sagaId: 's-1', orderId: 'o-1', customerId: 'c-1', amount: 150, currency: 'USD', status: 'COMPLETED', paymentMethod: { type: 'CREDIT_CARD', last4Digits: '4242' }, refunds: [], createdAt: new Date(), updatedAt: new Date() };
const mockSvc: jest.Mocked<IPaymentService> = { processPayment: jest.fn(), refundPayment: jest.fn(), getPayment: jest.fn(), listPayments: jest.fn() };
const mockLogger: ILogger = { info: jest.fn(), error: jest.fn(), warn: jest.fn(), debug: jest.fn() };

function mkReqRes(overrides: Partial<TenantRequest> = {}) {
  const req = { params: {}, query: {}, body: {}, headers: {}, tenantId: 't-1', ...overrides } as TenantRequest;
  const res = { json: jest.fn().mockReturnThis(), status: jest.fn().mockReturnThis() } as unknown as Response;
  const next: NextFunction = jest.fn();
  return { req, res, next };
}

describe('PaymentController', () => {
  let ctrl: PaymentController;
  beforeEach(() => { jest.clearAllMocks(); ctrl = new PaymentController(mockSvc, mockLogger); });

  it('getPayment returns 200 with data', async () => {
    mockSvc.getPayment.mockResolvedValue(mockPayment);
    const { req, res, next } = mkReqRes({ params: { id: 'pay-1' } });
    await ctrl.getPayment(req as Request, res, next);
    expect(res.json).toHaveBeenCalledWith({ success: true, data: mockPayment });
  });
  it('getPayment returns 404 when not found', async () => {
    mockSvc.getPayment.mockResolvedValue(null);
    const { req, res, next } = mkReqRes({ params: { id: 'x' } });
    await ctrl.getPayment(req as Request, res, next);
    expect(res.status).toHaveBeenCalledWith(404);
  });
  it('getPayment returns 403 on tenant mismatch', async () => {
    mockSvc.getPayment.mockResolvedValue({ ...mockPayment, tenantId: 'other' });
    const { req, res, next } = mkReqRes({ params: { id: 'pay-1' }, tenantId: 't-1' });
    await ctrl.getPayment(req as Request, res, next);
    expect(res.status).toHaveBeenCalledWith(403);
  });
  it('listPayments returns paginated list', async () => {
    const paged: PaginatedResult<Payment> = { data: [mockPayment], total: 1, page: 1, limit: 20, totalPages: 1 };
    mockSvc.listPayments.mockResolvedValue(paged);
    const { req, res, next } = mkReqRes();
    await ctrl.listPayments(req as Request, res, next);
    expect(res.json).toHaveBeenCalledWith(expect.objectContaining({ success: true }));
  });
  it('processPayment returns 201 on success', async () => {
    mockSvc.processPayment.mockResolvedValue({ success: true, paymentId: 'pay-new', transactionId: 'txn-abc' });
    const { req, res, next } = mkReqRes({ body: {} });
    await ctrl.processPayment(req as Request, res, next);
    expect(res.status).toHaveBeenCalledWith(201);
  });
  it('processPayment returns 402 on decline', async () => {
    mockSvc.processPayment.mockResolvedValue({ success: false, paymentId: 'pay-new', errorCode: 'PAYMENT_DECLINED' });
    const { req, res, next } = mkReqRes({ body: {} });
    await ctrl.processPayment(req as Request, res, next);
    expect(res.status).toHaveBeenCalledWith(402);
  });
  it('refundPayment returns success', async () => {
    mockSvc.getPayment.mockResolvedValue(mockPayment);
    mockSvc.refundPayment.mockResolvedValue({ success: true, refundId: 'ref-1', paymentId: 'pay-1', amount: 150 });
    const { req, res, next } = mkReqRes({ params: { id: 'pay-1' }, body: { reason: 'Customer request' } });
    await ctrl.refundPayment(req as Request, res, next);
    expect(res.json).toHaveBeenCalledWith(expect.objectContaining({ success: true }));
  });
});
