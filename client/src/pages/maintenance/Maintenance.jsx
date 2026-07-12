import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { useState } from 'react';
import { useForm } from 'react-hook-form';
import api from '../../services/api';
import PageHeader from '../../components/ui/PageHeader.jsx';
import Modal from '../../components/ui/Modal.jsx';
import toast from 'react-hot-toast';
import { Plus, ChevronRight } from 'lucide-react';
import { hasRole, useAuth } from '../../store/auth';

const STAGES = [
  { key: 'pending', label: 'Pending', color: 'bg-slate-100 border-slate-200', headerColor: 'bg-slate-200 text-slate-700' },
  { key: 'approved', label: 'Approved', color: 'bg-blue-50 border-blue-100', headerColor: 'bg-blue-100 text-blue-800' },
  { key: 'technician_assigned', label: 'Technician Assigned', color: 'bg-amber-50 border-amber-100', headerColor: 'bg-amber-100 text-amber-800' },
  { key: 'in_progress', label: 'In Progress', color: 'bg-orange-50 border-orange-100', headerColor: 'bg-orange-100 text-orange-800' },
  { key: 'resolved', label: 'Resolved', color: 'bg-emerald-50 border-emerald-100', headerColor: 'bg-emerald-600 text-white' },
];

const NEXT_STAGE = {
  pending: 'approved',
  approved: 'technician_assigned',
  technician_assigned: 'in_progress',
  in_progress: 'resolved',
};

function PriorityDot({ priority }) {
  const colors = { low: 'bg-emerald-400', medium: 'bg-amber-400', high: 'bg-red-500' };
  return <span className={`inline-block h-2 w-2 rounded-full ${colors[priority] || 'bg-slate-300'}`} title={priority} />;
}

function KanbanCard({ item, onAdvance, canAdvance }) {
  const next = NEXT_STAGE[item.stage];
  const isResolved = item.stage === 'resolved';

  return (
    <div className={`rounded-lg border p-3 mb-2 bg-white shadow-sm ${isResolved ? 'border-emerald-300' : 'border-slate-200'}`}>
      <div className="flex items-center gap-1.5 mb-1">
        <PriorityDot priority={item.priority} />
        <span className="text-xs font-bold text-slate-600">{item.asset?.assetTag || '—'}</span>
      </div>
      <div className="text-sm font-medium text-slate-800 leading-tight">{item.description}</div>
      {item.technicianName && (
        <div className="text-xs text-slate-500 mt-1">tech: {item.technicianName}</div>
      )}
      {item.resolvedAt && (
        <div className="text-xs text-slate-500 mt-1">
          Resolved {new Date(item.resolvedAt).toLocaleDateString('en-GB', { day: 'numeric', month: 'short' })}
        </div>
      )}
      {canAdvance && next && (
        <button
          className="mt-2 flex items-center gap-1 text-xs text-brand-600 hover:text-brand-800 font-medium"
          onClick={() => onAdvance(item._id, next)}
        >
          <ChevronRight size={12} /> Move to {STAGES.find(s => s.key === next)?.label}
        </button>
      )}
    </div>
  );
}

export default function Maintenance() {
  const { user } = useAuth();
  const qc = useQueryClient();
  const [open, setOpen] = useState(false);
  const { register, handleSubmit, reset } = useForm({ defaultValues: { priority: 'medium' } });

  const canManage = hasRole(user, 'admin', 'asset_manager', 'department_head');
  const isManagerOrAdmin = hasRole(user, 'admin', 'asset_manager');

  // Fetch all assets for the dropdown
  const { data: assetsData } = useQuery({
    queryKey: ['assets-all'],
    queryFn: async () => (await api.get('/assets', { params: { limit: 200 } })).data,
  });

  // Fetch all maintenance requests
  const { data } = useQuery({
    queryKey: ['maintenance'],
    queryFn: async () => (await api.get('/maintenance')).data,
    refetchInterval: 15000,
  });

  const requests = data?.data || [];

  // Group by stage
  const grouped = STAGES.reduce((acc, s) => {
    acc[s.key] = requests.filter((r) => r.stage === s.key);
    return acc;
  }, {});

  const createMutation = useMutation({
    mutationFn: (form) => api.post('/maintenance', form),
    onSuccess: () => {
      toast.success('Maintenance request created');
      qc.invalidateQueries({ queryKey: ['maintenance'] });
      setOpen(false);
      reset();
    },
    onError: (e) => toast.error(e.response?.data?.message || 'Failed'),
  });

  const stageMutation = useMutation({
    mutationFn: ({ id, stage }) => api.patch(`/maintenance/${id}/stage`, { stage }),
    onSuccess: () => {
      toast.success('Stage updated');
      qc.invalidateQueries({ queryKey: ['maintenance'] });
      qc.invalidateQueries({ queryKey: ['assets'] });
    },
    onError: (e) => toast.error(e.response?.data?.message || 'Failed'),
  });

  return (
    <div>
      <PageHeader
        title="Maintenance"
        subtitle="Approval workflow — approving a card moves the asset to Under Maintenance; resolving returns it to Available."
        actions={
          <button className="btn-primary" onClick={() => setOpen(true)}>
            <Plus size={16} /> Report issue
          </button>
        }
      />

      {/* Kanban Board */}
      <div className="flex gap-3 overflow-x-auto pb-4" style={{ minHeight: '60vh' }}>
        {STAGES.map((stage) => {
          const cards = grouped[stage.key] || [];
          return (
            <div key={stage.key} className="flex-shrink-0 w-56">
              {/* Column header */}
              <div className={`rounded-t-lg px-3 py-2 text-xs font-semibold flex items-center justify-between ${stage.headerColor}`}>
                <span>{stage.label}</span>
                <span className="bg-white/30 rounded-full px-1.5">{cards.length}</span>
              </div>
              {/* Column body */}
              <div className={`rounded-b-lg border ${stage.color} p-2 min-h-48`}>
                {cards.map((item) => (
                  <KanbanCard
                    key={item._id}
                    item={item}
                    canAdvance={canManage}
                    onAdvance={(id, nextStage) => stageMutation.mutate({ id, stage: nextStage })}
                  />
                ))}
                {cards.length === 0 && (
                  <div className="text-xs text-slate-400 text-center py-4">Empty</div>
                )}
              </div>
            </div>
          );
        })}
      </div>

      {/* Bottom rule bar */}
      <div className="mt-2 bg-slate-100 rounded text-xs text-slate-500 px-4 py-2">
        Approving a card moves the asset to <strong>Under Maintenance</strong> · Resolving returns it to <strong>Available</strong>
      </div>

      {/* Create Maintenance Request Modal */}
      <Modal
        open={open}
        onClose={() => setOpen(false)}
        title="Report maintenance issue"
        footer={
          <>
            <button className="btn-secondary" onClick={() => setOpen(false)}>Cancel</button>
            <button
              className="btn-primary"
              onClick={handleSubmit((v) => createMutation.mutate(v))}
              disabled={createMutation.isPending}
            >
              Submit Request
            </button>
          </>
        }
      >
        <form className="space-y-4">
          <div>
            <label className="label">Asset *</label>
            <select className="input" {...register('asset', { required: true })}>
              <option value="">— select asset —</option>
              {(assetsData?.items || []).map((a) => (
                <option key={a._id} value={a._id}>
                  {a.assetTag} – {a.name}
                </option>
              ))}
            </select>
          </div>
          <div>
            <label className="label">Description of issue *</label>
            <textarea
              className="input h-24 resize-none"
              placeholder="Describe the problem…"
              {...register('description', { required: true })}
            />
          </div>
          <div className="grid grid-cols-2 gap-4">
            <div>
              <label className="label">Priority</label>
              <select className="input" {...register('priority')}>
                {['low', 'medium', 'high'].map((p) => (
                  <option key={p} value={p}>{p.charAt(0).toUpperCase() + p.slice(1)}</option>
                ))}
              </select>
            </div>
            <div>
              <label className="label">Technician name (optional)</label>
              <input className="input" placeholder="e.g. R. Varma" {...register('technicianName')} />
            </div>
          </div>
        </form>
      </Modal>
    </div>
  );
}
