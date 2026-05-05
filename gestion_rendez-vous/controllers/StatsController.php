<?php
// controllers/StatsController.php
require_once __DIR__ . '/../models/Stats.php';
require_once __DIR__ . '/../models/Auth.php';

class StatsController {
    public function showMonthlyStats() {
        if (!Auth::isLoggedIn() || !isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin_local') {
            header('Location: /login');
            exit;
        }

        $month = $_GET['month'] ?? date('m');
        $year = $_GET['year'] ?? date('Y');
        $stats = Stats::generateMonthlySummary($month, $year);
        require __DIR__ . '/../views/admin/stats.php';
    }
}
?>