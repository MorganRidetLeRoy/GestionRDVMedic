<?php
// model/Appointement.php
require_once __DIR__ . '/Database.php';

class Appointment {
    private $conn;

    public function __construct() {
        $db = new Database();
        $this->conn = $db->getConnection();
    }

    /**
     * Crée un nouveau rendez-vous et met à jour le statut du créneau.
     * @param array $patientData Données du patient (nom, prénom, téléphone)
     * @param array $appointmentData Données du rendez-vous (id_creneau, notes_medecin, statut)
     * @return bool
     */
    public function createAppointment($patientData, $appointmentData) {
        try {
            // Vérifie que les données obligatoires sont présentes
            if (empty($patientData['nom']) || empty($patientData['prenom']) || empty($patientData['telephone']) || empty($appointmentData['id_creneau'])) {
                error_log("Données manquantes pour créer un rendez-vous.");
                return false;
            }

            $this->conn->beginTransaction();

            // 1. Crée le rendez-vous
            $query = "INSERT INTO rendez_vous (id_creneau, nom_patient, prenom_patient, telephone_patient, notes_medecin, statut)
                      VALUES (:id_creneau,
                              AES_ENCRYPT(:nom_patient, 'Clé de Chiffrement78513'),
                              AES_ENCRYPT(:prenom_patient, 'Clé de Chiffrement78513'),
                              AES_ENCRYPT(:telephone_patient, 'Clé de Chiffrement78513'),
                              AES_ENCRYPT(:notes_medecin, 'Clé de Chiffrement78513'),
                              :statut)";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id_creneau', $appointmentData['id_creneau'], PDO::PARAM_INT);
            $stmt->bindParam(':nom_patient', $patientData['nom']);
            $stmt->bindParam(':prenom_patient', $patientData['prenom']);
            $stmt->bindParam(':telephone_patient', $patientData['telephone']);
            $stmt->bindParam(':notes_medecin', $appointmentData['notes_medecin'] ?? '');
            $stmt->bindParam(':statut', $appointmentData['statut']);

            if (!$stmt->execute()) {
                $this->conn->rollBack();
                error_log("Erreur SQL lors de l'insertion du rendez-vous: " . implode(", ", $stmt->errorInfo()));
                return false;
            }

            // 2. Met à jour le statut du créneau
            $query = "UPDATE creneau SET statut = 'reserve' WHERE id_creneau = :id_creneau";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id_creneau', $appointmentData['id_creneau'], PDO::PARAM_INT);
            if (!$stmt->execute()) {
                $this->conn->rollBack();
                error_log("Erreur SQL lors de la mise à jour du créneau: " . implode(", ", $stmt->errorInfo()));
                return false;
            }

            $this->conn->commit();
            return true;
        } catch (PDOException $e) {
            $this->conn->rollBack();
            error_log("Erreur dans createAppointment: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupère tous les rendez-vous.
     * @return array
     */
    public function getAppointments() {
        try {
            $query = "SELECT id_rdv, id_creneau,
                             AES_DECRYPT(nom_patient, 'Clé de Chiffrement78513') as nom_patient,
                             AES_DECRYPT(prenom_patient, 'Clé de Chiffrement78513') as prenom_patient,
                             AES_DECRYPT(telephone_patient, 'Clé de Chiffrement78513') as telephone_patient,
                             AES_DECRYPT(notes_medecin, 'Clé de Chiffrement78513') as notes_medecin,
                             statut
                      FROM rendez_vous";

            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur dans getAppointments: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Met à jour un rendez-vous.
     * @param int $appointmentId ID du rendez-vous
     * @param array $newData Nouvelles données du rendez-vous
     * @return bool
     */
    public function updateAppointment($appointmentId, $newData) {
        try {
            // Vérifie que les données obligatoires sont présentes
            if (empty($newData['id_creneau']) || empty($newData['nom_patient']) || empty($newData['prenom_patient']) || empty($newData['telephone_patient'])) {
                error_log("Données manquantes pour mettre à jour un rendez-vous.");
                return false;
            }

            $this->conn->beginTransaction();

            // 1. Récupère l'ancien créneau pour le libérer si nécessaire
            $oldSlotQuery = "SELECT id_creneau FROM rendez_vous WHERE id_rdv = :id_rdv";
            $oldSlotStmt = $this->conn->prepare($oldSlotQuery);
            $oldSlotStmt->bindParam(':id_rdv', $appointmentId, PDO::PARAM_INT);
            $oldSlotStmt->execute();
            $oldSlot = $oldSlotStmt->fetch(PDO::FETCH_ASSOC);

            if (!$oldSlot) {
                $this->conn->rollBack();
                error_log("Rendez-vous introuvable pour l'ID: $appointmentId");
                return false;
            }

            // 2. Met à jour le rendez-vous
            $query = "UPDATE rendez_vous
                      SET id_creneau = :id_creneau,
                          nom_patient = AES_ENCRYPT(:nom_patient, 'Clé de Chiffrement78513'),
                          prenom_patient = AES_ENCRYPT(:prenom_patient, 'Clé de Chiffrement78513'),
                          telephone_patient = AES_ENCRYPT(:telephone_patient, 'Clé de Chiffrement78513'),
                          notes_medecin = AES_ENCRYPT(:notes_medecin, 'Clé de Chiffrement78513'),
                          statut = :statut
                      WHERE id_rdv = :id_rdv";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id_rdv', $appointmentId, PDO::PARAM_INT);
            $stmt->bindParam(':id_creneau', $newData['id_creneau'], PDO::PARAM_INT);
            $stmt->bindParam(':nom_patient', $newData['nom_patient']);
            $stmt->bindParam(':prenom_patient', $newData['prenom_patient']);
            $stmt->bindParam(':telephone_patient', $newData['telephone_patient']);
            $stmt->bindParam(':notes_medecin', $newData['notes_medecin'] ?? '');
            $stmt->bindParam(':statut', $newData['statut']);

            if (!$stmt->execute()) {
                $this->conn->rollBack();
                error_log("Erreur SQL lors de la mise à jour du rendez-vous: " . implode(", ", $stmt->errorInfo()));
                return false;
            }

            // 3. Libère l'ancien créneau
            $query = "UPDATE creneau SET statut = 'disponible' WHERE id_creneau = :id_creneau";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id_creneau', $oldSlot['id_creneau'], PDO::PARAM_INT);
            if (!$stmt->execute()) {
                $this->conn->rollBack();
                error_log("Erreur SQL lors de la libération de l'ancien créneau: " . implode(", ", $stmt->errorInfo()));
                return false;
            }

            // 4. Réserve le nouveau créneau
            $query = "UPDATE creneau SET statut = 'reserve' WHERE id_creneau = :id_creneau";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id_creneau', $newData['id_creneau'], PDO::PARAM_INT);
            if (!$stmt->execute()) {
                $this->conn->rollBack();
                error_log("Erreur SQL lors de la réservation du nouveau créneau: " . implode(", ", $stmt->errorInfo()));
                return false;
            }

            $this->conn->commit();
            return true;
        } catch (PDOException $e) {
            $this->conn->rollBack();
            error_log("Erreur dans updateAppointment: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Annule un rendez-vous et libère le créneau associé.
     * @param int $appointmentId ID du rendez-vous
     * @return bool
     */
    public function cancelAppointment($appointmentId) {
        try {
            $this->conn->beginTransaction();

            // 1. Récupère l'ID du créneau associé au rendez-vous
            $query = "SELECT id_creneau FROM rendez_vous WHERE id_rdv = :id_rdv";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id_rdv', $appointmentId, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$result) {
                $this->conn->rollBack();
                error_log("Rendez-vous introuvable pour l'ID: $appointmentId");
                return false;
            }

            $id_creneau = $result['id_creneau'];

            // 2. Annule le rendez-vous
            $query = "UPDATE rendez_vous SET statut = 'annule' WHERE id_rdv = :id_rdv";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id_rdv', $appointmentId, PDO::PARAM_INT);
            if (!$stmt->execute()) {
                $this->conn->rollBack();
                error_log("Erreur SQL lors de l'annulation du rendez-vous: " . implode(", ", $stmt->errorInfo()));
                return false;
            }

            // 3. Libère le créneau
            $query = "UPDATE creneau SET statut = 'disponible' WHERE id_creneau = :id_creneau";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id_creneau', $id_creneau, PDO::PARAM_INT);
            if (!$stmt->execute()) {
                $this->conn->rollBack();
                error_log("Erreur SQL lors de la libération du créneau: " . implode(", ", $stmt->errorInfo()));
                return false;
            }

            $this->conn->commit();
            return true;
        } catch (PDOException $e) {
            $this->conn->rollBack();
            error_log("Erreur dans cancelAppointment: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Recherche des patients par nom ou prénom.
     * @param string $searchTerm Terme de recherche
     * @return array
     */
    public function searchPatient($searchTerm) {
        try {
            $query = "SELECT id_rdv, id_creneau,
                             AES_DECRYPT(nom_patient, 'Clé de Chiffrement78513') as nom_patient,
                             AES_DECRYPT(prenom_patient, 'Clé de Chiffrement78513') as prenom_patient,
                             AES_DECRYPT(telephone_patient, 'Clé de Chiffrement78513') as telephone_patient,
                             AES_DECRYPT(notes_medecin, 'Clé de Chiffrement78513') as notes_medecin,
                             statut
                      FROM rendez_vous
                      WHERE AES_DECRYPT(nom_patient, 'Clé de Chiffrement78513') LIKE :searchTerm
                         OR AES_DECRYPT(prenom_patient, 'Clé de Chiffrement78513') LIKE :searchTerm";

            $stmt = $this->conn->prepare($query);
            $searchTerm = "%" . $searchTerm . "%";
            $stmt->bindParam(':searchTerm', $searchTerm);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur dans searchPatient: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère les rendez-vous pour le calendrier FullCalendar.
     * @param int $id_medecin ID du médecin
     * @return array
     */
    public function getAppointmentsForCalendar($id_medecin) {
        try {
            $query = "SELECT
                        rv.id_rdv as id,
                        CONCAT(
                            CASE WHEN rv.statut = 'en_attente' THEN '[EN ATTENTE] ' ELSE '' END,
                            AES_DECRYPT(rv.nom_patient, 'Clé de Chiffrement78513'), ' ', AES_DECRYPT(rv.prenom_patient, 'Clé de Chiffrement78513')
                        ) as title,
                        CONCAT(p.date_planning, 'T', c.heure_debut) as start,
                        CONCAT(p.date_planning, 'T', c.heure_fin) as end,
                        rv.statut
                      FROM rendez_vous rv
                      JOIN creneau c ON rv.id_creneau = c.id_creneau
                      JOIN planning p ON c.id_planning = p.id_planning
                      WHERE p.id_medecin = :id_medecin
                      AND rv.statut != 'annule'";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id_medecin', $id_medecin, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur dans getAppointmentsForCalendar: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Vérifie si un créneau est disponible.
     * @param int $id_creneau ID du créneau
     * @return bool
     */
    public function isSlotAvailable($id_creneau) {
        try {
            $query = "SELECT COUNT(*) as count
                      FROM rendez_vous
                      WHERE id_creneau = :id_creneau AND statut != 'annule'";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id_creneau', $id_creneau, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['count'] == 0;
        } catch (PDOException $e) {
            error_log("Erreur dans isSlotAvailable: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupère un rendez-vous par son ID.
     * @param int $id_rdv ID du rendez-vous
     * @return array
     */
    public function getAppointmentById($id_rdv) {
        try {
            $query = "SELECT id_creneau FROM rendez_vous WHERE id_rdv = :id_rdv";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id_rdv', $id_rdv, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            error_log("Erreur dans getAppointmentById: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère les créneaux disponibles pour un médecin et une date.
     * @param int $id_medecin ID du médecin
     * @param string $date Format: YYYY-MM-DD
     * @return array Tableau de créneaux disponibles
     */
    public function getAvailableSlots($id_medecin, $date) {
        try {
            // Vérifie d'abord si le médecin est en congé pour cette date
            $query = "SELECT COUNT(*) as is_on_leave
                      FROM conge
                      WHERE id_medecin = :id_medecin
                      AND date_debut <= :date
                      AND date_fin >= :date";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id_medecin', $id_medecin, PDO::PARAM_INT);
            $stmt->bindParam(':date', $date);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result['is_on_leave'] > 0) {
                // Le médecin est en congé : aucun créneau disponible
                return [];
            }

            // Sinon, récupère les créneaux disponibles
            $query = "SELECT c.id_creneau, c.heure_debut, c.heure_fin
                      FROM creneau c
                      JOIN planning p ON c.id_planning = p.id_planning
                      LEFT JOIN rendez_vous rv ON c.id_creneau = rv.id_creneau
                      WHERE p.id_medecin = :id_medecin
                      AND p.date_planning = :date
                      AND (rv.id_rdv IS NULL OR rv.statut IN ('annule'))
                      AND c.statut = 'disponible'
                      ORDER BY c.heure_debut ASC";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id_medecin', $id_medecin, PDO::PARAM_INT);
            $stmt->bindParam(':date', $date);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur SQL dans getAvailableSlots: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Crée automatiquement des créneaux pour un médecin sur une période.
     * @param int $id_medecin ID du médecin
     * @param string $date_debut Format: YYYY-MM-DD
     * @param string $date_fin Format: YYYY-MM-DD
     * @param string $heure_debut Format: HH:MM:SS
     * @param string $heure_fin Format: HH:MM:SS
     * @param int $duree_creneau Durée en minutes
     * @return bool
     */
    public function createSlotsForPeriod($id_medecin, $date_debut, $date_fin, $heure_debut, $heure_fin, $duree_creneau) {
        try {
            // Validation des paramètres
            if (!filter_var($id_medecin, FILTER_VALIDATE_INT) ||
                !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_debut) ||
                !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_fin) ||
                !preg_match('/^\d{2}:\d{2}:\d{2}$/', $heure_debut) ||
                !preg_match('/^\d{2}:\d{2}:\d{2}$/', $heure_fin) ||
                !filter_var($duree_creneau, FILTER_VALIDATE_INT)) {
                error_log("Paramètres invalides pour createSlotsForPeriod.");
                return false;
            }

            $this->conn->beginTransaction();

            $current_date = $date_debut;
            $end_date = $date_fin;

            while (strtotime($current_date) <= strtotime($end_date)) {
                // Vérifie si un planning existe déjà pour cette date
                $query = "SELECT id_planning FROM planning
                          WHERE id_medecin = :id_medecin AND date_planning = :date_planning";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':id_medecin', $id_medecin, PDO::PARAM_INT);
                $stmt->bindParam(':date_planning', $current_date);
                $stmt->execute();
                $planning = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$planning) {
                    // Crée un nouveau planning
                    $query = "INSERT INTO planning (id_medecin, date_planning, heure_debut, heure_fin, duree_creneau)
                              VALUES (:id_medecin, :date_planning, :heure_debut, :heure_fin, :duree_creneau)";
                    $stmt = $this->conn->prepare($query);
                    $stmt->bindParam(':id_medecin', $id_medecin, PDO::PARAM_INT);
                    $stmt->bindParam(':date_planning', $current_date);
                    $stmt->bindParam(':heure_debut', $heure_debut);
                    $stmt->bindParam(':heure_fin', $heure_fin);
                    $stmt->bindParam(':duree_creneau', $duree_creneau, PDO::PARAM_INT);
                    $stmt->execute();
                    $planning_id = $this->conn->lastInsertId();
                } else {
                    $planning_id = $planning['id_planning'];
                }

                // Supprime les anciens créneaux pour cette date (optionnel)
                $query = "DELETE FROM creneau WHERE id_planning = :id_planning";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':id_planning', $planning_id, PDO::PARAM_INT);
                $stmt->execute();

                // Crée les nouveaux créneaux
                $current_time = $heure_debut;
                $end_time = $heure_fin;

                while (strtotime($current_time) < strtotime($end_time)) {
                    $next_time = date('H:i:s', strtotime($current_time) + ($duree_creneau * 60));

                    $query = "INSERT INTO creneau (id_planning, heure_debut, heure_fin, statut)
                              VALUES (:id_planning, :heure_debut, :heure_fin, 'disponible')";
                    $stmt = $this->conn->prepare($query);
                    $stmt->bindParam(':id_planning', $planning_id, PDO::PARAM_INT);
                    $stmt->bindParam(':heure_debut', $current_time);
                    $stmt->bindParam(':heure_fin', $next_time);
                    $stmt->execute();

                    $current_time = $next_time;
                }

                // Passe à la date suivante
                $current_date = date('Y-m-d', strtotime($current_date . ' +1 day'));
            }

            $this->conn->commit();
            return true;
        } catch (PDOException $e) {
            $this->conn->rollBack();
            error_log("Erreur dans createSlotsForPeriod: " . $e->getMessage());
            return false;
        }
    }
}
?>
