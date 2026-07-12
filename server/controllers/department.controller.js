import asyncHandler from 'express-async-handler';
import Department from '../models/Department.js';
import { logActivity } from '../services/activity.service.js';

export const list = asyncHandler(async (req, res) => {
  const { search = '', active } = req.query;
  const q = {};
  if (search) q.name = new RegExp(search, 'i');
  if (active !== undefined) q.isActive = active === 'true';
  const items = await Department.find(q).populate('head', 'name email').populate('parent', 'name');
  res.json({ items, total: items.length });
});

export const get = asyncHandler(async (req, res) => {
  const d = await Department.findById(req.params.id).populate('head parent');
  if (!d) { res.status(404); throw new Error('Department not found'); }
  res.json(d);
});

export const create = asyncHandler(async (req, res) => {
  const d = await Department.create(req.body);
  await logActivity({ user: req.user._id, action: 'create', entity: 'Department', entityId: d._id, newValue: d.toObject() });
  res.status(201).json(d);
});

export const update = asyncHandler(async (req, res) => {
  const d = await Department.findById(req.params.id);
  if (!d) { res.status(404); throw new Error('Department not found'); }
  const before = d.toObject();
  Object.assign(d, req.body);
  await d.save();
  await logActivity({ user: req.user._id, action: 'update', entity: 'Department', entityId: d._id, previousValue: before, newValue: d.toObject() });
  res.json(d);
});

export const remove = asyncHandler(async (req, res) => {
  const d = await Department.findByIdAndDelete(req.params.id);
  if (!d) { res.status(404); throw new Error('Department not found'); }
  await logActivity({ user: req.user._id, action: 'delete', entity: 'Department', entityId: d._id, previousValue: d.toObject() });
  res.json({ ok: true });
});
