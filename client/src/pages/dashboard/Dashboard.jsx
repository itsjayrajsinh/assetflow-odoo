import { useQuery } from '@tanstack/react-query';
import api from '../../services/api';
import PageHeader from '../../components/ui/PageHeader.jsx';
import { PieChart, Pie, Cell, ResponsiveContainer, Tooltip, BarChart, Bar, XAxis, YAxis, CartesianGrid } from 'recharts';
import { Package, PackageCheck, Wrench, ArrowLeftRight, Clock, AlertTriangle } from 'lucide-react';

const kpiConfig = [
  { key: 'available', label: 'Available', icon: Package, color: 'text-emerald-600 bg-emerald-50' },
  { key: 'allocated', label: 'Allocated', icon: PackageCheck, color: 'text-sky-600 bg-sky-50' },
  { key: 'maintenance', label: 'Under Maintenance', icon: Wrench, color: 'text-amber-600 bg-amber-50' },
  { key: 'pendingTransfers', label: 'Pending Transfers', icon: ArrowLeftRight, color: 'text-indigo-600 bg-indigo-50' },
  { key: 'upcomingReturns', label: 'Upcoming Returns', icon: Clock, color: 'text-slate-600 bg-slate-100' },
  { key: 'overdueReturns', label: 'Overdue Returns', icon: AlertTriangle, color: 'text-red-600 bg-red-50' },
];

const COLORS = ['#10b981', '#0ea5e9', '#f59e0b', '#ef4444', '#8b5cf6', '#64748b', '#f43f5e'];

export default function Dashboard() {
  const { data } = useQuery({ queryKey: ['dashboard'], queryFn: async () => (await api.get('/dashboard/summary')).data });
  const kpi = data?.kpi || {};
  const chartData = (data?.charts?.byStatus || []).map((d) => ({ name: d._id, value: d.count }));

  return (
    <div>
      <PageHeader title="Dashboard" subtitle="Overview of your asset ecosystem." />
      <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
        {kpiConfig.map(({ key, label, icon: Icon, color }) => (
          <div key={key} className="card p-4">
            <div className={`inline-flex items-center justify-center h-9 w-9 rounded-md ${color}`}><Icon size={18} /></div>
            <div className="mt-3 text-2xl font-semibold text-slate-900">{kpi[key] ?? 0}</div>
            <div className="text-xs text-slate-500 mt-0.5">{label}</div>
          </div>
        ))}
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-4 mt-6">
        <div className="card lg:col-span-2">
          <div className="card-header"><h3 className="font-semibold">Assets by Status</h3></div>
          <div className="card-body h-72">
            <ResponsiveContainer>
              <BarChart data={chartData}>
                <CartesianGrid strokeDasharray="3 3" stroke="#f1f5f9" />
                <XAxis dataKey="name" tick={{ fontSize: 12 }} />
                <YAxis tick={{ fontSize: 12 }} />
                <Tooltip />
                <Bar dataKey="value" fill="#10b981" radius={[4,4,0,0]} />
              </BarChart>
            </ResponsiveContainer>
          </div>
        </div>
        <div className="card">
          <div className="card-header"><h3 className="font-semibold">Distribution</h3></div>
          <div className="card-body h-72">
            <ResponsiveContainer>
              <PieChart>
                <Pie data={chartData} dataKey="value" nameKey="name" outerRadius={90}>
                  {chartData.map((_, i) => <Cell key={i} fill={COLORS[i % COLORS.length]} />)}
                </Pie>
                <Tooltip />
              </PieChart>
            </ResponsiveContainer>
          </div>
        </div>
      </div>

      <div className="card mt-6">
        <div className="card-header"><h3 className="font-semibold">Recent Activity</h3></div>
        <div className="divide-y">
          {(data?.recent || []).map((a) => (
            <div key={a._id} className="px-5 py-3 text-sm flex justify-between">
              <div>
                <span className="font-medium text-slate-800">{a.user?.name || 'System'}</span>
                <span className="text-slate-500"> {a.action} </span>
                <span className="text-slate-800">{a.entity}</span>
              </div>
              <span className="text-xs text-slate-400">{new Date(a.createdAt).toLocaleString()}</span>
            </div>
          ))}
          {(data?.recent || []).length === 0 && <div className="p-6 text-sm text-slate-500 text-center">No activity yet.</div>}
        </div>
      </div>
    </div>
  );
}
