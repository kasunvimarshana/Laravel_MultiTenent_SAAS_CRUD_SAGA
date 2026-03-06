import mongoose, { Document } from 'mongoose';
export interface TenantDocument extends Document {
    tenantId: string;
    name: string;
    apiKey: string;
    isActive: boolean;
    createdAt: Date;
}
export declare const TenantModel: mongoose.Model<TenantDocument, {}, {}, {}, mongoose.Document<unknown, {}, TenantDocument> & TenantDocument & {
    _id: mongoose.Types.ObjectId;
}, any>;
//# sourceMappingURL=Tenant.d.ts.map