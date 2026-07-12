import { useQuery } from '@tanstack/react-query';
import api from '../../services/api';
import PageHeader from '../../components/ui/PageHeader.jsx';
import toast from 'react-hot-toast';
import { Download, AlertTriangle, Clock, TrendingUp } from 'lucide-react';
import {
  BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer,
  LineChart, Line,
} from 'recharts';
import jsPDF from 'jspdf';
import 'jspdf-autotable';

export default function Reports() {
  const { data, isLoading } = useQuery({
    queryKey: ['reports-analytics'],
    queryFn: async () => (await api.get('/reports/analytics')).data,
  });

  const analytics = data?.data || {};
  const { utilizationByDept = [], maintenanceByMonth = [], mostUsedAssets = [], idleAssets = [], agingAssets = [] } = analytics;

  // Format month labels
  const maintData = maintenanceByMonth.map((m) => ({
    ...m,
    label: new Date(m.month + '-01').toLocaleString('en-US', { month: 'short', year: '2-digit' }),
  }));

  const exportReport = () => {
    const doc = new jsPDF();
    doc.setFontSize(16);
    doc.text('AssetFlow — Reports & Analytics', 14, 18);
    doc.setFontSize(9);
    doc.text(`Generated: ${new Date().toLocaleString()}`, 14, 26);

    let y = 34;

    // Utilization by department
    doc.setFontSize(12);
    doc.text('Utilization by Department', 14, y);
    y += 4;
    doc.autoTable({
      startY: y,
      head: [['Department', 'Total Assets', 'Allocated']],
      body: utilizationByDept.map((d) => [d.department, d.total, d.allocated]),
      styles: { fontSize: 8 },
      headStyles: { fillColor: [15, 23, 42] },
    });
    y = doc.lastAutoTable.finalY + 10;

    // Most used assets
    doc.setFontSize(12);
    doc.text('Most Used Assets', 14, y);
    y += 4;
    doc.autoTable({
      startY: y,
      head: [['Asset Tag', 'Name', 'Bookings']],
      body: mostUsedAssets.map((a) => [a.assetTag, a.name, a.bookings]),
      styles: { fontSize: 8 },
      headStyles: { fillColor: [15, 23, 42] },
    });
    y = doc.lastAutoTable.finalY + 10;

    // Idle assets
    doc.setFontSize(12);
    doc.text('Idle Assets (60+ days)', 14, y);
    y += 4;
    doc.autoTable({
      startY: y,
      head: [['Asset Tag', 'Name']],
      body: idleAssets.map((a) => [a.assetTag, a.name]),
      styles: { fontSize: 8 },
      headStyles: { fillColor: [15, 23, 42] },
    });
    y = doc.lastAutoTable.finalY + 10;

    // Aging assets
    doc.setFontSize(12);
    doc.text('Assets Due for Maintenance / Nearing Retirement', 14, y);
    y += 4;
    doc.autoTable({
      startY: y,
      head: [['Asset Tag', 'Name', 'Condition', 'Acquired']],
      body: agingAssets.map((a) => [
        a.assetTag, a.name, a.condition || '—',
        a.acquisitionDate ? new Date(a.acquisitionDate).toLocaleDateString() : '—',
      ]),
      styles: { fontSize: 8 },
      headStyles: { fillColor: [15, 23, 42] },
    });

    doc.save('assetflow-report.pdf');
    toast.success('Report exported as PDF');
  };

  if (isLoading) {
    return (
      <div>
        <PageHeader title="Reports" subtitle="Loading analytics…" />
        <div className="card p-12 text-center text-slate-400">Loading…</div>
      </div>
    );
  }

  return (
    <div>
      <PageHeader
        title="Reports & Analytics"
        subtitle="Utilization, maintenance frequency, most-used & idle assets."
        actions={
          <button className="btn-primary" onClick={exportReport}>
            <Download size={16} /> Export Report
          </button>
        }
      />

      {/* Charts row */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
        {/* Utilization by department */}
        <div className="card">
          <div className="card-header">
            <span className="text-sm font-semibold text-slate-700">Utilization by Department</span>
          </div>
          <div className="card-body" style={{ height: 260 }}>
            {utilizationByDept.length > 0 ? (
              <ResponsiveContainer width="100%" height="100%">
                <BarChart data={utilizationByDept}>
                  <CartesianGrid strokeDasharray="3 3" stroke="#e2e8f0" />
                  <XAxis dataKey="department" tick={{ fontSize: 11 }} />
                  <YAxis tick={{ fontSize: 11 }} allowDecimals={false} />
                  <Tooltip />
                  <Bar dataKey="total" name="Total" fill="#94a3b8" radius={[4, 4, 0, 0]} />
                  <Bar dataKey="allocated" name="Allocated" fill="#d97706" radius={[4, 4, 0, 0]} />
                </BarChart>
              </ResponsiveContainer>
            ) : (
              <div className="flex items-center justify-center h-full text-slate-400 text-sm">No department data</div>
            )}
          </div>
        </div>

        {/* Maintenance frequency */}
        <div className="card">
          <div className="card-header">
            <span className="text-sm font-semibold text-slate-700">Maintenance Frequency</span>
          </div>
          <div className="card-body" style={{ height: 260 }}>
            {maintData.length > 0 ? (
              <ResponsiveContainer width="100%" height="100%">
                <LineChart data={maintData}>
                  <CartesianGrid strokeDasharray="3 3" stroke="#e2e8f0" />
                  <XAxis dataKey="label" tick={{ fontSize: 11 }} />
                  <YAxis tick={{ fontSize: 11 }} allowDecimals={false} />
                  <Tooltip />
                  <Line
                    type="monotone"
                    dataKey="count"
                    name="Requests"
                    stroke="#ef4444"
                    strokeWidth={2}
                    dot={{ fill: '#ef4444', r: 4 }}
                  />
                </LineChart>
              </ResponsiveContainer>
            ) : (
              <div className="flex items-center justify-center h-full text-slate-400 text-sm">No maintenance data yet</div>
            )}
          </div>
        </div>
      </div>

      {/* Stats cards row */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
        {/* Most used assets */}
        <div className="card">
          <div className="card-header">
            <div className="flex items-center gap-2">
              <TrendingUp size={16} className="text-emerald-600" />
              <span className="text-sm font-semibold text-slate-700">Most Used Assets</span>
            </div>
          </div>
          <div className="card-body">
            {mostUsedAssets.length > 0 ? (
              <ul className="space-y-2">
                {mostUsedAssets.map((a, i) => (
                  <li key={i} className="flex items-center justify-between text-sm">
                    <span>
                      <span className="font-medium text-slate-800">{a.name}</span>
                      <span className="text-slate-400 ml-2">{a.assetTag}</span>
                    </span>
                    <span className="badge badge-blue">{a.bookings} bookings</span>
                  </li>
                ))}
              </ul>
            ) : (
              <div className="text-slate-400 text-sm">No booking data yet</div>
            )}
          </div>
        </div>

        {/* Idle assets */}
        <div className="card">
          <div className="card-header">
            <div className="flex items-center gap-2">
              <Clock size={16} className="text-amber-500" />
              <span className="text-sm font-semibold text-slate-700">Idle Assets</span>
            </div>
          </div>
          <div className="card-body">
            {idleAssets.length > 0 ? (
              <ul className="space-y-2">
                {idleAssets.map((a, i) => (
                  <li key={i} className="text-sm text-slate-600">
                    <span className="font-medium text-slate-800">{a.name}</span>
                    <span className="text-slate-400 ml-2">{a.assetTag}</span>
                    <span className="text-slate-400 ml-1">: unused 60+ days</span>
                  </li>
                ))}
              </ul>
            ) : (
              <div className="text-slate-400 text-sm">All assets recently active</div>
            )}
          </div>
        </div>
      </div>

      {/* Aging assets */}
      <div className="card">
        <div className="card-header">
          <div className="flex items-center gap-2">
            <AlertTriangle size={16} className="text-red-500" />
            <span className="text-sm font-semibold text-slate-700">Assets Due for Maintenance / Nearing Retirement</span>
          </div>
        </div>
        <div className="card-body">
          {agingAssets.length > 0 ? (
            <ul className="space-y-2">
              {agingAssets.map((a, i) => (
                <li key={i} className="text-sm text-slate-600">
                  <span className="font-medium text-slate-800">{a.name}</span>
                  <span className="text-slate-400 ml-2">{a.assetTag}</span>
                  <span className="text-slate-400 ml-1">
                    : {a.condition === 'poor' ? 'poor condition' : 'nearing retirement'}
                  </span>
                </li>
              ))}
            </ul>
          ) : (
            <div className="text-slate-400 text-sm">No assets flagged</div>
          )}
        </div>
      </div>
    </div>
  );
}
