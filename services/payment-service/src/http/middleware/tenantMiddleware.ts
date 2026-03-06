import { Request, Response, NextFunction } from 'express';
export interface TenantRequest extends Request { tenantId?: string; }
export function tenantMiddleware(req: TenantRequest, res: Response, next: NextFunction): void {
  const tenantId = req.headers['x-tenant-id'] as string | undefined;
  if (!tenantId?.trim()) { res.status(400).json({ success: false, error: 'Missing X-Tenant-ID header', code: 'MISSING_TENANT_ID' }); return; }
  req.tenantId = tenantId.trim();
  next();
}
