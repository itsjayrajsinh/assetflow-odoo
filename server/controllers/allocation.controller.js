import asyncHandler from 'express-async-handler';
import Allocation from '../models/Allocation.js';
import Asset from '../models/Asset.js';
import User from '../models/User.js';
import { logActivity } from '../services/activity.service.js';
import { notify } from '../services/notification.service.js';

export const list = asyncHandler(async (req, res) => {
  const { status, asset, employee, page = 1, limit = 20 } = req.query;
  const q = {};
  if (status) q.status = status;
  if (asset) q.asset = asset;
  if (employee) q.employee = employee;
  // employees only see their own
  if (req.user.role === 'employee') q.employee = req.user._id;
  const skip = (Number(page) - 1) * Number(limit);
  const [items, total] = await Promise.all([
    Allocation.find(q)
      .populate('asset', 'assetTag name')
      .populate('employee', 'name email')
      .populate('department', 'name')
      .populate('allocatedBy', 'name')
      .sort({ createdAt: -1 })
      .skip(skip)
      .limit(Number(limit)),
    Allocation.countDocuments(q),
  ]);
  // auto-mark overdue
  const now = new Date();
  await Allocation.updateMany(
    { status: 'active', expectedReturnDate: { $lt: now } },
    { status: 'overdue' }
  );
  res.json({ items, total, page: Number(page), limit: Number(limit) });
});

export const allocate = asyncHandler(async (req, res) => {
  const { asset: assetId, employee: employeeId, expectedReturnDate } = req.body;
  const asset = await Asset.findById(assetId);
  if (!asset) { res.status(404); throw new Error('Asset not found'); }
  if (asset.status === 'allocated') { res.status(400); throw new Error('Asset already allocated'); }
  if (!['available', 'reserved'].includes(asset.status)) {
    res.status(400); throw new Error(`Asset is ${asset.status} and cannot be allocated`);
  }
  const employee = await User.findById(employeeId);
  if (!employee) { res.status(404); throw new Error('Employee not found'); }

  const a = await Allocation.create({
    asset: asset._id,
    employee: employee._id,
    department: employee.department,
    allocatedBy: req.user._id,
    expectedReturnDate: expectedReturnDate ? new Date(expectedReturnDate) : undefined,
  });
  asset.status = 'allocated';
  asset.currentHolder = employee._id;
  asset.currentDepartment = employee.department;
  await asset.save();

  await notify({
    user: employee._id,
    type: 'asset_assigned',
    title: 'Asset assigned to you',
    message: `${asset.name} (${asset.assetTag}) has been allocated to you.`,
    link: `/assets/${asset._id}`,
  });
  await logActivity({ user: req.user._id, action: 'allocate', entity: 'Allocation', entityId: a._id, newValue: a.toObject() });
  res.status(201).json(a);
});

export const returnAsset = asyncHandler(async (req, res) => {
  const { returnCondition, returnNotes } = req.body;
  const a = await Allocation.findById(req.params.id);
  if (!a) { res.status(404); throw new Error('Allocation not found'); }
  if (a.status === 'returned') { res.status(400); throw new Error('Already returned'); }

  const before = a.toObject();
  a.status = 'returned';
  a.returnedAt = new Date();
  a.returnCondition = returnCondition;
  a.returnNotes = returnNotes;
  await a.save();

  const asset = await Asset.findById(a.asset);
  if (asset) {
    asset.status = returnCondition === 'damaged' ? 'under_maintenance' : 'available';
    asset.currentHolder = null;
    asset.currentDepartment = null;
    if (returnCondition && ['good', 'fair', 'poor'].includes(returnCondition)) asset.condition = returnCondition;
    await asset.save();
  }
  await logActivity({ user: req.user._id, action: 'return', entity: 'Allocation', entityId: a._id, previousValue: before, newValue: a.toObject() });
  res.json(a);
});
