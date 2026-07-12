import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { useState } from 'react';
import { useForm } from 'react-hook-form';
import api from '../../services/api';
import PageHeader from '../../components/ui/PageHeader.jsx';
import Modal from '../../components/ui/Modal.jsx';
import toast from 'react-hot-toast';
import { Plus, CheckCircle, AlertTriangle, XCircle, Clock, ChevronLeft, FileText, Lock } from 'lucide-react';
import { hasRole, useAuth } from '../../store/auth';

const VERIF_CONFIG = {
  pending: { label: 'Pending', icon: Clock, cls: 'badge-slate' },
  verified: { label: 'Verified', icon: CheckCircle, cls: 'badge-green' },
  missing: { label: 'Missing', icon: XCircle, cls: 'badge-red' },
  damaged: { label: 'Damaged', icon: AlertTriangle, cls: 'badge-amber' },
};

function VerifBadge({ value }) {
  const cfg = VERIF_CONFIG[value] || VERIF_CONFIG.pending;
  const Icon = cfg.icon;
  return (
    <span className={`badge ${cfg.cls} gap-1`}>
      <Icon size={11} /> {cfg.label}
    </span>
  );
}

function VerifSelector({ value, onChange, disabled }) {
  return (
    <select
      className="input text-xs py-1"
      value={value}
      onChange={(e) => onChange(e.target.value)}
      disabled={disabled}
    >
      {Object.entries(VERIF_CONFIG).map(([k, v]) => (
        <option key={k} value={k}>{v.label}</option>
      ))}
    </select>
  );
}

export default function Audit() {
  const { user } = useAuth();
  const qc = useQueryClient();
  const [selectedId, setSelectedId] = useState(null);
  const [createOpen, setCreateOpen] = useState(false);

  const canManage = hasRole(user, 'admin', 'asset_manager');

  // List of audits
  const { data: auditList } = useQuery({
    queryKey: ['audits'],
    queryFn: async () => (await api.get('/audits')).data,
  });

  // Selected audit detail
  const { data: auditDetail } = useQuery({
    queryKey: ['audit', selectedId],
    queryFn: async () => (await api.get(`/audits/${selectedId}`)).data,
    enabled: !!selectedId,
  });

  const audit = auditDetail?.data;

  const { register, handleSubmit, reset } = useForm({
    defaultValues: {
      startDate: new Date().toISOString().slice(0, 10),
      endDate: new Date(Date.now() + 14 * 86400000).toISOString().slice(0, 10),
    },
  });

  const { data: depts } = useQuery({
    queryKey: ['departments'],
    queryFn: async () => (await api.get('/departments')).data,
  });

  const createMutation = useMutation({
    mutationFn: (form) =>
      api.post('/audits', {
        ...form,
        auditorNames: form.auditorNames ? form.auditorNames.split(',').map(s => s.trim()) : [],
      }),
    onSuccess: (res) => {
      toast.success('Audit cycle created');
      qc.invalidateQueries({ queryKey: ['audits'] });
      setSelectedId(res.data.data._id);
      setCreateOpen(false);
      reset();
    },
    onError: (e) => toast.error(e.response?.data?.message || 'Failed'),
  });

  const updateItemMutation = useMutation({
    mutationFn: ({ itemId, verification }) =>
      api.patch(`/audits/${selectedId}/items/${itemId}`, { verification }),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['audit', selectedId] });
    },
    onError: (e) => toast.error(e.response?.data?.message || 'Failed'),
  });

  const closeMutation = useMutation({
    mutationFn: () => api.post(`/audits/${selectedId}/close`),
    onSuccess: () => {
      toast.success('Audit closed & discrepancy report generated');
      qc.invalidateQueries({ queryKey: ['audit', selectedId] });
      qc.invalidateQueries({ queryKey: ['audits'] });
    },
    onError: (e) => toast.error(e.response?.data?.message || 'Failed'),
  });

  const isClosed = audit?.status === 'closed';
  const flagged = audit?.items?.filter((i) => ['missing', 'damaged'].includes(i.verification)) || [];
  const fmtDate = (d) => d ? new Date(d).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' }) : '';

  // ── Detail View ───────────────────────────────────────────────────────────
  if (selectedId && audit) {
    return (
      <div>
        <PageHeader
          title={audit.title}
          subtitle={`${fmtDate(audit.startDate)} – ${fmtDate(audit.endDate)} · Auditors: ${audit.auditorNames?.join(', ') || '—'}`}
          actions={
            <button className="btn-secondary" onClick={() => setSelectedId(null)}>
              <ChevronLeft size={16} /> All Audits
            </button>
          }
        />

        {/* Audit status banner */}
        {isClosed && (
          <div className="flex items-center gap-2 bg-slate-800 text-white text-sm rounded-lg px-4 py-3 mb-4">
            <Lock size={14} /> This audit is closed. Discrepancy report was generated on{' '}
            {fmtDate(audit.discrepancyReport?.generatedAt)}.
          </div>
        )}

        {/* Discrepancy banner */}
        {flagged.length > 0 && (
          <div className="flex items-center gap-2 bg-amber-600 text-white text-sm rounded-lg px-4 py-3 mb-4">
            <AlertTriangle size={14} />
            {flagged.length} asset{flagged.length > 1 ? 's' : ''} flagged — discrepancy report{' '}
            {isClosed ? 'generated automatically' : 'will be generated on close'}
          </div>
        )}

        {/* Checklist table */}
        <div className="card mb-4">
          <div className="overflow-x-auto">
            <table className="w-full">
              <thead className="bg-slate-50">
                <tr>
                  <th className="table-th">Asset</th>
                  <th className="table-th">Expected Location</th>
                  <th className="table-th">Verification</th>
                  <th className="table-th">Notes</th>
                </tr>
              </thead>
              <tbody>
                {(audit.items || []).map((item) => (
                  <tr key={item._id} className="hover:bg-slate-50">
                    <td className="table-td">
                      <div className="font-medium text-brand-700">{item.assetTag}</div>
                      <div className="text-xs text-slate-500">{item.assetName}</div>
                    </td>
                    <td className="table-td text-slate-600">{item.expectedLocation || '—'}</td>
                    <td className="table-td">
                      {isClosed ? (
                        <VerifBadge value={item.verification} />
                      ) : (
                        <VerifSelector
                          value={item.verification}
                          onChange={(v) => updateItemMutation.mutate({ itemId: item._id, verification: v })}
                        />
                      )}
                    </td>
                    <td className="table-td text-slate-500 text-xs">{item.notes || '—'}</td>
                  </tr>
                ))}
              </tbody>
            </table>
            {(audit.items || []).length === 0 && (
              <div className="p-8 text-center text-slate-400 text-sm">No assets in this audit.</div>
            )}
          </div>
        </div>

        {/* Discrepancy Report (closed) */}
        {isClosed && audit.discrepancyReport?.items?.length > 0 && (
          <div className="card mb-4">
            <div className="card-header">
              <div className="flex items-center gap-2 text-sm font-semibold">
                <FileText size={16} className="text-red-500" /> Discrepancy Report
              </div>
            </div>
            <div className="card-body">
              <table className="w-full">
                <thead className="bg-red-50">
                  <tr>
                    <th className="table-th text-red-700">Asset Tag</th>
                    <th className="table-th text-red-700">Name</th>
                    <th className="table-th text-red-700">Issue</th>
                    <th className="table-th text-red-700">Notes</th>
                  </tr>
                </thead>
                <tbody>
                  {audit.discrepancyReport.items.map((d, i) => (
                    <tr key={i} className="hover:bg-red-50/50">
                      <td className="table-td font-medium">{d.assetTag}</td>
                      <td className="table-td">{d.assetName}</td>
                      <td className="table-td"><VerifBadge value={d.issue} /></td>
                      <td className="table-td text-xs">{d.notes || '—'}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>
        )}

        {/* Close Audit button */}
        {!isClosed && canManage && (
          <div className="flex justify-end">
            <button
              className="btn-secondary border-amber-300 text-amber-700 hover:bg-amber-50"
              onClick={() => {
                if (window.confirm('Close this audit? This will generate the discrepancy report and lock the checklist.')) {
                  closeMutation.mutate();
                }
              }}
              disabled={closeMutation.isPending}
            >
              <Lock size={16} /> Close audit cycle
            </button>
          </div>
        )}
      </div>
    );
  }

  // ── List View ─────────────────────────────────────────────────────────────
  return (
    <div>
      <PageHeader
        title="Audit"
        subtitle="Asset audit cycles with auto-generated discrepancy reports."
        actions={
          canManage && (
            <button className="btn-primary" onClick={() => setCreateOpen(true)}>
              <Plus size={16} /> New Audit Cycle
            </button>
          )
        }
      />

      <div className="card">
        <div className="overflow-x-auto">
          <table className="w-full">
            <thead className="bg-slate-50">
              <tr>
                <th className="table-th">Title</th>
                <th className="table-th">Auditors</th>
                <th className="table-th">Period</th>
                <th className="table-th">Assets</th>
                <th className="table-th">Flagged</th>
                <th className="table-th">Status</th>
              </tr>
            </thead>
            <tbody>
              {(auditList?.data || []).map((a) => (
                <tr
                  key={a._id}
                  className="hover:bg-slate-50 cursor-pointer"
                  onClick={() => setSelectedId(a._id)}
                >
                  <td className="table-td font-medium text-brand-700">{a.title}</td>
                  <td className="table-td text-sm text-slate-500">{a.auditorNames?.join(', ') || '—'}</td>
                  <td className="table-td text-xs">{fmtDate(a.startDate)} – {fmtDate(a.endDate)}</td>
                  <td className="table-td">{a.items?.length ?? 0}</td>
                  <td className="table-td">
                    {(a.discrepancyReport?.flaggedCount ?? 0) > 0 ? (
                      <span className="badge badge-red">{a.discrepancyReport.flaggedCount} flagged</span>
                    ) : '—'}
                  </td>
                  <td className="table-td">
                    <span className={`badge ${a.status === 'open' ? 'badge-green' : 'badge-slate'}`}>
                      {a.status}
                    </span>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
          {(auditList?.data || []).length === 0 && (
            <div className="p-10 text-center text-slate-400 text-sm">No audit cycles yet.</div>
          )}
        </div>
      </div>

      {/* Create Audit Modal */}
      <Modal
        open={createOpen}
        onClose={() => setCreateOpen(false)}
        title="New Audit Cycle"
        size="lg"
        footer={
          <>
            <button className="btn-secondary" onClick={() => setCreateOpen(false)}>Cancel</button>
            <button
              className="btn-primary"
              onClick={handleSubmit((v) => createMutation.mutate(v))}
              disabled={createMutation.isPending}
            >
              Create & Build Checklist
            </button>
          </>
        }
      >
        <form className="grid grid-cols-2 gap-4">
          <div className="col-span-2">
            <label className="label">Audit title *</label>
            <input className="input" placeholder="e.g. Q3 audit: Engineering dept" {...register('title', { required: true })} />
          </div>
          <div>
            <label className="label">Start date *</label>
            <input className="input" type="date" {...register('startDate', { required: true })} />
          </div>
          <div>
            <label className="label">End date *</label>
            <input className="input" type="date" {...register('endDate', { required: true })} />
          </div>
          <div className="col-span-2">
            <label className="label">Auditors (comma-separated names)</label>
            <input className="input" placeholder="e.g. A. Rao, S. Iqbal" {...register('auditorNames')} />
          </div>
          <div>
            <label className="label">Filter assets by department (optional)</label>
            <select className="input" {...register('assetFilter.department')}>
              <option value="">All departments</option>
              {(depts?.items || []).map((d) => (
                <option key={d._id} value={d._id}>{d.name}</option>
              ))}
            </select>
          </div>
          <div className="col-span-2 bg-blue-50 rounded p-3 text-xs text-blue-700">
            💡 The checklist will be auto-populated with all active assets matching your filter.
          </div>
        </form>
      </Modal>
    </div>
  );
}
