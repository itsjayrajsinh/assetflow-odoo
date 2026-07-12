import mongoose from 'mongoose';

const STAGES = ['pending', 'approved', 'technician_assigned', 'in_progress', 'resolved'];

const maintenanceSchema = new mongoose.Schema(
  {
    asset: { type: mongoose.Schema.Types.ObjectId, ref: 'Asset', required: true, index: true },
    reportedBy: { type: mongoose.Schema.Types.ObjectId, ref: 'User', required: true },
    description: { type: String, required: true, trim: true },
    stage: {
      type: String,
      enum: STAGES,
      default: 'pending',
      index: true,
    },
    technician: { type: mongoose.Schema.Types.ObjectId, ref: 'User' },
    technicianName: { type: String, trim: true }, // free-text fallback
    priority: {
      type: String,
      enum: ['low', 'medium', 'high'],
      default: 'medium',
    },
    notes: { type: String, trim: true },
    resolvedAt: Date,
    resolvedNotes: { type: String, trim: true },
  },
  { timestamps: true }
);

export const MAINTENANCE_STAGES = STAGES;
export default mongoose.model('Maintenance', maintenanceSchema);
