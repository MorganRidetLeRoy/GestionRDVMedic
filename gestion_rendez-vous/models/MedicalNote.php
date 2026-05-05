<?php
// models/MedicalNote.php
require_once __DIR__ . '/../config/database.php';

class MedicalNote {
    private $id;
    private $medical_record_id;
    private $note_type;
    private $title;
    private $content;
    private $created_by;
    private $created_at;
    private $updated_at;

    public function __construct($id, $medical_record_id, $note_type, $title, $content, $created_by, $created_at, $updated_at) {
        $this->id = $id;
        $this->medical_record_id = $medical_record_id;
        $this->note_type = $note_type;
        $this->title = $title;
        $this->content = $content;
        $this->created_by = $created_by;
        $this->created_at = $created_at;
        $this->updated_at = $updated_at;
    }

    // Getters
    public function getId() { return $this->id; }
    public function getMedicalRecordId() { return $this->medical_record_id; }
    public function getNoteType() { return $this->note_type; }
    public function getTitle() { return $this->title; }
    public function getContent() { return $this->content; }
    public function getCreatedBy() { return $this->created_by; }
    public function getCreatedAt() { return $this->created_at; }
    public function getUpdatedAt() { return $this->updated_at; }

    // Créer une note médicale (US-27)
    public static function create($medical_record_id, $note_type, $title, $content, $created_by) {
        global $pdo;
        $stmt = $pdo->prepare("
            INSERT INTO medical_notes (medical_record_id, note_type, title, content, created_by)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$medical_record_id, $note_type, $title, $content, $created_by]);
        return $pdo->lastInsertId();
    }

    // Lister les notes d'un dossier médical
    public static function getByMedicalRecord($medical_record_id) {
        global $pdo;
        $stmt = $pdo->prepare("
            SELECT * FROM medical_notes
            WHERE medical_record_id = ?
            ORDER BY created_at DESC
        ");
        $stmt->execute([$medical_record_id]);
        $notes = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $notes[] = new MedicalNote(
                $row['id'], $row['medical_record_id'], $row['note_type'],
                $row['title'], $row['content'], $row['created_by'],
                $row['created_at'], $row['updated_at']
            );
        }
        return $notes;
    }

    // Trouver une note par ID
    public static function findById($id) {
        global $pdo;
        $stmt = $pdo->prepare("SELECT * FROM medical_notes WHERE id = ?");
        $stmt->execute([$id]);
        $note = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($note) {
            return new MedicalNote(
                $note['id'], $note['medical_record_id'], $note['note_type'],
                $note['title'], $note['content'], $note['created_by'],
                $note['created_at'], $note['updated_at']
            );
        }
        return null;
    }

    // Mettre à jour une note médicale (US-27)
    public function update($title, $content) {
        global $pdo;
        $stmt = $pdo->prepare("
            UPDATE medical_notes
            SET title = ?, content = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$title, $content, $this->id]);
        return true;
    }

    // Supprimer une note médicale
    public function delete() {
        global $pdo;
        $stmt = $pdo->prepare("DELETE FROM medical_notes WHERE id = ?");
        $stmt->execute([$this->id]);
        return true;
    }
}
?>