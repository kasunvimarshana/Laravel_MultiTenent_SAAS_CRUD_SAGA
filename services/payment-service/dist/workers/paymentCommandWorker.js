"use strict";
var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};
Object.defineProperty(exports, "__esModule", { value: true });
const mongoose_1 = __importDefault(require("mongoose"));
const config_1 = require("../config");
const WinstonLogger_1 = require("../services/WinstonLogger");
const RabbitMQService_1 = require("../services/RabbitMQService");
const PaymentRepository_1 = require("../repositories/PaymentRepository");
const PaymentService_1 = require("../services/PaymentService");
const logger = new WinstonLogger_1.WinstonLogger('payment-command-worker');
async function main() {
    logger.info('Starting payment command worker');
    await mongoose_1.default.connect(config_1.config.mongodb.uri);
    logger.info('Connected to MongoDB');
    const broker = new RabbitMQService_1.RabbitMQService(logger);
    const paymentService = new PaymentService_1.PaymentService(new PaymentRepository_1.PaymentRepository(), logger);
    await broker.subscribe({ queue: config_1.config.rabbitmq.commandQueue }, async (message, ack, nack) => {
        const command = message;
        logger.info('Received command', { commandType: command.commandType, sagaId: command.sagaId });
        try {
            if (command.commandType === 'process_payment') {
                const result = await paymentService.processPayment({ tenantId: command.tenantId, sagaId: command.sagaId, orderId: command.orderId, customerId: command.customerId, amount: command.amount, currency: command.currency, paymentMethod: command.paymentMethod });
                const eventType = result.success ? 'payment_processed' : 'payment_failed';
                await broker.publish(config_1.config.rabbitmq.eventExchange, { eventType, sagaId: command.sagaId, paymentId: result.paymentId, transactionId: result.transactionId, errorCode: result.errorCode, errorMessage: result.errorMessage, occurredAt: new Date().toISOString() }, { exchange: config_1.config.rabbitmq.eventExchange, routingKey: `saga.${eventType}` });
                logger.info('Published event', { eventType, sagaId: command.sagaId });
                ack();
            }
            else if (command.commandType === 'refund_payment') {
                const result = await paymentService.refundPayment(command.paymentId, command.reason ?? 'SAGA compensation');
                await broker.publish(config_1.config.rabbitmq.eventExchange, { eventType: 'payment_refunded', sagaId: command.sagaId, paymentId: command.paymentId, refundId: result.refundId, amount: result.amount, success: result.success, errorCode: result.errorCode, occurredAt: new Date().toISOString() }, { exchange: config_1.config.rabbitmq.eventExchange, routingKey: 'saga.payment_refunded' });
                logger.info('Published refund event', { sagaId: command.sagaId });
                ack();
            }
            else {
                logger.warn('Unknown command type, discarding');
                nack(false);
            }
        }
        catch (err) {
            logger.error('Failed to process command', { error: err instanceof Error ? err.message : String(err), sagaId: command.sagaId });
            nack(false);
        }
    });
    logger.info('Worker listening', { queue: config_1.config.rabbitmq.commandQueue });
    const shutdown = async (signal) => { logger.info(`${signal} received`); await broker.close(); await mongoose_1.default.disconnect(); process.exit(0); };
    process.on('SIGTERM', () => void shutdown('SIGTERM'));
    process.on('SIGINT', () => void shutdown('SIGINT'));
}
main().catch((err) => { logger.error('Worker failed', { error: err instanceof Error ? err.message : String(err) }); process.exit(1); });
//# sourceMappingURL=paymentCommandWorker.js.map