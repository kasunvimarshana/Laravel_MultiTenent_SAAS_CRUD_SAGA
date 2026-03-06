"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.authMiddleware = authMiddleware;
const config_1 = require("../../config");
function authMiddleware(req, res, next) {
    const authHeader = req.headers['authorization'];
    if (!authHeader) {
        res.status(401).json({ success: false, error: 'Missing Authorization header', code: 'UNAUTHORIZED' });
        return;
    }
    const parts = authHeader.split(' ');
    if (parts.length !== 2 || parts[0]?.toLowerCase() !== 'bearer') {
        res.status(401).json({ success: false, error: 'Invalid Authorization format', code: 'UNAUTHORIZED' });
        return;
    }
    const token = parts[1];
    if (config_1.config.app.nodeEnv === 'production' && token !== config_1.config.auth.jwtSecret) {
        res.status(401).json({ success: false, error: 'Invalid token', code: 'UNAUTHORIZED' });
        return;
    }
    next();
}
//# sourceMappingURL=authMiddleware.js.map