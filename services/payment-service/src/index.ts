import 'dotenv/config';
import express, { Request, Response, NextFunction } from 'express';
import helmet from 'helmet';
import cors from 'cors';
import rateLimit from 'express-rate-limit';
import mongoose from 'mongoose';
import { config } from './config';
import { WinstonLogger } from './services/WinstonLogger';
import { PaymentRepository } from './repositories/PaymentRepository';
import { PaymentService } from './services/PaymentService';
import { PaymentController } from './http/controllers/PaymentController';
import { createPaymentRouter } from './http/routes/paymentRoutes';

const logger = new WinstonLogger('payment-service');
const app = express();

app.use(helmet());
app.use(cors({ origin: process.env['CORS_ORIGIN'] ?? '*' }));
app.use(express.json({ limit: '1mb' }));
app.use(express.urlencoded({ extended: true }));
app.use(rateLimit({ windowMs: config.rateLimit.windowMs, max: config.rateLimit.max, standardHeaders: true, legacyHeaders: false, message: { success: false, error: 'Too many requests', code: 'RATE_LIMITED' } }));

app.get('/health', (_req: Request, res: Response) => { res.json({ status: 'ok', service: 'payment-service', timestamp: new Date().toISOString(), mongodb: mongoose.connection.readyState === 1 ? 'connected' : 'disconnected' }); });

const paymentRepository = new PaymentRepository();
const paymentService = new PaymentService(paymentRepository, logger);
const paymentController = new PaymentController(paymentService, logger);
app.use('/api/payments', createPaymentRouter(paymentController));

app.use((_req: Request, res: Response) => { res.status(404).json({ success: false, error: 'Route not found', code: 'NOT_FOUND' }); });
app.use((err: Error, _req: Request, res: Response, _next: NextFunction) => { logger.error('Unhandled error', { error: err.message }); res.status(500).json({ success: false, error: 'Internal server error', code: 'INTERNAL_ERROR' }); });

async function start(): Promise<void> {
  await mongoose.connect(config.mongodb.uri);
  logger.info('Connected to MongoDB');
  const server = app.listen(config.app.port, () => { logger.info('Payment service started', { port: config.app.port }); });
  const shutdown = async (signal: string): Promise<void> => {
    logger.info(`${signal} received, shutting down`);
    server.close(async () => { await mongoose.disconnect(); logger.info('Shutdown complete'); process.exit(0); });
    setTimeout(() => process.exit(1), 10000);
  };
  process.on('SIGTERM', () => void shutdown('SIGTERM'));
  process.on('SIGINT', () => void shutdown('SIGINT'));
}

start().catch((err) => { logger.error('Failed to start', { error: err instanceof Error ? err.message : String(err) }); process.exit(1); });
export { app };
