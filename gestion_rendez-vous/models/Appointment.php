<?php
// models/Appointment.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/DatabaseManager.php';

class Appointment {
    private $id;
    private $patient_id;
    private $practitioner_id;
    private $date;
    private $status;
    private $reason;
    private $created_at;
    private $updated_at;

    public function __construct($id, $patient_id, $practitioner_id, $date, $status, $reason, $created_at, $updated_at) {
        $this->id = $id;
        $this->patient_id = $patient_id;
        $this->practitioner_id = $practitioner_id;
        $this->date = $date;
        $this->status = $status;
        $this->reason = $reason;
        $this->created_at = $created_at;
        $this->updated_at = $updated_at;
    }

    // Getters
    public function getId() { return $this->id; }
    public function getPatientId() { return $this->patient_id; }
    public function getPractitionerId() { return $this->practitioner_id; }
    public function getDate() { return $this->date; }
    public function getStatus() { return $this->status; }
    public function getReason() { return $this->reason; }

    // Créer un rendez-vous (US-35 : incrémentation automatique)
    public static function create($patient_id, $practitioner_id, $date, $reason = null) {
        $data = [
            'patient_id' => $patient_id,
            'practitioner_id' => $practitioner_id,
            'date' => $date,
            'reason' => $reason,
            'status' => 'scheduled',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        $appointmentId = DatabaseManager::autoIncrementData('appointments', $data);

        // Envoyer la confirmation par email (US-32)
        $appointment = self::findById($appointmentId);
        if ($appointment) {
            require_once __DIR__ . '/../services/EmailService.php';
            EmailService::sendAppointmentConfirmationEmail($appointment);
        }

        return $appointmentId;
    }

    // Méthode pour envoyer des rappels (US-33)
    public static function sendReminders() {
        global $pdo;
        // Récupérer les rendez-vous de demain
        $tomorrow = date('Y-m-d', strtotime('+1 day'));
        $stmt = $pdo->prepare("
            SELECT * FROM appointments
            WHERE DATE(date) = ? AND status = 'scheduled'
        ");
        $stmt->execute([$tomorrow]);
        $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($appointments as $appointmentData) {
            $appointment = new Appointment(
                $appointmentData['id'], $appointmentData['patient_id'], $appointmentData['practitioner_id'],
                $appointmentData['date'], $appointmentData['status'], $appointmentData['reason'],
                $appointmentData['created_at'], $appointmentData['updated_at']
            );
            EmailService::sendAppointmentReminderEmail($appointment);
        }

        return count($appointments);
    }

    // Lister les rendez-vous d'un patient (US-21)
    public static function getByPatient($patient_id) {
        global $pdo;
        $stmt = $pdo->prepare("
            SELECT a.*, u.first_name as practitioner_first_name, u.last_name as practitioner_last_name
            FROM appointments a
            JOIN users u ON a.practitioner_id = u.id
            WHERE a.patient_id = ?
            ORDER BY a.date DESC
        ");
        $stmt->execute([$patient_id]);
        $appointments = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $appointments[] = new Appointment(
                $row['id'], $row['patient_id'], $row['practitioner_id'],
                $row['date'], $row['status'], $row['reason'],
                $row['created_at'], $row['updated_at']
            );
        }
        return $appointments;
    }

    // Lister les rendez-vous d'un praticien (US-19, US-20)
    public static function getByPractitioner($practitioner_id, $status = null) {
        global $pdo;
        $query = "
            SELECT a.*, p.first_name as patient_first_name, p.last_name as patient_last_name
            FROM appointments a
            JOIN patients p ON a.patient_id = p.id
            WHERE a.practitioner_id = ?
        ";
        $params = [$practitioner_id];
        if ($status) {
            $query .= " AND a.status = ?";
            $params[] = $status;
        }
        $query .= " ORDER BY a.date DESC";
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $appointments = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $appointments[] = [
                'appointment' => new Appointment(
                    $row['id'], $row['patient_id'], $row['practitioner_id'],
                    $row['date'], $row['status'], $row['reason'],
                    $row['created_at'], $row['updated_at']
                ),
                'patient_first_name' => $row['patient_first_name'],
                'patient_last_name' => $row['patient_last_name']
            ];
        }
        return $appointments;
    }

    // Modifier un rendez-vous (US-17)
    public function update($date, $practitioner_id, $reason = null) {
        global $pdo;
        $stmt = $pdo->prepare("
            UPDATE appointments
            SET date = ?, practitioner_id = ?, reason = ?, status = 'scheduled'
            WHERE id = ?
        ");
        $stmt->execute([$date, $practitioner_id, $reason, $this->id]);
        return true;
    }

    // Annuler un rendez-vous (US-18)
    public function cancel() {
        global $pdo;
        $stmt = $pdo->prepare("UPDATE appointments SET status = 'cancelled' WHERE id = ?");
        $stmt->execute([$this->id]);
        return true;
    }

    // Confirmer un rendez-vous (US-16)
    public function confirm() {
        global $pdo;
        $stmt = $pdo->prepare("UPDATE appointments SET status = 'confirmed' WHERE id = ?");
        $stmt->execute([$this->id]);
        return true;
    }

    // Trouver un rendez-vous par ID (avec déchiffrement)
    public static function findById($id) {
        $results = DatabaseManager::getDecryptedData('appointments', ['id' => $id]);
        if (!empty($results)) {
            $appointment = $results[0];
            return new Appointment(
                $appointment['id'], $appointment['patient_id'], $appointment['practitioner_id'],
                $appointment['date'], $appointment['status'], $appointment['reason'],
                $appointment['created_at'], $appointment['updated_at']
            );
        }
        return null;
    }

    // Vérifier les disponibilités d'un praticien (US-16, US-20)
    public static function getAvailableSlots($practitioner_id, $date) {
        global $pdo;
        // Récupérer les créneaux horaires du praticien
        $stmt = $pdo->prepare("
            SELECT day_of_week, start_time, end_time
            FROM practitioner_slots
            WHERE practitioner_id = ? AND is_available = TRUE
        ");
        $stmt->execute([$practitioner_id]);
        $slots = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Récupérer les rendez-vous existants pour ce praticien à cette date
        $stmt = $pdo->prepare("
            SELECT date
            FROM appointments
            WHERE practitioner_id = ? AND DATE(date) = DATE(?)
        ");
        $stmt->execute([$practitioner_id, $date]);
        $bookedSlots = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Générer les créneaux disponibles
        $availableSlots = [];
        $dayOfWeek = date('w', strtotime($date)); // 0 (dimanche) à 6 (samedi)

        foreach ($slots as $slot) {
            if ($slot['day_of_week'] == $dayOfWeek) {
                $start = strtotime($date . ' ' . $slot['start_time']);
                $end = strtotime($date . ' ' . $slot['end_time']);
                $current = $start;

                while ($current < $end) {
                    $slotTime = date('H:i', $current);
                    $fullDateTime = date('Y-m-d H:i:s', $current);
                    if (!in_array($fullDateTime, $bookedSlots)) {
                        $availableSlots[] = $slotTime;
                    }
                    $current += 1800; // Incrément de 30 minutes
                }
            }
        }
        return $availableSlots;
    }

    // Lister tous les rendez-vous (avec déchiffrement)
    public static function getAll() {
        $results = DatabaseManager::getDecryptedData('appointments');
        $appointments = [];
        foreach ($results as $row) {
            $appointments[] = new Appointment(
                $row['id'], $row['patient_id'], $row['practitioner_id'],
                $row['date'], $row['status'], $row['reason'],
                $row['created_at'], $row['updated_at']
            );
        }
        return $appointments;
    }
}
?>