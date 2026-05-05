<?php
// models/PractitionerSlot.php
require_once __DIR__ . '/../config/database.php';

class PractitionerSlot {
    private $id;
    private $practitioner_id;
    private $day_of_week;
    private $start_time;
    private $end_time;
    private $is_available;

    public function __construct($id, $practitioner_id, $day_of_week, $start_time, $end_time, $is_available) {
        $this->id = $id;
        $this->practitioner_id = $practitioner_id;
        $this->day_of_week = $day_of_week;
        $this->start_time = $start_time;
        $this->end_time = $end_time;
        $this->is_available = $is_available;
    }

    // Getters
    public function getId() { return $this->id; }
    public function getPractitionerId() { return $this->practitioner_id; }
    public function getDayOfWeek() { return $this->day_of_week; }
    public function getStartTime() { return $this->start_time; }
    public function getEndTime() { return $this->end_time; }
    public function isAvailable() { return $this->is_available; }

    // Ajouter un créneau horaire (US-20)
    public static function create($practitioner_id, $day_of_week, $start_time, $end_time) {
        global $pdo;
        $stmt = $pdo->prepare("
            INSERT INTO practitioner_slots (practitioner_id, day_of_week, start_time, end_time, is_available)
            VALUES (?, ?, ?, ?, TRUE)
        ");
        $stmt->execute([$practitioner_id, $day_of_week, $start_time, $end_time]);
        return $pdo->lastInsertId();
    }

    // Lister les créneaux d'un praticien
    public static function getByPractitioner($practitioner_id) {
        global $pdo;
        $stmt = $pdo->prepare("
            SELECT * FROM practitioner_slots
            WHERE practitioner_id = ?
            ORDER BY day_of_week, start_time
        ");
        $stmt->execute([$practitioner_id]);
        $slots = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $slots[] = new PractitionerSlot(
                $row['id'], $row['practitioner_id'], $row['day_of_week'],
                $row['start_time'], $row['end_time'], $row['is_available']
            );
        }
        return $slots;
    }

    // Mettre à jour la disponibilité d'un créneau
    public function updateAvailability($is_available) {
        global $pdo;
        $stmt = $pdo->prepare("UPDATE practitioner_slots SET is_available = ? WHERE id = ?");
        $stmt->execute([$is_available, $this->id]);
        return true;
    }
}
?>