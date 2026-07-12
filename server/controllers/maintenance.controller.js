import asyncHandler from 'express-async-handler';
import Maintenance, { MAINTENANCE_STAGES } from '../models/Maintenance.js';
import Asset from '../models/Asset.js';

// ─── GET /api/maintenance ─────────────────────────────────────────────────────
export const getMaintenance = asyncHandler(async (req, res) => {
  const { stage, assetId } = req.query;
  const filter = {};
  if (stage) filter.stage = stage;
  if (assetId) filter.asset = assetId;

  const requests = await Maintenance.find(filter)
    .populate('asset', 'name assetTag status')
    .populate('reportedBy', 'name')
    .populate('technician', 'name')
    .sort({ createdAt: -1 });

  res.json({ success: true, data: requests });
});

// ─── POST /api/maintenance ────────────────────────────────────────────────────
export const createMaintenance = asyncHandler(async (req, res) => {
  const { asset, description, priority, technicianName } = req.body;

  if (!asset || !description) {
    res.status(400); throw new Error('asset and description are required');
  }

  const request = await Maintenance.create({
    asset,
    description,
    priority: priority || 'medium',
    technicianName,
    reportedBy: req.user._id,
  });

  await request.populate('asset', 'name assetTag');
  await request.populate('reportedBy', 'name');
  res.status(201).json({ success: true, data: request });
});

// ─── PATCH /api/maintenance/:id/stage ─────────────────────────────────────────
// Move a card to a new stage (Kanban advance). Only admin/asset_manager.
export const updateStage = asyncHandler(async (req, res) => {
  const { stage } = req.body;
  if (!MAINTENANCE_STAGES.includes(stage)) {
    res.status(400); throw new Error(`Invalid stage. Must be one of: ${MAINTENANCE_STAGES.join(', ')}`);
  }

  const request = await Maintenance.findById(req.params.id).populate('asset');
  if (!request) { res.status(404); throw new Error('Maintenance request not found'); }

  const prevStage = request.stage;
  request.stage = stage;

  // Business logic: approved → asset goes under_maintenance
  if (stage === 'approved' && prevStage === 'pending') {
    await Asset.findByIdAndUpdate(request.asset._id, { status: 'under_maintenance' });
  }

  // Business logic: resolved → asset goes back to available
  if (stage === 'resolved') {
    request.resolvedAt = new Date();
    await Asset.findByIdAndUpdate(request.asset._id, { status: 'available' });
  }

  await request.save();
  await request.populate('asset', 'name assetTag status');
  await request.populate('reportedBy', 'name');
  await request.populate('technician', 'name');
  res.json({ success: true, data: request });
});

// ─── PATCH /api/maintenance/:id ───────────────────────────────────────────────
// Update details: assign technician, add notes
export const updateMaintenance = asyncHandler(async (req, res) => {
  const allowed = ['technicianName', 'technician', 'notes', 'resolvedNotes', 'priority'];
  const updates = {};
  allowed.forEach((k) => { if (req.body[k] !== undefined) updates[k] = req.body[k]; });

  const request = await Maintenance.findByIdAndUpdate(req.params.id, updates, { new: true })
    .populate('asset', 'name assetTag status')
    .populate('reportedBy', 'name')
    .populate('technician', 'name');

  if (!request) { res.status(404); throw new Error('Maintenance request not found'); }
  res.json({ success: true, data: request });
});

// ─── DELETE /api/maintenance/:id ──────────────────────────────────────────────
export const deleteMaintenance = asyncHandler(async (req, res) => {
  const request = await Maintenance.findByIdAndDelete(req.params.id);
  if (!request) { res.status(404); throw new Error('Not found'); }
  res.json({ success: true, data: {} });
});
