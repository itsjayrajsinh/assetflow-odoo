import asyncHandler from 'express-async-handler';
import Audit from '../models/Audit.js';
import Asset from '../models/Asset.js';

// ─── GET /api/audits ──────────────────────────────────────────────────────────
export const getAudits = asyncHandler(async (req, res) => {
  const { status } = req.query;
  const filter = {};
  if (status) filter.status = status;

  const audits = await Audit.find(filter)
    .populate('auditors', 'name')
    .populate('department', 'name')
    .select('-items.notes') // keep list lightweight
    .sort({ createdAt: -1 });

  res.json({ success: true, data: audits });
});

// ─── GET /api/audits/:id ──────────────────────────────────────────────────────
export const getAudit = asyncHandler(async (req, res) => {
  const audit = await Audit.findById(req.params.id)
    .populate('auditors', 'name')
    .populate('department', 'name')
    .populate('items.asset', 'name assetTag location');

  if (!audit) { res.status(404); throw new Error('Audit not found'); }
  res.json({ success: true, data: audit });
});

// ─── POST /api/audits ─────────────────────────────────────────────────────────
// Creates an audit cycle and auto-populates the checklist from assets
export const createAudit = asyncHandler(async (req, res) => {
  const { title, auditorNames, auditors, department, startDate, endDate, assetFilter } = req.body;

  if (!title || !startDate || !endDate) {
    res.status(400); throw new Error('title, startDate and endDate are required');
  }

  // Build checklist from existing assets
  const assetQuery = { status: { $nin: ['retired', 'disposed', 'lost'] } };
  if (assetFilter?.department) assetQuery.currentDepartment = assetFilter.department;
  if (assetFilter?.category) assetQuery.category = assetFilter.category;

  const assets = await Asset.find(assetQuery).select('_id assetTag name location');
  const items = assets.map((a) => ({
    asset: a._id,
    assetTag: a.assetTag,
    assetName: a.name,
    expectedLocation: a.location || '',
    verification: 'pending',
  }));

  const audit = await Audit.create({
    title,
    auditorNames: auditorNames || [],
    auditors: auditors || [],
    department,
    startDate,
    endDate,
    items,
  });

  res.status(201).json({ success: true, data: audit });
});

// ─── PATCH /api/audits/:id/items/:itemId ──────────────────────────────────────
export const updateAuditItem = asyncHandler(async (req, res) => {
  const { verification, notes } = req.body;

  const audit = await Audit.findById(req.params.id);
  if (!audit) { res.status(404); throw new Error('Audit not found'); }
  if (audit.status === 'closed') { res.status(400); throw new Error('Audit is closed'); }

  const item = audit.items.id(req.params.itemId);
  if (!item) { res.status(404); throw new Error('Audit item not found'); }

  if (verification) item.verification = verification;
  if (notes !== undefined) item.notes = notes;

  await audit.save();
  res.json({ success: true, data: item });
});

// ─── POST /api/audits/:id/close ───────────────────────────────────────────────
// Closes the audit and auto-generates the discrepancy report
export const closeAudit = asyncHandler(async (req, res) => {
  const audit = await Audit.findById(req.params.id);
  if (!audit) { res.status(404); throw new Error('Audit not found'); }
  if (audit.status === 'closed') { res.status(400); throw new Error('Audit already closed'); }

  const flagged = audit.items.filter((i) => ['missing', 'damaged'].includes(i.verification));

  audit.status = 'closed';
  audit.discrepancyReport = {
    flaggedCount: flagged.length,
    generatedAt: new Date(),
    items: flagged.map((i) => ({
      assetTag: i.assetTag,
      assetName: i.assetName,
      issue: i.verification,
      notes: i.notes || '',
    })),
  };

  await audit.save();
  res.json({ success: true, data: audit });
});
