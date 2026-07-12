import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useState } from 'react';
import { useForm } from 'react-hook-form';
import api from '../../services/api';
import PageHeader from '../../components/ui/PageHeader.jsx';
import Modal from '../../components/ui/Modal.jsx';
import toast from 'react-hot-toast';
import { Plus, Pencil, Trash2 } from 'lucide-react';

export default function Departments() {
  const qc = useQueryClient();
  const [editing, setEditing] = useState(null);
  const [open, setOpen] = useState(false);
  const { data } = useQuery({ queryKey: ['departments'], queryFn: async () => (await api.get('/departments')).data });
  const { data: users } = useQuery({ queryKey: ['users','all'], queryFn: async () => (await api.get('/users?limit=200')).data });

  const { register, handleSubmit, reset } = useForm();
  const openNew = () => { setEditing(null); reset({ name: '', code: '', description: '', head: '', isActive: true }); setOpen(true); };
  const openEdit = (d) => { setEditing(d); reset({ ...d, head: d.head?._id || '' }); setOpen(true); };

  const save = useMutation({
    mutationFn: async (payload) => {
      const body = { ...payload, head: payload.head || null };
      return editing ? api.patch(`/departments/${editing._id}`, body) : api.post('/departments', body);
    },
    onSuccess: () => { toast.success('Saved'); qc.invalidateQueries({ queryKey: ['departments'] }); setOpen(false); },
    onError: (e) => toast.error(e.response?.data?.message || 'Failed'),
  });

  const del = useMutation({
    mutationFn: (id) => api.delete(`/departments/${id}`),
    onSuccess: () => { toast.success('Deleted'); qc.invalidateQueries({ queryKey: ['departments'] }); },
  });

  return (
    <div>
      <PageHeader
        title="Departments"
        subtitle="Organizational structure and department heads."
        actions={<button className="btn-primary" onClick={openNew}><Plus size={16} /> New Department</button>}
      />
      <div className="card overflow-x-auto">
        <table className="w-full">
          <thead className="bg-slate-50">
            <tr>
              <th className="table-th">Name</th><th className="table-th">Code</th>
              <th className="table-th">Head</th><th className="table-th">Status</th><th className="table-th"></th>
            </tr>
          </thead>
          <tbody>
            {(data?.items || []).map((d) => (
              <tr key={d._id}>
                <td className="table-td font-medium text-slate-900">{d.name}</td>
                <td className="table-td">{d.code || '—'}</td>
                <td className="table-td">{d.head?.name || '—'}</td>
                <td className="table-td">{d.isActive ? <span className="badge-green">Active</span> : <span className="badge-slate">Inactive</span>}</td>
                <td className="table-td text-right">
                  <button className="btn-ghost p-1.5" onClick={() => openEdit(d)}><Pencil size={14} /></button>
                  <button className="btn-ghost p-1.5 text-red-600" onClick={() => confirm('Delete department?') && del.mutate(d._id)}><Trash2 size={14} /></button>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      <Modal
        open={open}
        onClose={() => setOpen(false)}
        title={editing ? 'Edit department' : 'New department'}
        footer={<>
          <button className="btn-secondary" onClick={() => setOpen(false)}>Cancel</button>
          <button className="btn-primary" onClick={handleSubmit((v) => save.mutate(v))}>Save</button>
        </>}
      >
        <form className="space-y-4">
          <div><label className="label">Name *</label><input className="input" {...register('name', { required: true })} /></div>
          <div className="grid grid-cols-2 gap-3">
            <div><label className="label">Code</label><input className="input" {...register('code')} /></div>
            <div>
              <label className="label">Head</label>
              <select className="input" {...register('head')}>
                <option value="">— none —</option>
                {(users?.items || []).map((u) => <option key={u._id} value={u._id}>{u.name}</option>)}
              </select>
            </div>
          </div>
          <div><label className="label">Description</label><textarea className="input" rows={2} {...register('description')} /></div>
          <label className="flex items-center gap-2 text-sm text-slate-700">
            <input type="checkbox" {...register('isActive')} defaultChecked /> Active
          </label>
        </form>
      </Modal>
    </div>
  );
}
