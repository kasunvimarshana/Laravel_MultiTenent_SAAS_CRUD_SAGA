"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.tenantMiddleware = tenantMiddleware;
function tenantMiddleware(req, res, next) {
    const tenantId = req.headers['x-tenant-id'];
    if (!tenantId?.trim()) {
        res.status(400).json({ success: false, error: 'Missing X-Tenant-ID header', code: 'MISSING_TENANT_ID' });
        return;
    }
    req.tenantId = tenantId.trim();
    next();
}
//# sourceMappingURL=tenantMiddleware.js.map