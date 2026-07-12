import { Routes, Route, Navigate } from 'react-router-dom';
import ProtectedRoute from './components/common/ProtectedRoute.jsx';
import AppLayout from './layouts/AppLayout.jsx';
import Login from './pages/auth/Login.jsx';
import Signup from './pages/auth/Signup.jsx';
import ForgotPassword from './pages/auth/ForgotPassword.jsx';
import ResetPassword from './pages/auth/ResetPassword.jsx';
import Dashboard from './pages/dashboard/Dashboard.jsx';
import Departments from './pages/departments/Departments.jsx';
import Categories from './pages/categories/Categories.jsx';
import Employees from './pages/employees/Employees.jsx';
import Assets from './pages/assets/Assets.jsx';
import AssetDetail from './pages/assets/AssetDetail.jsx';
import Allocations from './pages/allocations/Allocations.jsx';
import Transfers from './pages/transfers/Transfers.jsx';
import Bookings from './pages/bookings/Bookings.jsx';
import Maintenance from './pages/maintenance/Maintenance.jsx';
import Audit from './pages/audit/Audit.jsx';
import Reports from './pages/reports/Reports.jsx';
import Notifications from './pages/notifications/Notifications.jsx';

export default function App() {
  return (
    <Routes>
      <Route path="/login" element={<Login />} />
      <Route path="/signup" element={<Signup />} />
      <Route path="/forgot-password" element={<ForgotPassword />} />
      <Route path="/reset-password/:token" element={<ResetPassword />} />

      <Route element={<ProtectedRoute><AppLayout /></ProtectedRoute>}>
        <Route path="/" element={<Dashboard />} />
        <Route path="/departments" element={<Departments />} />
        <Route path="/categories" element={<Categories />} />
        <Route path="/employees" element={<Employees />} />
        <Route path="/assets" element={<Assets />} />
        <Route path="/assets/:id" element={<AssetDetail />} />
        <Route path="/allocations" element={<Allocations />} />
        <Route path="/transfers" element={<Transfers />} />
        <Route path="/bookings" element={<Bookings />} />
        <Route path="/maintenance" element={<Maintenance />} />
        <Route path="/audit" element={<Audit />} />
        <Route path="/reports" element={<Reports />} />
        <Route path="/notifications" element={<Notifications />} />
      </Route>

      <Route path="*" element={<Navigate to="/" replace />} />
    </Routes>
  );
}
