import mongoose from 'mongoose';

const assetSchema = new mongoose.Schema(
  {
    assetTag: { type: String, required: true, unique: true, index: true },
    name: { type: String, required: true, trim: true },
    category: { type: mongoose.Schema.Types.ObjectId, ref: 'AssetCategory', required: true },
    serialNumber: { type: String, trim: true },
    acquisitionDate: Date,
    acquisitionCost: { type: Number, default: 0 },
    condition: {
      type: String,
      enum: ['new', 'good', 'fair', 'poor'],
      default: 'good',
    },
    location: String,
    photo: String,
    documents: [{ name: String, url: String }],
    qrCode: String,
    isShared: { type: Boolean, default: false },
    status: {
      type: String,
      enum: [
        'available',
        'allocated',
        'reserved',
        'under_maintenance',
        'lost',
        'retired',
        'disposed',
      ],
      default: 'available',
      index: true,
    },
    currentHolder: { type: mongoose.Schema.Types.ObjectId, ref: 'User' },
    currentDepartment: { type: mongoose.Schema.Types.ObjectId, ref: 'Department' },
    customFieldValues: { type: Map, of: mongoose.Schema.Types.Mixed },
  },
  { timestamps: true }
);

export default mongoose.model('Asset', assetSchema);
