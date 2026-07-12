import { Router } from 'express';
import { protect, authorize } from '../middleware/auth.js';
import { upload } from '../middleware/upload.js';
import * as c from '../controllers/asset.controller.js';

const r = Router();
r.use(protect);
r.get('/', c.list);
r.get('/:id', c.get);
r.post('/', authorize('admin', 'asset_manager'), upload.single('photo'), c.create);
r.patch('/:id', authorize('admin', 'asset_manager'), upload.single('photo'), c.update);
r.delete('/:id', authorize('admin'), c.remove);
export default r;
