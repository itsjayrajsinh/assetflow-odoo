import asyncHandler from 'express-async-handler';
import Asset from '../models/Asset.js';
import Allocation from '../models/Allocation.js';
import Transfer from '../models/Transfer.js';
import ActivityLog from '../models/ActivityLog.js';

export const summary = asyncHandler(async (req, res) => {
  const now = new Date();
  const in7 = new Date(now.getTime() + 7 * 86400000);

  const [
    available, allocated, maintenance, reserved,
    pendingTransfers, upcomingReturns, overdueReturns,
    byStatus, recent
  ] = await Promise.all([
    Asset.countDocuments({ status: 'available' }),
    Asset.countDocuments({ status: 'allocated' }),
    Asset.countDocuments({ status: 'under_maintenance' }),
    Asset.countDocuments({ status: 'reserved' }),
    Transfer.countDocuments({ status: 'requested' }),
    Allocation.countDocuments({ status: 'active', expectedReturnDate: { $gte: now, $lte: in7 } }),
    Allocation.countDocuments({ status: 'active', expectedReturnDate: { $lt: now } }),
    Asset.aggregate([{ $group: { _id: '$status', count: { $sum: 1 } } }]),
    ActivityLog.find().populate('user', 'name').sort({ createdAt: -1 }).limit(10),
  ]);

  res.json({
    kpi: {
      available, allocated, maintenance, reserved,
      pendingTransfers, upcomingReturns, overdueReturns,
      activeBookings: 0,
    },
    charts: { byStatus },
    recent,
  });
});
