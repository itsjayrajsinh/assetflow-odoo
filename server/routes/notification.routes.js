import { Router } from 'express';
import { protect } from '../middleware/auth.js';
import * as c from '../controllers/notification.controller.js';

const r = Router();
r.use(protect);
r.get('/', c.list);
r.post('/:id/read', c.markRead);
r.post('/read-all', c.markAllRead);
export default r;
