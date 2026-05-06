<?php
// controller/AppointmentController.php
require_once '../model/Appointment.php';

$action = isset($_GET['action']) ? $_GET['action'] : '';

$appointment = new Appointment();

switch ($action) {
    case 'create':
        $patientData = [
            'nom' => $_POST['nom'],
            'prenom' => $_POST['prenom'],
            'telephone' => $_POST['telephone']
        ];

        $appointmentData = [
            'id_creneau' => $_POST['id_creneau'],
            'notes_medecin' => $_POST['notes_medecin'],
            'statut' => 'confirme'
        ];

        $result = $appointment->createAppointment($patientData, $appointmentData);
        echo json_encode(['success' => $result]);
        break;

    case 'getAppointments':
        $appointments = $appointment->getAppointments();
        echo json_encode($appointments);
        break;

    case 'update':
        $appointmentId = $_POST['appointmentId'];
        $newData = [
            'id_creneau' => $_POST['id_creneau'],
            'nom_patient' => $_POST['nom_patient'],
            'prenom_patient' => $_POST['prenom_patient'],
            'telephone_patient' => $_POST['telephone_patient'],
            'notes_medecin' => $_POST['notes_medecin'],
            'statut' => $_POST['statut']
        ];

        $result = $appointment->updateAppointment($appointmentId, $newData);
        echo json_encode(['success' => $result]);
        break;

    case 'cancel':
        $appointmentId = $_GET['appointmentId'];
        $result = $appointment->cancelAppointment($appointmentId);
        echo json_encode(['success' => $result]);
        break;

    case 'search':
        $searchTerm = $_GET['searchTerm'];
        $patients = $appointment->searchPatient($searchTerm);
        echo json_encode($patients);
        break;

    default:
        echo json_encode(['error' => 'Action non valide']);
        break;
        
        case 'getCalendarEvents':
        // Par défaut, on prend le médecin 1 si non spécifié (à adapter avec ton système de session)
        $id_medecin = isset($_GET['id_medecin']) ? $_GET['id_medecin'] : 1;
        $events = $appointment->getAppointmentsForCalendar($id_medecin);
        
        // FullCalendar s'attend à recevoir un tableau JSON avec 'id', 'title', 'start', 'end'
        echo json_encode($events);
        break;
        
        case 'getSlots':
        $id_medecin = $_GET['id_medecin'];
        $date = $_GET['date'];
        $slots = $appointment->getAvailableSlots($id_medecin, $date);
        echo json_encode($slots);
        break;
}
?>
