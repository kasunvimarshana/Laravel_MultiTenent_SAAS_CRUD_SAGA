"use strict";
var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};
Object.defineProperty(exports, "__esModule", { value: true });
exports.RabbitMQService = void 0;
const amqplib_1 = __importDefault(require("amqplib"));
const config_1 = require("../config");
class RabbitMQService {
    constructor(logger) {
        this.logger = logger;
        this.connection = null;
        this.publishChannel = null;
        this.consumeChannel = null;
        this.isConnecting = false;
    }
    async connect() {
        if (this.connection || this.isConnecting)
            return;
        this.isConnecting = true;
        try {
            this.logger.info('Connecting to RabbitMQ');
            const conn = await amqplib_1.default.connect(config_1.config.rabbitmq.url);
            this.connection = conn;
            conn.on('error', (err) => { this.logger.error('RabbitMQ error', { error: err.message }); this.connection = null; this.publishChannel = null; this.consumeChannel = null; });
            conn.on('close', () => { this.logger.warn('RabbitMQ closed'); this.connection = null; this.publishChannel = null; this.consumeChannel = null; });
            this.publishChannel = await conn.createChannel();
            this.consumeChannel = await conn.createChannel();
            await this.consumeChannel.prefetch(config_1.config.rabbitmq.prefetch);
            this.logger.info('Connected to RabbitMQ');
        }
        finally {
            this.isConnecting = false;
        }
    }
    async ensureConnected() { if (!this.connection)
        await this.connect(); }
    async publish(queue, message, options) {
        await this.ensureConnected();
        const channel = this.publishChannel;
        const exchange = options?.exchange ?? '';
        const routingKey = options?.routingKey ?? queue;
        const content = Buffer.from(JSON.stringify(message));
        const msgOptions = { persistent: options?.persistent ?? true, contentType: 'application/json' };
        if (exchange) {
            await channel.assertExchange(exchange, 'topic', { durable: true });
            channel.publish(exchange, routingKey, content, msgOptions);
        }
        else {
            await channel.assertQueue(queue, { durable: true });
            channel.sendToQueue(queue, content, msgOptions);
        }
        this.logger.debug('Published message', { queue, exchange, routingKey });
    }
    async subscribe(options, handler) {
        await this.ensureConnected();
        const channel = this.consumeChannel;
        await channel.assertQueue(options.queue, { durable: true });
        this.logger.info('Subscribing to queue', { queue: options.queue });
        await channel.consume(options.queue, async (msg) => {
            if (!msg)
                return;
            let parsed;
            try {
                parsed = JSON.parse(msg.content.toString());
            }
            catch {
                this.logger.error('Failed to parse message');
                channel.nack(msg, false, false);
                return;
            }
            const ack = () => channel.ack(msg);
            const nack = (requeue = false) => channel.nack(msg, false, requeue);
            try {
                await handler(parsed, ack, nack);
            }
            catch (err) {
                this.logger.error('Handler error', { error: err instanceof Error ? err.message : String(err) });
                nack(false);
            }
        }, { noAck: options.noAck ?? false });
    }
    async close() {
        try {
            if (this.publishChannel)
                await this.publishChannel.close();
            if (this.consumeChannel)
                await this.consumeChannel.close();
            if (this.connection)
                await this.connection.close();
            this.logger.info('RabbitMQ closed gracefully');
        }
        catch (err) {
            this.logger.error('Error closing RabbitMQ', { error: err instanceof Error ? err.message : String(err) });
        }
    }
}
exports.RabbitMQService = RabbitMQService;
//# sourceMappingURL=RabbitMQService.js.map