import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useState } from 'react';
import { useForm } from 'react-hook-form';
import { Link } from 'react-router-dom';
import api from '../../services/api';
import PageHeader from '../../components/ui/PageHeader.jsx';
import Modal from '../../components/ui/Modal.jsx';
import StatusBadge from '../../components/ui/StatusBadge.jsx';
import toast from 'react-hot-toast';
import { Plus, Download } from 'lucide-react';
import { hasRole, useAuth } from '../../store/auth';
import * as XLSX from 'xlsx';

export default function Assets() {
  const { user } = useAuth();
  const qc = useQueryClient();
  const [status, setStatus] = useState('');
  const [search, setSearch] = useState('');
  const [open, setOpen] = useState(false);
  const { data } = useQuery({
    queryKey: ['assets', status, search],
    queryFn: async () => (await api.get('/assets', { params: { status, search, limit: 100 } })).data,
  });
  const { data: cats } = useQuery({ queryKey: ['categories'], queryFn: async () => (await api.get('/categories')).data });
  const { register, handleSubmit, reset } = useForm();

  const create = useMutation({
    mutationFn: (form) => {
      const fd = new FormData();
      Object.entries(form).forEach(([k, v]) => {
        if (k === 'photo' && v?.[0]) fd.append('photo', v[0]);
        else if (v !== undefined && v !== '') fd.append(k, v);
      });
      return api.post('/assets', fd, { headers: { 'Content-Type': 'multipart/form-data' } });
    },
    onSuccess: () => { toast.success('Asset registered'); qc.invalidateQueries({ queryKey: ['assets'] }); setOpen(false); reset(); },
    onError: (e) => toast.error(e.response?.data?.message || 'Failed'),
  });

  const exportXlsx = () => {
    const rows = (data?.items || []).map((a) => ({
      Tag: a.assetTag, Name: a.name, Category: a.category?.name, Status: a.status,
      Holder: a.currentHolder?.name || '', Location: a.location || '', Cost: a.acquisitionCost,
    }));
    const ws = XLSX.utils.json_to_sheet(rows);
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, 'Assets');
    XLSX.writeFile(wb, 'assets.xlsx');
  };

  const canManage = hasRole(user, 'admin', 'asset_manager');

  return (
    <div>
      <PageHeader
        title="Assets"
        subtitle="Every asset — with tag, QR code and lifecycle status."
        actions={<>
          <button className="btn-secondary" onClick={exportXlsx}><Download size={16} /> Export</button>
          {canManage && <button className="btn-primary" onClick={() => setOpen(true)}><Plus size={16} /> Register Asset</button>}
        </>}
      />
      <div className="card">
        <div className="p-4 border-b flex flex-wrap gap-3">
          <input className="input max-w-xs" placeholder="Search tag, name, serial…" value={search} onChange={(e) => setSearch(e.target.value)} />
          <select className="input max-w-xs" value={status} onChange={(e) => setStatus(e.target.value)}>
            <option value="">All statuses</option>
            {['available','allocated','reserved','under_maintenance','lost','retired','disposed'].map(s => <option key={s} value={s}>{s.replace('_',' ')}</option>)}
          </select>
        </div>
        <div className="overflow-x-auto">
          <table className="w-full">
            <thead className="bg-slate-50"><tr>
              <th className="table-th">Tag</th><th className="table-th">Name</th><th className="table-th">Category</th>
              <th className="table-th">Status</th><th className="table-th">Holder</th><th className="table-th">Location</th>
            </tr></thead>
            <tbody>
              {(data?.items || []).map((a) => (
                <tr key={a._id} className="hover:bg-slate-50">
                  <td className="table-td"><Link className="text-brand-600 font-medium" to={`/assets/${a._id}`}>{a.assetTag}</Link></td>
                  <td className="table-td">{a.name}</td>
                  <td className="table-td">{a.category?.name}</td>
                  <td className="table-td"><StatusBadge value={a.status} /></td>
                  <td className="table-td">{a.currentHolder?.name || '—'}</td>
                  <td className="table-td">{a.location || '—'}</td>
                </tr>
              ))}
            </tbody>
          </table>
          {(data?.items || []).length === 0 && <div className="p-8 text-center text-slate-500 text-sm">No assets found.</div>}
        </div>
      </div>

      <Modal open={open} onClose={() => setOpen(false)} title="Register asset" size="lg"
        footer={<>
          <button className="btn-secondary" onClick={() => setOpen(false)}>Cancel</button>
          <button className="btn-primary" onClick={handleSubmit((v) => create.mutate(v))}>Create</button>
        </>}>
        <form className="grid grid-cols-2 gap-4">
          <div className="col-span-2"><label className="label">Asset name *</label><input className="input" {...register('name', { required: true })} /></div>
          <div>
            <label className="label">Category *</label>
            <select className="input" {...register('category', { required: true })}>
              <option value="">— select —</option>
              {(cats?.items || []).map((c) => <option key={c._id} value={c._id}>{c.name}</option>)}
            </select>
          </div>
          <div><label className="label">Serial number</label><input className="input" {...register('serialNumber')} /></div>
          <div><label className="label">Acquisition date</label><input className="input" type="date" {...register('acquisitionDate')} /></div>
          <div><label className="label">Acquisition cost</label><input className="input" type="number" step="0.01" {...register('acquisitionCost')} /></div>
          <div>
            <label className="label">Condition</label>
            <select className="input" {...register('condition')}>
              {['new','good','fair','poor'].map(c => <option key={c} value={c}>{c}</option>)}
            </select>
          </div>
          <div><label className="label">Location</label><input className="input" {...register('location')} /></div>
          <div className="col-span-2"><label className="label">Photo</label><input className="input" type="file" accept="image/*" {...register('photo')} /></div>
          <label className="col-span-2 flex items-center gap-2 text-sm text-slate-700">
            <input type="checkbox" {...register('isShared')} /> Shared resource (bookable)
          </label>
        </form>
      </Modal>
    </div>
  );
}
