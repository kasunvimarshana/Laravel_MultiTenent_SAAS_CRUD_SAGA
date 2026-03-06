import { Router } from 'express';
import Joi from 'joi';
import { PaymentController } from '../controllers/PaymentController';
import { tenantMiddleware } from '../middleware/tenantMiddleware';
import { authMiddleware } from '../middleware/authMiddleware';
import { validateBody, validateQuery } from '../middleware/validationMiddleware';

const processPaymentSchema = Joi.object({ sagaId: Joi.string().uuid().required(), orderId: Joi.string().required(), customerId: Joi.string().required(), amount: Joi.number().positive().required(), currency: Joi.string().length(3).uppercase().default('USD'), paymentMethod: Joi.object({ type: Joi.string().valid('CREDIT_CARD','DEBIT_CARD','BANK_TRANSFER').required(), last4Digits: Joi.string().length(4).pattern(/^\d+$/).optional(), bankCode: Joi.string().optional() }).required() });
const refundSchema = Joi.object({ reason: Joi.string().min(3).max(500).required() });
const listQuerySchema = Joi.object({ status: Joi.string().valid('PENDING','PROCESSING','COMPLETED','FAILED','REFUNDED','PARTIALLY_REFUNDED').optional(), orderId: Joi.string().optional(), customerId: Joi.string().optional(), page: Joi.number().integer().min(1).default(1), limit: Joi.number().integer().min(1).max(100).default(20) });

export function createPaymentRouter(controller: PaymentController): Router {
  const router = Router();
  router.use(authMiddleware);
  router.use(tenantMiddleware);
  router.get('/', validateQuery(listQuerySchema), controller.listPayments);
  router.get('/:id', controller.getPayment);
  router.post('/', validateBody(processPaymentSchema), controller.processPayment);
  router.post('/:id/refund', validateBody(refundSchema), controller.refundPayment);
  return router;
}
