import asyncHandler from 'express-async-handler';
import QRCode from 'qrcode';
import Asset from '../models/Asset.js';
import Allocation from '../models/Allocation.js';
import { generateAssetTag } from '../utils/assetTag.js';
import { logActivity } from '../services/activity.service.js';

export const list = asyncHandler(async (req, res) => {
  const { search = '', status, category, page = 1, limit = 20 } = req.query;
  const q = {};
  if (search) q.$or = [{ name: new RegExp(search, 'i') }, { assetTag: new RegExp(search, 'i') }, { serialNumber: new RegExp(search, 'i') }];
  if (status) q.status = status;
  if (category) q.category = category;
  const skip = (Number(page) - 1) * Number(limit);
  const [items, total] = await Promise.all([
    Asset.find(q)
      .populate('category', 'name')
      .populate('currentHolder', 'name email')
      .populate('currentDepartment', 'name')
      .sort({ createdAt: -1 })
      .skip(skip)
      .limit(Number(limit)),
    Asset.countDocuments(q),
  ]);
  res.json({ items, total, page: Number(page), limit: Number(limit) });
});

export const get = asyncHandler(async (req, res) => {
  const a = await Asset.findById(req.params.id)
    .populate('category')
    .populate('currentHolder', 'name email')
    .populate('currentDepartment', 'name');
  if (!a) { res.status(404); throw new Error('Asset not found'); }
  const history = await Allocation.find({ asset: a._id })
    .populate('employee', 'name email')
    .populate('allocatedBy', 'name')
    .sort({ createdAt: -1 });
  res.json({ asset: a, allocationHistory: history });
});

export const create = asyncHandler(async (req, res) => {
  const body = { ...req.body };
  body.assetTag = await generateAssetTag();
  if (req.file) body.photo = `/uploads/${req.file.filename}`;
  body.qrCode = await QRCode.toDataURL(body.assetTag);
  const a = await Asset.create(body);
  await logActivity({ user: req.user._id, action: 'create', entity: 'Asset', entityId: a._id, newValue: a.toObject() });
  res.status(201).json(a);
});

export const update = asyncHandler(async (req, res) => {
  const a = await Asset.findById(req.params.id);
  if (!a) { res.status(404); throw new Error('Asset not found'); }
  const before = a.toObject();
  const body = { ...req.body };
  if (req.file) body.photo = `/uploads/${req.file.filename}`;
  Object.assign(a, body);
  await a.save();
  await logActivity({ user: req.user._id, action: 'update', entity: 'Asset', entityId: a._id, previousValue: before, newValue: a.toObject() });
  res.json(a);
});

export const remove = asyncHandler(async (req, res) => {
  const a = await Asset.findByIdAndDelete(req.params.id);
  if (!a) { res.status(404); throw new Error('Asset not found'); }
  await logActivity({ user: req.user._id, action: 'delete', entity: 'Asset', entityId: a._id, previousValue: a.toObject() });
  res.json({ ok: true });
});
