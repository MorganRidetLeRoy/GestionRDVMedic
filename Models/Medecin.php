<?php
class Medecin {
    private $conn;
    private $table = "medecin";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getAll() {
        $query = "SELECT id_medecin, nom, prenom FROM " . $this->table;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getMedecinsLesPlusSollicites($date) {
        $query = "
            SELECT m.id_medecin, CONCAT(m.prenom, ' ', m.nom) as nom_complet, COUNT(r.id_rdv) as nombre_rdv
            FROM " . $this->table . " m
            LEFT JOIN planning p ON m.id_medecin = p.id_medecin
            LEFT JOIN creneau c ON p.id_planning = c.id_planning
            LEFT JOIN rendez_vous r ON c.id_creneau = r.id_creneau
            WHERE DATE(c.heure_debut) = :date
            GROUP BY m.id_medecin
            ORDER BY nombre_rdv DESC
            LIMIT 3";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':date', $date);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
