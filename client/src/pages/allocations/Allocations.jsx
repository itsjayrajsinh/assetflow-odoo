import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useState } from 'react';
import { useForm } from 'react-hook-form';
import api from '../../services/api';
import PageHeader from '../../components/ui/PageHeader.jsx';
import Modal from '../../components/ui/Modal.jsx';
import StatusBadge from '../../components/ui/StatusBadge.jsx';
import toast from 'react-hot-toast';
import { Plus, RotateCcw } from 'lucide-react';
import { hasRole, useAuth } from '../../store/auth';

export default function Allocations() {
  const { user } = useAuth();
  const qc = useQueryClient();
  const [open, setOpen] = useState(false);
  const [returnFor, setReturnFor] = useState(null);

  const { data } = useQuery({ queryKey: ['allocations'], queryFn: async () => (await api.get('/allocations')).data });
  const { data: assets } = useQuery({ queryKey: ['assets','avail'], queryFn: async () => (await api.get('/assets', { params: { status: 'available', limit: 200 } })).data });
  const { data: users } = useQuery({ queryKey: ['users','all'], queryFn: async () => (await api.get('/users?limit=200')).data });

  const { register, handleSubmit, reset } = useForm();
  const returnForm = useForm();

  const allocate = useMutation({
    mutationFn: (v) => api.post('/allocations', v),
    onSuccess: () => { toast.success('Allocated'); qc.invalidateQueries({ queryKey: ['allocations'] }); qc.invalidateQueries({ queryKey: ['assets'] }); setOpen(false); reset(); },
    onError: (e) => toast.error(e.response?.data?.message || 'Failed'),
  });
  const doReturn = useMutation({
    mutationFn: ({ id, ...v }) => api.post(`/allocations/${id}/return`, v),
    onSuccess: () => { toast.success('Returned'); qc.invalidateQueries({ queryKey: ['allocations'] }); setReturnFor(null); },
  });

  const canAllocate = hasRole(user, 'admin', 'asset_manager');

  return (
    <div>
      <PageHeader
        title="Allocations"
        subtitle="Track who has what and when it's due back."
        actions={canAllocate && <button className="btn-primary" onClick={() => setOpen(true)}><Plus size={16} /> Allocate</button>}
      />
      <div className="card overflow-x-auto">
        <table className="w-full">
          <thead className="bg-slate-50"><tr>
            <th className="table-th">Asset</th><th className="table-th">Employee</th><th className="table-th">Allocated</th>
            <th className="table-th">Expected Return</th><th className="table-th">Status</th><th className="table-th"></th>
          </tr></thead>
          <tbody>
            {(data?.items || []).map((a) => (
              <tr key={a._id}>
                <td className="table-td">{a.asset?.assetTag} · {a.asset?.name}</td>
                <td className="table-td">{a.employee?.name}</td>
                <td className="table-td">{new Date(a.allocatedAt).toLocaleDateString()}</td>
                <td className="table-td">{a.expectedReturnDate ? new Date(a.expectedReturnDate).toLocaleDateString() : '—'}</td>
                <td className="table-td"><StatusBadge value={a.status} /></td>
                <td className="table-td text-right">
                  {a.status !== 'returned' && (
                    <button className="btn-secondary text-xs" onClick={() => { returnForm.reset({ returnCondition: 'good', returnNotes: '' }); setReturnFor(a); }}>
                      <RotateCcw size={14} /> Return
                    </button>
                  )}
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      <Modal open={open} onClose={() => setOpen(false)} title="Allocate asset"
        footer={<>
          <button className="btn-secondary" onClick={() => setOpen(false)}>Cancel</button>
          <button className="btn-primary" onClick={handleSubmit((v) => allocate.mutate(v))}>Allocate</button>
        </>}>
        <form className="space-y-4">
          <div>
            <label className="label">Asset *</label>
            <select className="input" {...register('asset', { required: true })}>
              <option value="">— select —</option>
              {(assets?.items || []).map((a) => <option key={a._id} value={a._id}>{a.assetTag} · {a.name}</option>)}
            </select>
          </div>
          <div>
            <label className="label">Employee *</label>
            <select className="input" {...register('employee', { required: true })}>
              <option value="">— select —</option>
              {(users?.items || []).map((u) => <option key={u._id} value={u._id}>{u.name} ({u.email})</option>)}
            </select>
          </div>
          <div><label className="label">Expected return date</label><input className="input" type="date" {...register('expectedReturnDate')} /></div>
        </form>
      </Modal>

      <Modal open={!!returnFor} onClose={() => setReturnFor(null)} title="Return asset"
        footer={<>
          <button className="btn-secondary" onClick={() => setReturnFor(null)}>Cancel</button>
          <button className="btn-primary" onClick={returnForm.handleSubmit((v) => doReturn.mutate({ id: returnFor._id, ...v }))}>Confirm return</button>
        </>}>
        <form className="space-y-4">
          <div>
            <label className="label">Condition on return</label>
            <select className="input" {...returnForm.register('returnCondition')}>
              {['good','fair','poor','damaged'].map(c => <option key={c} value={c}>{c}</option>)}
            </select>
          </div>
          <div><label className="label">Notes</label><textarea className="input" rows={3} {...returnForm.register('returnNotes')} /></div>
        </form>
      </Modal>
    </div>
  );
}
