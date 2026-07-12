import 'dotenv/config';
import mongoose from 'mongoose';
import connectDB from '../config/db.js';
import User from '../models/User.js';
import Department from '../models/Department.js';
import AssetCategory from '../models/AssetCategory.js';
import Asset from '../models/Asset.js';
import QRCode from 'qrcode';

await connectDB();

console.log('🧹 Clearing DB...');
await Promise.all([
  User.deleteMany({}),
  Department.deleteMany({}),
  AssetCategory.deleteMany({}),
  Asset.deleteMany({}),
]);

console.log('🌱 Seeding departments...');
const [it, ops, hr] = await Department.create([
  { name: 'IT', code: 'IT', description: 'Information Technology' },
  { name: 'Operations', code: 'OPS' },
  { name: 'Human Resources', code: 'HR' },
]);

console.log('🌱 Seeding users...');
const admin = await User.create({ name: 'System Admin', email: 'admin@assetflow.com', password: 'Admin@123', role: 'admin', department: it._id });
const manager = await User.create({ name: 'Asset Manager', email: 'manager@assetflow.com', password: 'Manager@123', role: 'asset_manager', department: it._id });
const head = await User.create({ name: 'Dept Head', email: 'head@assetflow.com', password: 'Head@123', role: 'department_head', department: ops._id });
const employee = await User.create({ name: 'Jane Employee', email: 'employee@assetflow.com', password: 'Employee@123', role: 'employee', department: ops._id });

it.head = admin._id; await it.save();
ops.head = head._id; await ops.save();

console.log('🌱 Seeding categories...');
const [laptop, monitor, chair] = await AssetCategory.create([
  { name: 'Laptops', warrantyPeriodMonths: 24 },
  { name: 'Monitors', warrantyPeriodMonths: 36 },
  { name: 'Office Chairs', warrantyPeriodMonths: 12 },
]);

console.log('🌱 Seeding assets...');
const tag = (i) => `AF-${new Date().getFullYear()}-${String(i).padStart(5,'0')}`;
const samples = [
  { name: 'MacBook Pro 14"', category: laptop._id, serialNumber: 'MBP14-001', acquisitionCost: 2200, condition: 'new', location: 'HQ Floor 3' },
  { name: 'Dell Latitude 7440', category: laptop._id, serialNumber: 'DL7440-002', acquisitionCost: 1600, condition: 'good', location: 'HQ Floor 2' },
  { name: 'LG UltraFine 27"', category: monitor._id, serialNumber: 'LG27-003', acquisitionCost: 550, condition: 'good', location: 'HQ Floor 3' },
  { name: 'Herman Miller Aeron', category: chair._id, serialNumber: 'HM-004', acquisitionCost: 1200, condition: 'good', location: 'HQ Floor 1' },
];
let i = 1;
for (const s of samples) {
  const t = tag(i++);
  const qr = await QRCode.toDataURL(t);
  await Asset.create({ ...s, assetTag: t, qrCode: qr, acquisitionDate: new Date() });
}

console.log('✅ Seed complete');
console.log('Login credentials:');
console.log('  Admin        admin@assetflow.com    Admin@123');
console.log('  Asset Mgr    manager@assetflow.com  Manager@123');
console.log('  Dept Head    head@assetflow.com     Head@123');
console.log('  Employee     employee@assetflow.com Employee@123');
await mongoose.disconnect();
process.exit(0);
