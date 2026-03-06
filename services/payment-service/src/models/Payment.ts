import mongoose, { Schema, Document } from 'mongoose';
import { Payment, PaymentStatus, PaymentMethodType } from '../interfaces/IPaymentService';
export interface PaymentDocument extends Omit<Payment, '_id'>, Document { _id: string; }
const RefundSchema = new Schema({ refundId: { type: String, required: true }, amount: { type: Number, required: true }, reason: { type: String, required: true }, refundedAt: { type: Date, required: true } }, { _id: false });
const PaymentMethodSchema = new Schema({ type: { type: String, enum: ['CREDIT_CARD', 'DEBIT_CARD', 'BANK_TRANSFER'] as PaymentMethodType[], required: true }, last4Digits: { type: String }, bankCode: { type: String } }, { _id: false });
const PaymentSchema = new Schema<PaymentDocument>({
  _id: { type: String, required: true },
  tenantId: { type: String, required: true, index: true },
  sagaId: { type: String, required: true, index: true },
  orderId: { type: String, required: true, index: true },
  customerId: { type: String, required: true },
  amount: { type: Number, required: true, min: 0 },
  currency: { type: String, default: 'USD', uppercase: true },
  status: { type: String, enum: ['PENDING','PROCESSING','COMPLETED','FAILED','REFUNDED','PARTIALLY_REFUNDED'] as PaymentStatus[], default: 'PENDING' },
  paymentMethod: { type: PaymentMethodSchema, required: true },
  transactionId: { type: String },
  refunds: { type: [RefundSchema], default: [] },
  errorCode: { type: String },
  errorMessage: { type: String },
  processedAt: { type: Date },
  refundedAt: { type: Date },
}, { timestamps: true, versionKey: false });
PaymentSchema.index({ tenantId: 1, status: 1 });
PaymentSchema.index({ tenantId: 1, orderId: 1 });
export const PaymentModel = mongoose.model<PaymentDocument>('Payment', PaymentSchema);
