import asyncHandler from 'express-async-handler';
import Booking from '../models/Booking.js';
import Asset from '../models/Asset.js';

// ─── Helpers ─────────────────────────────────────────────────────────────────
const toMinutes = (t) => {
  const [h, m] = t.split(':').map(Number);
  return h * 60 + m;
};

const hasConflict = (existingBookings, startTime, endTime) => {
  const s = toMinutes(startTime);
  const e = toMinutes(endTime);
  return existingBookings.some((b) => {
    const bs = toMinutes(b.startTime);
    const be = toMinutes(b.endTime);
    return s < be && e > bs; // overlap
  });
};

// ─── GET /api/bookings?assetId=&date= ────────────────────────────────────────
export const getBookings = asyncHandler(async (req, res) => {
  const { assetId, date } = req.query;
  const filter = { status: 'confirmed' };
  if (assetId) filter.asset = assetId;
  if (date) filter.date = date;

  const bookings = await Booking.find(filter)
    .populate('bookedBy', 'name department')
    .populate('asset', 'name assetTag')
    .sort({ startTime: 1 });

  res.json({ success: true, data: bookings });
});

// ─── GET /api/bookings/resources ─────────────────────────────────────────────
// Returns assets that are bookable (isShared = true)
export const getSharedAssets = asyncHandler(async (_req, res) => {
  const assets = await Asset.find({ isShared: true, status: { $nin: ['retired', 'disposed', 'lost'] } })
    .select('name assetTag location')
    .sort({ name: 1 });
  res.json({ success: true, data: assets });
});

// ─── POST /api/bookings ───────────────────────────────────────────────────────
export const createBooking = asyncHandler(async (req, res) => {
  const { asset, date, startTime, endTime, purpose } = req.body;

  if (!asset || !date || !startTime || !endTime) {
    res.status(400);
    throw new Error('asset, date, startTime, endTime are required');
  }

  if (toMinutes(startTime) >= toMinutes(endTime)) {
    res.status(400);
    throw new Error('startTime must be before endTime');
  }

  // Conflict check
  const existing = await Booking.find({ asset, date, status: 'confirmed' });
  if (hasConflict(existing, startTime, endTime)) {
    res.status(409);
    throw new Error('Time slot conflict — that slot is already booked');
  }

  const booking = await Booking.create({
    asset,
    bookedBy: req.user._id,
    department: req.user.department,
    date,
    startTime,
    endTime,
    purpose,
  });

  await booking.populate('bookedBy', 'name');
  await booking.populate('asset', 'name assetTag');

  res.status(201).json({ success: true, data: booking });
});

// ─── DELETE /api/bookings/:id ─────────────────────────────────────────────────
export const cancelBooking = asyncHandler(async (req, res) => {
  const booking = await Booking.findById(req.params.id);
  if (!booking) { res.status(404); throw new Error('Booking not found'); }

  // Only the booker or admin/asset_manager can cancel
  const isOwner = booking.bookedBy.toString() === req.user._id.toString();
  const isManager = ['admin', 'asset_manager'].includes(req.user.role);
  if (!isOwner && !isManager) {
    res.status(403); throw new Error('Not authorised to cancel this booking');
  }

  booking.status = 'cancelled';
  await booking.save();
  res.json({ success: true, data: booking });
});
