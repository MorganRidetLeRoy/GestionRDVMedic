<?php
// app/Models/Medecin.php
require_once __DIR__ . '/../../config/database.php';

class Medecin {
    private PDO $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    /**
     * Recherche des médecins par nom, prénom ou email.
     * @param string $terme Terme de recherche (ex: "%Dupond%")
     * @return array Liste des médecins correspondants
     */
    public function rechercherMedecins(string $terme): array {
        $key = AES_KEY;

        $sql = "SELECT
                    id_medecin,
                    CAST(AES_DECRYPT(nom, :key) AS CHAR) AS nom,
                    CAST(AES_DECRYPT(prenom, :key) AS CHAR) AS prenom,
                    CAST(AES_DECRYPT(email_pro, :key) AS CHAR) AS email_pro,
                    genre
                FROM medecin
                WHERE AES_DECRYPT(nom, :key) LIKE :terme
                   OR AES_DECRYPT(prenom, :key) LIKE :terme
                   OR AES_DECRYPT(email_pro, :key) LIKE :terme
                ORDER BY nom ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':key', $key, PDO::PARAM_STR);
        $stmt->bindValue(':terme', $terme, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère un médecin par son ID.
     * @param int $id ID du médecin
     * @return array|false
     */
    public function getMedecinParId(int $id) {
        $key = AES_KEY;

        $sql = "SELECT
                    id_medecin,
                    CAST(AES_DECRYPT(nom, :key) AS CHAR) AS nom,
                    CAST(AES_DECRYPT(prenom, :key) AS CHAR) AS prenom,
                    CAST(AES_DECRYPT(email_pro, :key) AS CHAR) AS email_pro,
                    genre
                FROM medecin
                WHERE id_medecin = :id";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':key', $key, PDO::PARAM_STR);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>
