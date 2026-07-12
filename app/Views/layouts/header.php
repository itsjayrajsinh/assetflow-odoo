<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="AssetFlow — Enterprise Asset & Resource Management System">
    <title><?= htmlspecialchars($title ?? 'AssetFlow') ?></title>
    
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- FullCalendar CSS -->
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.css" rel="stylesheet">
    <!-- AssetFlow Theme -->
    <link href="/css/theme.css" rel="stylesheet">
    <link href="/css/custom.css" rel="stylesheet">
</head>
<body>

<!-- Sidebar Overlay (mobile) -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- Top Header -->
<header class="top-header">
    <div class="header-left">
        <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle sidebar">
            <i class="bi bi-list"></i>
        </button>
        <div class="header-search">
            <i class="bi bi-search"></i>
            <input type="text" id="globalSearch" placeholder="Search assets, employees, bookings..." autocomplete="off">
        </div>
    </div>
    <div class="header-right">
        <!-- Notifications -->
        <div class="dropdown">
            <button class="header-btn" data-bs-toggle="dropdown" aria-label="Notifications" id="notifBell">
                <i class="bi bi-bell"></i>
                <span class="notification-dot" id="notifDot" style="display:none;"></span>
            </button>
            <div class="dropdown-menu dropdown-menu-end" style="width:340px;max-height:400px;overflow-y:auto;" id="notifDropdown">
                <div class="d-flex align-items-center justify-content-between px-3 py-2">
                    <h6 class="mb-0 fw-bold" style="font-size:14px;">Notifications</h6>
                    <button class="btn btn-sm btn-ghost" onclick="markAllNotificationsRead()" style="font-size:11px;">Mark all read</button>
                </div>
                <hr class="dropdown-divider my-1">
                <div id="notifList">
                    <div class="text-center py-4 text-muted" style="font-size:13px;">
                        <i class="bi bi-bell-slash" style="font-size:24px;display:block;margin-bottom:8px;"></i>
                        No new notifications
                    </div>
                </div>
                <hr class="dropdown-divider my-1">
                <a href="/notifications" class="dropdown-item text-center py-2" style="font-size:12px;font-weight:600;color:var(--primary);">
                    View All Notifications
                </a>
            </div>
        </div>

        <!-- User Menu -->
        <div class="dropdown user-menu">
            <button class="user-menu-btn" data-bs-toggle="dropdown">
                <?php if ($currentUser): ?>
                <div class="user-avatar" style="background:<?= Helpers::stringToColor($currentUser['name']) ?>">
                    <?= Helpers::initials($currentUser['name']) ?>
                </div>
                <div class="user-info">
                    <div class="user-name"><?= Helpers::e($currentUser['name']) ?></div>
                    <div class="user-role"><?= Helpers::e($currentUser['role']) ?></div>
                </div>
                <i class="bi bi-chevron-down" style="font-size:12px;color:var(--text-muted);"></i>
                <?php endif; ?>
            </button>
            <div class="dropdown-menu dropdown-menu-end">
                <div class="px-3 py-2">
                    <div class="fw-bold" style="font-size:13px;"><?= Helpers::e($currentUser['name'] ?? '') ?></div>
                    <div style="font-size:12px;color:var(--text-muted);"><?= Helpers::e($currentUser['email'] ?? '') ?></div>
                </div>
                <hr class="dropdown-divider">
                <a class="dropdown-item" href="/dashboard"><i class="bi bi-speedometer2 me-2"></i> Dashboard</a>
                <a class="dropdown-item" href="/notifications"><i class="bi bi-bell me-2"></i> Notifications</a>
                <hr class="dropdown-divider">
                <a class="dropdown-item text-danger" href="/logout"><i class="bi bi-box-arrow-right me-2"></i> Logout</a>
            </div>
        </div>
    </div>
</header>
