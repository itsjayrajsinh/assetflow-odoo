<?php
/**
 * AssetFlow — Notification & Activity Log Controller
 */

class NotificationController extends Controller
{
    public function index(): void
    {
        Middleware::auth();
        $notifications = Database::fetchAll(
            "SELECT * FROM notifications WHERE user_id = :uid ORDER BY created_at DESC LIMIT 100",
            ['uid' => Auth::id()]
        );
        $this->view('notifications/index', ['notifications' => $notifications], 'Notifications — AssetFlow');
    }

    public function markRead(): void
    {
        Middleware::auth();
        $id = (int) $this->input('id');
        Database::execute("UPDATE notifications SET is_read = 1 WHERE id = :id AND user_id = :uid", ['id' => $id, 'uid' => Auth::id()]);
        $this->json(['success' => true]);
    }

    public function markAllRead(): void
    {
        Middleware::auth();
        Database::execute("UPDATE notifications SET is_read = 1 WHERE user_id = :uid AND is_read = 0", ['uid' => Auth::id()]);
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            $this->flash('success', 'All notifications marked as read.');
            $this->redirect('/notifications');
        } else {
            $this->json(['success' => true]);
        }
    }

    public function apiCount(): void
    {
        if (!Auth::check()) { $this->json(['count' => 0]); return; }
        $count = (int) Database::fetchColumn("SELECT COUNT(*) FROM notifications WHERE user_id = :uid AND is_read = 0", ['uid' => Auth::id()]);
        $this->json(['count' => $count]);
    }

    public function apiRecent(): void
    {
        if (!Auth::check()) { $this->json(['notifications' => []]); return; }
        $notifs = Database::fetchAll(
            "SELECT * FROM notifications WHERE user_id = :uid ORDER BY created_at DESC LIMIT 8",
            ['uid' => Auth::id()]
        );
        $notifs = array_map(function($n) {
            $n['time_ago'] = Helpers::timeAgo($n['created_at']);
            return $n;
        }, $notifs);
        $this->json(['notifications' => $notifs]);
    }

    public function logs(): void
    {
        Middleware::manager();
        $page = max(1, (int) $this->query('page', 1));
        $sql = "SELECT al.*, u.name as user_name
                FROM activity_logs al
                LEFT JOIN users u ON al.user_id = u.id
                ORDER BY al.created_at DESC";
        $pagination = Helpers::paginate($sql, [], $page, 30);

        $this->view('notifications/index', ['logs' => $pagination['data'], 'pagination' => $pagination, 'showLogs' => true], 'Activity Logs — AssetFlow');
    }
}
