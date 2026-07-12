import { useForm } from 'react-hook-form';
import { Link } from 'react-router-dom';
import api from '../../services/api';
import toast from 'react-hot-toast';

export default function ForgotPassword() {
  const { register, handleSubmit, formState: { isSubmitting } } = useForm();
  const onSubmit = async (data) => {
    await api.post('/auth/forgot-password', data);
    toast.success('If that email exists, a reset link has been sent.');
  };
  return (
    <div className="min-h-screen flex items-center justify-center p-6">
      <form onSubmit={handleSubmit(onSubmit)} className="w-full max-w-sm card p-6">
        <h2 className="text-2xl font-semibold">Forgot password</h2>
        <p className="text-sm text-slate-500 mt-1">We'll email you a reset link.</p>
        <div className="mt-6 space-y-4">
          <div><label className="label">Email</label><input className="input" type="email" {...register('email', { required: true })} /></div>
          <button className="btn-primary w-full" disabled={isSubmitting}>Send reset link</button>
        </div>
        <p className="mt-4 text-sm text-center"><Link to="/login" className="text-brand-600">Back to sign in</Link></p>
      </form>
    </div>
  );
}
