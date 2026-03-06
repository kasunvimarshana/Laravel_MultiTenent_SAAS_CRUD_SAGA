import { IMessageBroker, PublishOptions, SubscribeOptions, MessageHandler } from '../interfaces/IMessageBroker';
import { ILogger } from '../interfaces/ILogger';
export declare class RabbitMQService implements IMessageBroker {
    private readonly logger;
    private connection;
    private publishChannel;
    private consumeChannel;
    private isConnecting;
    constructor(logger: ILogger);
    private connect;
    private ensureConnected;
    publish(queue: string, message: unknown, options?: PublishOptions): Promise<void>;
    subscribe(options: SubscribeOptions, handler: MessageHandler): Promise<void>;
    close(): Promise<void>;
}
//# sourceMappingURL=RabbitMQService.d.ts.map