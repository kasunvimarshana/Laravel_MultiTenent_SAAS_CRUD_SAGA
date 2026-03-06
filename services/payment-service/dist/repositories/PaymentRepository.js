"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.PaymentRepository = void 0;
const Payment_1 = require("../models/Payment");
class PaymentRepository {
    async findById(id) {
        return Payment_1.PaymentModel.findById(id).lean().exec();
    }
    async findByOrderId(orderId, tenantId) {
        return Payment_1.PaymentModel.findOne({ orderId, tenantId }).lean().exec();
    }
    async findBySagaId(sagaId) {
        return Payment_1.PaymentModel.findOne({ sagaId }).lean().exec();
    }
    async create(data) {
        const doc = new Payment_1.PaymentModel({ _id: data._id, tenantId: data.tenantId, sagaId: data.sagaId, orderId: data.orderId, customerId: data.customerId, amount: data.amount, currency: data.currency ?? 'USD', status: data.status, paymentMethod: data.paymentMethod, refunds: [] });
        const saved = await doc.save();
        return saved.toObject();
    }
    async update(id, data) {
        return Payment_1.PaymentModel.findByIdAndUpdate(id, { $set: data }, { new: true }).lean().exec();
    }
    async addRefund(id, refund) {
        return Payment_1.PaymentModel.findByIdAndUpdate(id, { $push: { refunds: refund } }, { new: true }).lean().exec();
    }
    async findByTenant(tenantId, filters) {
        const page = Math.max(1, filters.page ?? 1);
        const limit = Math.min(100, Math.max(1, filters.limit ?? 20));
        const skip = (page - 1) * limit;
        const query = { tenantId };
        if (filters.status)
            query['status'] = filters.status;
        if (filters.orderId)
            query['orderId'] = filters.orderId;
        if (filters.customerId)
            query['customerId'] = filters.customerId;
        const [data, total] = await Promise.all([
            Payment_1.PaymentModel.find(query).skip(skip).limit(limit).lean().exec(),
            Payment_1.PaymentModel.countDocuments(query),
        ]);
        return { data, total, page, limit, totalPages: Math.ceil(total / limit) };
    }
}
exports.PaymentRepository = PaymentRepository;
//# sourceMappingURL=PaymentRepository.js.map