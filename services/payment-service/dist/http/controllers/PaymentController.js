"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.PaymentController = void 0;
class PaymentController {
    constructor(paymentService, logger) {
        this.paymentService = paymentService;
        this.logger = logger;
        this.listPayments = async (req, res, next) => {
            try {
                const filters = { status: req.query['status'], orderId: req.query['orderId'], customerId: req.query['customerId'], page: req.query['page'] ? parseInt(req.query['page'], 10) : undefined, limit: req.query['limit'] ? parseInt(req.query['limit'], 10) : undefined };
                const result = await this.paymentService.listPayments(req.tenantId, filters);
                res.json({ success: true, ...result });
            }
            catch (err) {
                next(err);
            }
        };
        this.getPayment = async (req, res, next) => {
            try {
                const payment = await this.paymentService.getPayment(req.params['id']);
                if (!payment) {
                    res.status(404).json({ success: false, error: 'Payment not found', code: 'NOT_FOUND' });
                    return;
                }
                if (payment.tenantId !== req.tenantId) {
                    res.status(403).json({ success: false, error: 'Forbidden', code: 'FORBIDDEN' });
                    return;
                }
                res.json({ success: true, data: payment });
            }
            catch (err) {
                next(err);
            }
        };
        this.processPayment = async (req, res, next) => {
            try {
                const result = await this.paymentService.processPayment({ tenantId: req.tenantId, ...req.body });
                res.status(result.success ? 201 : 402).json({ success: result.success, data: result });
            }
            catch (err) {
                next(err);
            }
        };
        this.refundPayment = async (req, res, next) => {
            try {
                const paymentId = req.params['id'];
                const payment = await this.paymentService.getPayment(paymentId);
                if (!payment) {
                    res.status(404).json({ success: false, error: 'Payment not found', code: 'NOT_FOUND' });
                    return;
                }
                if (payment.tenantId !== req.tenantId) {
                    res.status(403).json({ success: false, error: 'Forbidden', code: 'FORBIDDEN' });
                    return;
                }
                const result = await this.paymentService.refundPayment(paymentId, req.body.reason);
                if (!result.success) {
                    res.status(400).json({ success: false, data: result });
                    return;
                }
                res.json({ success: true, data: result });
            }
            catch (err) {
                next(err);
            }
        };
    }
}
exports.PaymentController = PaymentController;
//# sourceMappingURL=PaymentController.js.map