import asyncHandler from 'express-async-handler';
import AssetCategory from '../models/AssetCategory.js';
import { logActivity } from '../services/activity.service.js';

export const list = asyncHandler(async (_req, res) => {
  const items = await AssetCategory.find().sort({ name: 1 });
  res.json({ items, total: items.length });
});

export const get = asyncHandler(async (req, res) => {
  const c = await AssetCategory.findById(req.params.id);
  if (!c) { res.status(404); throw new Error('Category not found'); }
  res.json(c);
});

export const create = asyncHandler(async (req, res) => {
  const c = await AssetCategory.create(req.body);
  await logActivity({ user: req.user._id, action: 'create', entity: 'AssetCategory', entityId: c._id, newValue: c.toObject() });
  res.status(201).json(c);
});

export const update = asyncHandler(async (req, res) => {
  const c = await AssetCategory.findById(req.params.id);
  if (!c) { res.status(404); throw new Error('Category not found'); }
  const before = c.toObject();
  Object.assign(c, req.body);
  await c.save();
  await logActivity({ user: req.user._id, action: 'update', entity: 'AssetCategory', entityId: c._id, previousValue: before, newValue: c.toObject() });
  res.json(c);
});

export const remove = asyncHandler(async (req, res) => {
  const c = await AssetCategory.findByIdAndDelete(req.params.id);
  if (!c) { res.status(404); throw new Error('Category not found'); }
  await logActivity({ user: req.user._id, action: 'delete', entity: 'AssetCategory', entityId: c._id, previousValue: c.toObject() });
  res.json({ ok: true });
});
