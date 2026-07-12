import { Navigate, useLocation } from 'react-router-dom';
import { useAuth } from '../../store/auth';

export default function ProtectedRoute({ children, roles }) {
  const { user, token } = useAuth();
  const location = useLocation();
  if (!token || !user) return <Navigate to="/login" state={{ from: location }} replace />;
  if (roles && !roles.includes(user.role)) return <Navigate to="/" replace />;
  return children;
}
