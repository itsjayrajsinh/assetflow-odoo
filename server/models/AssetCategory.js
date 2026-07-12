import mongoose from 'mongoose';

const customFieldSchema = new mongoose.Schema(
  {
    name: { type: String, required: true },
    type: { type: String, enum: ['text', 'number', 'date', 'boolean'], default: 'text' },
    required: { type: Boolean, default: false },
  },
  { _id: false }
);

const categorySchema = new mongoose.Schema(
  {
    name: { type: String, required: true, unique: true, trim: true },
    description: String,
    warrantyPeriodMonths: { type: Number, default: 0 },
    customFields: [customFieldSchema],
    isActive: { type: Boolean, default: true },
  },
  { timestamps: true }
);

export default mongoose.model('AssetCategory', categorySchema);
