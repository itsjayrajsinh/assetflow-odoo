import asyncHandler from 'express-async-handler';
import ActivityLog from '../models/ActivityLog.js';

export const list = asyncHandler(async (req, res) => {
  const { entity, entityId, page = 1, limit = 30 } = req.query;
  const q = {};
  if (entity) q.entity = entity;
  if (entityId) q.entityId = entityId;
  const skip = (Number(page) - 1) * Number(limit);
  const [items, total] = await Promise.all([
    ActivityLog.find(q).populate('user', 'name email').sort({ createdAt: -1 }).skip(skip).limit(Number(limit)),
    ActivityLog.countDocuments(q),
  ]);
  res.json({ items, total, page: Number(page), limit: Number(limit) });
});
