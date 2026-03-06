"use strict";
var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};
Object.defineProperty(exports, "__esModule", { value: true });
exports.app = void 0;
require("dotenv/config");
const express_1 = __importDefault(require("express"));
const helmet_1 = __importDefault(require("helmet"));
const cors_1 = __importDefault(require("cors"));
const express_rate_limit_1 = __importDefault(require("express-rate-limit"));
const mongoose_1 = __importDefault(require("mongoose"));
const config_1 = require("./config");
const WinstonLogger_1 = require("./services/WinstonLogger");
const PaymentRepository_1 = require("./repositories/PaymentRepository");
const PaymentService_1 = require("./services/PaymentService");
const PaymentController_1 = require("./http/controllers/PaymentController");
const paymentRoutes_1 = require("./http/routes/paymentRoutes");
const logger = new WinstonLogger_1.WinstonLogger('payment-service');
const app = (0, express_1.default)();
exports.app = app;
app.use((0, helmet_1.default)());
app.use((0, cors_1.default)({ origin: process.env['CORS_ORIGIN'] ?? '*' }));
app.use(express_1.default.json({ limit: '1mb' }));
app.use(express_1.default.urlencoded({ extended: true }));
app.use((0, express_rate_limit_1.default)({ windowMs: config_1.config.rateLimit.windowMs, max: config_1.config.rateLimit.max, standardHeaders: true, legacyHeaders: false, message: { success: false, error: 'Too many requests', code: 'RATE_LIMITED' } }));
app.get('/health', (_req, res) => { res.json({ status: 'ok', service: 'payment-service', timestamp: new Date().toISOString(), mongodb: mongoose_1.default.connection.readyState === 1 ? 'connected' : 'disconnected' }); });
const paymentRepository = new PaymentRepository_1.PaymentRepository();
const paymentService = new PaymentService_1.PaymentService(paymentRepository, logger);
const paymentController = new PaymentController_1.PaymentController(paymentService, logger);
app.use('/api/payments', (0, paymentRoutes_1.createPaymentRouter)(paymentController));
app.use((_req, res) => { res.status(404).json({ success: false, error: 'Route not found', code: 'NOT_FOUND' }); });
app.use((err, _req, res, _next) => { logger.error('Unhandled error', { error: err.message }); res.status(500).json({ success: false, error: 'Internal server error', code: 'INTERNAL_ERROR' }); });
async function start() {
    await mongoose_1.default.connect(config_1.config.mongodb.uri);
    logger.info('Connected to MongoDB');
    const server = app.listen(config_1.config.app.port, () => { logger.info('Payment service started', { port: config_1.config.app.port }); });
    const shutdown = async (signal) => {
        logger.info(`${signal} received, shutting down`);
        server.close(async () => { await mongoose_1.default.disconnect(); logger.info('Shutdown complete'); process.exit(0); });
        setTimeout(() => process.exit(1), 10000);
    };
    process.on('SIGTERM', () => void shutdown('SIGTERM'));
    process.on('SIGINT', () => void shutdown('SIGINT'));
}
start().catch((err) => { logger.error('Failed to start', { error: err instanceof Error ? err.message : String(err) }); process.exit(1); });
//# sourceMappingURL=index.js.map