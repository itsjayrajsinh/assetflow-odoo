<?php
$currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$role = $currentRole ?? 'Employee';

// Helper to check active state
function isActive($path, $currentPath) {
    if ($path === '/dashboard' && ($currentPath === '/' || $currentPath === '/dashboard')) return true;
    if ($path !== '/dashboard' && str_starts_with($currentPath, $path)) return true;
    return false;
}
?>

<!-- Sidebar Navigation -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div class="brand-icon">AF</div>
        <div class="brand-text">Asset<span>Flow</span></div>
    </div>

    <nav class="sidebar-nav">
        <!-- Main -->
        <div class="sidebar-section">Main</div>
        <a href="/dashboard" class="sidebar-link <?= isActive('/dashboard', $currentPath) ? 'active' : '' ?>">
            <i class="bi bi-speedometer2"></i>
            <span>Dashboard</span>
        </a>

        <?php if (in_array($role, ['Admin'])): ?>
        <!-- Admin Section -->
        <div class="sidebar-section">Administration</div>
        <a href="/organization/departments" class="sidebar-link <?= isActive('/organization', $currentPath) ? 'active' : '' ?>">
            <i class="bi bi-building-gear"></i>
            <span>Organization Setup</span>
        </a>
        <?php endif; ?>

        <!-- Asset Management -->
        <div class="sidebar-section">Asset Management</div>
        <a href="/assets" class="sidebar-link <?= isActive('/assets', $currentPath) ? 'active' : '' ?>">
            <i class="bi bi-box-seam"></i>
            <span>Asset Directory</span>
        </a>

        <?php if (in_array($role, ['Admin', 'Asset Manager', 'Department Head'])): ?>
        <a href="/allocation" class="sidebar-link <?= isActive('/allocation', $currentPath) ? 'active' : '' ?>">
            <i class="bi bi-arrow-left-right"></i>
            <span>Allocation & Transfer</span>
        </a>
        <?php endif; ?>

        <!-- Resources -->
        <div class="sidebar-section">Resources</div>
        <a href="/booking" class="sidebar-link <?= isActive('/booking', $currentPath) ? 'active' : '' ?>">
            <i class="bi bi-calendar-event"></i>
            <span>Resource Booking</span>
        </a>

        <!-- Maintenance -->
        <div class="sidebar-section">Operations</div>
        <a href="/maintenance" class="sidebar-link <?= isActive('/maintenance', $currentPath) ? 'active' : '' ?>">
            <i class="bi bi-tools"></i>
            <span>Maintenance</span>
        </a>

        <?php if (in_array($role, ['Admin', 'Asset Manager'])): ?>
        <a href="/audit" class="sidebar-link <?= isActive('/audit', $currentPath) ? 'active' : '' ?>">
            <i class="bi bi-clipboard-check"></i>
            <span>Asset Audit</span>
        </a>
        <?php endif; ?>

        <?php if (in_array($role, ['Admin', 'Asset Manager', 'Department Head'])): ?>
        <a href="/reports" class="sidebar-link <?= isActive('/reports', $currentPath) ? 'active' : '' ?>">
            <i class="bi bi-graph-up-arrow"></i>
            <span>Reports & Analytics</span>
        </a>
        <?php endif; ?>

        <!-- Activity -->
        <div class="sidebar-section">Activity</div>
        <a href="/notifications" class="sidebar-link <?= isActive('/notifications', $currentPath) ? 'active' : '' ?>">
            <i class="bi bi-bell"></i>
            <span>Notifications</span>
            <span class="badge bg-danger-subtle text-danger" id="sidebarNotifCount" style="display:none;">0</span>
        </a>

        <?php if (in_array($role, ['Admin', 'Asset Manager'])): ?>
        <a href="/notifications/logs" class="sidebar-link <?= str_contains($currentPath, '/logs') ? 'active' : '' ?>">
            <i class="bi bi-journal-text"></i>
            <span>Activity Logs</span>
        </a>
        <?php endif; ?>
    </nav>

    <!-- Sidebar Footer -->
    <div style="padding:16px;border-top:1px solid var(--border);margin-top:auto;">
        <div style="background:var(--primary-subtle);border-radius:var(--radius);padding:14px;text-align:center;">
            <i class="bi bi-question-circle" style="font-size:20px;color:var(--primary);display:block;margin-bottom:6px;"></i>
            <div style="font-size:12px;font-weight:600;color:var(--text-primary);margin-bottom:4px;">Need Help?</div>
            <div style="font-size:11px;color:var(--text-muted);margin-bottom:8px;">Ask our AI assistant</div>
            <button class="btn btn-sm btn-primary" onclick="toggleChatbot()" style="font-size:11px;padding:4px 14px;">
                <i class="bi bi-chat-dots me-1"></i> Open Chat
            </button>
        </div>
    </div>
</aside>
