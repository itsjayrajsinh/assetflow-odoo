import { useForm } from 'react-hook-form';
import { Link, useNavigate } from 'react-router-dom';
import api from '../../services/api';
import { useAuth } from '../../store/auth';
import toast from 'react-hot-toast';

export default function Signup() {
  const { register, handleSubmit, formState: { isSubmitting } } = useForm();
  const setSession = useAuth((s) => s.setSession);
  const navigate = useNavigate();

  const onSubmit = async (data) => {
    try {
      const { data: res } = await api.post('/auth/signup', data);
      setSession(res.user, res.token);
      toast.success('Account created');
      navigate('/');
    } catch (e) {
      toast.error(e.response?.data?.message || 'Signup failed');
    }
  };

  return (
    <div className="min-h-screen flex items-center justify-center bg-slate-50 p-6">
      <form onSubmit={handleSubmit(onSubmit)} className="w-full max-w-sm card p-6">
        <h2 className="text-2xl font-semibold text-slate-900">Create your account</h2>
        <p className="text-sm text-slate-500 mt-1">Employees only. Roles are assigned by admins.</p>
        <div className="mt-6 space-y-4">
          <div><label className="label">Full name</label><input className="input" {...register('name', { required: true })} /></div>
          <div><label className="label">Email</label><input className="input" type="email" {...register('email', { required: true })} /></div>
          <div><label className="label">Password</label><input className="input" type="password" {...register('password', { required: true, minLength: 6 })} /></div>
          <button className="btn-primary w-full" disabled={isSubmitting}>{isSubmitting ? 'Creating…' : 'Create account'}</button>
        </div>
        <p className="mt-4 text-sm text-center">Already have an account? <Link to="/login" className="text-brand-600">Sign in</Link></p>
      </form>
    </div>
  );
}
