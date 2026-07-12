import Asset from '../models/Asset.js';

export async function generateAssetTag() {
  const year = new Date().getFullYear();
  const prefix = `AF-${year}-`;
  const last = await Asset.findOne({ assetTag: new RegExp(`^${prefix}`) })
    .sort({ createdAt: -1 })
    .lean();
  let n = 1;
  if (last) {
    const m = last.assetTag.match(/(\d+)$/);
    if (m) n = parseInt(m[1], 10) + 1;
  }
  return `${prefix}${String(n).padStart(5, '0')}`;
}
