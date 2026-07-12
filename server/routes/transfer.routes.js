import { Router } from 'express';
import { protect, authorize } from '../middleware/auth.js';
import * as c from '../controllers/transfer.controller.js';

const r = Router();
r.use(protect);
r.get('/', c.list);
r.post('/', c.request);
r.post('/:id/approve', authorize('admin', 'asset_manager', 'department_head'), c.approve);
r.post('/:id/reject', authorize('admin', 'asset_manager', 'department_head'), c.reject);
r.post('/:id/complete', authorize('admin', 'asset_manager'), c.complete);
export default r;
