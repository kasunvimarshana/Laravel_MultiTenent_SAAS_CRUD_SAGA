"use strict";
var __createBinding = (this && this.__createBinding) || (Object.create ? (function(o, m, k, k2) {
    if (k2 === undefined) k2 = k;
    var desc = Object.getOwnPropertyDescriptor(m, k);
    if (!desc || ("get" in desc ? !m.__esModule : desc.writable || desc.configurable)) {
      desc = { enumerable: true, get: function() { return m[k]; } };
    }
    Object.defineProperty(o, k2, desc);
}) : (function(o, m, k, k2) {
    if (k2 === undefined) k2 = k;
    o[k2] = m[k];
}));
var __setModuleDefault = (this && this.__setModuleDefault) || (Object.create ? (function(o, v) {
    Object.defineProperty(o, "default", { enumerable: true, value: v });
}) : function(o, v) {
    o["default"] = v;
});
var __importStar = (this && this.__importStar) || (function () {
    var ownKeys = function(o) {
        ownKeys = Object.getOwnPropertyNames || function (o) {
            var ar = [];
            for (var k in o) if (Object.prototype.hasOwnProperty.call(o, k)) ar[ar.length] = k;
            return ar;
        };
        return ownKeys(o);
    };
    return function (mod) {
        if (mod && mod.__esModule) return mod;
        var result = {};
        if (mod != null) for (var k = ownKeys(mod), i = 0; i < k.length; i++) if (k[i] !== "default") __createBinding(result, mod, k[i]);
        __setModuleDefault(result, mod);
        return result;
    };
})();
Object.defineProperty(exports, "__esModule", { value: true });
exports.PaymentModel = void 0;
const mongoose_1 = __importStar(require("mongoose"));
const RefundSchema = new mongoose_1.Schema({ refundId: { type: String, required: true }, amount: { type: Number, required: true }, reason: { type: String, required: true }, refundedAt: { type: Date, required: true } }, { _id: false });
const PaymentMethodSchema = new mongoose_1.Schema({ type: { type: String, enum: ['CREDIT_CARD', 'DEBIT_CARD', 'BANK_TRANSFER'], required: true }, last4Digits: { type: String }, bankCode: { type: String } }, { _id: false });
const PaymentSchema = new mongoose_1.Schema({
    _id: { type: String, required: true },
    tenantId: { type: String, required: true, index: true },
    sagaId: { type: String, required: true, index: true },
    orderId: { type: String, required: true, index: true },
    customerId: { type: String, required: true },
    amount: { type: Number, required: true, min: 0 },
    currency: { type: String, default: 'USD', uppercase: true },
    status: { type: String, enum: ['PENDING', 'PROCESSING', 'COMPLETED', 'FAILED', 'REFUNDED', 'PARTIALLY_REFUNDED'], default: 'PENDING' },
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
exports.PaymentModel = mongoose_1.default.model('Payment', PaymentSchema);
//# sourceMappingURL=Payment.js.map