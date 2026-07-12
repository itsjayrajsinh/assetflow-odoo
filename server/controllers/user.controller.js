import asyncHandler from 'express-async-handler';
import User from '../models/User.js';
import { logActivity } from '../services/activity.service.js';

export const listUsers = asyncHandler(async (req, res) => {
  const { search = '', role, department, page = 1, limit = 20 } = req.query;
  const q = {};
  if (search) q.$or = [{ name: new RegExp(search, 'i') }, { email: new RegExp(search, 'i') }];
  if (role) q.role = role;
  if (department) q.department = department;
  const skip = (Number(page) - 1) * Number(limit);
  const [items, total] = await Promise.all([
    User.find(q).populate('department', 'name').sort({ createdAt: -1 }).skip(skip).limit(Number(limit)),
    User.countDocuments(q),
  ]);
  res.json({ items, total, page: Number(page), limit: Number(limit) });
});

export const getUser = asyncHandler(async (req, res) => {
  const u = await User.findById(req.params.id).populate('department', 'name');
  if (!u) { res.status(404); throw new Error('User not found'); }
  res.json(u);
});

export const updateUser = asyncHandler(async (req, res) => {
  const u = await User.findById(req.params.id);
  if (!u) { res.status(404); throw new Error('User not found'); }
  if (u._id.toString() !== req.user._id.toString() && req.user.role !== 'admin') {
    res.status(403); throw new Error('Not authorized to update this user');
  }
  const before = u.toObject();
  const { name, email, phone, department, isActive, role } = req.body;
  if (name !== undefined) u.name = name;
  if (email !== undefined) u.email = email;
  if (phone !== undefined) u.phone = phone;
  if (department !== undefined) u.department = department || null;
  if (isActive !== undefined && req.user.role === 'admin') u.isActive = isActive;
  if (role !== undefined && req.user.role === 'admin') u.role = role;
  await u.save();
  await logActivity({ user: req.user._id, action: 'update', entity: 'User', entityId: u._id, previousValue: before, newValue: u.toObject() });
  res.json(u);
});

export const promote = asyncHandler(async (req, res) => {
  const { role } = req.body;
  if (!['department_head', 'asset_manager', 'employee'].includes(role)) {
    res.status(400); throw new Error('Invalid target role');
  }
  const u = await User.findById(req.params.id);
  if (!u) { res.status(404); throw new Error('User not found'); }
  const previous = u.role;
  u.role = role;
  await u.save();
  await logActivity({ user: req.user._id, action: 'promote', entity: 'User', entityId: u._id, previousValue: { role: previous }, newValue: { role } });
  res.json(u);
});

export const deleteUser = asyncHandler(async (req, res) => {
  const u = await User.findByIdAndDelete(req.params.id);
  if (!u) { res.status(404); throw new Error('User not found'); }
  await logActivity({ user: req.user._id, action: 'delete', entity: 'User', entityId: u._id, previousValue: u.toObject() });
  res.json({ ok: true });
});
