export interface LogMeta {
    [key: string]: unknown;
}
export interface ILogger {
    info(message: string, meta?: LogMeta): void;
    error(message: string, meta?: LogMeta): void;
    warn(message: string, meta?: LogMeta): void;
    debug(message: string, meta?: LogMeta): void;
}
//# sourceMappingURL=ILogger.d.ts.map