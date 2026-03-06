import { Response, NextFunction } from 'express';
import { IPaymentService } from '../../interfaces/IPaymentService';
import { ILogger } from '../../interfaces/ILogger';
import { TenantRequest } from '../middleware/tenantMiddleware';
export declare class PaymentController {
    private readonly paymentService;
    private readonly logger;
    constructor(paymentService: IPaymentService, logger: ILogger);
    listPayments: (req: TenantRequest, res: Response, next: NextFunction) => Promise<void>;
    getPayment: (req: TenantRequest, res: Response, next: NextFunction) => Promise<void>;
    processPayment: (req: TenantRequest, res: Response, next: NextFunction) => Promise<void>;
    refundPayment: (req: TenantRequest, res: Response, next: NextFunction) => Promise<void>;
}
//# sourceMappingURL=PaymentController.d.ts.map