import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useState, useRef, useEffect } from 'react';
import { useForm } from 'react-hook-form';
import { Link } from 'react-router-dom';
import api from '../../services/api';
import PageHeader from '../../components/ui/PageHeader.jsx';
import Modal from '../../components/ui/Modal.jsx';
import StatusBadge from '../../components/ui/StatusBadge.jsx';
import toast from 'react-hot-toast';
import { Plus, Download, ChevronDown, FileSpreadsheet, FileText, FileDown } from 'lucide-react';
import { hasRole, useAuth } from '../../store/auth';
import * as XLSX from 'xlsx';
import jsPDF from 'jspdf';
import 'jspdf-autotable';

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

  // ─── Export helpers ────────────────────────────────────────────────────────
  const fetchAllForExport = async () => {
    try {
      const res = await api.get('/assets', { params: { status, search, limit: 10000 } });
      return res.data?.items || [];
    } catch (e) {
      toast.error('Failed to fetch data for export');
      return [];
    }
  };

  const getRows = (items) =>
    (items || []).map((a) => ({
      Tag: a.assetTag,
      Name: a.name,
      Category: a.category?.name || '',
      Status: a.status?.replace(/_/g, ' ') || '',
      Holder: a.currentHolder?.name || '',
      Location: a.location || '',
      Cost: a.acquisitionCost ?? '',
      Condition: a.condition || '',
      'Serial #': a.serialNumber || '',
    }));

  const exportXlsx = async () => {
    const items = await fetchAllForExport();
    if (!items.length) { toast.error('No data to export'); return; }
    const ws = XLSX.utils.json_to_sheet(getRows(items));
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, 'Assets');
    XLSX.writeFile(wb, 'assets.xlsx');
    toast.success('Exported as XLSX');
  };

  const exportCsv = async () => {
    const items = await fetchAllForExport();
    if (!items.length) { toast.error('No data to export'); return; }
    const rows = getRows(items);
    const headers = Object.keys(rows[0]);
    const csv = [
      headers.join(','),
      ...rows.map((r) =>
        headers.map((h) => `"${String(r[h]).replace(/"/g, '""')}"`).join(',')
      ),
    ].join('\n');
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url; a.download = 'assets.csv'; a.click();
    URL.revokeObjectURL(url);
    toast.success('Exported as CSV');
  };

  const exportPdf = async () => {
    const items = await fetchAllForExport();
    if (!items.length) { toast.error('No data to export'); return; }
    const rows = getRows(items);
    const doc = new jsPDF({ orientation: 'landscape' });
    doc.setFontSize(14);
    doc.text('Asset Register', 14, 15);
    doc.setFontSize(9);
    doc.text(`Generated: ${new Date().toLocaleString()}`, 14, 22);
    doc.autoTable({
      startY: 28,
      head: [['Tag', 'Name', 'Category', 'Status', 'Holder', 'Location', 'Cost', 'Condition']],
      body: rows.map((r) => [
        r.Tag, r.Name, r.Category, r.Status, r.Holder, r.Location,
        r.Cost !== '' ? `₹${Number(r.Cost).toLocaleString()}` : '',
        r.Condition,
      ]),
      styles: { fontSize: 8, cellPadding: 3 },
      headStyles: { fillColor: [15, 23, 42], textColor: 255, fontStyle: 'bold' },
      alternateRowStyles: { fillColor: [248, 250, 252] },
    });
    doc.save('assets.pdf');
    toast.success('Exported as PDF');
  };

  // ─── Export dropdown state ─────────────────────────────────────────────────
  const [exportOpen, setExportOpen] = useState(false);
  const exportRef = useRef(null);
  useEffect(() => {
    const handler = (e) => { if (exportRef.current && !exportRef.current.contains(e.target)) setExportOpen(false); };
    document.addEventListener('mousedown', handler);
    return () => document.removeEventListener('mousedown', handler);
  }, []);

  const canManage = hasRole(user, 'admin', 'asset_manager');

  return (
    <div>
      <PageHeader
        title="Assets"
        subtitle="Every asset — with tag, QR code and lifecycle status."
        actions={<>
          {/* Export dropdown */}
          <div className="relative" ref={exportRef}>
            <button
              className="btn-secondary"
              onClick={() => setExportOpen((v) => !v)}
            >
              <Download size={16} /> Export <ChevronDown size={14} />
            </button>
            {exportOpen && (
              <div className="absolute right-0 mt-1 w-44 bg-white border border-slate-200 rounded-lg shadow-lg z-50 overflow-hidden">
                <button
                  className="w-full flex items-center gap-2 px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50"
                  onClick={() => { exportXlsx(); setExportOpen(false); }}
                >
                  <FileSpreadsheet size={15} className="text-emerald-600" /> Excel (.xlsx)
                </button>
                <button
                  className="w-full flex items-center gap-2 px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50"
                  onClick={() => { exportCsv(); setExportOpen(false); }}
                >
                  <FileDown size={15} className="text-blue-600" /> CSV (.csv)
                </button>
                <button
                  className="w-full flex items-center gap-2 px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50"
                  onClick={() => { exportPdf(); setExportOpen(false); }}
                >
                  <FileText size={15} className="text-red-600" /> PDF (.pdf)
                </button>
              </div>
            )}
          </div>
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
