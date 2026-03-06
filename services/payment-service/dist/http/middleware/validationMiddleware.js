"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.validateBody = validateBody;
exports.validateQuery = validateQuery;
function validateBody(schema) {
    return (req, res, next) => {
        const { error, value } = schema.validate(req.body, { abortEarly: false, stripUnknown: true });
        if (error) {
            res.status(422).json({ success: false, error: 'Validation failed', code: 'VALIDATION_ERROR', details: error.details.map((d) => ({ field: d.path.join('.'), message: d.message })) });
            return;
        }
        req.body = value;
        next();
    };
}
function validateQuery(schema) {
    return (req, res, next) => {
        const { error, value } = schema.validate(req.query, { abortEarly: false, stripUnknown: true });
        if (error) {
            res.status(422).json({ success: false, error: 'Validation failed', code: 'VALIDATION_ERROR', details: error.details.map((d) => ({ field: d.path.join('.'), message: d.message })) });
            return;
        }
        req.query = value;
        next();
    };
}
//# sourceMappingURL=validationMiddleware.js.map