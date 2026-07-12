import { useForm } from 'react-hook-form';
import { Link, useNavigate } from 'react-router-dom';
import api from '../../services/api';
import { useAuth } from '../../store/auth';
import toast from 'react-hot-toast';

export default function Login() {
  const { register, handleSubmit, formState: { isSubmitting } } = useForm({
    defaultValues: { email: 'admin@assetflow.com', password: 'Admin@123' },
  });
  const setSession = useAuth((s) => s.setSession);
  const navigate = useNavigate();

  const onSubmit = async (data) => {
    try {
      const { data: res } = await api.post('/auth/login', data);
      setSession(res.user, res.token);
      toast.success(`Welcome back, ${res.user.name}`);
      navigate('/');
    } catch (e) {
      toast.error(e.response?.data?.message || 'Login failed');
    }
  };

  return (
    <div className="min-h-screen flex">
      <div className="hidden lg:flex flex-1 bg-slate-950 text-white p-12 flex-col justify-between">
        <div className="flex items-center gap-2">
          <div className="h-10 w-10 rounded-lg bg-brand-500 flex items-center justify-center font-bold text-slate-950">A</div>
          <span className="text-lg font-semibold">AssetFlow</span>
        </div>
        <div className="max-w-md">
          <h1 className="text-4xl font-bold leading-tight">Enterprise asset & resource management, done right.</h1>
          <p className="mt-4 text-slate-400">Track every laptop, monitor, meeting room and vehicle across every department — with full audit, allocation and lifecycle workflows.</p>
        </div>
        <div className="text-xs text-slate-500">© AssetFlow · Enterprise Edition</div>
      </div>
      <div className="flex-1 flex items-center justify-center p-6">
        <form onSubmit={handleSubmit(onSubmit)} className="w-full max-w-sm">
          <h2 className="text-2xl font-semibold text-slate-900">Sign in</h2>
          <p className="text-sm text-slate-500 mt-1">Use your organization account.</p>
          <div className="mt-6 space-y-4">
            <div>
              <label className="label">Email</label>
              <input className="input" type="email" {...register('email', { required: true })} />
            </div>
            <div>
              <label className="label">Password</label>
              <input className="input" type="password" {...register('password', { required: true })} />
            </div>
            <button className="btn-primary w-full" disabled={isSubmitting}>{isSubmitting ? 'Signing in…' : 'Sign in'}</button>
          </div>
          <div className="mt-4 flex justify-between text-sm">
            <Link to="/forgot-password" className="text-brand-600 hover:underline">Forgot password?</Link>
            <Link to="/signup" className="text-brand-600 hover:underline">Create account</Link>
          </div>
        </form>
      </div>
    </div>
  );
}
