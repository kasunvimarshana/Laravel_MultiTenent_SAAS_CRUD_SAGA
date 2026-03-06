export interface PublishOptions {
    exchange?: string;
    routingKey?: string;
    persistent?: boolean;
}
export interface SubscribeOptions {
    queue: string;
    prefetch?: number;
    noAck?: boolean;
}
export type MessageHandler = (message: unknown, ack: () => void, nack: (requeue?: boolean) => void) => Promise<void>;
export interface IMessageBroker {
    publish(queue: string, message: unknown, options?: PublishOptions): Promise<void>;
    subscribe(options: SubscribeOptions, handler: MessageHandler): Promise<void>;
    close(): Promise<void>;
}
//# sourceMappingURL=IMessageBroker.d.ts.map