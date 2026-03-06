import { ILogger, LogMeta } from '../interfaces/ILogger';
export declare class WinstonLogger implements ILogger {
    private readonly logger;
    constructor(service?: string);
    info(message: string, meta?: LogMeta): void;
    error(message: string, meta?: LogMeta): void;
    warn(message: string, meta?: LogMeta): void;
    debug(message: string, meta?: LogMeta): void;
}
//# sourceMappingURL=WinstonLogger.d.ts.map