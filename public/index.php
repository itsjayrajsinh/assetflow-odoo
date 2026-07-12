<?php
/**
 * AssetFlow — Front Controller (Entry Point)
 * All requests are routed through this file.
 */

// ── Error Reporting ──────────────────────────────────────
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ── Path Constants ───────────────────────────────────────
define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/app');
define('PUBLIC_PATH', __DIR__);

// ── Autoload Core Files ──────────────────────────────────
require_once APP_PATH . '/Config/Database.php';
require_once APP_PATH . '/Core/Router.php';
require_once APP_PATH . '/Core/Controller.php';
require_once APP_PATH . '/Core/Model.php';
require_once APP_PATH . '/Core/Auth.php';
require_once APP_PATH . '/Core/Middleware.php';
require_once APP_PATH . '/Core/Helpers.php';

// ── Start Session ────────────────────────────────────────
Auth::init();

// ── Define Routes ────────────────────────────────────────

// Auth Routes
Router::get('/login',               'AuthController',         'showLogin');
Router::post('/login',              'AuthController',         'login');
Router::get('/signup',              'AuthController',         'showSignup');
Router::post('/signup',             'AuthController',         'signup');
Router::get('/forgot-password',     'AuthController',         'showForgotPassword');
Router::post('/forgot-password',    'AuthController',         'forgotPassword');
Router::get('/reset-password',      'AuthController',         'showResetPassword');
Router::post('/reset-password',     'AuthController',         'resetPassword');
Router::get('/logout',              'AuthController',         'logout');

// Dashboard
Router::get('/',                    'DashboardController',    'index');
Router::get('/dashboard',           'DashboardController',    'index');
Router::get('/api/dashboard/stats', 'DashboardController',    'stats');

// Organization Setup — Departments
Router::get('/organization/departments',       'DepartmentController',   'index');
Router::post('/organization/departments',      'DepartmentController',   'store');
Router::post('/organization/departments/update','DepartmentController',  'update');
Router::post('/organization/departments/delete','DepartmentController',  'delete');

// Organization Setup — Categories
Router::get('/organization/categories',        'CategoryController',     'index');
Router::post('/organization/categories',       'CategoryController',     'store');
Router::post('/organization/categories/update','CategoryController',     'update');
Router::post('/organization/categories/delete','CategoryController',     'delete');

// Organization Setup — Employees
Router::get('/organization/employees',         'EmployeeController',     'index');
Router::post('/organization/employees/update', 'EmployeeController',     'update');
Router::post('/organization/employees/role',   'EmployeeController',     'updateRole');
Router::get('/api/employees',                  'EmployeeController',     'apiList');

// Assets
Router::get('/assets',                 'AssetController',        'index');
Router::get('/assets/register',        'AssetController',        'showRegister');
Router::post('/assets/register',       'AssetController',        'register');
Router::get('/assets/detail/{id}',     'AssetController',        'detail');
Router::post('/assets/update',         'AssetController',        'update');
Router::post('/assets/status',         'AssetController',        'updateStatus');
Router::get('/api/assets/search',      'AssetController',        'apiSearch');
Router::get('/api/assets/available',   'AssetController',        'apiAvailable');

// Allocation & Transfer
Router::get('/allocation',                     'AllocationController',   'index');
Router::get('/allocation/allocate',            'AllocationController',   'showAllocate');
Router::post('/allocation/allocate',           'AllocationController',   'allocate');
Router::post('/allocation/return',             'AllocationController',   'returnAsset');
Router::get('/allocation/transfers',           'AllocationController',   'transfers');
Router::post('/allocation/transfer/request',   'AllocationController',   'requestTransfer');
Router::post('/allocation/transfer/approve',   'AllocationController',   'approveTransfer');
Router::post('/allocation/transfer/reject',    'AllocationController',   'rejectTransfer');

// Resource Booking
Router::get('/booking',                'BookingController',      'index');
Router::get('/booking/new',            'BookingController',      'showBook');
Router::post('/booking/new',           'BookingController',      'book');
Router::post('/booking/cancel',        'BookingController',      'cancel');
Router::get('/api/booking/events/{id}','BookingController',      'apiEvents');
Router::get('/api/booking/check',      'BookingController',      'apiCheckOverlap');

// Maintenance
Router::get('/maintenance',            'MaintenanceController',  'index');
Router::get('/maintenance/create',     'MaintenanceController',  'showCreate');
Router::post('/maintenance/create',    'MaintenanceController',  'create');
Router::post('/maintenance/approve',   'MaintenanceController',  'approve');
Router::post('/maintenance/reject',    'MaintenanceController',  'reject');
Router::post('/maintenance/assign',    'MaintenanceController',  'assign');
Router::post('/maintenance/progress',  'MaintenanceController',  'progress');
Router::post('/maintenance/resolve',   'MaintenanceController',  'resolve');

// Audit
Router::get('/audit',                  'AuditController',        'index');
Router::get('/audit/create',           'AuditController',        'showCreate');
Router::post('/audit/create',          'AuditController',        'create');
Router::get('/audit/verify/{id}',      'AuditController',        'showVerify');
Router::post('/audit/verify',          'AuditController',        'verify');
Router::post('/audit/close',           'AuditController',        'close');
Router::get('/audit/report/{id}',      'AuditController',        'report');

// Reports
Router::get('/reports',                'ReportController',       'index');
Router::get('/api/reports/utilization','ReportController',       'apiUtilization');
Router::get('/api/reports/maintenance','ReportController',       'apiMaintenance');
Router::get('/api/reports/bookings',   'ReportController',       'apiBookings');
Router::get('/api/reports/departments','ReportController',       'apiDepartments');
Router::get('/reports/export',         'ReportController',       'export');

// Notifications
Router::get('/notifications',          'NotificationController', 'index');
Router::post('/notifications/read',    'NotificationController', 'markRead');
Router::post('/notifications/read-all','NotificationController', 'markAllRead');
Router::get('/api/notifications/count','NotificationController', 'apiCount');
Router::get('/api/notifications/recent','NotificationController','apiRecent');
Router::get('/notifications/logs',     'NotificationController', 'logs');

// Chatbot
Router::post('/api/chatbot',           'ChatbotController',      'chat');

// ── Dispatch Request ─────────────────────────────────────
Router::dispatch();
