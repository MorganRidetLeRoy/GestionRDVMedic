<?php
class Statistique {
    private $conn;
    private $table_avis = "avis_patient";
    private $table_rdv = "rendez_vous";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getAvisParJour($date, $medecin_id = null) {
        $query = "
            SELECT
                SUM(CASE WHEN ap.satisfaction = 'satisfait' THEN 1 ELSE 0 END) as satisfaits,
                SUM(CASE WHEN ap.satisfaction = 'insatisfait' THEN 1 ELSE 0 END) as insatisfaits,
                COUNT(ap.id_avis) as totalAvis,
                (
                    SELECT COUNT(*)
                    FROM " . $this->table_rdv . " r
                    JOIN creneau c ON r.id_creneau = c.id_creneau
                    JOIN planning p ON c.id_planning = p.id_planning
                    WHERE DATE(c.heure_debut) = :date
                    " . ($medecin_id ? " AND p.id_medecin = :medecin_id" : "") . "
                ) -
                COUNT(ap.id_avis) as avisManquants
            FROM " . $this->table_avis . " ap
            JOIN " . $this->table_rdv . " r ON ap.id_rdv = r.id_rdv
            JOIN creneau c ON r.id_creneau = c.id_creneau
            JOIN planning p ON c.id_planning = p.id_planning
            WHERE DATE(c.heure_debut) = :date
            " . ($medecin_id ? " AND p.id_medecin = :medecin_id" : "");

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':date', $date);
        if ($medecin_id) {
            $stmt->bindParam(':medecin_id', $medecin_id);
        }
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>
