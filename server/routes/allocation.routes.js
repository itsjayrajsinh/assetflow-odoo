import { Router } from 'express';
import { protect, authorize } from '../middleware/auth.js';
import * as c from '../controllers/allocation.controller.js';

const r = Router();
r.use(protect);
r.get('/', c.list);
r.post('/', authorize('admin', 'asset_manager'), c.allocate);
r.post('/:id/return', c.returnAsset);
export default r;
