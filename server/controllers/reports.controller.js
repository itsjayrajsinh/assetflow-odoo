import asyncHandler from 'express-async-handler';
import Asset from '../models/Asset.js';
import Allocation from '../models/Allocation.js';
import Booking from '../models/Booking.js';
import Maintenance from '../models/Maintenance.js';
import Department from '../models/Department.js';

// ─── GET /api/reports/analytics ───────────────────────────────────────────────
export const analytics = asyncHandler(async (req, res) => {
  // 1. Utilization by department — count of allocated assets per department
  const departments = await Department.find().select('name');
  const utilizationByDept = await Promise.all(
    departments.map(async (dept) => {
      const total = await Asset.countDocuments({ currentDepartment: dept._id });
      const allocated = await Asset.countDocuments({ currentDepartment: dept._id, status: 'allocated' });
      return { department: dept.name, total, allocated };
    })
  );

  // 2. Maintenance frequency — count of maintenance requests per month (last 6 months)
  const sixMonthsAgo = new Date();
  sixMonthsAgo.setMonth(sixMonthsAgo.getMonth() - 6);
  const maintenanceByMonth = await Maintenance.aggregate([
    { $match: { createdAt: { $gte: sixMonthsAgo } } },
    {
      $group: {
        _id: { $dateToString: { format: '%Y-%m', date: '$createdAt' } },
        count: { $sum: 1 },
      },
    },
    { $sort: { _id: 1 } },
  ]);

  // 3. Most used assets — by booking count
  const mostUsedAssets = await Booking.aggregate([
    { $match: { status: 'confirmed' } },
    { $group: { _id: '$asset', bookings: { $sum: 1 } } },
    { $sort: { bookings: -1 } },
    { $limit: 5 },
    {
      $lookup: {
        from: 'assets',
        localField: '_id',
        foreignField: '_id',
        as: 'asset',
      },
    },
    { $unwind: '$asset' },
    {
      $project: {
        assetTag: '$asset.assetTag',
        name: '$asset.name',
        bookings: 1,
      },
    },
  ]);

  // 4. Idle assets — available for 60+ days (approximated by no recent allocation)
  const sixtyDaysAgo = new Date(Date.now() - 60 * 86400000);
  const recentlyAllocatedIds = await Allocation.distinct('asset', {
    createdAt: { $gte: sixtyDaysAgo },
  });
  const idleAssets = await Asset.find({
    status: 'available',
    _id: { $nin: recentlyAllocatedIds },
  })
    .select('assetTag name')
    .limit(5);

  // 5. Assets due for maintenance / nearing retirement
  // Simple heuristic: assets acquired > 3 years ago in "poor" condition
  const threeYearsAgo = new Date();
  threeYearsAgo.setFullYear(threeYearsAgo.getFullYear() - 3);
  const agingAssets = await Asset.find({
    $or: [
      { condition: 'poor' },
      { acquisitionDate: { $lte: threeYearsAgo } },
    ],
    status: { $nin: ['retired', 'disposed', 'lost'] },
  })
    .select('assetTag name condition acquisitionDate')
    .limit(5);

  res.json({
    success: true,
    data: {
      utilizationByDept,
      maintenanceByMonth: maintenanceByMonth.map((m) => ({
        month: m._id,
        count: m.count,
      })),
      mostUsedAssets,
      idleAssets,
      agingAssets,
    },
  });
});
