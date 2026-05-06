<?php
// model/Appointment.php
require_once 'Database.php';

class Appointment {
    private $conn;

    public function __construct() {
        $db = new Database();
        $this->conn = $db->getConnection();
    }

    public function createAppointment($patientData, $appointmentData) {
        $query = "INSERT INTO rendez_vous (id_creneau, nom_patient, prenom_patient, telephone_patient, notes_medecin, statut)
                  VALUES (:id_creneau, AES_ENCRYPT(:nom_patient, 'Clé de Chiffrement78513'),
                  AES_ENCRYPT(:prenom_patient, 'Clé de Chiffrement78513'),
                  AES_ENCRYPT(:telephone_patient, 'Clé de Chiffrement78513'), :notes_medecin, :statut)";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_creneau', $appointmentData['id_creneau']);
        $stmt->bindParam(':nom_patient', $patientData['nom']);
        $stmt->bindParam(':prenom_patient', $patientData['prenom']);
        $stmt->bindParam(':telephone_patient', $patientData['telephone']);
        $stmt->bindParam(':notes_medecin', $appointmentData['notes_medecin']);
        $stmt->bindParam(':statut', $appointmentData['statut']);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function getAppointments() {
        $query = "SELECT id_rdv, id_creneau, AES_DECRYPT(nom_patient, 'Clé de Chiffrement78513') as nom_patient,
                  AES_DECRYPT(prenom_patient, 'Clé de Chiffrement78513') as prenom_patient,
                  AES_DECRYPT(telephone_patient, 'Clé de Chiffrement78513') as telephone_patient,
                  notes_medecin, statut FROM rendez_vous";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateAppointment($appointmentId, $newData) {
        $query = "UPDATE rendez_vous SET id_creneau = :id_creneau,
                  nom_patient = AES_ENCRYPT(:nom_patient, 'Clé de Chiffrement78513'),
                  prenom_patient = AES_ENCRYPT(:prenom_patient, 'Clé de Chiffrement78513'),
                  telephone_patient = AES_ENCRYPT(:telephone_patient, 'Clé de Chiffrement78513'),
                  notes_medecin = :notes_medecin, statut = :statut WHERE id_rdv = :id_rdv";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_rdv', $appointmentId);
        $stmt->bindParam(':id_creneau', $newData['id_creneau']);
        $stmt->bindParam(':nom_patient', $newData['nom_patient']);
        $stmt->bindParam(':prenom_patient', $newData['prenom_patient']);
        $stmt->bindParam(':telephone_patient', $newData['telephone_patient']);
        $stmt->bindParam(':notes_medecin', $newData['notes_medecin']);
        $stmt->bindParam(':statut', $newData['statut']);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function cancelAppointment($appointmentId) {
        $query = "UPDATE rendez_vous SET statut = 'annule' WHERE id_rdv = :id_rdv";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_rdv', $appointmentId);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function searchPatient($searchTerm) {
        $query = "SELECT id_rdv, id_creneau, AES_DECRYPT(nom_patient, 'Clé de Chiffrement78513') as nom_patient,
                  AES_DECRYPT(prenom_patient, 'Clé de Chiffrement78513') as prenom_patient,
                  AES_DECRYPT(telephone_patient, 'Clé de Chiffrement78513') as telephone_patient,
                  notes_medecin, statut FROM rendez_vous
                  WHERE AES_DECRYPT(nom_patient, 'Clé de Chiffrement78513') LIKE :searchTerm
                  OR AES_DECRYPT(prenom_patient, 'Clé de Chiffrement78513') LIKE :searchTerm";

        $stmt = $this->conn->prepare($query);
        $searchTerm = "%" . $searchTerm . "%";
        $stmt->bindParam(':searchTerm', $searchTerm);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getAppointmentsForCalendar($id_medecin) {
        // Jointure entre rendez_vous, creneau et planning pour obtenir les dates et heures exactes
        $query = "SELECT 
                    rv.id_rdv as id, 
                    CONCAT(AES_DECRYPT(rv.nom_patient, 'Clé de Chiffrement78513'), ' ', AES_DECRYPT(rv.prenom_patient, 'Clé de Chiffrement78513')) as title,
                    CONCAT(p.date_planning, 'T', c.heure_debut) as start,
                    CONCAT(p.date_planning, 'T', c.heure_fin) as end,
                    rv.statut
                  FROM rendez_vous rv
                  JOIN creneau c ON rv.id_creneau = c.id_creneau
                  JOIN planning p ON c.id_planning = p.id_planning
                  WHERE p.id_medecin = :id_medecin 
                  AND rv.statut = 'confirme'"; // On n'affiche que les RDV confirmés

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_medecin', $id_medecin);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getAvailableSlots($id_medecin, $date) {
    // On cherche les créneaux qui :
    // 1. Appartiennent au planning du médecin à cette date
    // 2. Ne sont PAS associés à un rendez-vous (ou alors un RDV annulé)
    $query = "SELECT c.id_creneau, c.heure_debut, c.heure_fin 
              FROM creneau c
              JOIN planning p ON c.id_planning = p.id_planning
              LEFT JOIN rendez_vous rv ON c.id_creneau = rv.id_creneau AND rv.statut = 'confirme'
              WHERE p.id_medecin = :id_medecin 
              AND p.date_planning = :date_selection
              AND rv.id_rdv IS NULL
              ORDER BY c.heure_debut ASC";

    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(':id_medecin', $id_medecin);
    $stmt->bindParam(':date_selection', $date);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
}
?>
