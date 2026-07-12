/* AssetFlow — Global JavaScript */

document.addEventListener('DOMContentLoaded', function() {
    initSidebar();
    initNotifications();
    initGlobalSearch();
});

// ── Sidebar Toggle ───────────────────────────────────────
function initSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const toggle = document.getElementById('sidebarToggle');

    if (toggle) {
        toggle.addEventListener('click', () => {
            sidebar.classList.toggle('show');
            overlay.classList.toggle('show');
        });
    }

    if (overlay) {
        overlay.addEventListener('click', () => {
            sidebar.classList.remove('show');
            overlay.classList.remove('show');
        });
    }
}

// ── Toast Notifications ──────────────────────────────────
function showToast(message, type = 'success', duration = 4000) {
    const container = document.getElementById('toastContainer');
    if (!container) return;

    const icons = {
        success: 'bi-check-circle-fill',
        error: 'bi-exclamation-circle-fill',
        warning: 'bi-exclamation-triangle-fill',
        info: 'bi-info-circle-fill'
    };

    const toast = document.createElement('div');
    toast.className = `toast-custom toast-${type}`;
    toast.innerHTML = `
        <i class="bi ${icons[type] || icons.info}" style="font-size:18px;"></i>
        <span>${message}</span>
        <button class="toast-close" onclick="this.parentElement.remove()">×</button>
    `;

    container.appendChild(toast);

    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(100px)';
        setTimeout(() => toast.remove(), 300);
    }, duration);
}

// ── Notifications ────────────────────────────────────────
function initNotifications() {
    loadNotificationCount();
    // Poll every 30 seconds
    setInterval(loadNotificationCount, 30000);
}

function loadNotificationCount() {
    fetch('/api/notifications/count')
        .then(r => r.json())
        .then(data => {
            const dot = document.getElementById('notifDot');
            const badge = document.getElementById('sidebarNotifCount');
            if (data.count > 0) {
                if (dot) dot.style.display = 'block';
                if (badge) {
                    badge.style.display = 'inline';
                    badge.textContent = data.count > 99 ? '99+' : data.count;
                }
            } else {
                if (dot) dot.style.display = 'none';
                if (badge) badge.style.display = 'none';
            }
        })
        .catch(() => {});
}

function loadRecentNotifications() {
    fetch('/api/notifications/recent')
        .then(r => r.json())
        .then(data => {
            const list = document.getElementById('notifList');
            if (!list) return;
            
            if (!data.notifications || data.notifications.length === 0) {
                list.innerHTML = `
                    <div class="text-center py-4 text-muted" style="font-size:13px;">
                        <i class="bi bi-bell-slash" style="font-size:24px;display:block;margin-bottom:8px;"></i>
                        No new notifications
                    </div>`;
                return;
            }

            list.innerHTML = data.notifications.map(n => `
                <a href="${n.link || '/notifications'}" class="dropdown-item notification-item ${n.is_read ? '' : 'unread'}" style="white-space:normal;padding:10px 14px;">
                    <div>
                        <div style="font-size:13px;font-weight:600;">${escapeHtml(n.title)}</div>
                        <div style="font-size:12px;color:var(--text-muted);margin-top:2px;">${escapeHtml(n.message).substring(0, 80)}...</div>
                        <div style="font-size:11px;color:var(--text-muted);margin-top:4px;">${n.time_ago}</div>
                    </div>
                </a>
            `).join('');
        })
        .catch(() => {});
}

// Load notifications when dropdown opens
document.addEventListener('DOMContentLoaded', () => {
    const notifBell = document.getElementById('notifBell');
    if (notifBell) {
        notifBell.addEventListener('click', loadRecentNotifications);
    }
});

function markAllNotificationsRead() {
    fetch('/notifications/read-all', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-TOKEN': getCsrfToken() },
        body: 'csrf_token=' + getCsrfToken()
    }).then(() => {
        loadNotificationCount();
        loadRecentNotifications();
    });
}

// ── Global Search ────────────────────────────────────────
function initGlobalSearch() {
    const searchInput = document.getElementById('globalSearch');
    if (!searchInput) return;

    let searchTimeout;
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        const query = this.value.trim();
        if (query.length < 2) return;

        searchTimeout = setTimeout(() => {
            window.location.href = '/assets?search=' + encodeURIComponent(query);
        }, 800);
    });

    searchInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            const query = this.value.trim();
            if (query) {
                window.location.href = '/assets?search=' + encodeURIComponent(query);
            }
        }
    });
}

// ── CSRF Token ───────────────────────────────────────────
function getCsrfToken() {
    const meta = document.querySelector('input[name="csrf_token"]');
    return meta ? meta.value : '';
}

// ── HTML Escaping ────────────────────────────────────────
function escapeHtml(text) {
    const div = document.createElement('div');
    div.appendChild(document.createTextNode(text));
    return div.innerHTML;
}

// ── Confirm Delete ───────────────────────────────────────
function confirmAction(message, callback) {
    if (confirm(message)) {
        callback();
    }
}

// ── Animated Counter ─────────────────────────────────────
function animateCounter(element, target, duration = 1000) {
    let start = 0;
    const increment = target / (duration / 16);
    
    function update() {
        start += increment;
        if (start >= target) {
            element.textContent = target;
            return;
        }
        element.textContent = Math.floor(start);
        requestAnimationFrame(update);
    }
    
    requestAnimationFrame(update);
}

// ── Format Numbers ───────────────────────────────────────
function formatNumber(num) {
    return new Intl.NumberFormat('en-IN').format(num);
}

function formatCurrency(num) {
    return '₹' + new Intl.NumberFormat('en-IN', { minimumFractionDigits: 2 }).format(num);
}

// ── Debounce ─────────────────────────────────────────────
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(this, args), wait);
    };
}
