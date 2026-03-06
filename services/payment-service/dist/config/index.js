"use strict";
var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};
Object.defineProperty(exports, "__esModule", { value: true });
exports.config = void 0;
const dotenv_1 = __importDefault(require("dotenv"));
dotenv_1.default.config();
function requireEnv(name, defaultValue) {
    const value = process.env[name] ?? defaultValue;
    if (value === undefined)
        throw new Error(`Missing required env var: ${name}`);
    return value;
}
exports.config = {
    app: { port: parseInt(process.env['PORT'] ?? '3003', 10), nodeEnv: process.env['NODE_ENV'] ?? 'development', logLevel: process.env['LOG_LEVEL'] ?? 'info' },
    mongodb: { uri: requireEnv('MONGODB_URI', 'mongodb://localhost:27017/payment_service') },
    rabbitmq: {
        url: requireEnv('RABBITMQ_URL', 'amqp://guest:guest@localhost:5672'),
        commandQueue: process.env['PAYMENT_COMMAND_QUEUE'] ?? 'payment.commands',
        eventExchange: process.env['PAYMENT_EVENT_EXCHANGE'] ?? 'saga.events',
        deadLetterQueue: process.env['PAYMENT_DLQ'] ?? 'payment.commands.dlq',
        prefetch: parseInt(process.env['RABBITMQ_PREFETCH'] ?? '10', 10),
    },
    payment: {
        successRate: parseFloat(process.env['PAYMENT_SUCCESS_RATE'] ?? '0.9'),
        processingDelayMs: parseInt(process.env['PAYMENT_PROCESSING_DELAY_MS'] ?? '100', 10),
    },
    auth: { jwtSecret: process.env['JWT_SECRET'] ?? 'change-me-in-production' },
    rateLimit: { windowMs: parseInt(process.env['RATE_LIMIT_WINDOW_MS'] ?? '60000', 10), max: parseInt(process.env['RATE_LIMIT_MAX'] ?? '100', 10) },
};
//# sourceMappingURL=index.js.map