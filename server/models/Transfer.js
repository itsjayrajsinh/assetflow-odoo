import mongoose from 'mongoose';

const transferSchema = new mongoose.Schema(
  {
    asset: { type: mongoose.Schema.Types.ObjectId, ref: 'Asset', required: true },
    fromUser: { type: mongoose.Schema.Types.ObjectId, ref: 'User' },
    toUser: { type: mongoose.Schema.Types.ObjectId, ref: 'User', required: true },
    fromDepartment: { type: mongoose.Schema.Types.ObjectId, ref: 'Department' },
    toDepartment: { type: mongoose.Schema.Types.ObjectId, ref: 'Department' },
    requestedBy: { type: mongoose.Schema.Types.ObjectId, ref: 'User' },
    approvedBy: { type: mongoose.Schema.Types.ObjectId, ref: 'User' },
    reason: String,
    status: {
      type: String,
      enum: ['requested', 'approved', 'rejected', 'transferred'],
      default: 'requested',
      index: true,
    },
    rejectionReason: String,
  },
  { timestamps: true }
);

export default mongoose.model('Transfer', transferSchema);
