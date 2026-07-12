    </div><!-- /.container-fluid -->

    <!-- Toast Container -->
    <div class="toast-container" id="toastContainer"></div>

    <!-- Chatbot Widget -->
    <?php require APP_PATH . '/Views/chatbot/widget.php'; ?>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
    <!-- FullCalendar -->
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>
    <!-- AssetFlow JS -->
    <script src="/js/app.js"></script>

    <?php
    // Flash messages
    $flash = $_SESSION['flash'] ?? null;
    if ($flash) {
        unset($_SESSION['flash']);
        echo "<script>document.addEventListener('DOMContentLoaded', () => showToast('" . addslashes($flash['message']) . "', '" . $flash['type'] . "'));</script>";
    }
    ?>
</body>
</html>
