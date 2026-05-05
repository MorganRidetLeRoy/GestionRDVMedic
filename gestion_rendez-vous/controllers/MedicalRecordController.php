<?php
// controllers/MedicalRecordController.php
require_once __DIR__ . '/../models/MedicalRecord.php';
require_once __DIR__ . '/../models/MedicalNote.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Patient.php';
require_once __DIR__ . '/../models/Auth.php';

class MedicalRecordController {
    // Vérifier que l'utilisateur est un praticien
    private function checkPractitioner() {
        if (!Auth::isLoggedIn() || !isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'praticien') {
            header('Location: /login');
            exit;
        }
    }

    // Vérifier que l'utilisateur est un praticien ou une secrétaire (US-29)
    private function checkPractitionerOrSecretary() {
        if (!Auth::isLoggedIn() || !isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['praticien', 'secrétaire'])) {
            header('Location: /login');
            exit;
        }
    }

    // Afficher le formulaire de création de dossier médical (US-27)
    public function showCreateRecordForm($patient_id) {
        $this->checkPractitioner();
        $patient = Patient::findById($patient_id);
        if (!$patient) {
            header('Location: /practitioner/appointments');
            exit;
        }
        require __DIR__ . '/../views/practitioner/medical_records/create_record.php';
    }

    // Créer un dossier médical (US-27)
    public function createRecord($patient_id) {
        $this->checkPractitioner();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $practitioner_id = $_SESSION['user']['id'];
            $recordId = MedicalRecord::create($patient_id, $practitioner_id);

            if ($recordId) {
                // Créer une première note si des données sont fournies
                if (!empty($_POST['note_type']) && !empty($_POST['content'])) {
                    MedicalNote::create(
                        $recordId,
                        $_POST['note_type'],
                        $_POST['title'] ?? 'Première note',
                        $_POST['content'],
                        $practitioner_id
                    );
                }
                $success = "Dossier médical créé avec succès !";
            } else {
                $error = "Erreur lors de la création du dossier médical.";
            }

            $patient = Patient::findById($patient_id);
            require __DIR__ . '/../views/practitioner/medical_records/create_record.php';
        } else {
            $this->showCreateRecordForm($patient_id);
        }
    }

    // Afficher la liste des dossiers médicaux (US-28)
    public function listRecords() {
        $this->checkPractitioner();
        $practitioner_id = $_SESSION['user']['id'];
        $records = MedicalRecord::getByPractitioner($practitioner_id);
        require __DIR__ . '/../views/practitioner/medical_records/list_records.php';
    }

    // Afficher un dossier médical (US-28, US-29)
    public function viewRecord($record_id) {
        $this->checkPractitionerOrSecretary();
        $record = MedicalRecord::findById($record_id);
        if (!$record) {
            header('Location: /practitioner/medical-records');
            exit;
        }

        // Vérifier que le praticien ou la secrétaire a accès à ce dossier
        $practitioner_id = $_SESSION['user']['id'];
        $patient = Patient::findById($record->getPatientId());
        $notes = MedicalNote::getByMedicalRecord($record_id);

        require __DIR__ . '/../views/practitioner/medical_records/view_record.php';
    }

    // Afficher le formulaire d'ajout de note (US-27)
    public function showAddNoteForm($record_id) {
        $this->checkPractitioner();
        $record = MedicalRecord::findById($record_id);
        if (!$record) {
            header('Location: /practitioner/medical-records');
            exit;
        }
        require __DIR__ . '/../views/practitioner/medical_records/add_note.php';
    }

    // Ajouter une note à un dossier médical (US-27)
    public function addNote($record_id) {
        $this->checkPractitioner();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $record = MedicalRecord::findById($record_id);
            if (!$record) {
                header('Location: /practitioner/medical-records');
                exit;
            }

            $practitioner_id = $_SESSION['user']['id'];
            $note_type = $_POST['note_type'];
            $title = $_POST['title'] ?? null;
            $content = $_POST['content'];

            if (MedicalNote::create($record_id, $note_type, $title, $content, $practitioner_id)) {
                $success = "Note ajoutée avec succès !";
            } else {
                $error = "Erreur lors de l'ajout de la note.";
            }

            $notes = MedicalNote::getByMedicalRecord($record_id);
            require __DIR__ . '/../views/practitioner/medical_records/view_record.php';
        } else {
            $this->showAddNoteForm($record_id);
        }
    }

    // Afficher le formulaire de modification de note (US-27)
    public function showEditNoteForm($note_id) {
        $this->checkPractitioner();
        $note = MedicalNote::findById($note_id);
        if (!$note) {
            header('Location: /practitioner/medical-records');
            exit;
        }
        require __DIR__ . '/../views/practitioner/medical_records/edit_note.php';
    }

    // Modifier une note (US-27)
    public function editNote($note_id) {
        $this->checkPractitioner();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $note = MedicalNote::findById($note_id);
            if (!$note) {
                header('Location: /practitioner/medical-records');
                exit;
            }

            $title = $_POST['title'];
            $content = $_POST['content'];

            if ($note->update($title, $content)) {
                $success = "Note modifiée avec succès !";
            } else {
                $error = "Erreur lors de la modification de la note.";
            }

            $record = MedicalRecord::findById($note->getMedicalRecordId());
            $notes = MedicalNote::getByMedicalRecord($note->getMedicalRecordId());
            require __DIR__ . '/../views/practitioner/medical_records/view_record.php';
        } else {
            $this->showEditNoteForm($note_id);
        }
    }

    // Supprimer une note
    public function deleteNote($note_id) {
        $this->checkPractitioner();
        $note = MedicalNote::findById($note_id);
        if (!$note) {
            header('Location: /practitioner/medical-records');
            exit;
        }

        $record_id = $note->getMedicalRecordId();
        if ($note->delete()) {
            $success = "Note supprimée avec succès !";
        } else {
            $error = "Erreur lors de la suppression de la note.";
        }

        $record = MedicalRecord::findById($record_id);
        $notes = MedicalNote::getByMedicalRecord($record_id);
        require __DIR__ . '/../views/practitioner/medical_records/view_record.php';
    }

    // Accès secrétaire au dossier médical (US-29)
    public function viewRecordAsSecretary($record_id) {
        $this->checkPractitionerOrSecretary();
        if ($_SESSION['user']['role'] !== 'secrétaire') {
            header('Location: /login');
            exit;
        }

        $record = MedicalRecord::findById($record_id);
        if (!$record) {
            header('Location: /secretary/appointments');
            exit;
        }

        $notes = MedicalNote::getByMedicalRecord($record_id);
        require __DIR__ . '/../views/secretary/medical_records/view_record.php';
    }
}
?>