import { Router } from 'express';
import { protect, authorize } from '../middleware/auth.js';
import { analytics } from '../controllers/reports.controller.js';

const router = Router();
router.use(protect);
router.get('/analytics', authorize('admin', 'asset_manager'), analytics);

export default router;
