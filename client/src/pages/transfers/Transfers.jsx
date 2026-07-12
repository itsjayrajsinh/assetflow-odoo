import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useState } from 'react';
import { useForm } from 'react-hook-form';
import api from '../../services/api';
import PageHeader from '../../components/ui/PageHeader.jsx';
import Modal from '../../components/ui/Modal.jsx';
import StatusBadge from '../../components/ui/StatusBadge.jsx';
import toast from 'react-hot-toast';
import { Plus, Check, X, ArrowRight } from 'lucide-react';
import { hasRole, useAuth } from '../../store/auth';

export default function Transfers() {
  const { user } = useAuth();
  const qc = useQueryClient();
  const [open, setOpen] = useState(false);
  const { data } = useQuery({ queryKey: ['transfers'], queryFn: async () => (await api.get('/transfers')).data });
  const { data: assets } = useQuery({ queryKey: ['assets','all'], queryFn: async () => (await api.get('/assets?limit=200')).data });
  const { data: users } = useQuery({ queryKey: ['users','all'], queryFn: async () => (await api.get('/users?limit=200')).data });

  const { register, handleSubmit, reset } = useForm();
  const canApprove = hasRole(user, 'admin', 'asset_manager', 'department_head');
  const canComplete = hasRole(user, 'admin', 'asset_manager');

  const req = useMutation({
    mutationFn: (v) => api.post('/transfers', v),
    onSuccess: () => { toast.success('Request submitted'); qc.invalidateQueries({ queryKey: ['transfers'] }); setOpen(false); reset(); },
  });
  const act = useMutation({
    mutationFn: ({ id, action, reason }) => api.post(`/transfers/${id}/${action}`, { reason }),
    onSuccess: () => { toast.success('Updated'); qc.invalidateQueries({ queryKey: ['transfers'] }); },
  });

  return (
    <div>
      <PageHeader
        title="Transfers"
        subtitle="Requested → Approved → Transferred workflow."
        actions={<button className="btn-primary" onClick={() => setOpen(true)}><Plus size={16} /> Request Transfer</button>}
      />
      <div className="card overflow-x-auto">
        <table className="w-full">
          <thead className="bg-slate-50"><tr>
            <th className="table-th">Asset</th><th className="table-th">From</th><th className="table-th"></th><th className="table-th">To</th>
            <th className="table-th">Reason</th><th className="table-th">Status</th><th className="table-th"></th>
          </tr></thead>
          <tbody>
            {(data?.items || []).map((t) => (
              <tr key={t._id}>
                <td className="table-td">{t.asset?.assetTag} · {t.asset?.name}</td>
                <td className="table-td">{t.fromUser?.name || '—'}</td>
                <td className="table-td"><ArrowRight size={14} className="text-slate-400" /></td>
                <td className="table-td">{t.toUser?.name}</td>
                <td className="table-td text-slate-500">{t.reason || '—'}</td>
                <td className="table-td"><StatusBadge value={t.status} /></td>
                <td className="table-td text-right space-x-1">
                  {t.status === 'requested' && canApprove && (<>
                    <button className="btn-secondary text-xs" onClick={() => act.mutate({ id: t._id, action: 'approve' })}><Check size={13} /> Approve</button>
                    <button className="btn-secondary text-xs text-red-600" onClick={() => { const r = prompt('Reason?'); if (r) act.mutate({ id: t._id, action: 'reject', reason: r }); }}><X size={13} /> Reject</button>
                  </>)}
                  {t.status === 'approved' && canComplete && (
                    <button className="btn-primary text-xs" onClick={() => act.mutate({ id: t._id, action: 'complete' })}>Complete transfer</button>
                  )}
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      <Modal open={open} onClose={() => setOpen(false)} title="Request transfer"
        footer={<>
          <button className="btn-secondary" onClick={() => setOpen(false)}>Cancel</button>
          <button className="btn-primary" onClick={handleSubmit((v) => req.mutate(v))}>Submit</button>
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
            <label className="label">Transfer to *</label>
            <select className="input" {...register('toUser', { required: true })}>
              <option value="">— select —</option>
              {(users?.items || []).map((u) => <option key={u._id} value={u._id}>{u.name} ({u.email})</option>)}
            </select>
          </div>
          <div><label className="label">Reason</label><textarea className="input" rows={2} {...register('reason')} /></div>
        </form>
      </Modal>
    </div>
  );
}
