<?php
/**
 * AssetFlow — Middleware for Role-Based Access Control
 */

class Middleware
{
    /**
     * Check if user is authenticated; redirect to login if not
     */
    public static function auth(): void
    {
        if (!Auth::check()) {
            $_SESSION['intended_url'] = $_SERVER['REQUEST_URI'];
            header('Location: /login');
            exit;
        }
    }

    /**
     * Check if user has one of the specified roles
     */
    public static function role(string ...$allowedRoles): void
    {
        self::auth();
        $user = Auth::user();
        if (!in_array($user['role'], $allowedRoles)) {
            http_response_code(403);
            echo '<div style="text-align:center;padding:80px;font-family:Inter,sans-serif;">';
            echo '<h1 style="font-size:72px;color:#FF6B6B;margin:0;">403</h1>';
            echo '<p style="font-size:18px;color:#636E72;">Access Denied — You don\'t have permission to view this page.</p>';
            echo '<p style="color:#96E6A1;">Required role: ' . implode(' or ', $allowedRoles) . '</p>';
            echo '<a href="/dashboard" style="color:#7C83FD;text-decoration:none;">← Back to Dashboard</a>';
            echo '</div>';
            exit;
        }
    }

    /**
     * Guest only (redirect logged-in users to dashboard)
     */
    public static function guest(): void
    {
        if (Auth::check()) {
            header('Location: /dashboard');
            exit;
        }
    }

    /**
     * Admin only
     */
    public static function admin(): void
    {
        self::role('Admin');
    }

    /**
     * Asset Manager or Admin
     */
    public static function manager(): void
    {
        self::role('Admin', 'Asset Manager');
    }

    /**
     * Department Head, Asset Manager, or Admin
     */
    public static function leadership(): void
    {
        self::role('Admin', 'Asset Manager', 'Department Head');
    }
}
