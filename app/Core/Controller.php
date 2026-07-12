<?php
/**
 * AssetFlow — Base Controller
 */

class Controller
{
    /**
     * Render a view with layout (header + sidebar + content + footer)
     */
    protected function view(string $viewPath, array $data = [], string $pageTitle = 'AssetFlow'): void
    {
        // Extract data variables for use in views
        extract($data);
        $title = $pageTitle;
        $currentUser = Auth::user();
        $currentRole = $currentUser['role'] ?? 'Employee';

        $viewFile = APP_PATH . '/Views/' . $viewPath . '.php';
        if (!file_exists($viewFile)) {
            Router::error(500, "View not found: {$viewPath}");
            return;
        }

        // Start output buffering for the main content
        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        // Render layout
        require APP_PATH . '/Views/layouts/header.php';
        require APP_PATH . '/Views/layouts/sidebar.php';
        echo '<main class="main-content" id="mainContent">';
        echo '<div class="container-fluid py-4">';
        echo $content;
        echo '</div>';
        echo '</main>';
        require APP_PATH . '/Views/layouts/footer.php';
    }

    /**
     * Render an auth view (login/signup layout without sidebar)
     */
    protected function authView(string $viewPath, array $data = [], string $pageTitle = 'AssetFlow'): void
    {
        extract($data);
        $title = $pageTitle;

        $viewFile = APP_PATH . '/Views/' . $viewPath . '.php';
        if (!file_exists($viewFile)) {
            Router::error(500, "View not found: {$viewPath}");
            return;
        }

        require APP_PATH . '/Views/layouts/auth_layout.php';
    }

    /**
     * Return a JSON response
     */
    protected function json(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    /**
     * Redirect to a URL
     */
    protected function redirect(string $url): void
    {
        header("Location: {$url}");
        exit;
    }

    /**
     * Get POST data safely
     */
    protected function input(string $key, $default = null)
    {
        return isset($_POST[$key]) ? trim($_POST[$key]) : $default;
    }

    /**
     * Get GET data safely
     */
    protected function query(string $key, $default = null)
    {
        return isset($_GET[$key]) ? trim($_GET[$key]) : $default;
    }

    /**
     * Validate CSRF token
     */
    protected function validateCsrf(): bool
    {
        $token = $this->input('csrf_token') ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
        return Helpers::validateCsrfToken($token);
    }

    /**
     * Require authentication
     */
    protected function requireAuth(): void
    {
        if (!Auth::check()) {
            $this->redirect('/login');
        }
    }

    /**
     * Require specific role(s)
     */
    protected function requireRole(array $roles): void
    {
        $this->requireAuth();
        $user = Auth::user();
        if (!in_array($user['role'], $roles)) {
            Router::error(403, 'You do not have permission to access this page.');
            exit;
        }
    }

    /**
     * Set a flash message
     */
    protected function flash(string $type, string $message): void
    {
        $_SESSION['flash'] = ['type' => $type, 'message' => $message];
    }

    /**
     * Get and clear flash message
     */
    protected function getFlash(): ?array
    {
        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);
        return $flash;
    }
}
