import { Router } from 'express';
import { protect, authorize } from '../middleware/auth.js';
import {
  getMaintenance,
  createMaintenance,
  updateStage,
  updateMaintenance,
  deleteMaintenance,
} from '../controllers/maintenance.controller.js';

const router = Router();

router.use(protect);

router.route('/')
  .get(getMaintenance)
  .post(createMaintenance);

router.patch('/:id/stage', authorize('admin', 'asset_manager', 'department_head'), updateStage);

router.route('/:id')
  .patch(authorize('admin', 'asset_manager'), updateMaintenance)
  .delete(authorize('admin', 'asset_manager'), deleteMaintenance);

export default router;
