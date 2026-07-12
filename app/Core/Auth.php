<?php
/**
 * AssetFlow — Authentication & Session Management
 */

class Auth
{
    /**
     * Start session if not already started
     */
    public static function init(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Check if user is logged in
     */
    public static function check(): bool
    {
        return isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0;
    }

    /**
     * Get current logged-in user data
     */
    public static function user(): ?array
    {
        if (!self::check()) {
            return null;
        }

        // Cache user data in session to avoid repeated DB queries
        if (!isset($_SESSION['user_data']) || $_SESSION['user_data']['id'] !== $_SESSION['user_id']) {
            $_SESSION['user_data'] = Database::fetch(
                "SELECT u.*, d.name as department_name 
                 FROM users u 
                 LEFT JOIN departments d ON u.department_id = d.id 
                 WHERE u.id = :id AND u.status = 'Active'",
                ['id' => $_SESSION['user_id']]
            );
        }

        return $_SESSION['user_data'];
    }

    /**
     * Get current user ID
     */
    public static function id(): ?int
    {
        return $_SESSION['user_id'] ?? null;
    }

    /**
     * Get current user role
     */
    public static function role(): ?string
    {
        $user = self::user();
        return $user['role'] ?? null;
    }

    /**
     * Attempt login with email and password
     */
    public static function attempt(string $email, string $password): array
    {
        $user = Database::fetch(
            "SELECT * FROM users WHERE email = :email LIMIT 1",
            ['email' => $email]
        );

        if (!$user) {
            return ['success' => false, 'message' => 'No account found with that email address.'];
        }

        if ($user['status'] === 'Inactive') {
            return ['success' => false, 'message' => 'Your account has been deactivated. Contact admin.'];
        }

        if (!password_verify($password, $user['password'])) {
            return ['success' => false, 'message' => 'Incorrect password. Please try again.'];
        }

        // Successful login
        self::login($user);
        return ['success' => true, 'message' => 'Login successful!'];
    }

    /**
     * Set session data for logged-in user
     */
    public static function login(array $user): void
    {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_data'] = $user;
        $_SESSION['login_time'] = time();
    }

    /**
     * Logout - destroy session
     */
    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }
        session_destroy();
    }

    /**
     * Register a new user (Employee role only)
     */
    public static function register(string $name, string $email, string $password, ?int $departmentId = null): array
    {
        // Check if email exists
        $existing = Database::fetch("SELECT id FROM users WHERE email = :email", ['email' => $email]);
        if ($existing) {
            return ['success' => false, 'message' => 'An account with this email already exists.'];
        }

        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

        $userId = Database::insert(
            "INSERT INTO users (name, email, password, role, department_id, status) 
             VALUES (:name, :email, :password, 'Employee', :department_id, 'Active')",
            [
                'name'          => $name,
                'email'         => $email,
                'password'      => $hashedPassword,
                'department_id' => $departmentId,
            ]
        );

        if ($userId) {
            // Log the registration
            Helpers::logActivity($userId, 'user_registered', 'user', $userId, ['name' => $name, 'email' => $email]);
            return ['success' => true, 'message' => 'Account created successfully! Please login.', 'user_id' => $userId];
        }

        return ['success' => false, 'message' => 'Registration failed. Please try again.'];
    }

    /**
     * Generate a password reset token
     */
    public static function generateResetToken(string $email): array
    {
        $user = Database::fetch("SELECT id FROM users WHERE email = :email AND status = 'Active'", ['email' => $email]);
        if (!$user) {
            return ['success' => false, 'message' => 'No active account found with that email.'];
        }

        $token = bin2hex(random_bytes(32));
        $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));

        Database::execute(
            "UPDATE users SET reset_token = :token, reset_expiry = :expiry WHERE id = :id",
            ['token' => $token, 'expiry' => $expiry, 'id' => $user['id']]
        );

        return ['success' => true, 'message' => 'Password reset token generated.', 'token' => $token];
    }

    /**
     * Reset password using token
     */
    public static function resetPassword(string $token, string $newPassword): array
    {
        $user = Database::fetch(
            "SELECT id FROM users WHERE reset_token = :token AND reset_expiry > NOW()",
            ['token' => $token]
        );

        if (!$user) {
            return ['success' => false, 'message' => 'Invalid or expired reset token.'];
        }

        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
        Database::execute(
            "UPDATE users SET password = :password, reset_token = NULL, reset_expiry = NULL WHERE id = :id",
            ['password' => $hashedPassword, 'id' => $user['id']]
        );

        return ['success' => true, 'message' => 'Password reset successfully! Please login.'];
    }

    /**
     * Check if current user has a specific role
     */
    public static function hasRole(string ...$roles): bool
    {
        $userRole = self::role();
        return $userRole && in_array($userRole, $roles);
    }

    /**
     * Refresh cached user data
     */
    public static function refresh(): void
    {
        unset($_SESSION['user_data']);
        self::user();
    }
}
