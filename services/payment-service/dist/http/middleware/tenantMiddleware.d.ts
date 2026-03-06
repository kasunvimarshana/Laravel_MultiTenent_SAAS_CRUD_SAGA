import { Request, Response, NextFunction } from 'express';
export interface TenantRequest extends Request {
    tenantId?: string;
}
export declare function tenantMiddleware(req: TenantRequest, res: Response, next: NextFunction): void;
//# sourceMappingURL=tenantMiddleware.d.ts.map