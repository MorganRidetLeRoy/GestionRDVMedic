<?php
// controllers/AppointmentController.php
require_once __DIR__ . '/../models/Appointment.php';
require_once __DIR__ . '/../models/PractitionerSlot.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Patient.php';
require_once __DIR__ . '/../models/Auth.php';

class AppointmentController {
    // Afficher le formulaire de création de rendez-vous (US-15, US-16)
    public function showCreateAppointment() {
        if (!Auth::isLoggedIn() || (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'secrétaire')) {
            header('Location: /login');
            exit;
        }

        $practitioners = User::getPractitioners();
        $patients = Patient::getAll(); // À ajouter dans Patient.php
        require __DIR__ . '/../views/secretary/create_appointment.php';
    }

    // Créer un rendez-vous (US-15, US-16)
    public function createAppointment() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $patient_id = $_POST['patient_id'];
            $practitioner_id = $_POST['practitioner_id'];
            $date = $_POST['date'] . ' ' . $_POST['time'];
            $reason = $_POST['reason'] ?? null;

            // Vérifier si le créneau est disponible
            $availableSlots = Appointment::getAvailableSlots($practitioner_id, $date);
            if (!in_array($_POST['time'], $availableSlots)) {
                $error = "Ce créneau n'est pas disponible.";
                $practitioners = User::getPractitioners();
                $patients = Patient::getAll();
                require __DIR__ . '/../views/secretary/create_appointment.php';
                return;
            }

            // Dans la méthode createAppointment, après la création du rendez-vous :
            if ($recordId) {
                $success = "Rendez-vous créé avec succès !";
                // Le modèle Appointment::create envoie déjà l'email de confirmation (US-32)
            } else {
                $error = "Erreur lors de la création du rendez-vous.";
            }

            $practitioners = User::getPractitioners();
            $patients = Patient::getAll();
            require __DIR__ . '/../views/secretary/create_appointment.php';
        } else {
            $this->showCreateAppointment();
        }
    }

    // Ajoutez une méthode pour déclencher manuellement les rappels (optionnel)
    public function sendReminders() {
        $this->checkPractitionerOrSecretary(); // ou checkAdmin()
        $count = Appointment::sendReminders();
        $success = "Rappels envoyés pour $count rendez-vous.";
        require __DIR__ . '/../views/admin/dashboard.php';
    }

    // Afficher le formulaire de modification de rendez-vous (US-17)
    public function showEditAppointment($appointment_id) {
        if (!Auth::isLoggedIn() || (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'secrétaire')) {
            header('Location: /login');
            exit;
        }

        $appointment = Appointment::findById($appointment_id);
        if (!$appointment) {
            header('Location: /secretary/appointments');
            exit;
        }

        $practitioners = User::getPractitioners();
        $patients = Patient::getAll();
        require __DIR__ . '/../views/secretary/edit_appointment.php';
    }

    // Modifier un rendez-vous (US-17)
    public function editAppointment($appointment_id) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $appointment = Appointment::findById($appointment_id);
            if (!$appointment) {
                header('Location: /secretary/appointments');
                exit;
            }

            $date = $_POST['date'] . ' ' . $_POST['time'];
            $practitioner_id = $_POST['practitioner_id'];
            $reason = $_POST['reason'] ?? null;

            if ($appointment->update($date, $practitioner_id, $reason)) {
                $success = "Rendez-vous modifié avec succès !";
            } else {
                $error = "Erreur lors de la modification du rendez-vous.";
            }

            $practitioners = User::getPractitioners();
            $patients = Patient::getAll();
            require __DIR__ . '/../views/secretary/edit_appointment.php';
        } else {
            $this->showEditAppointment($appointment_id);
        }
    }

    // Annuler un rendez-vous (US-18)
    public function cancelAppointment($appointment_id) {
        if (!Auth::isLoggedIn() || (!isset($_SESSION['user']) || ($_SESSION['user']['role'] !== 'secrétaire' && $_SESSION['user']['role'] !== 'praticien'))) {
            header('Location: /login');
            exit;
        }

        $appointment = Appointment::findById($appointment_id);
        if (!$appointment) {
            header('Location: /secretary/appointments');
            exit;
        }

        if ($appointment->cancel()) {
            $success = "Rendez-vous annulé avec succès !";
        } else {
            $error = "Erreur lors de l'annulation du rendez-vous.";
        }

        // Rediriger vers la liste des rendez-vous
        if (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'secrétaire') {
            header('Location: /secretary/appointments');
        } else {
            header('Location: /practitioner/appointments');
        }
        exit;
    }

    // Lister les rendez-vous pour un secrétaire
    public function listSecretaryAppointments() {
        if (!Auth::isLoggedIn() || (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'secrétaire')) {
            header('Location: /login');
            exit;
        }

        $appointments = Appointment::getAll(); // À ajouter dans Appointment.php
        require __DIR__ . '/../views/secretary/appointments.php';
    }

    // Lister les rendez-vous pour un praticien (US-19, US-20)
    public function listPractitionerAppointments() {
        if (!Auth::isLoggedIn() || (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'praticien')) {
            header('Location: /login');
            exit;
        }

        $practitioner_id = $_SESSION['user']['id'];
        $appointments = Appointment::getByPractitioner($practitioner_id);
        require __DIR__ . '/../views/practitioner/appointments.php';
    }

    // Afficher les rendez-vous d'un patient (US-21)
    public function listPatientAppointments() {
        if (!Auth::isLoggedIn() || !isset($_SESSION['patient'])) {
            header('Location: /login');
            exit;
        }

        $patient_id = $_SESSION['patient']['id'];
        $appointments = Appointment::getByPatient($patient_id);
        require __DIR__ . '/../views/patient/my_appointments.php';
    }

    // Afficher le formulaire de gestion des créneaux (US-20)
    public function showScheduleForm() {
        if (!Auth::isLoggedIn() || (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'praticien')) {
            header('Location: /login');
            exit;
        }

        $practitioner_id = $_SESSION['user']['id'];
        $slots = PractitionerSlot::getByPractitioner($practitioner_id);
        require __DIR__ . '/../views/practitioner/schedule.php';
    }

    // Mettre à jour les créneaux horaires (US-20)
    public function updateSchedule() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $practitioner_id = $_SESSION['user']['id'];
            $day_of_week = $_POST['day_of_week'];
            $start_time = $_POST['start_time'];
            $end_time = $_POST['end_time'];

            if (PractitionerSlot::create($practitioner_id, $day_of_week, $start_time, $end_time)) {
                $success = "Créneau horaire ajouté avec succès !";
            } else {
                $error = "Erreur lors de l'ajout du créneau horaire.";
            }

            $slots = PractitionerSlot::getByPractitioner($practitioner_id);
            require __DIR__ . '/../views/practitioner/schedule.php';
        } else {
            $this->showScheduleForm();
        }
    }

    private function validateAppointment($patient_id, $practitioner_id, $date) {
        
        $errors = [];
        
        if (!$patient_id || !Patient::findById($patient_id)) {
            $errors[] = "Patient invalide.";
        }
        
        if (!$practitioner_id || !User::getById($practitioner_id)) {
            $errors[] = "Praticien invalide.";
        }
        
        if (strtotime($date) < time()) {
            $errors[] = "La date doit être dans le futur.";
        }
        return $errors;
    }

    $errors = $this->validateAppointment($patient_id, $practitioner_id, $date);
    if (!empty($errors)) {
        $error = implode("<br>", $errors);
        $practitioners = User::getPractitioners();
        $patients = Patient::getAll();
        require __DIR__ . '/../views/secretary/create_appointment.php';
        return;
    }
}
?>