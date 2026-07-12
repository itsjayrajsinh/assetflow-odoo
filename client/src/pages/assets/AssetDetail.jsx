import { useQuery } from '@tanstack/react-query';
import { useParams } from 'react-router-dom';
import api from '../../services/api';
import PageHeader from '../../components/ui/PageHeader.jsx';
import StatusBadge from '../../components/ui/StatusBadge.jsx';
import QRCode from 'react-qr-code';

export default function AssetDetail() {
  const { id } = useParams();
  const { data } = useQuery({ queryKey: ['asset', id], queryFn: async () => (await api.get(`/assets/${id}`)).data });
  const a = data?.asset;
  if (!a) return <div className="p-6 text-slate-500">Loading…</div>;

  return (
    <div>
      <PageHeader title={a.name} subtitle={a.assetTag} actions={<StatusBadge value={a.status} />} />
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div className="card p-5 lg:col-span-2">
          <h3 className="font-semibold mb-4">Details</h3>
          <dl className="grid grid-cols-2 gap-y-3 gap-x-6 text-sm">
            <div><dt className="text-slate-500">Category</dt><dd className="font-medium">{a.category?.name}</dd></div>
            <div><dt className="text-slate-500">Serial</dt><dd>{a.serialNumber || '—'}</dd></div>
            <div><dt className="text-slate-500">Acquisition Date</dt><dd>{a.acquisitionDate ? new Date(a.acquisitionDate).toLocaleDateString() : '—'}</dd></div>
            <div><dt className="text-slate-500">Cost</dt><dd>{a.acquisitionCost?.toLocaleString?.() || 0}</dd></div>
            <div><dt className="text-slate-500">Condition</dt><dd className="capitalize">{a.condition}</dd></div>
            <div><dt className="text-slate-500">Location</dt><dd>{a.location || '—'}</dd></div>
            <div><dt className="text-slate-500">Current Holder</dt><dd>{a.currentHolder?.name || '—'}</dd></div>
            <div><dt className="text-slate-500">Department</dt><dd>{a.currentDepartment?.name || '—'}</dd></div>
          </dl>
        </div>
        <div className="card p-5 flex flex-col items-center">
          <h3 className="font-semibold mb-4 self-start">Asset Tag QR</h3>
          <div className="bg-white p-3 border rounded">
            <QRCode value={a.assetTag} size={160} />
          </div>
          <div className="mt-3 text-sm font-mono">{a.assetTag}</div>
        </div>
      </div>

      <div className="card mt-6">
        <div className="card-header"><h3 className="font-semibold">Allocation history</h3></div>
        <div className="overflow-x-auto">
          <table className="w-full">
            <thead className="bg-slate-50"><tr>
              <th className="table-th">Employee</th><th className="table-th">Allocated</th>
              <th className="table-th">Expected Return</th><th className="table-th">Returned</th><th className="table-th">Status</th>
            </tr></thead>
            <tbody>
              {(data?.allocationHistory || []).map((h) => (
                <tr key={h._id}>
                  <td className="table-td">{h.employee?.name}</td>
                  <td className="table-td">{new Date(h.allocatedAt).toLocaleDateString()}</td>
                  <td className="table-td">{h.expectedReturnDate ? new Date(h.expectedReturnDate).toLocaleDateString() : '—'}</td>
                  <td className="table-td">{h.returnedAt ? new Date(h.returnedAt).toLocaleDateString() : '—'}</td>
                  <td className="table-td"><StatusBadge value={h.status} /></td>
                </tr>
              ))}
              {(data?.allocationHistory || []).length === 0 && <tr><td className="table-td text-center text-slate-500" colSpan={5}>No history</td></tr>}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
}
