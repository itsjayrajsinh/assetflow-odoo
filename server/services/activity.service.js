import ActivityLog from '../models/ActivityLog.js';

export async function logActivity({ user, action, entity, entityId, previousValue, newValue, metadata }) {
  try {
    await ActivityLog.create({ user, action, entity, entityId, previousValue, newValue, metadata });
  } catch (e) {
    console.error('activity log failed', e.message);
  }
}
