import { Request, Response, NextFunction } from 'express';
import Joi from 'joi';
export function validateBody(schema: Joi.ObjectSchema) {
  return (req: Request, res: Response, next: NextFunction): void => {
    const { error, value } = schema.validate(req.body, { abortEarly: false, stripUnknown: true });
    if (error) { res.status(422).json({ success: false, error: 'Validation failed', code: 'VALIDATION_ERROR', details: error.details.map((d) => ({ field: d.path.join('.'), message: d.message })) }); return; }
    req.body = value; next();
  };
}
export function validateQuery(schema: Joi.ObjectSchema) {
  return (req: Request, res: Response, next: NextFunction): void => {
    const { error, value } = schema.validate(req.query, { abortEarly: false, stripUnknown: true });
    if (error) { res.status(422).json({ success: false, error: 'Validation failed', code: 'VALIDATION_ERROR', details: error.details.map((d) => ({ field: d.path.join('.'), message: d.message })) }); return; }
    req.query = value as typeof req.query; next();
  };
}
