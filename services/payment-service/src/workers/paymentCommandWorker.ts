import mongoose from 'mongoose';
import { config } from '../config';
import { WinstonLogger } from '../services/WinstonLogger';
import { RabbitMQService } from '../services/RabbitMQService';
import { PaymentRepository } from '../repositories/PaymentRepository';
import { PaymentService } from '../services/PaymentService';
import { ProcessPaymentParams } from '../interfaces/IPaymentService';

interface ProcessPaymentCommand { commandType: 'process_payment'; sagaId: string; tenantId: string; orderId: string; customerId: string; amount: number; currency?: string; paymentMethod: ProcessPaymentParams['paymentMethod']; }
interface RefundPaymentCommand { commandType: 'refund_payment'; sagaId: string; paymentId: string; reason?: string; }
type PaymentCommand = ProcessPaymentCommand | RefundPaymentCommand;

const logger = new WinstonLogger('payment-command-worker');

async function main(): Promise<void> {
  logger.info('Starting payment command worker');
  await mongoose.connect(config.mongodb.uri);
  logger.info('Connected to MongoDB');
  const broker = new RabbitMQService(logger);
  const paymentService = new PaymentService(new PaymentRepository(), logger);

  await broker.subscribe({ queue: config.rabbitmq.commandQueue }, async (message, ack, nack) => {
    const command = message as PaymentCommand;
    logger.info('Received command', { commandType: command.commandType, sagaId: command.sagaId });
    try {
      if (command.commandType === 'process_payment') {
        const result = await paymentService.processPayment({ tenantId: command.tenantId, sagaId: command.sagaId, orderId: command.orderId, customerId: command.customerId, amount: command.amount, currency: command.currency, paymentMethod: command.paymentMethod });
        const eventType = result.success ? 'payment_processed' : 'payment_failed';
        await broker.publish(config.rabbitmq.eventExchange, { eventType, sagaId: command.sagaId, paymentId: result.paymentId, transactionId: result.transactionId, errorCode: result.errorCode, errorMessage: result.errorMessage, occurredAt: new Date().toISOString() }, { exchange: config.rabbitmq.eventExchange, routingKey: `saga.${eventType}` });
        logger.info('Published event', { eventType, sagaId: command.sagaId });
        ack();
      } else if (command.commandType === 'refund_payment') {
        const result = await paymentService.refundPayment(command.paymentId, command.reason ?? 'SAGA compensation');
        await broker.publish(config.rabbitmq.eventExchange, { eventType: 'payment_refunded', sagaId: command.sagaId, paymentId: command.paymentId, refundId: result.refundId, amount: result.amount, success: result.success, errorCode: result.errorCode, occurredAt: new Date().toISOString() }, { exchange: config.rabbitmq.eventExchange, routingKey: 'saga.payment_refunded' });
        logger.info('Published refund event', { sagaId: command.sagaId });
        ack();
      } else {
        logger.warn('Unknown command type, discarding');
        nack(false);
      }
    } catch (err) {
      logger.error('Failed to process command', { error: err instanceof Error ? err.message : String(err), sagaId: command.sagaId });
      nack(false);
    }
  });

  logger.info('Worker listening', { queue: config.rabbitmq.commandQueue });
  const shutdown = async (signal: string): Promise<void> => { logger.info(`${signal} received`); await broker.close(); await mongoose.disconnect(); process.exit(0); };
  process.on('SIGTERM', () => void shutdown('SIGTERM'));
  process.on('SIGINT', () => void shutdown('SIGINT'));
}

main().catch((err) => { logger.error('Worker failed', { error: err instanceof Error ? err.message : String(err) }); process.exit(1); });
