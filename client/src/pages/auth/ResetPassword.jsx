import { useForm } from 'react-hook-form';
import { Link, useNavigate, useParams } from 'react-router-dom';
import api from '../../services/api';
import toast from 'react-hot-toast';

export default function ResetPassword() {
  const { token } = useParams();
  const { register, handleSubmit, formState: { isSubmitting } } = useForm();
  const navigate = useNavigate();
  const onSubmit = async (data) => {
    try {
      await api.post(`/auth/reset-password/${token}`, data);
      toast.success('Password updated. Please sign in.');
      navigate('/login');
    } catch (e) {
      toast.error(e.response?.data?.message || 'Reset failed');
    }
  };
  return (
    <div className="min-h-screen flex items-center justify-center p-6">
      <form onSubmit={handleSubmit(onSubmit)} className="w-full max-w-sm card p-6">
        <h2 className="text-2xl font-semibold">Reset password</h2>
        <div className="mt-6 space-y-4">
          <div><label className="label">New password</label><input className="input" type="password" {...register('password', { required: true, minLength: 6 })} /></div>
          <button className="btn-primary w-full" disabled={isSubmitting}>Update password</button>
        </div>
        <p className="mt-4 text-sm text-center"><Link to="/login" className="text-brand-600">Back to sign in</Link></p>
      </form>
    </div>
  );
}
