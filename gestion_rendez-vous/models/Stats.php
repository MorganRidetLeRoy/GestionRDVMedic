<?php
// models/Stats.php
require_once __DIR__ . '/../config/database.php';

class Stats {
    // Générer un résumé mensuel (US-07)
    public static function generateMonthlySummary($month, $year) {
        global $pdo;
        $startDate = "$year-$month-01";
        $endDate = date("Y-m-t", strtotime($startDate));

        $stmt = $pdo->prepare("
            SELECT
                COUNT(*) as total_appointments,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_appointments,
                SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_appointments,
                COUNT(DISTINCT patient_id) as unique_patients,
                COUNT(DISTINCT practitioner_id) as unique_practitioners
            FROM appointments
            WHERE date BETWEEN ? AND ?
        ");
        $stmt->execute([$startDate, $endDate]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Statistiques par praticien
    public static function getPractitionerStats($practitioner_id, $month, $year) {
        global $pdo;
        $startDate = "$year-$month-01";
        $endDate = date("Y-m-t", strtotime($startDate));

        $stmt = $pdo->prepare("
            SELECT
                COUNT(*) as total_appointments,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_appointments,
                SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_appointments
            FROM appointments
            WHERE practitioner_id = ? AND date BETWEEN ? AND ?
        ");
        $stmt->execute([$practitioner_id, $startDate, $endDate]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>