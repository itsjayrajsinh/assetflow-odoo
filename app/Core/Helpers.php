<?php
/**
 * AssetFlow — Helper Utilities
 */

class Helpers
{
    /**
     * Generate a CSRF token and store in session
     */
    public static function csrfToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Output a hidden CSRF field for forms
     */
    public static function csrfField(): string
    {
        return '<input type="hidden" name="csrf_token" value="' . self::csrfToken() . '">';
    }

    /**
     * Validate a CSRF token
     */
    public static function validateCsrfToken(string $token): bool
    {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Sanitize string output for HTML
     */
    public static function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Generate the next asset tag (AF-0001, AF-0002, etc.)
     */
    public static function generateAssetTag(): string
    {
        $last = Database::fetchColumn(
            "SELECT asset_tag FROM assets ORDER BY id DESC LIMIT 1"
        );

        if ($last) {
            $num = (int) substr($last, 3); // Remove "AF-"
            $next = $num + 1;
        } else {
            $next = 1;
        }

        return 'AF-' . str_pad($next, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Format date for display
     */
    public static function formatDate(?string $date, string $format = 'M d, Y'): string
    {
        if (!$date) return '—';
        return date($format, strtotime($date));
    }

    /**
     * Format datetime for display
     */
    public static function formatDateTime(?string $datetime, string $format = 'M d, Y h:i A'): string
    {
        if (!$datetime) return '—';
        return date($format, strtotime($datetime));
    }

    /**
     * Format currency
     */
    public static function formatCurrency(float $amount): string
    {
        return '₹' . number_format($amount, 2);
    }

    /**
     * Get time ago string
     */
    public static function timeAgo(string $datetime): string
    {
        $now = time();
        $timestamp = strtotime($datetime);
        $diff = $now - $timestamp;

        if ($diff < 60) return 'Just now';
        if ($diff < 3600) return floor($diff / 60) . 'm ago';
        if ($diff < 86400) return floor($diff / 3600) . 'h ago';
        if ($diff < 604800) return floor($diff / 86400) . 'd ago';
        return self::formatDate($datetime);
    }

    /**
     * Log an activity
     */
    public static function logActivity(?int $userId, string $action, string $entityType, ?int $entityId = null, ?array $details = null): void
    {
        try {
            Database::insert(
                "INSERT INTO activity_logs (user_id, action, entity_type, entity_id, details, ip_address) 
                 VALUES (:user_id, :action, :entity_type, :entity_id, :details, :ip)",
                [
                    'user_id'     => $userId,
                    'action'      => $action,
                    'entity_type' => $entityType,
                    'entity_id'   => $entityId,
                    'details'     => $details ? json_encode($details) : null,
                    'ip'          => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                ]
            );
        } catch (Exception $e) {
            // Silently fail - logging should not break the app
        }
    }

    /**
     * Create a notification
     */
    public static function notify(int $userId, string $type, string $title, string $message, ?string $link = null): void
    {
        try {
            Database::insert(
                "INSERT INTO notifications (user_id, type, title, message, link) 
                 VALUES (:user_id, :type, :title, :message, :link)",
                [
                    'user_id' => $userId,
                    'type'    => $type,
                    'title'   => $title,
                    'message' => $message,
                    'link'    => $link,
                ]
            );
        } catch (Exception $e) {
            // Silently fail
        }
    }

    /**
     * Get status badge class for Bootstrap
     */
    public static function statusBadge(string $status): string
    {
        $map = [
            // Asset statuses
            'Available'          => 'bg-success-subtle text-success',
            'Allocated'          => 'bg-primary-subtle text-primary',
            'Reserved'           => 'bg-info-subtle text-info',
            'Under Maintenance'  => 'bg-warning-subtle text-warning',
            'Lost'               => 'bg-danger-subtle text-danger',
            'Retired'            => 'bg-secondary-subtle text-secondary',
            'Disposed'           => 'bg-dark-subtle text-dark',
            // Allocation statuses
            'Active'             => 'bg-success-subtle text-success',
            'Returned'           => 'bg-secondary-subtle text-secondary',
            'Overdue'            => 'bg-danger-subtle text-danger',
            'Transferred'        => 'bg-info-subtle text-info',
            // Transfer/Maintenance statuses
            'Requested'          => 'bg-info-subtle text-info',
            'Approved'           => 'bg-success-subtle text-success',
            'Rejected'           => 'bg-danger-subtle text-danger',
            'Completed'          => 'bg-success-subtle text-success',
            'Pending'            => 'bg-warning-subtle text-warning',
            'Assigned'           => 'bg-primary-subtle text-primary',
            'In Progress'        => 'bg-info-subtle text-info',
            'Resolved'           => 'bg-success-subtle text-success',
            // Booking statuses
            'Upcoming'           => 'bg-info-subtle text-info',
            'Ongoing'            => 'bg-primary-subtle text-primary',
            'Cancelled'          => 'bg-secondary-subtle text-secondary',
            // Audit statuses
            'Open'               => 'bg-info-subtle text-info',
            'Closed'             => 'bg-secondary-subtle text-secondary',
            'Verified'           => 'bg-success-subtle text-success',
            'Missing'            => 'bg-danger-subtle text-danger',
            'Damaged'            => 'bg-warning-subtle text-warning',
            // Priority
            'Low'                => 'bg-secondary-subtle text-secondary',
            'Medium'             => 'bg-info-subtle text-info',
            'High'               => 'bg-warning-subtle text-warning',
            'Critical'           => 'bg-danger-subtle text-danger',
            // Condition
            'New'                => 'bg-success-subtle text-success',
            'Good'               => 'bg-primary-subtle text-primary',
            'Fair'               => 'bg-warning-subtle text-warning',
            'Poor'               => 'bg-danger-subtle text-danger',
            // User status
            'Inactive'           => 'bg-secondary-subtle text-secondary',
        ];

        $class = $map[$status] ?? 'bg-secondary-subtle text-secondary';
        return '<span class="badge rounded-pill ' . $class . '">' . self::e($status) . '</span>';
    }

    /**
     * Handle file upload
     */
    public static function uploadFile(array $file, string $subfolder = ''): ?string
    {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'application/pdf'];
        $maxSize = 5 * 1024 * 1024; // 5MB

        if ($file['error'] !== UPLOAD_ERR_OK) return null;
        if (!in_array($file['type'], $allowedTypes)) return null;
        if ($file['size'] > $maxSize) return null;

        $uploadDir = PUBLIC_PATH . '/uploads/' . $subfolder;
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid('af_') . '.' . $ext;
        $destination = $uploadDir . '/' . $filename;

        if (move_uploaded_file($file['tmp_name'], $destination)) {
            return '/uploads/' . $subfolder . '/' . $filename;
        }

        return null;
    }

    /**
     * Get user's initials for avatar fallback
     */
    public static function initials(string $name): string
    {
        $words = explode(' ', trim($name));
        $initials = '';
        foreach (array_slice($words, 0, 2) as $word) {
            $initials .= strtoupper($word[0] ?? '');
        }
        return $initials;
    }

    /**
     * Generate a pastel color from a string (for avatar backgrounds)
     */
    public static function stringToColor(string $str): string
    {
        $colors = ['#7C83FD', '#96E6A1', '#FFB5B5', '#6FC8CE', '#FFD93D', '#B8BBFF', '#F0B86E', '#A8D8EA'];
        $hash = crc32($str);
        return $colors[abs($hash) % count($colors)];
    }

    /**
     * Paginate results
     */
    public static function paginate(string $sql, array $params, int $page = 1, int $perPage = 15): array
    {
        $offset = ($page - 1) * $perPage;

        // Get total count
        $countSql = "SELECT COUNT(*) FROM (" . $sql . ") as count_query";
        $total = (int) Database::fetchColumn($countSql, $params);

        // Get paginated results
        $sql .= " LIMIT {$perPage} OFFSET {$offset}";
        $data = Database::fetchAll($sql, $params);

        return [
            'data'        => $data,
            'total'       => $total,
            'page'        => $page,
            'per_page'    => $perPage,
            'total_pages' => ceil($total / $perPage),
            'has_prev'    => $page > 1,
            'has_next'    => ($page * $perPage) < $total,
        ];
    }

    /**
     * Render pagination HTML
     */
    public static function paginationHtml(array $pagination, string $baseUrl): string
    {
        if ($pagination['total_pages'] <= 1) return '';

        $html = '<nav aria-label="Page navigation"><ul class="pagination justify-content-center">';

        // Previous button
        if ($pagination['has_prev']) {
            $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '?page=' . ($pagination['page'] - 1) . '">‹</a></li>';
        } else {
            $html .= '<li class="page-item disabled"><span class="page-link">‹</span></li>';
        }

        // Page numbers
        $start = max(1, $pagination['page'] - 2);
        $end = min($pagination['total_pages'], $pagination['page'] + 2);

        if ($start > 1) {
            $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '?page=1">1</a></li>';
            if ($start > 2) $html .= '<li class="page-item disabled"><span class="page-link">…</span></li>';
        }

        for ($i = $start; $i <= $end; $i++) {
            $active = $i === $pagination['page'] ? ' active' : '';
            $html .= '<li class="page-item' . $active . '"><a class="page-link" href="' . $baseUrl . '?page=' . $i . '">' . $i . '</a></li>';
        }

        if ($end < $pagination['total_pages']) {
            if ($end < $pagination['total_pages'] - 1) $html .= '<li class="page-item disabled"><span class="page-link">…</span></li>';
            $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '?page=' . $pagination['total_pages'] . '">' . $pagination['total_pages'] . '</a></li>';
        }

        // Next button
        if ($pagination['has_next']) {
            $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '?page=' . ($pagination['page'] + 1) . '">›</a></li>';
        } else {
            $html .= '<li class="page-item disabled"><span class="page-link">›</span></li>';
        }

        $html .= '</ul></nav>';
        return $html;
    }
}
