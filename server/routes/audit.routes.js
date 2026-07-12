import { Router } from 'express';
import { protect, authorize } from '../middleware/auth.js';
import {
  getAudits,
  getAudit,
  createAudit,
  updateAuditItem,
  closeAudit,
} from '../controllers/audit.controller.js';

const router = Router();

router.use(protect);

router.route('/')
  .get(getAudits)
  .post(authorize('admin', 'asset_manager'), createAudit);

router.get('/:id', getAudit);
router.patch('/:id/items/:itemId', updateAuditItem);
router.post('/:id/close', authorize('admin', 'asset_manager'), closeAudit);

export default router;
