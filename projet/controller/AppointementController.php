<?php
// controller/AppointmentController.php
require_once __DIR__ . '/../model/Appointement.php';

// Démarre la session si ce n'est pas déjà fait
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Fonction helper pour valider les données d'un rendez-vous
function validateAppointmentData($data) {
    $requiredFields = ['nom', 'prenom', 'telephone', 'id_creneau'];
    foreach ($requiredFields as $field) {
        if (empty($data[$field])) {
            return ['valid' => false, 'error' => "Champ manquant: $field"];
        }
    }
    if (!filter_var($data['id_creneau'], FILTER_VALIDATE_INT)) {
        return ['valid' => false, 'error' => "ID créneau invalide"];
    }
    return ['valid' => true];
}

// Initialise le contrôleur
$action = isset($_GET['action']) ? $_GET['action'] : '';
$appointment = new Appointment();
$response = ['success' => false];

switch ($action) {
    // =============================================
    // 🔹 CRÉER UN RENDEZ-VOUS
    // =============================================
    case 'create':
        // Vérifie que l'utilisateur est connecté
        if (!isset($_SESSION['user_id'])) {
            $response['error'] = 'Non autorisé : vous devez être connecté.';
            echo json_encode($response);
            exit;
        }

        // Validation des données
        $validation = validateAppointmentData($_POST);
        if (!$validation['valid']) {
            $response['error'] = $validation['error'];
            echo json_encode($response);
            exit;
        }

        // Vérifie la disponibilité du créneau
        $id_creneau = filter_input(INPUT_POST, 'id_creneau', FILTER_VALIDATE_INT);
        if (!$id_creneau || !$appointment->isSlotAvailable($id_creneau)) {
            $response['error'] = 'Créneau non disponible ou invalide.';
            echo json_encode($response);
            exit;
        }

        // Prépare les données
        $patientData = [
            'nom' => filter_input(INPUT_POST, 'nom', FILTER_SANITIZE_STRING),
            'prenom' => filter_input(INPUT_POST, 'prenom', FILTER_SANITIZE_STRING),
            'telephone' => filter_input(INPUT_POST, 'telephone', FILTER_SANITIZE_STRING)
        ];

        $appointmentData = [
            'id_creneau' => $id_creneau,
            'notes_medecin' => filter_input(INPUT_POST, 'notes_medecin', FILTER_SANITIZE_STRING) ?? '',
            'statut' => 'en_attente' // Statut initial
        ];

        $response['success'] = $appointment->createAppointment($patientData, $appointmentData);
        echo json_encode($response);
        exit;

    // =============================================
    // 🔹 RÉCUPÉRER TOUS LES RENDEZ-VOUS (ADMIN)
    // =============================================
    case 'getAppointments':
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            $response['error'] = 'Non autorisé : accès réservé aux administrateurs.';
            echo json_encode($response);
            exit;
        }
        $appointments = $appointment->getAppointments();
        echo json_encode(['success' => true, 'data' => $appointments]);
        exit;

    // =============================================
    // 🔹 METTRE À JOUR UN RENDEZ-VOUS
    // =============================================
    case 'update':
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || !in_array($_SESSION['role'], ['medecin', 'admin'])) {
            $response['error'] = 'Non autorisé : accès réservé aux médecins et administrateurs.';
            echo json_encode($response);
            exit;
        }

        $appointmentId = filter_input(INPUT_POST, 'appointmentId', FILTER_VALIDATE_INT);
        if (!$appointmentId) {
            $response['error'] = 'ID de rendez-vous invalide';
            echo json_encode($response);
            exit;
        }

        // Récupère les données du formulaire
        $newData = [
            'id_creneau' => filter_input(INPUT_POST, 'id_creneau', FILTER_VALIDATE_INT),
            'nom_patient' => filter_input(INPUT_POST, 'nom_patient', FILTER_SANITIZE_STRING),
            'prenom_patient' => filter_input(INPUT_POST, 'prenom_patient', FILTER_SANITIZE_STRING),
            'telephone_patient' => filter_input(INPUT_POST, 'telephone_patient', FILTER_SANITIZE_STRING),
            'notes_medecin' => filter_input(INPUT_POST, 'notes_medecin', FILTER_SANITIZE_STRING) ?? '',
            'statut' => filter_input(INPUT_POST, 'statut', FILTER_SANITIZE_STRING)
        ];

        // Vérifie que tous les champs obligatoires sont présents
        if (!$newData['id_creneau'] || !$newData['nom_patient'] || !$newData['prenom_patient'] || !$newData['telephone_patient']) {
            $response['error'] = 'Champs manquants';
            echo json_encode($response);
            exit;
        }

        // Vérifie si le créneau est modifié et disponible
        $currentAppointment = $appointment->getAppointmentById($appointmentId);
        if (!$currentAppointment) {
            $response['error'] = 'Rendez-vous introuvable';
            echo json_encode($response);
            exit;
        }

        if ($newData['id_creneau'] && $currentAppointment['id_creneau'] != $newData['id_creneau']) {
            if (!$appointment->isSlotAvailable($newData['id_creneau'])) {
                $response['error'] = 'Le nouveau créneau n\'est pas disponible';
                echo json_encode($response);
                exit;
            }
        }

        $response['success'] = $appointment->updateAppointment($appointmentId, $newData);
        echo json_encode($response);
        exit;

    // =============================================
    // 🔹 ANNULER UN RENDEZ-VOUS
    // =============================================
    case 'cancel':
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || !in_array($_SESSION['role'], ['medecin', 'admin'])) {
            $response['error'] = 'Non autorisé : accès réservé aux médecins et administrateurs.';
            echo json_encode($response);
            exit;
        }

        $appointmentId = filter_input(INPUT_POST, 'appointmentId', FILTER_VALIDATE_INT);
        if (!$appointmentId) {
            $response['error'] = 'ID de rendez-vous invalide';
            echo json_encode($response);
            exit;
        }

        $response['success'] = $appointment->cancelAppointment($appointmentId);
        echo json_encode($response);
        exit;

    // =============================================
    // 🔹 RECHERCHER UN PATIENT
    // =============================================
    case 'search':
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || !in_array($_SESSION['role'], ['medecin', 'admin'])) {
            $response['error'] = 'Non autorisé : accès réservé aux médecins et administrateurs.';
            echo json_encode($response);
            exit;
        }

        $searchTerm = filter_input(INPUT_GET, 'searchTerm', FILTER_SANITIZE_STRING);
        if (!$searchTerm) {
            $response['error'] = 'Terme de recherche manquant';
            echo json_encode($response);
            exit;
        }

        $patients = $appointment->searchPatient($searchTerm);
        echo json_encode(['success' => true, 'data' => $patients]);
        exit;

    // =============================================
    // 🔹 RÉCUPÉRER LES ÉVÉNEMENTS POUR LE CALENDRIER
    // =============================================
    case 'getCalendarEvents':
        if (!isset($_SESSION['user_id'])) {
            $response['error'] = 'Non autorisé : vous devez être connecté.';
            echo json_encode($response);
            exit;
        }

        $id_medecin = isset($_GET['id_medecin']) ? filter_input(INPUT_GET, 'id_medecin', FILTER_VALIDATE_INT) : $_SESSION['user_id'];

        // Vérifie que l'utilisateur a le droit d'accéder aux données du médecin
        if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'admin' && $_SESSION['user_id'] != $id_medecin)) {
            $response['error'] = 'Accès refusé : vous ne pouvez pas accéder aux rendez-vous de ce médecin.';
            echo json_encode($response);
            exit;
        }

        $events = $appointment->getAppointmentsForCalendar($id_medecin);
        echo json_encode(['success' => true, 'data' => $events]);
        exit;

    // =============================================
    // 🔹 RÉCUPÉRER LES CRÉNEAUX DISPONIBLES
    // =============================================
    case 'getSlots':
        if (!isset($_SESSION['user_id'])) {
            $response['error'] = 'Non autorisé : vous devez être connecté.';
            echo json_encode($response);
            exit;
        }

        $id_medecin = filter_input(INPUT_GET, 'id_medecin', FILTER_VALIDATE_INT);
        $date = filter_input(INPUT_GET, 'date', FILTER_SANITIZE_STRING);

        // Si id_medecin n'est pas fourni, utilise l'ID de l'utilisateur connecté
        if (!$id_medecin) {
            $id_medecin = $_SESSION['user_id'];
        }

        if (!$date || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $response['error'] = 'Date invalide (format attendu: YYYY-MM-DD)';
            echo json_encode($response);
            exit;
        }

        // Vérifie que l'utilisateur a le droit d'accéder aux créneaux du médecin
        if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'admin' && $_SESSION['user_id'] != $id_medecin)) {
            $response['error'] = 'Accès refusé : vous ne pouvez pas accéder aux créneaux de ce médecin.';
            echo json_encode($response);
            exit;
        }

        try {
            $slots = $appointment->getAvailableSlots($id_medecin, $date);
            echo json_encode(['success' => true, 'data' => $slots]);
        } catch (Exception $e) {
            error_log("Erreur dans getSlots: " . $e->getMessage());
            echo json_encode(['success' => false, 'error' => 'Erreur serveur lors de la récupération des créneaux.']);
        }
        exit;

    // =============================================
    // 🔹 GÉNÉRER DES CRÉNEAUX AUTOMATIQUEMENT
    // =============================================
    case 'generateSlots':
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'medecin'])) {
            $response['error'] = 'Non autorisé : accès réservé aux administrateurs et médecins.';
            echo json_encode($response);
            exit;
        }

        // Récupère les paramètres avec des valeurs par défaut
        $id_medecin = filter_input(INPUT_GET, 'id_medecin', FILTER_VALIDATE_INT) ?: $_SESSION['user_id'];
        $date_debut = filter_input(INPUT_GET, 'date_debut', FILTER_SANITIZE_STRING);
        $date_fin = filter_input(INPUT_GET, 'date_fin', FILTER_SANITIZE_STRING);
        $heure_debut = filter_input(INPUT_GET, 'heure_debut', FILTER_SANITIZE_STRING) ?: '09:00:00';
        $heure_fin = filter_input(INPUT_GET, 'heure_fin', FILTER_SANITIZE_STRING) ?: '18:00:00';
        $duree_creneau = filter_input(INPUT_GET, 'duree_creneau', FILTER_VALIDATE_INT) ?: 30;

        // Validation des paramètres
        if (!$id_medecin || !$date_debut || !$date_fin) {
            $response['error'] = 'Paramètres manquants (id_medecin, date_debut, date_fin).';
            echo json_encode($response);
            exit;
        }

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_debut) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_fin)) {
            $response['error'] = 'Format de date invalide (attendu: YYYY-MM-DD).';
            echo json_encode($response);
            exit;
        }

        if (strtotime($date_debut) > strtotime($date_fin)) {
            $response['error'] = 'La date de début doit être antérieure à la date de fin.';
            echo json_encode($response);
            exit;
        }

        if (!preg_match('/^\d{2}:\d{2}:\d{2}$/', $heure_debut) || !preg_match('/^\d{2}:\d{2}:\d{2}$/', $heure_fin)) {
            $response['error'] = 'Format d\'heure invalide (attendu: HH:MM:SS).';
            echo json_encode($response);
            exit;
        }

        if (strtotime($heure_debut) >= strtotime($heure_fin)) {
            $response['error'] = 'L\'heure de début doit être antérieure à l\'heure de fin.';
            echo json_encode($response);
            exit;
        }

        // Appel de la méthode pour créer les créneaux
        $result = $appointment->createSlotsForPeriod(
            $id_medecin,
            $date_debut,
            $date_fin,
            $heure_debut,
            $heure_fin,
            $duree_creneau
        );

        if ($result) {
            $response['success'] = true;
            $response['message'] = "Créneaux générés avec succès pour le médecin $id_medecin du $date_debut au $date_fin.";
        } else {
            $response['error'] = 'Échec de la génération des créneaux.';
        }
        echo json_encode($response);
        exit;

    // =============================================
    // 🔹 ACTION PAR DÉFAUT
    // =============================================
    default:
        $response['error'] = 'Action non valide.';
        echo json_encode($response);
        exit;
}
?>
