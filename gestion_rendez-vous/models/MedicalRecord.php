<?php
// models/MedicalRecord.php
require_once __DIR__ . '/../config/database.php';

class MedicalRecord {
    private $id;
    private $patient_id;
    private $created_by;
    private $created_at;
    private $updated_at;

    public function __construct($id, $patient_id, $created_by, $created_at, $updated_at) {
        $this->id = $id;
        $this->patient_id = $patient_id;
        $this->created_by = $created_by;
        $this->created_at = $created_at;
        $this->updated_at = $updated_at;
    }

    // Getters
    public function getId() { return $this->id; }
    public function getPatientId() { return $this->patient_id; }
    public function getCreatedBy() { return $this->created_by; }
    public function getCreatedAt() { return $this->created_at; }
    public function getUpdatedAt() { return $this->updated_at; }

    // Créer un dossier médical (US-27)
    public static function create($patient_id, $created_by) {
        global $pdo;
        $stmt = $pdo->prepare("
            INSERT INTO medical_records (patient_id, created_by)
            VALUES (?, ?)
        ");
        $stmt->execute([$patient_id, $created_by]);

        // Mettre à jour le flag has_medical_record pour le patient
        $stmt = $pdo->prepare("UPDATE patients SET has_medical_record = TRUE WHERE id = ?");
        $stmt->execute([$patient_id]);

        return $pdo->lastInsertId();
    }

    // Trouver un dossier médical par ID
    public static function findById($id) {
        global $pdo;
        $stmt = $pdo->prepare("SELECT * FROM medical_records WHERE id = ?");
        $stmt->execute([$id]);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($record) {
            return new MedicalRecord(
                $record['id'], $record['patient_id'], $record['created_by'],
                $record['created_at'], $record['updated_at']
            );
        }
        return null;
    }

    // Trouver un dossier médical par patient
    public static function findByPatient($patient_id) {
        global $pdo;
        $stmt = $pdo->prepare("SELECT * FROM medical_records WHERE patient_id = ?");
        $stmt->execute([$patient_id]);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($record) {
            return new MedicalRecord(
                $record['id'], $record['patient_id'], $record['created_by'],
                $record['created_at'], $record['updated_at']
            );
        }
        return null;
    }

    // Lister tous les dossiers médicaux (US-28)
    public static function getAll() {
        global $pdo;
        $stmt = $pdo->prepare("SELECT * FROM medical_records ORDER BY created_at DESC");
        $stmt->execute();
        $records = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $records[] = new MedicalRecord(
                $row['id'], $row['patient_id'], $row['created_by'],
                $row['created_at'], $row['updated_at']
            );
        }
        return $records;
    }

    // Lister les dossiers médicaux d'un praticien (US-28)
    public static function getByPractitioner($practitioner_id) {
        global $pdo;
        $stmt = $pdo->prepare("
            SELECT DISTINCT mr.*
            FROM medical_records mr
            JOIN medical_notes mn ON mr.id = mn.medical_record_id
            WHERE mr.created_by = ? OR mn.created_by = ?
            ORDER BY mr.updated_at DESC
        ");
        $stmt->execute([$practitioner_id, $practitioner_id]);
        $records = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $records[] = new MedicalRecord(
                $row['id'], $row['patient_id'], $row['created_by'],
                $row['created_at'], $row['updated_at']
            );
        }
        return $records;
    }
}
?>