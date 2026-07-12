<?php
/**
 * AssetFlow — Auth Controller
 */

require_once APP_PATH . '/Models/User.php';

class AuthController extends Controller
{
    public function showLogin(): void
    {
        Middleware::guest();
        $this->authView('auth/login', [], 'Login — AssetFlow');
    }

    public function login(): void
    {
        $email = $this->input('email');
        $password = $this->input('password');

        if (!$email || !$password) {
            $this->flash('error', 'Please enter both email and password.');
            $this->redirect('/login');
            return;
        }

        $result = Auth::attempt($email, $password);

        if ($result['success']) {
            Helpers::logActivity(Auth::id(), 'user_login', 'user', Auth::id());
            $intended = $_SESSION['intended_url'] ?? '/dashboard';
            unset($_SESSION['intended_url']);
            $this->redirect($intended);
        } else {
            $this->flash('error', $result['message']);
            $this->redirect('/login');
        }
    }

    public function showSignup(): void
    {
        Middleware::guest();
        $departments = Database::fetchAll("SELECT id, name FROM departments WHERE status = 'Active' ORDER BY name");
        $this->authView('auth/signup', ['departments' => $departments], 'Sign Up — AssetFlow');
    }

    public function signup(): void
    {
        $name = $this->input('name');
        $email = $this->input('email');
        $password = $this->input('password');
        $confirmPassword = $this->input('confirm_password');
        $departmentId = $this->input('department_id');

        // Validation
        if (!$name || !$email || !$password) {
            $this->flash('error', 'All fields are required.');
            $this->redirect('/signup');
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->flash('error', 'Please enter a valid email address.');
            $this->redirect('/signup');
            return;
        }

        if (strlen($password) < 6) {
            $this->flash('error', 'Password must be at least 6 characters.');
            $this->redirect('/signup');
            return;
        }

        if ($password !== $confirmPassword) {
            $this->flash('error', 'Passwords do not match.');
            $this->redirect('/signup');
            return;
        }

        $result = Auth::register($name, $email, $password, $departmentId ?: null);

        if ($result['success']) {
            $this->flash('success', $result['message']);
            $this->redirect('/login');
        } else {
            $this->flash('error', $result['message']);
            $this->redirect('/signup');
        }
    }

    public function showForgotPassword(): void
    {
        Middleware::guest();
        $this->authView('auth/forgot_password', [], 'Reset Password — AssetFlow');
    }

    public function forgotPassword(): void
    {
        $email = $this->input('email');

        if (!$email) {
            $this->flash('error', 'Please enter your email address.');
            $this->redirect('/forgot-password');
            return;
        }

        $result = Auth::generateResetToken($email);

        // Always show success to prevent email enumeration
        $this->flash('success', 'If an account with that email exists, a reset link has been generated. Token: ' . ($result['token'] ?? 'N/A'));
        $this->redirect('/forgot-password');
    }

    public function showResetPassword(): void
    {
        $token = $this->query('token');
        if (!$token) {
            $this->redirect('/login');
            return;
        }
        $this->authView('auth/forgot_password', ['resetToken' => $token], 'Reset Password — AssetFlow');
    }

    public function resetPassword(): void
    {
        $token = $this->input('token');
        $password = $this->input('password');
        $confirmPassword = $this->input('confirm_password');

        if (!$token || !$password) {
            $this->flash('error', 'Invalid request.');
            $this->redirect('/login');
            return;
        }

        if ($password !== $confirmPassword) {
            $this->flash('error', 'Passwords do not match.');
            $this->redirect('/reset-password?token=' . urlencode($token));
            return;
        }

        $result = Auth::resetPassword($token, $password);
        $this->flash($result['success'] ? 'success' : 'error', $result['message']);
        $this->redirect('/login');
    }

    public function logout(): void
    {
        if (Auth::check()) {
            Helpers::logActivity(Auth::id(), 'user_logout', 'user', Auth::id());
        }
        Auth::logout();
        $this->redirect('/login');
    }
}
