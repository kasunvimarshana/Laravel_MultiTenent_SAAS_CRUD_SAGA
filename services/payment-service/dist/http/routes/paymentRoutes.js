"use strict";
var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};
Object.defineProperty(exports, "__esModule", { value: true });
exports.createPaymentRouter = createPaymentRouter;
const express_1 = require("express");
const joi_1 = __importDefault(require("joi"));
const tenantMiddleware_1 = require("../middleware/tenantMiddleware");
const authMiddleware_1 = require("../middleware/authMiddleware");
const validationMiddleware_1 = require("../middleware/validationMiddleware");
const processPaymentSchema = joi_1.default.object({ sagaId: joi_1.default.string().uuid().required(), orderId: joi_1.default.string().required(), customerId: joi_1.default.string().required(), amount: joi_1.default.number().positive().required(), currency: joi_1.default.string().length(3).uppercase().default('USD'), paymentMethod: joi_1.default.object({ type: joi_1.default.string().valid('CREDIT_CARD', 'DEBIT_CARD', 'BANK_TRANSFER').required(), last4Digits: joi_1.default.string().length(4).pattern(/^\d+$/).optional(), bankCode: joi_1.default.string().optional() }).required() });
const refundSchema = joi_1.default.object({ reason: joi_1.default.string().min(3).max(500).required() });
const listQuerySchema = joi_1.default.object({ status: joi_1.default.string().valid('PENDING', 'PROCESSING', 'COMPLETED', 'FAILED', 'REFUNDED', 'PARTIALLY_REFUNDED').optional(), orderId: joi_1.default.string().optional(), customerId: joi_1.default.string().optional(), page: joi_1.default.number().integer().min(1).default(1), limit: joi_1.default.number().integer().min(1).max(100).default(20) });
function createPaymentRouter(controller) {
    const router = (0, express_1.Router)();
    router.use(authMiddleware_1.authMiddleware);
    router.use(tenantMiddleware_1.tenantMiddleware);
    router.get('/', (0, validationMiddleware_1.validateQuery)(listQuerySchema), controller.listPayments);
    router.get('/:id', controller.getPayment);
    router.post('/', (0, validationMiddleware_1.validateBody)(processPaymentSchema), controller.processPayment);
    router.post('/:id/refund', (0, validationMiddleware_1.validateBody)(refundSchema), controller.refundPayment);
    return router;
}
//# sourceMappingURL=paymentRoutes.js.map