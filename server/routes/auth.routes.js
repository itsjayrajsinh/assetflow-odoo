import { Router } from 'express';
import { signup, login, me, forgotPassword, resetPassword } from '../controllers/auth.controller.js';
import { protect } from '../middleware/auth.js';

const r = Router();
r.post('/signup', signup);
r.post('/login', login);
r.get('/me', protect, me);
r.post('/forgot-password', forgotPassword);
r.post('/reset-password/:token', resetPassword);
export default r;
