import { NavLink, Outlet, useNavigate } from 'react-router-dom';
import { useEffect, useState } from 'react';
import { useAuth, hasRole } from '../store/auth';
import api from '../services/api';
import { io } from 'socket.io-client';
import toast from 'react-hot-toast';
import { useMutation } from '@tanstack/react-query';
import { useForm } from 'react-hook-form';
import Modal from '../components/ui/Modal.jsx';
import {
  LayoutDashboard, Building2, Tags, Users, Package, ArrowLeftRight,
  ClipboardList, Bell, LogOut, Menu, Search, CalendarDays, Wrench,
  ClipboardCheck, ChevronDown, ChevronRight as ChevronRightIcon,
  BarChart2, Moon, Sun,
} from 'lucide-react';

// Navigation sections to match wireframe
const NAV_SECTIONS = [
  {
    items: [
      { to: '/', label: 'Dashboard', icon: LayoutDashboard, roles: null },
    ],
  },
  {
    label: 'Organization Setup',
    icon: Building2,
    collapsible: true,
    roles: ['admin', 'asset_manager', 'department_head'],
    items: [
      { to: '/departments', label: 'Departments', icon: Building2, roles: ['admin', 'asset_manager', 'department_head'] },
      { to: '/categories', label: 'Categories', icon: Tags, roles: ['admin', 'asset_manager'] },
      { to: '/employees', label: 'Employees', icon: Users, roles: ['admin', 'asset_manager', 'department_head'] },
    ],
  },
  {
    items: [
      { to: '/assets', label: 'Assets', icon: Package, roles: null },
      { to: '/allocations', label: 'Allocation & Transfer', icon: ArrowLeftRight, roles: null },
      { to: '/bookings', label: 'Resource Booking', icon: CalendarDays, roles: null },
      { to: '/maintenance', label: 'Maintenance', icon: Wrench, roles: null },
      { to: '/audit', label: 'Audit', icon: ClipboardCheck, roles: null },
    ],
  },
  {
    items: [
      { to: '/reports', label: 'Reports', icon: BarChart2, roles: ['admin', 'asset_manager'] },
      { to: '/notifications', label: 'Notifications', icon: Bell, roles: null },
    ],
  },
];

export default function AppLayout() {
  const { user, token, setSession, logout } = useAuth();
  const navigate = useNavigate();
  const [open, setOpen] = useState(true);
  const [notifs, setNotifs] = useState([]);
  const [showNotif, setShowNotif] = useState(false);
  const [orgOpen, setOrgOpen] = useState(false);
  const [isDark, setIsDark] = useState(() => {
    if (typeof window !== 'undefined') {
      return localStorage.getItem('theme') === 'dark' ||
        (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches);
    }
    return false;
  });

  useEffect(() => {
    const root = window.document.documentElement;
    if (isDark) {
      root.classList.add('dark');
      localStorage.setItem('theme', 'dark');
    } else {
      root.classList.remove('dark');
      localStorage.setItem('theme', 'light');
    }
  }, [isDark]);

  const [profileOpen, setProfileOpen] = useState(false);
  const { register: regProfile, handleSubmit: handleProfileSubmit } = useForm({
    defaultValues: { name: user?.name, email: user?.email }
  });
  const updateProfile = useMutation({
    mutationFn: (data) => api.patch(`/users/${user._id}`, data),
    onSuccess: (res) => {
      setSession(res.data, token);
      toast.success('Profile updated');
      setProfileOpen(false);
    },
    onError: (e) => toast.error(e.response?.data?.message || 'Update failed'),
  });

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
  const initials = (user?.name || '?').split(' ').map(s => s[0]).slice(0, 2).join('').toUpperCase();

  // NavLink class helper
  const linkCls = ({ isActive }) =>
    `flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition ${
      isActive ? 'bg-brand-500/10 text-brand-400' : 'text-slate-400 hover:bg-slate-900 hover:text-white'
    }`;

  // Filter visible items for a section
  const visibleItems = (items) =>
    items.filter((i) => !i.roles || hasRole(user, ...i.roles));

  return (
    <div className="flex min-h-screen">
      {/* Sidebar */}
      <aside className={`bg-slate-950 text-slate-100 transition-all duration-200 ${open ? 'w-64' : 'w-16'} flex-shrink-0 flex flex-col`}>
        <div className="h-16 flex items-center px-4 border-b border-slate-800">
          <div className="h-9 w-9 rounded-md bg-brand-500 flex items-center justify-center font-bold text-slate-950 flex-shrink-0">A</div>
          {open && <div className="ml-3 font-semibold tracking-tight">AssetFlow</div>}
        </div>

        <nav className="flex-1 p-2 space-y-1 overflow-y-auto">
          {NAV_SECTIONS.map((section, si) => {
            // Collapsible section (Organization Setup)
            if (section.collapsible) {
              if (!hasRole(user, ...section.roles)) return null;
              return (
                <div key={si}>
                  <button
                    className={`w-full flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium text-slate-400 hover:bg-slate-900 hover:text-white transition ${open ? '' : 'justify-center'}`}
                    onClick={() => setOrgOpen((v) => !v)}
                  >
                    <section.icon size={18} className="flex-shrink-0" />
                    {open && (
                      <>
                        <span className="flex-1 text-left">{section.label}</span>
                        {orgOpen ? <ChevronDown size={14} /> : <ChevronRightIcon size={14} />}
                      </>
                    )}
                  </button>
                  {orgOpen && open && (
                    <div className="pl-4 space-y-1 mt-1">
                      {visibleItems(section.items).map(({ to, label, icon: Icon }) => (
                        <NavLink key={to} to={to} className={linkCls}>
                          <Icon size={16} />
                          <span>{label}</span>
                        </NavLink>
                      ))}
                    </div>
                  )}
                </div>
              );
            }

            // Regular section
            return (
              <div key={si} className={si > 0 ? 'pt-1 border-t border-slate-800/60 mt-1' : ''}>
                {visibleItems(section.items).map(({ to, label, icon: Icon }) => (
                  <NavLink
                    key={to}
                    to={to}
                    end={to === '/'}
                    className={linkCls}
                  >
                    <Icon size={18} className="flex-shrink-0" />
                    {open && <span>{label}</span>}
                  </NavLink>
                ))}
              </div>
            );
          })}
        </nav>

        <div className="p-3 border-t border-slate-800 text-xs text-slate-500">
          {open ? 'v1.0 · Enterprise' : 'v1'}
        </div>
      </aside>

      {/* Main */}
      <div className="flex-1 flex flex-col min-w-0">
        <header className="h-16 bg-white dark:bg-slate-950 border-b border-slate-200 dark:border-slate-800 flex items-center px-4 md:px-6 gap-3 transition-colors duration-200">
          <button className="btn-ghost p-2" onClick={() => setOpen(!open)}><Menu size={18} /></button>
          <div className="hidden md:flex items-center flex-1 max-w-md relative">
            <Search size={16} className="absolute left-3 text-slate-400" />
            <input className="input pl-9" placeholder="Search assets, employees…" />
          </div>
          <div className="flex-1 md:hidden" />
          
          <button 
            className="btn-ghost p-2 rounded-full" 
            onClick={() => setIsDark(!isDark)}
            title="Toggle theme"
          >
            {isDark ? <Sun size={18} /> : <Moon size={18} />}
          </button>

          <div className="relative">
            <button className="btn-ghost p-2 relative" onClick={() => setShowNotif(!showNotif)}>
              <Bell size={18} />
              {unread > 0 && <span className="absolute -top-0.5 -right-0.5 bg-red-500 text-white text-[10px] rounded-full h-4 w-4 flex items-center justify-center">{unread}</span>}
            </button>
            {showNotif && (
              <div className="absolute right-0 mt-2 w-80 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg shadow-lg z-50 max-h-96 overflow-auto">
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
            <div 
              className="flex items-center gap-3 cursor-pointer hover:opacity-80 transition"
              onClick={() => setProfileOpen(true)}
              title="Edit Profile"
            >
              <div className="h-9 w-9 rounded-full bg-brand-600 dark:bg-brand-500 text-white flex items-center justify-center text-xs font-semibold">{initials}</div>
              <div className="hidden md:block">
                <div className="text-sm font-medium leading-tight dark:text-slate-200">{user?.name}</div>
                <div className="text-xs text-slate-500 dark:text-slate-400 capitalize">{user?.role?.replace('_', ' ')}</div>
              </div>
            </div>
            <button className="btn-ghost p-2 ml-2" title="Sign out" onClick={doLogout}><LogOut size={16} /></button>
          </div>
        </header>
        <main className="flex-1 p-4 md:p-6 overflow-x-hidden dark:bg-slate-950 transition-colors duration-200">
          <Outlet />
        </main>
      </div>

      {/* Profile Edit Modal */}
      <Modal open={profileOpen} onClose={() => setProfileOpen(false)} title="Edit Profile"
        footer={<>
          <button className="btn-secondary" onClick={() => setProfileOpen(false)}>Cancel</button>
          <button className="btn-primary" onClick={handleProfileSubmit((v) => updateProfile.mutate(v))} disabled={updateProfile.isPending}>
            {updateProfile.isPending ? 'Saving...' : 'Save'}
          </button>
        </>}>
        <form className="space-y-4">
          <div>
            <label className="label">Full Name</label>
            <input className="input" {...regProfile('name', { required: true })} />
          </div>
          <div>
            <label className="label">Email Address</label>
            <input className="input" type="email" {...regProfile('email', { required: true })} />
          </div>
        </form>
      </Modal>

    </div>
  );
}

