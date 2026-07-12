import { Router } from 'express';
import { protect, authorize } from '../middleware/auth.js';
import * as c from '../controllers/activity.controller.js';

const r = Router();
r.use(protect);
r.get('/', authorize('admin', 'asset_manager'), c.list);
export default r;
