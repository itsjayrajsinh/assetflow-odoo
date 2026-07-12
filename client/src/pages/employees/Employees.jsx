import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useState } from 'react';
import api from '../../services/api';
import PageHeader from '../../components/ui/PageHeader.jsx';
import toast from 'react-hot-toast';
import { useAuth } from '../../store/auth';

const ROLES = ['employee', 'department_head', 'asset_manager'];

export default function Employees() {
  const qc = useQueryClient();
  const [search, setSearch] = useState('');
  const { user: me } = useAuth();
  const { data } = useQuery({
    queryKey: ['users', search],
    queryFn: async () => (await api.get('/users', { params: { search, limit: 100 } })).data,
  });
  const { data: depts } = useQuery({ queryKey: ['departments'], queryFn: async () => (await api.get('/departments')).data });

  const promote = useMutation({
    mutationFn: ({ id, role }) => api.post(`/users/${id}/promote`, { role }),
    onSuccess: () => { toast.success('Role updated'); qc.invalidateQueries({ queryKey: ['users'] }); },
    onError: (e) => toast.error(e.response?.data?.message || 'Failed'),
  });
  const update = useMutation({
    mutationFn: ({ id, ...rest }) => api.patch(`/users/${id}`, rest),
    onSuccess: () => { qc.invalidateQueries({ queryKey: ['users'] }); toast.success('Saved'); },
  });

  return (
    <div>
      <PageHeader title="Employee Directory" subtitle="Manage roles and department assignments." />
      <div className="card">
        <div className="p-4 border-b"><input className="input max-w-xs" placeholder="Search…" value={search} onChange={(e) => setSearch(e.target.value)} /></div>
        <div className="overflow-x-auto">
          <table className="w-full">
            <thead className="bg-slate-50"><tr>
              <th className="table-th">Name</th><th className="table-th">Email</th><th className="table-th">Department</th><th className="table-th">Role</th><th className="table-th">Status</th>
            </tr></thead>
            <tbody>
              {(data?.items || []).map((u) => (
                <tr key={u._id}>
                  <td className="table-td font-medium">{u.name}</td>
                  <td className="table-td text-slate-500">{u.email}</td>
                  <td className="table-td">
                    <select className="input py-1" defaultValue={u.department?._id || ''} onChange={(e) => update.mutate({ id: u._id, department: e.target.value || null })}>
                      <option value="">—</option>
                      {(depts?.items || []).map((d) => <option key={d._id} value={d._id}>{d.name}</option>)}
                    </select>
                  </td>
                  <td className="table-td">
                    {me?.role === 'admin' && u.role !== 'admin' ? (
                      <select className="input py-1" defaultValue={u.role} onChange={(e) => promote.mutate({ id: u._id, role: e.target.value })}>
                        {ROLES.map((r) => <option key={r} value={r}>{r.replace('_', ' ')}</option>)}
                      </select>
                    ) : <span className="capitalize text-sm">{u.role.replace('_', ' ')}</span>}
                  </td>
                  <td className="table-td">
                    <button className={u.isActive ? 'badge-green' : 'badge-slate'} onClick={() => update.mutate({ id: u._id, isActive: !u.isActive })}>
                      {u.isActive ? 'Active' : 'Disabled'}
                    </button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
}
