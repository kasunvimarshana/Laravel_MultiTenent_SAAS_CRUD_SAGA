import { Request, Response, NextFunction } from 'express';
import Joi from 'joi';
export declare function validateBody(schema: Joi.ObjectSchema): (req: Request, res: Response, next: NextFunction) => void;
export declare function validateQuery(schema: Joi.ObjectSchema): (req: Request, res: Response, next: NextFunction) => void;
//# sourceMappingURL=validationMiddleware.d.ts.map