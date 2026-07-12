import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { useState } from 'react';
import api from '../../services/api';
import PageHeader from '../../components/ui/PageHeader.jsx';
import { Bell, CheckCheck, Circle } from 'lucide-react';

// Category config — maps notification type prefixes to filter tabs
const TABS = [
  { key: 'all', label: 'All' },
  { key: 'alert', label: 'Alerts' },
  { key: 'approval', label: 'Approvals' },
  { key: 'booking', label: 'Bookings' },
];

// Map notification types to tab keys and dot colors
function categorize(type) {
  const t = (type || '').toLowerCase();
  if (t.includes('booking') || t.includes('resource')) return { tab: 'booking', color: 'bg-blue-500' };
  if (t.includes('approval') || t.includes('transfer') || t.includes('maintenance')) return { tab: 'approval', color: 'bg-slate-400' };
  if (t.includes('overdue') || t.includes('audit') || t.includes('discrepancy') || t.includes('flag')) return { tab: 'alert', color: 'bg-red-500' };
  if (t.includes('allocation') || t.includes('assign')) return { tab: 'alert', color: 'bg-emerald-500' };
  return { tab: 'alert', color: 'bg-slate-400' };
}

function timeAgo(date) {
  const seconds = Math.floor((Date.now() - new Date(date).getTime()) / 1000);
  if (seconds < 60) return 'just now';
  const minutes = Math.floor(seconds / 60);
  if (minutes < 60) return `${minutes}m ago`;
  const hours = Math.floor(minutes / 60);
  if (hours < 24) return `${hours}h ago`;
  const days = Math.floor(hours / 24);
  return `${days}d ago`;
}

export default function Notifications() {
  const qc = useQueryClient();
  const [activeTab, setActiveTab] = useState('all');

  const { data } = useQuery({
    queryKey: ['notifications-page'],
    queryFn: async () => (await api.get('/notifications')).data,
    refetchInterval: 15000,
  });

  const notifications = data?.items || [];

  const markAllRead = useMutation({
    mutationFn: () => api.post('/notifications/read-all'),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['notifications-page'] }),
  });

  const markRead = useMutation({
    mutationFn: (id) => api.patch(`/notifications/${id}/read`),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['notifications-page'] }),
  });

  // Filter by tab
  const filtered = activeTab === 'all'
    ? notifications
    : notifications.filter((n) => categorize(n.type).tab === activeTab);

  const unreadCount = notifications.filter((n) => !n.read).length;

  return (
    <div>
      <PageHeader
        title="Notifications"
        subtitle={`${unreadCount} unread notification${unreadCount !== 1 ? 's' : ''}`}
        actions={
          unreadCount > 0 && (
            <button
              className="btn-secondary"
              onClick={() => markAllRead.mutate()}
              disabled={markAllRead.isPending}
            >
              <CheckCheck size={16} /> Mark all read
            </button>
          )
        }
      />

      {/* Filter tabs */}
      <div className="flex gap-2 mb-4">
        {TABS.map((tab) => (
          <button
            key={tab.key}
            className={`px-4 py-2 rounded-md text-sm font-medium transition ${
              activeTab === tab.key
                ? 'bg-brand-600 text-white shadow-sm'
                : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50'
            }`}
            onClick={() => setActiveTab(tab.key)}
          >
            {tab.label}
          </button>
        ))}
      </div>

      {/* Notification list */}
      <div className="card">
        {filtered.length > 0 ? (
          <div className="divide-y divide-slate-100">
            {filtered.map((n) => {
              const cat = categorize(n.type);
              return (
                <div
                  key={n._id}
                  className={`flex items-start gap-3 px-5 py-4 transition cursor-pointer hover:bg-slate-50 ${
                    !n.read ? 'bg-slate-50/60' : ''
                  }`}
                  onClick={() => { if (!n.read) markRead.mutate(n._id); }}
                >
                  {/* Color dot */}
                  <div className="pt-1.5 flex-shrink-0">
                    <span className={`inline-block h-2.5 w-2.5 rounded-full ${cat.color}`} />
                  </div>

                  {/* Content */}
                  <div className="flex-1 min-w-0">
                    <div className={`text-sm leading-snug ${!n.read ? 'font-medium text-slate-800' : 'text-slate-600'}`}>
                      {n.title}
                    </div>
                    {n.message && (
                      <div className="text-xs text-slate-500 mt-0.5">{n.message}</div>
                    )}
                  </div>

                  {/* Time ago */}
                  <div className="text-xs text-slate-400 flex-shrink-0 whitespace-nowrap pt-0.5">
                    {timeAgo(n.createdAt)}
                  </div>
                </div>
              );
            })}
          </div>
        ) : (
          <div className="p-12 text-center">
            <Bell size={32} className="mx-auto text-slate-300 mb-3" />
            <div className="text-sm text-slate-400">
              {activeTab === 'all' ? 'No notifications yet.' : `No ${activeTab} notifications.`}
            </div>
          </div>
        )}
      </div>
    </div>
  );
}
