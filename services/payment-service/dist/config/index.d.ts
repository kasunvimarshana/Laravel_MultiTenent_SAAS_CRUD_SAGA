export declare const config: {
    app: {
        port: number;
        nodeEnv: string;
        logLevel: string;
    };
    mongodb: {
        uri: string;
    };
    rabbitmq: {
        url: string;
        commandQueue: string;
        eventExchange: string;
        deadLetterQueue: string;
        prefetch: number;
    };
    payment: {
        successRate: number;
        processingDelayMs: number;
    };
    auth: {
        jwtSecret: string;
    };
    rateLimit: {
        windowMs: number;
        max: number;
    };
};
//# sourceMappingURL=index.d.ts.map