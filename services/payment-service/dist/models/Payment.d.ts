import mongoose, { Document } from 'mongoose';
import { Payment } from '../interfaces/IPaymentService';
export interface PaymentDocument extends Omit<Payment, '_id'>, Document {
    _id: string;
}
export declare const PaymentModel: mongoose.Model<PaymentDocument, {}, {}, {}, mongoose.Document<unknown, {}, PaymentDocument> & PaymentDocument & Required<{
    _id: string;
}>, any>;
//# sourceMappingURL=Payment.d.ts.map