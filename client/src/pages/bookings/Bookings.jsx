import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { useState } from 'react';
import { useForm } from 'react-hook-form';
import api from '../../services/api';
import PageHeader from '../../components/ui/PageHeader.jsx';
import Modal from '../../components/ui/Modal.jsx';
import toast from 'react-hot-toast';
import { Plus, X, CalendarDays, Clock } from 'lucide-react';
import { useAuth } from '../../store/auth';

// ─── Helpers ─────────────────────────────────────────────────────────────────
const toMinutes = (t) => {
  const [h, m] = t.split(':').map(Number);
  return h * 60 + m;
};

const fmtTime = (t) => {
  const [h, m] = t.split(':').map(Number);
  const ampm = h >= 12 ? 'pm' : 'am';
  return `${h % 12 || 12}:${m.toString().padStart(2, '0')} ${ampm}`;
};

const HOURS = Array.from({ length: 10 }, (_, i) => `${i + 8}:00`); // 08:00 – 17:00

function TimelineRow({ label, bookings, conflict }) {
  // Render a slot row: 8am–6pm, 60px per hour
  const PX_PER_MIN = 2;
  const START_MIN = toMinutes('08:00');

  return (
    <div className="flex items-start gap-3 mb-3">
      <div className="text-xs text-slate-500 w-20 pt-1 flex-shrink-0">{label}</div>
      <div className="relative flex-1 h-10 bg-slate-100 rounded overflow-visible" style={{ minWidth: '600px' }}>
        {/* Hour grid lines */}
        {HOURS.map((h) => (
          <div
            key={h}
            className="absolute top-0 h-full border-l border-slate-200"
            style={{ left: `${(toMinutes(h) - START_MIN) * PX_PER_MIN}px` }}
          />
        ))}

        {/* Confirmed bookings */}
        {bookings.filter(b => b.status === 'confirmed').map((b) => {
          const left = (toMinutes(b.startTime) - START_MIN) * PX_PER_MIN;
          const width = (toMinutes(b.endTime) - toMinutes(b.startTime)) * PX_PER_MIN;
          return (
            <div
              key={b._id}
              className="absolute top-1 h-8 bg-emerald-600 rounded text-white text-xs flex items-center px-2 overflow-hidden whitespace-nowrap z-10"
              style={{ left: `${left}px`, width: `${Math.max(width, 40)}px` }}
              title={`${b.bookedBy?.name} · ${fmtTime(b.startTime)} – ${fmtTime(b.endTime)}`}
            >
              Booked – {b.bookedBy?.name} · {fmtTime(b.startTime)} to {fmtTime(b.endTime)}
            </div>
          );
        })}

        {/* Conflict overlay */}
        {conflict && (
          <div
            className="absolute top-1 h-8 border-2 border-dashed border-red-500 bg-red-500/10 rounded text-red-600 text-xs flex items-center px-2 z-20"
            style={{
              left: `${(toMinutes(conflict.startTime) - START_MIN) * PX_PER_MIN}px`,
              width: `${(toMinutes(conflict.endTime) - toMinutes(conflict.startTime)) * PX_PER_MIN}px`,
            }}
          >
            Requested {fmtTime(conflict.startTime)} to {fmtTime(conflict.endTime)} – conflict – slot unavailable
          </div>
        )}
      </div>
    </div>
  );
}

export default function Bookings() {
  const { user } = useAuth();
  const qc = useQueryClient();
  const [selectedAsset, setSelectedAsset] = useState('');
  const [date, setDate] = useState(new Date().toISOString().slice(0, 10));
  const [open, setOpen] = useState(false);
  const [conflict, setConflict] = useState(null);

  const { register, handleSubmit, reset, watch } = useForm({
    defaultValues: { date, startTime: '09:00', endTime: '10:00' },
  });

  // Shared resources list
  const { data: resources } = useQuery({
    queryKey: ['booking-resources'],
    queryFn: async () => (await api.get('/bookings/resources')).data,
  });

  // Bookings for selected resource+date
  const { data: bookingsData } = useQuery({
    queryKey: ['bookings', selectedAsset, date],
    queryFn: async () =>
      (await api.get('/bookings', { params: { assetId: selectedAsset, date } })).data,
    enabled: !!selectedAsset && !!date,
  });

  const bookings = bookingsData?.data || [];

  // Conflict preview as user types times
  const watchStart = watch('startTime');
  const watchEnd = watch('endTime');
  const previewConflict = watchStart && watchEnd && open ? { startTime: watchStart, endTime: watchEnd } : null;

  const create = useMutation({
    mutationFn: (form) =>
      api.post('/bookings', { ...form, asset: selectedAsset }),
    onSuccess: () => {
      toast.success('Slot booked!');
      qc.invalidateQueries({ queryKey: ['bookings', selectedAsset, date] });
      setOpen(false);
      setConflict(null);
      reset();
    },
    onError: (e) => {
      const msg = e.response?.data?.message || 'Booking failed';
      if (e.response?.status === 409) {
        setConflict({ startTime: watchStart, endTime: watchEnd });
        toast.error(msg);
      } else {
        toast.error(msg);
      }
    },
  });

  const cancel = useMutation({
    mutationFn: (id) => api.delete(`/bookings/${id}`),
    onSuccess: () => {
      toast.success('Booking cancelled');
      qc.invalidateQueries({ queryKey: ['bookings', selectedAsset, date] });
    },
    onError: (e) => toast.error(e.response?.data?.message || 'Failed'),
  });

  const resourceLabel = (resources?.data || []).find((r) => r._id === selectedAsset)
    ? `${(resources?.data || []).find((r) => r._id === selectedAsset).name} – ${date ? new Date(date + 'T00:00:00').toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric' }) : ''}`
    : '';

  return (
    <div>
      <PageHeader
        title="Resource Booking"
        subtitle="Book shared assets and conference rooms by time slot."
        actions={
          selectedAsset && (
            <button className="btn-primary" onClick={() => { setConflict(null); setOpen(true); }}>
              <Plus size={16} /> Book a slot
            </button>
          )
        }
      />

      {/* Filters */}
      <div className="card mb-6">
        <div className="card-body flex flex-wrap gap-4 items-end">
          <div className="flex-1 min-w-[200px]">
            <label className="label">Resource</label>
            <select
              className="input"
              value={selectedAsset}
              onChange={(e) => setSelectedAsset(e.target.value)}
            >
              <option value="">— select a resource —</option>
              {(resources?.data || []).map((r) => (
                <option key={r._id} value={r._id}>
                  {r.name} {r.location ? `· ${r.location}` : ''}
                </option>
              ))}
            </select>
          </div>
          <div>
            <label className="label">Date</label>
            <input
              className="input"
              type="date"
              value={date}
              onChange={(e) => setDate(e.target.value)}
            />
          </div>
        </div>
      </div>

      {/* Timeline */}
      {selectedAsset ? (
        <div className="card">
          <div className="card-header">
            <div className="flex items-center gap-2 text-sm font-medium text-slate-700">
              <CalendarDays size={16} className="text-brand-500" />
              {resourceLabel}
            </div>
          </div>
          <div className="card-body overflow-x-auto">
            {/* Hour labels */}
            <div className="flex items-center gap-3 mb-2">
              <div className="w-20 flex-shrink-0" />
              <div className="flex" style={{ minWidth: '600px' }}>
                {HOURS.map((h) => (
                  <div key={h} className="text-xs text-slate-400" style={{ width: '120px' }}>
                    {fmtTime(h)}
                  </div>
                ))}
              </div>
            </div>

            <TimelineRow
              label="Timeline"
              bookings={bookings}
              conflict={open ? previewConflict : conflict}
            />

            {bookings.length === 0 && (
              <p className="text-sm text-slate-500 mt-2">No bookings on this date. Click "Book a slot" to add one.</p>
            )}

            {/* Booking list */}
            {bookings.length > 0 && (
              <div className="mt-4 space-y-2">
                {bookings.map((b) => (
                  <div key={b._id} className="flex items-center justify-between bg-slate-50 rounded px-3 py-2 text-sm">
                    <div className="flex items-center gap-2">
                      <Clock size={14} className="text-slate-400" />
                      <span className="font-medium">{fmtTime(b.startTime)} – {fmtTime(b.endTime)}</span>
                      <span className="text-slate-500">· {b.bookedBy?.name}</span>
                      {b.purpose && <span className="text-slate-400 italic">· {b.purpose}</span>}
                    </div>
                    {(b.bookedBy?._id === user?._id || ['admin', 'asset_manager'].includes(user?.role)) && (
                      <button
                        className="btn-ghost p-1 text-red-500 hover:bg-red-50"
                        onClick={() => cancel.mutate(b._id)}
                      >
                        <X size={14} />
                      </button>
                    )}
                  </div>
                ))}
              </div>
            )}
          </div>
        </div>
      ) : (
        <div className="card">
          <div className="p-12 text-center text-slate-400 text-sm">
            Select a resource above to view its booking timeline.
          </div>
        </div>
      )}

      {/* Book Slot Modal */}
      <Modal
        open={open}
        onClose={() => { setOpen(false); setConflict(null); }}
        title="Book a slot"
        footer={
          <>
            <button className="btn-secondary" onClick={() => { setOpen(false); setConflict(null); }}>Cancel</button>
            <button className="btn-primary" onClick={handleSubmit((v) => create.mutate(v))}>
              Confirm Booking
            </button>
          </>
        }
      >
        <form className="space-y-4">
          <div className="grid grid-cols-2 gap-4">
            <div>
              <label className="label">Date</label>
              <input className="input" type="date" {...register('date', { required: true })} />
            </div>
            <div />
            <div>
              <label className="label">Start Time</label>
              <input className="input" type="time" {...register('startTime', { required: true })} />
            </div>
            <div>
              <label className="label">End Time</label>
              <input className="input" type="time" {...register('endTime', { required: true })} />
            </div>
          </div>
          <div>
            <label className="label">Purpose (optional)</label>
            <input className="input" placeholder="e.g. Team standup" {...register('purpose')} />
          </div>
          {conflict && (
            <div className="bg-red-50 border border-dashed border-red-400 rounded p-3 text-red-600 text-sm">
              ⚠ Requested {fmtTime(conflict.startTime)} to {fmtTime(conflict.endTime)} — conflict — slot is unavailable
            </div>
          )}
        </form>
      </Modal>
    </div>
  );
}
