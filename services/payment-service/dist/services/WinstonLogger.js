"use strict";
var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};
Object.defineProperty(exports, "__esModule", { value: true });
exports.WinstonLogger = void 0;
const winston_1 = __importDefault(require("winston"));
const config_1 = require("../config");
class WinstonLogger {
    constructor(service = 'payment-service') {
        this.logger = winston_1.default.createLogger({
            level: config_1.config.app.logLevel,
            format: winston_1.default.format.combine(winston_1.default.format.timestamp(), winston_1.default.format.errors({ stack: true }), winston_1.default.format.json()),
            defaultMeta: { service },
            transports: [new winston_1.default.transports.Console({
                    format: winston_1.default.format.combine(winston_1.default.format.colorize(), winston_1.default.format.printf(({ timestamp, level, message, service: svc, ...meta }) => {
                        const metaStr = Object.keys(meta).length ? ` ${JSON.stringify(meta)}` : '';
                        return `${timestamp} [${svc}] ${level}: ${message}${metaStr}`;
                    }))
                })],
        });
    }
    info(message, meta) { this.logger.info(message, meta); }
    error(message, meta) { this.logger.error(message, meta); }
    warn(message, meta) { this.logger.warn(message, meta); }
    debug(message, meta) { this.logger.debug(message, meta); }
}
exports.WinstonLogger = WinstonLogger;
//# sourceMappingURL=WinstonLogger.js.map