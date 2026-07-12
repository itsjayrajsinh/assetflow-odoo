import { Router } from 'express';
import { protect, authorize } from '../middleware/auth.js';
import { listUsers, getUser, updateUser, promote, deleteUser } from '../controllers/user.controller.js';

const r = Router();
r.use(protect);
r.get('/', listUsers);
r.get('/:id', getUser);
r.patch('/:id', updateUser);
r.post('/:id/promote', authorize('admin'), promote);
r.delete('/:id', authorize('admin'), deleteUser);
export default r;
