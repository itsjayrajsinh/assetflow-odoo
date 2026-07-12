import mongoose from 'mongoose';

const allocationSchema = new mongoose.Schema(
  {
    asset: { type: mongoose.Schema.Types.ObjectId, ref: 'Asset', required: true, index: true },
    employee: { type: mongoose.Schema.Types.ObjectId, ref: 'User', required: true },
    department: { type: mongoose.Schema.Types.ObjectId, ref: 'Department' },
    allocatedBy: { type: mongoose.Schema.Types.ObjectId, ref: 'User' },
    allocatedAt: { type: Date, default: Date.now },
    expectedReturnDate: Date,
    returnedAt: Date,
    returnCondition: { type: String, enum: ['good', 'fair', 'poor', 'damaged'] },
    returnNotes: String,
    status: {
      type: String,
      enum: ['active', 'returned', 'overdue'],
      default: 'active',
      index: true,
    },
  },
  { timestamps: true }
);

export default mongoose.model('Allocation', allocationSchema);
