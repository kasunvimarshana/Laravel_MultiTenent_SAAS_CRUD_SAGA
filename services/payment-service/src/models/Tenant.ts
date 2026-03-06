import mongoose, { Schema, Document } from 'mongoose';
export interface TenantDocument extends Document { tenantId: string; name: string; apiKey: string; isActive: boolean; createdAt: Date; }
const TenantSchema = new Schema<TenantDocument>({ tenantId: { type: String, required: true, unique: true, index: true }, name: { type: String, required: true }, apiKey: { type: String, required: true }, isActive: { type: Boolean, default: true } }, { timestamps: { createdAt: true, updatedAt: false }, versionKey: false });
export const TenantModel = mongoose.model<TenantDocument>('Tenant', TenantSchema);
