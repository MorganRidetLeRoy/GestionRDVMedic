<?php

//================================================================
// Cette page est pour prendre les rendez vous.
// Les rendez vous sont directment pris par les secrétaires
//================================================================

require_Once __DIR__ . '/';

class RDV
{
    private PDO $db
    public function __construct()
    {
        // Le  model obtient sa propre connexion PDO
        $this->db = getConnexion();
    }

    //________________________________
    // Read -- Lecture des données
    //________________________________

    /**
     * retourne tous les rendez vous avec leur catégorie.
     * La jointure LEFT JOIN conserve les rendez vous sans catégorie
     */

    public function findAll(): array
    {
        // Requête SQL pour voir tous les médecins et rendez vous
        //$sql = "SELECT "

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id => $id']);
        return $stmt->fetch();
    }

    public function getRendezVousParJour($date, $medecin_id = null) {
        $query = "
            SELECT
                COUNT(*) as totalRDV,
                SUM(CASE WHEN statut = 'termine' THEN 1 ELSE 0 END) as rdvRealises,
                SUM(CASE WHEN statut = 'annule' THEN 1 ELSE 0 END) as rdvAnnules,
                SUM(CASE WHEN statut = 'absent' THEN 1 ELSE 0 END) as rdvAbsents
            FROM " . $this->table . " r
            JOIN creneau c ON r.id_creneau = c.id_creneau
            JOIN planning p ON c.id_planning = p.id_planning
            WHERE DATE(c.heure_debut) = :date" .
            ($medecin_id ? " AND p.id_medecin = :medecin_id" : "");

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':date', $date);
        if ($medecin_id) {
            $stmt->bindParam(':medecin_id', $medecin_id);
        }
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getRendezVousParJourCompare($date, $compare_date, $medecin_id = null) {
        $query = "
            SELECT
                DATE(c.heure_debut) as jour,
                COUNT(*) as totalRDV,
                SUM(CASE WHEN r.statut = 'termine' THEN 1 ELSE 0 END) as rdvRealises
            FROM " . $this->table . " r
            JOIN creneau c ON r.id_creneau = c.id_creneau
            JOIN planning p ON c.id_planning = p.id_planning
            WHERE DATE(c.heure_debut) IN (:date, :compare_date)" .
            ($medecin_id ? " AND p.id_medecin = :medecin_id" : "") .
            " GROUP BY DATE(c.heure_debut)";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':date', $date);
        $stmt->bindParam(':compare_date', $compare_date);
        if ($medecin_id) {
            $stmt->bindParam(':medecin_id', $medecin_id);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTempsMoyenConsultation($date, $medecin_id = null) {
        $query = "
            SELECT AVG(TIMESTAMPDIFF(MINUTE, c.heure_debut, c.heure_fin)) as tempsMoyen
            FROM " . $this->table . " r
            JOIN creneau c ON r.id_creneau = c.id_creneau
            JOIN planning p ON c.id_planning = p.id_planning
            WHERE DATE(c.heure_debut) = :date
            AND r.statut = 'termine'" .
            ($medecin_id ? " AND p.id_medecin = :medecin_id" : "");

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':date', $date);
        if ($medecin_id) {
            $stmt->bindParam(':medecin_id', $medecin_id);
        }
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['tempsMoyen'] ?? 0;
    }

    // SÉCURITÉ : Race Condition - Exemple de réservation sécurisée
    public function reserverCreneau($id_creneau, $id_motif) {
        try {
            $this->conn->beginTransaction(); // SÉCURITÉ : Début de la transaction

            // Vérifier la disponibilité du créneau (avec verrou)
            $query = "
                SELECT id_creneau FROM creneau
                WHERE id_creneau = :id_creneau AND statut = 'disponible'
                FOR UPDATE"; // SÉCURITÉ : Verrouillage de la ligne
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id_creneau', $id_creneau);
            $stmt->execute();

            if ($stmt->rowCount() === 0) {
                throw new Exception("Créneau non disponible.");
            }

            // Réserver le créneau
            $query = "
                INSERT INTO rendez_vous (id_creneau, id_motif, statut)
                VALUES (:id_creneau, :id_motif, 'confirme')";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id_creneau', $id_creneau);
            $stmt->bindParam(':id_motif', $id_motif);
            $stmt->execute();

            // Mettre à jour le statut du créneau
            $query = "
                UPDATE creneau
                SET statut = 'reserve'
                WHERE id_creneau = :id_creneau";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id_creneau', $id_creneau);
            $stmt->execute();

            $this->conn->commit(); // SÉCURITÉ : Validation de la transaction
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack(); // SÉCURITÉ : Annulation en cas d'erreur
            throw $e;
        }
    }
}


?>
