const map = {
  available: 'badge-green',
  allocated: 'badge-blue',
  reserved: 'badge-amber',
  under_maintenance: 'badge-amber',
  lost: 'badge-red',
  retired: 'badge-slate',
  disposed: 'badge-slate',
  active: 'badge-green',
  returned: 'badge-slate',
  overdue: 'badge-red',
  requested: 'badge-amber',
  approved: 'badge-blue',
  rejected: 'badge-red',
  transferred: 'badge-green',
};

export default function StatusBadge({ value }) {
  const cls = map[value] || 'badge-slate';
  return <span className={cls}>{String(value || '').replace('_',' ')}</span>;
}
