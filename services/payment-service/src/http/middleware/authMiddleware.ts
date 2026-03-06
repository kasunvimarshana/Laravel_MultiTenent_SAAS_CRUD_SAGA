import { Request, Response, NextFunction } from 'express';
import { config } from '../../config';
export function authMiddleware(req: Request, res: Response, next: NextFunction): void {
  const authHeader = req.headers['authorization'];
  if (!authHeader) { res.status(401).json({ success: false, error: 'Missing Authorization header', code: 'UNAUTHORIZED' }); return; }
  const parts = authHeader.split(' ');
  if (parts.length !== 2 || parts[0]?.toLowerCase() !== 'bearer') { res.status(401).json({ success: false, error: 'Invalid Authorization format', code: 'UNAUTHORIZED' }); return; }
  const token = parts[1];
  if (config.app.nodeEnv === 'production' && token !== config.auth.jwtSecret) { res.status(401).json({ success: false, error: 'Invalid token', code: 'UNAUTHORIZED' }); return; }
  next();
}
