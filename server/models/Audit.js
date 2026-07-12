import mongoose from 'mongoose';

const auditItemSchema = new mongoose.Schema(
  {
    asset: { type: mongoose.Schema.Types.ObjectId, ref: 'Asset', required: true },
    assetTag: String,   // snapshot so it stays even if asset changes
    assetName: String,
    expectedLocation: String,
    verification: {
      type: String,
      enum: ['pending', 'verified', 'missing', 'damaged'],
      default: 'pending',
    },
    notes: String,
  },
  { _id: true }
);

const auditSchema = new mongoose.Schema(
  {
    title: { type: String, required: true, trim: true },
    auditors: [{ type: mongoose.Schema.Types.ObjectId, ref: 'User' }],
    auditorNames: [String], // free-text list shown in header
    department: { type: mongoose.Schema.Types.ObjectId, ref: 'Department' },
    startDate: { type: Date, required: true },
    endDate: { type: Date, required: true },
    status: {
      type: String,
      enum: ['open', 'closed'],
      default: 'open',
      index: true,
    },
    items: [auditItemSchema],
    // Generated on close
    discrepancyReport: {
      flaggedCount: { type: Number, default: 0 },
      generatedAt: Date,
      items: [
        {
          assetTag: String,
          assetName: String,
          issue: String, // 'missing' | 'damaged'
          notes: String,
        },
      ],
    },
  },
  { timestamps: true }
);

export default mongoose.model('Audit', auditSchema);
