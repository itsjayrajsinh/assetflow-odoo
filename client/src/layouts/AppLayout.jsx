import { NavLink, Outlet, useNavigate } from 'react-router-dom';
import { useEffect, useState } from 'react';
import { useAuth, hasRole } from '../store/auth';
import api from '../services/api';
import { io } from 'socket.io-client';
import toast from 'react-hot-toast';
import {
  LayoutDashboard, Building2, Tags, Users, Package, ArrowLeftRight,
  ClipboardList, Bell, LogOut, Menu, Search,
} from 'lucide-react';

const nav = [
  { to: '/', label: 'Dashboard', icon: LayoutDashboard, roles: null },
  { to: '/assets', label: 'Assets', icon: Package, roles: null },
  { to: '/allocations', label: 'Allocations', icon: ClipboardList, roles: null },
  { to: '/transfers', label: 'Transfers', icon: ArrowLeftRight, roles: null },
  { to: '/departments', label: 'Departments', icon: Building2, roles: ['admin', 'asset_manager', 'department_head'] },
  { to: '/categories', label: 'Categories', icon: Tags, roles: ['admin', 'asset_manager'] },
  { to: '/employees', label: 'Employees', icon: Users, roles: ['admin', 'asset_manager', 'department_head'] },
];

export default function AppLayout() {
  const { user, logout } = useAuth();
  const navigate = useNavigate();
  const [open, setOpen] = useState(true);
  const [notifs, setNotifs] = useState([]);
  const [showNotif, setShowNotif] = useState(false);

  useEffect(() => {
    api.get('/notifications').then((r) => setNotifs(r.data.items || []));
    const socket = io(import.meta.env.VITE_SOCKET_URL || 'http://localhost:5000', {
      query: { userId: user?.id },
    });
    socket.on('notification', (n) => {
      setNotifs((p) => [n, ...p]);
      toast(n.title, { icon: '🔔' });
    });
    return () => socket.disconnect();
  }, [user?.id]);

  const doLogout = () => { logout(); navigate('/login'); };
  const unread = notifs.filter((n) => !n.read).length;
  const initials = (user?.name || '?').split(' ').map(s => s[0]).slice(0,2).join('').toUpperCase();
  const items = nav.filter((n) => !n.roles || hasRole(user, ...n.roles));

  return (
    <div className="flex min-h-screen">
      {/* Sidebar */}
      <aside className={`bg-slate-950 text-slate-100 transition-all duration-200 ${open ? 'w-64' : 'w-16'} flex-shrink-0 flex flex-col`}>
        <div className="h-16 flex items-center px-4 border-b border-slate-800">
          <div className="h-9 w-9 rounded-md bg-brand-500 flex items-center justify-center font-bold text-slate-950 flex-shrink-0">A</div>
          {open && <div className="ml-3 font-semibold tracking-tight">AssetFlow</div>}
        </div>
        <nav className="flex-1 p-2 space-y-1">
          {items.map(({ to, label, icon: Icon }) => (
            <NavLink
              key={to}
              to={to}
              end={to === '/'}
              className={({ isActive }) =>
                `flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition ${
                  isActive ? 'bg-brand-500/10 text-brand-400' : 'text-slate-400 hover:bg-slate-900 hover:text-white'
                }`
              }
            >
              <Icon className="h-4.5 w-4.5" size={18} />
              {open && <span>{label}</span>}
            </NavLink>
          ))}
        </nav>
        <div className="p-3 border-t border-slate-800 text-xs text-slate-500">
          {open ? 'v1.0 · Enterprise' : 'v1'}
        </div>
      </aside>

      {/* Main */}
      <div className="flex-1 flex flex-col min-w-0">
        <header className="h-16 bg-white border-b border-slate-200 flex items-center px-4 md:px-6 gap-3">
          <button className="btn-ghost p-2" onClick={() => setOpen(!open)}><Menu size={18} /></button>
          <div className="hidden md:flex items-center flex-1 max-w-md relative">
            <Search size={16} className="absolute left-3 text-slate-400" />
            <input className="input pl-9" placeholder="Search assets, employees…" />
          </div>
          <div className="flex-1 md:hidden" />
          <div className="relative">
            <button className="btn-ghost p-2 relative" onClick={() => setShowNotif(!showNotif)}>
              <Bell size={18} />
              {unread > 0 && <span className="absolute -top-0.5 -right-0.5 bg-red-500 text-white text-[10px] rounded-full h-4 w-4 flex items-center justify-center">{unread}</span>}
            </button>
            {showNotif && (
              <div className="absolute right-0 mt-2 w-80 bg-white border border-slate-200 rounded-lg shadow-lg z-50 max-h-96 overflow-auto">
                <div className="p-3 border-b flex justify-between items-center">
                  <span className="text-sm font-semibold">Notifications</span>
                  <button className="text-xs text-brand-600" onClick={() => { api.post('/notifications/read-all'); setNotifs(notifs.map(n => ({...n, read: true}))); }}>Mark all read</button>
                </div>
                {notifs.length === 0 && <div className="p-6 text-center text-sm text-slate-500">No notifications</div>}
                {notifs.map((n) => (
                  <div key={n._id} className={`p-3 border-b text-sm ${!n.read ? 'bg-brand-50/40' : ''}`}>
                    <div className="font-medium text-slate-800">{n.title}</div>
                    {n.message && <div className="text-slate-500 text-xs mt-0.5">{n.message}</div>}
                  </div>
                ))}
              </div>
            )}
          </div>
          <div className="flex items-center gap-3 pl-3 border-l border-slate-200">
            <div className="h-9 w-9 rounded-full bg-slate-900 text-white flex items-center justify-center text-xs font-semibold">{initials}</div>
            <div className="hidden md:block">
              <div className="text-sm font-medium leading-tight">{user?.name}</div>
              <div className="text-xs text-slate-500 capitalize">{user?.role?.replace('_',' ')}</div>
            </div>
            <button className="btn-ghost p-2" title="Sign out" onClick={doLogout}><LogOut size={16} /></button>
          </div>
        </header>
        <main className="flex-1 p-4 md:p-6 overflow-x-hidden">
          <Outlet />
        </main>
      </div>
    </div>
  );
}
