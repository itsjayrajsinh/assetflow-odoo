import { Router } from 'express';
import { protect } from '../middleware/auth.js';
import * as c from '../controllers/dashboard.controller.js';

const r = Router();
r.use(protect);
r.get('/summary', c.summary);
export default r;
