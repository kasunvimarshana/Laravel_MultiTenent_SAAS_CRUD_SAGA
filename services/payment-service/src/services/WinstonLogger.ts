import winston from 'winston';
import { ILogger, LogMeta } from '../interfaces/ILogger';
import { config } from '../config';
export class WinstonLogger implements ILogger {
  private readonly logger: winston.Logger;
  constructor(service = 'payment-service') {
    this.logger = winston.createLogger({
      level: config.app.logLevel,
      format: winston.format.combine(winston.format.timestamp(), winston.format.errors({ stack: true }), winston.format.json()),
      defaultMeta: { service },
      transports: [new winston.transports.Console({
        format: winston.format.combine(winston.format.colorize(), winston.format.printf(({ timestamp, level, message, service: svc, ...meta }) => {
          const metaStr = Object.keys(meta).length ? ` ${JSON.stringify(meta)}` : '';
          return `${timestamp} [${svc}] ${level}: ${message}${metaStr}`;
        }))
      })],
    });
  }
  info(message: string, meta?: LogMeta): void { this.logger.info(message, meta); }
  error(message: string, meta?: LogMeta): void { this.logger.error(message, meta); }
  warn(message: string, meta?: LogMeta): void { this.logger.warn(message, meta); }
  debug(message: string, meta?: LogMeta): void { this.logger.debug(message, meta); }
}
