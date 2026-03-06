import { IPaymentRepository, CreatePaymentData, UpdatePaymentData } from '../interfaces/IPaymentRepository';
import { Payment, PaymentFilters, PaginatedResult, Refund } from '../interfaces/IPaymentService';
import { PaymentModel } from '../models/Payment';

export class PaymentRepository implements IPaymentRepository {
  async findById(id: string): Promise<Payment | null> {
    return PaymentModel.findById(id).lean<Payment>().exec();
  }
  async findByOrderId(orderId: string, tenantId: string): Promise<Payment | null> {
    return PaymentModel.findOne({ orderId, tenantId }).lean<Payment>().exec();
  }
  async findBySagaId(sagaId: string): Promise<Payment | null> {
    return PaymentModel.findOne({ sagaId }).lean<Payment>().exec();
  }
  async create(data: CreatePaymentData): Promise<Payment> {
    const doc = new PaymentModel({ _id: data._id, tenantId: data.tenantId, sagaId: data.sagaId, orderId: data.orderId, customerId: data.customerId, amount: data.amount, currency: data.currency ?? 'USD', status: data.status, paymentMethod: data.paymentMethod, refunds: [] });
    const saved = await doc.save();
    return saved.toObject() as Payment;
  }
  async update(id: string, data: UpdatePaymentData): Promise<Payment | null> {
    return PaymentModel.findByIdAndUpdate(id, { $set: data }, { new: true }).lean<Payment>().exec();
  }
  async addRefund(id: string, refund: Refund): Promise<Payment | null> {
    return PaymentModel.findByIdAndUpdate(id, { $push: { refunds: refund } }, { new: true }).lean<Payment>().exec();
  }
  async findByTenant(tenantId: string, filters: PaymentFilters): Promise<PaginatedResult<Payment>> {
    const page = Math.max(1, filters.page ?? 1);
    const limit = Math.min(100, Math.max(1, filters.limit ?? 20));
    const skip = (page - 1) * limit;
    const query: Record<string, unknown> = { tenantId };
    if (filters.status) query['status'] = filters.status;
    if (filters.orderId) query['orderId'] = filters.orderId;
    if (filters.customerId) query['customerId'] = filters.customerId;
    const [data, total] = await Promise.all([
      PaymentModel.find(query).skip(skip).limit(limit).lean<Payment[]>().exec(),
      PaymentModel.countDocuments(query),
    ]);
    return { data, total, page, limit, totalPages: Math.ceil(total / limit) };
  }
}
