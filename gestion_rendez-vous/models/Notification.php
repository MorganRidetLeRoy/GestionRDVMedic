<?php
// models/Notification.php
require_once __DIR__ . '/../config/database.php';

class Notification {
    private $id;
    private $user_id;
    private $patient_id;
    private $type;
    private $content;
    private $sent_at;
    private $status;

    public function __construct($id, $user_id, $patient_id, $type, $content, $sent_at, $status) {
        $this->id = $id;
        $this->user_id = $user_id;
        $this->patient_id = $patient_id;
        $this->type = $type;
        $this->content = $content;
        $this->sent_at = $sent_at;
        $this->status = $status;
    }

    // Getters
    public function getId() { return $this->id; }
    public function getUserId() { return $this->user_id; }
    public function getPatientId() { return $this->patient_id; }
    public function getType() { return $this->type; }
    public function getContent() { return $this->content; }
    public function getSentAt() { return $this->sent_at; }
    public function getStatus() { return $this->status; }

    // Créer une notification
    public static function create($user_id, $patient_id, $type, $content, $status = 'sent') {
        global $pdo;
        $stmt = $pdo->prepare("
            INSERT INTO notifications (user_id, patient_id, type, content, status)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$user_id, $patient_id, $type, $content, $status]);
        return $pdo->lastInsertId();
    }

    // Lister les notifications d'un patient
    public static function getByPatient($patient_id) {
        global $pdo;
        $stmt = $pdo->prepare("SELECT * FROM notifications WHERE patient_id = ? ORDER BY sent_at DESC");
        $stmt->execute([$patient_id]);
        $notifications = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $notifications[] = new Notification(
                $row['id'], $row['user_id'], $row['patient_id'],
                $row['type'], $row['content'], $row['sent_at'], $row['status']
            );
        }
        return $notifications;
    }
}
?>