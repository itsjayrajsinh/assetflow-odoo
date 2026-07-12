import { Router } from 'express';
import { protect } from '../middleware/auth.js';
import {
  getBookings,
  getSharedAssets,
  createBooking,
  cancelBooking,
} from '../controllers/booking.controller.js';

const router = Router();

router.use(protect);

router.get('/resources', getSharedAssets);
router.route('/').get(getBookings).post(createBooking);
router.delete('/:id', cancelBooking);

export default router;
