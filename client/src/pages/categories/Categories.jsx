import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useState } from 'react';
import { useForm } from 'react-hook-form';
import api from '../../services/api';
import PageHeader from '../../components/ui/PageHeader.jsx';
import Modal from '../../components/ui/Modal.jsx';
import toast from 'react-hot-toast';
import { Plus, Pencil, Trash2 } from 'lucide-react';

export default function Categories() {
  const qc = useQueryClient();
  const [editing, setEditing] = useState(null);
  const [open, setOpen] = useState(false);
  const { data } = useQuery({ queryKey: ['categories'], queryFn: async () => (await api.get('/categories')).data });
  const { register, handleSubmit, reset } = useForm();

  const openNew = () => { setEditing(null); reset({ name: '', description: '', warrantyPeriodMonths: 0 }); setOpen(true); };
  const openEdit = (c) => { setEditing(c); reset(c); setOpen(true); };

  const save = useMutation({
    mutationFn: (payload) => editing ? api.patch(`/categories/${editing._id}`, payload) : api.post('/categories', payload),
    onSuccess: () => { toast.success('Saved'); qc.invalidateQueries({ queryKey: ['categories'] }); setOpen(false); },
    onError: (e) => toast.error(e.response?.data?.message || 'Failed'),
  });
  const del = useMutation({
    mutationFn: (id) => api.delete(`/categories/${id}`),
    onSuccess: () => { toast.success('Deleted'); qc.invalidateQueries({ queryKey: ['categories'] }); },
  });

  return (
    <div>
      <PageHeader
        title="Asset Categories"
        subtitle="Group assets by type, define warranty and custom fields."
        actions={<button className="btn-primary" onClick={openNew}><Plus size={16} /> New Category</button>}
      />
      <div className="card overflow-x-auto">
        <table className="w-full">
          <thead className="bg-slate-50"><tr>
            <th className="table-th">Name</th><th className="table-th">Warranty (months)</th><th className="table-th">Description</th><th className="table-th"></th>
          </tr></thead>
          <tbody>
            {(data?.items || []).map((c) => (
              <tr key={c._id}>
                <td className="table-td font-medium text-slate-900">{c.name}</td>
                <td className="table-td">{c.warrantyPeriodMonths || 0}</td>
                <td className="table-td text-slate-500">{c.description || '—'}</td>
                <td className="table-td text-right">
                  <button className="btn-ghost p-1.5" onClick={() => openEdit(c)}><Pencil size={14} /></button>
                  <button className="btn-ghost p-1.5 text-red-600" onClick={() => confirm('Delete category?') && del.mutate(c._id)}><Trash2 size={14} /></button>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      <Modal open={open} onClose={() => setOpen(false)} title={editing ? 'Edit category' : 'New category'}
        footer={<>
          <button className="btn-secondary" onClick={() => setOpen(false)}>Cancel</button>
          <button className="btn-primary" onClick={handleSubmit((v) => save.mutate(v))}>Save</button>
        </>}>
        <form className="space-y-4">
          <div><label className="label">Name *</label><input className="input" {...register('name', { required: true })} /></div>
          <div><label className="label">Warranty period (months)</label><input className="input" type="number" {...register('warrantyPeriodMonths', { valueAsNumber: true })} /></div>
          <div><label className="label">Description</label><textarea className="input" rows={2} {...register('description')} /></div>
        </form>
      </Modal>
    </div>
  );
}
