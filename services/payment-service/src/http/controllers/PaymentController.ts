import { Response, NextFunction, Request } from 'express';
import { IPaymentService, PaymentFilters } from '../../interfaces/IPaymentService';
import { ILogger } from '../../interfaces/ILogger';
import { TenantRequest } from '../middleware/tenantMiddleware';

export class PaymentController {
  constructor(private readonly paymentService: IPaymentService, private readonly logger: ILogger) {}

  listPayments = async (req: TenantRequest, res: Response, next: NextFunction): Promise<void> => {
    try {
      const filters: PaymentFilters = { status: req.query['status'] as PaymentFilters['status'], orderId: req.query['orderId'] as string | undefined, customerId: req.query['customerId'] as string | undefined, page: req.query['page'] ? parseInt(req.query['page'] as string, 10) : undefined, limit: req.query['limit'] ? parseInt(req.query['limit'] as string, 10) : undefined };
      const result = await this.paymentService.listPayments(req.tenantId!, filters);
      res.json({ success: true, ...result });
    } catch (err) { next(err); }
  };

  getPayment = async (req: TenantRequest, res: Response, next: NextFunction): Promise<void> => {
    try {
      const payment = await this.paymentService.getPayment(req.params['id']!);
      if (!payment) { res.status(404).json({ success: false, error: 'Payment not found', code: 'NOT_FOUND' }); return; }
      if (payment.tenantId !== req.tenantId) { res.status(403).json({ success: false, error: 'Forbidden', code: 'FORBIDDEN' }); return; }
      res.json({ success: true, data: payment });
    } catch (err) { next(err); }
  };

  processPayment = async (req: TenantRequest, res: Response, next: NextFunction): Promise<void> => {
    try {
      const result = await this.paymentService.processPayment({ tenantId: req.tenantId!, ...req.body });
      res.status(result.success ? 201 : 402).json({ success: result.success, data: result });
    } catch (err) { next(err); }
  };

  refundPayment = async (req: TenantRequest, res: Response, next: NextFunction): Promise<void> => {
    try {
      const paymentId = req.params['id']!;
      const payment = await this.paymentService.getPayment(paymentId);
      if (!payment) { res.status(404).json({ success: false, error: 'Payment not found', code: 'NOT_FOUND' }); return; }
      if (payment.tenantId !== req.tenantId) { res.status(403).json({ success: false, error: 'Forbidden', code: 'FORBIDDEN' }); return; }
      const result = await this.paymentService.refundPayment(paymentId, (req.body as { reason: string }).reason);
      if (!result.success) { res.status(400).json({ success: false, data: result }); return; }
      res.json({ success: true, data: result });
    } catch (err) { next(err); }
  };
}
