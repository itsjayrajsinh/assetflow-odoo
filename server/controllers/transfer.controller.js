import asyncHandler from 'express-async-handler';
import Transfer from '../models/Transfer.js';
import Asset from '../models/Asset.js';
import Allocation from '../models/Allocation.js';
import User from '../models/User.js';
import { logActivity } from '../services/activity.service.js';
import { notify } from '../services/notification.service.js';

export const list = asyncHandler(async (req, res) => {
  const { status, page = 1, limit = 20 } = req.query;
  const q = {};
  if (status) q.status = status;
  if (req.user.role === 'employee') {
    q.$or = [{ requestedBy: req.user._id }, { toUser: req.user._id }, { fromUser: req.user._id }];
  }
  const skip = (Number(page) - 1) * Number(limit);
  const [items, total] = await Promise.all([
    Transfer.find(q)
      .populate('asset', 'assetTag name')
      .populate('fromUser toUser approvedBy requestedBy', 'name email')
      .populate('fromDepartment toDepartment', 'name')
      .sort({ createdAt: -1 })
      .skip(skip).limit(Number(limit)),
    Transfer.countDocuments(q),
  ]);
  res.json({ items, total, page: Number(page), limit: Number(limit) });
});

export const request = asyncHandler(async (req, res) => {
  const { asset: assetId, toUser: toUserId, reason } = req.body;
  const asset = await Asset.findById(assetId);
  if (!asset) { res.status(404); throw new Error('Asset not found'); }
  const toUser = await User.findById(toUserId);
  if (!toUser) { res.status(404); throw new Error('Target user not found'); }
  const t = await Transfer.create({
    asset: asset._id,
    fromUser: asset.currentHolder,
    toUser: toUser._id,
    fromDepartment: asset.currentDepartment,
    toDepartment: toUser.department,
    requestedBy: req.user._id,
    reason,
    status: 'requested',
  });
  await logActivity({ user: req.user._id, action: 'transfer_request', entity: 'Transfer', entityId: t._id, newValue: t.toObject() });
  res.status(201).json(t);
});

export const approve = asyncHandler(async (req, res) => {
  const t = await Transfer.findById(req.params.id);
  if (!t) { res.status(404); throw new Error('Transfer not found'); }
  if (t.status !== 'requested') { res.status(400); throw new Error('Not pending'); }
  t.status = 'approved';
  t.approvedBy = req.user._id;
  await t.save();
  await notify({ user: t.requestedBy, type: 'transfer_approved', title: 'Transfer approved', link: `/transfers` });
  await logActivity({ user: req.user._id, action: 'transfer_approve', entity: 'Transfer', entityId: t._id, newValue: t.toObject() });
  res.json(t);
});

export const reject = asyncHandler(async (req, res) => {
  const t = await Transfer.findById(req.params.id);
  if (!t) { res.status(404); throw new Error('Transfer not found'); }
  if (t.status !== 'requested') { res.status(400); throw new Error('Not pending'); }
  t.status = 'rejected';
  t.approvedBy = req.user._id;
  t.rejectionReason = req.body.reason;
  await t.save();
  await notify({ user: t.requestedBy, type: 'transfer_rejected', title: 'Transfer rejected', message: t.rejectionReason, link: `/transfers` });
  res.json(t);
});

export const complete = asyncHandler(async (req, res) => {
  const t = await Transfer.findById(req.params.id);
  if (!t) { res.status(404); throw new Error('Transfer not found'); }
  if (t.status !== 'approved') { res.status(400); throw new Error('Must be approved first'); }
  const asset = await Asset.findById(t.asset);
  // close existing active allocation
  await Allocation.updateMany(
    { asset: asset._id, status: 'active' },
    { status: 'returned', returnedAt: new Date(), returnCondition: 'good' }
  );
  // create new allocation
  await Allocation.create({
    asset: asset._id,
    employee: t.toUser,
    department: t.toDepartment,
    allocatedBy: req.user._id,
    status: 'active',
  });
  asset.currentHolder = t.toUser;
  asset.currentDepartment = t.toDepartment;
  asset.status = 'allocated';
  await asset.save();
  t.status = 'transferred';
  await t.save();
  await notify({ user: t.toUser, type: 'asset_assigned', title: 'Asset transferred to you', link: `/assets/${asset._id}` });
  res.json(t);
});
