<?php
// =========================================================
// Models/MedecinModel.php
// Gestion des médecins + agendas
// =========================================================
require_once __DIR__ . './../database/connexion_database.php';

class MedecinModel
{
    private PDO $db;
    private string $cle = 'CléCabinetMédical2024!';

    public function __construct()
    {
        $this->db = getConnexion();
    }

    // ─── Recherche médecins ───────────────────────────────────

    /**
     * Recherche un médecin par nom/prénom déchiffré
     */
    public function rechercher(string $terme): array
    {
        // On lit tous (déchiffrement côté PHP car LIKE ne fonctionne pas sur BLOB)
        $stmt = $this->db->prepare(
            "SELECT id_medecin, id_utilisateur, genre,
                    AES_DECRYPT(nom,    :cle) AS nom,
                    AES_DECRYPT(prenom, :cle) AS prenom,
                    AES_DECRYPT(email_pro, :cle) AS email_pro,
                    AES_DECRYPT(telephone, :cle) AS telephone
             FROM medecins"
        );
        $stmt->execute([':cle' => $this->cle]);
        $tous = $stmt->fetchAll();

        $terme = mb_strtolower(trim($terme));
        return array_filter($tous, function ($m) use ($terme) {
            return str_contains(mb_strtolower($m['nom'] ?? ''), $terme)
                || str_contains(mb_strtolower($m['prenom'] ?? ''), $terme);
        });
    }

    /**
     * Retourne tous les médecins (déchiffrés)
     */
    public function listerTous(): array
    {
        $stmt = $this->db->prepare(
            "SELECT id_medecin, id_utilisateur, genre,
                    AES_DECRYPT(nom,       :cle) AS nom,
                    AES_DECRYPT(prenom,    :cle) AS prenom,
                    AES_DECRYPT(email_pro, :cle) AS email_pro,
                    AES_DECRYPT(telephone, :cle) AS telephone
             FROM medecins
             ORDER BY nom, prenom"
        );
        $stmt->execute([':cle' => $this->cle]);
        return $stmt->fetchAll();
    }

    /**
     * Trouve un médecin par son id_utilisateur
     */
    public function trouverParUtilisateur(int $idUser): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT id_medecin, id_utilisateur, genre,
                    AES_DECRYPT(nom,       :cle) AS nom,
                    AES_DECRYPT(prenom,    :cle) AS prenom,
                    AES_DECRYPT(email_pro, :cle) AS email_pro,
                    AES_DECRYPT(telephone, :cle) AS telephone
             FROM medecins WHERE id_utilisateur = :uid LIMIT 1"
        );
        $stmt->execute([':cle' => $this->cle, ':uid' => $idUser]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Récupère les spécialités d'un médecin
     */
    public function specialites(int $idMedecin): array
    {
        $stmt = $this->db->prepare(
            'SELECT s.libelle, sm.principale
             FROM specialite_medecin sm
             JOIN specialites s ON s.id_specialite = sm.id_specialite
             WHERE sm.id_medecin = :id'
        );
        $stmt->execute([':id' => $idMedecin]);
        return $stmt->fetchAll();
    }

    // ─── Planning & Créneaux ─────────────────────────────────

    /**
     * Retourne les créneaux disponibles d'un médecin pour une date
     */
    public function creneauxDisponibles(int $idMedecin, string $date): array
    {
        $stmt = $this->db->prepare(
            'SELECT c.id_creneau, c.heure_debut, c.heure_fin, c.statut
             FROM creneaux c
             JOIN planning p ON p.id_planning = c.id_planning
             WHERE p.id_medecin = :id
               AND p.date_planning = :date
               AND c.statut = \'disponible\'
             ORDER BY c.heure_debut'
        );
        $stmt->execute([':id' => $idMedecin, ':date' => $date]);
        return $stmt->fetchAll();
    }

    /**
     * Retourne l'agenda complet d'un médecin (tous statuts)
     */
    public function agendaSemaine(int $idMedecin, string $dateDebut, string $dateFin): array
    {
        $stmt = $this->db->prepare(
            'SELECT p.date_planning, c.id_creneau, c.heure_debut, c.heure_fin, c.statut,
                    r.id_rdv, r.statut AS statut_rdv,
                    pat.nom AS patient_nom, pat.prenom AS patient_prenom
             FROM planning p
             JOIN creneaux c ON c.id_planning = p.id_planning
             LEFT JOIN rendez_vous r ON r.id_creneau = c.id_creneau
             LEFT JOIN patients pat  ON pat.id = r.id_patient
             WHERE p.id_medecin = :id
               AND p.date_planning BETWEEN :debut AND :fin
             ORDER BY p.date_planning, c.heure_debut'
        );
        $stmt->execute([':id' => $idMedecin, ':debut' => $dateDebut, ':fin' => $dateFin]);
        return $stmt->fetchAll();
    }

    /**
     * Crée un planning journalier et génère automatiquement les créneaux
     */
    public function genererPlanning(int $idMedecin, string $date, string $hDebut = '08:00', string $hFin = '18:00', int $duree = 20): bool
    {
        $this->db->beginTransaction();
        try {
            // Vérifie si planning existe déjà
            $stmt = $this->db->prepare('SELECT id_planning FROM planning WHERE id_medecin = :id AND date_planning = :date');
            $stmt->execute([':id' => $idMedecin, ':date' => $date]);
            if ($stmt->fetch()) {
                $this->db->rollBack();
                return false;
            }

            // Création du planning
            $stmt = $this->db->prepare(
                'INSERT INTO planning (id_medecin, date_planning, heure_debut, heure_fin, duree_creneau)
                 VALUES (:id, :date, :hd, :hf, :dur)'
            );
            $stmt->execute([':id' => $idMedecin, ':date' => $date, ':hd' => $hDebut . ':00', ':hf' => $hFin . ':00', ':dur' => $duree]);
            $planningId = (int) $this->db->lastInsertId();

            // Génération des créneaux
            $current = strtotime($date . ' ' . $hDebut);
            $end     = strtotime($date . ' ' . $hFin);
            $stmtC   = $this->db->prepare(
                'INSERT INTO creneaux (id_planning, heure_debut, heure_fin) VALUES (:pid, :hd, :hf)'
            );

            while ($current + ($duree * 60) <= $end) {
                $stmtC->execute([
                    ':pid' => $planningId,
                    ':hd'  => date('H:i:s', $current),
                    ':hf'  => date('H:i:s', $current + $duree * 60),
                ]);
                $current += $duree * 60;
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log('Erreur genererPlanning : ' . $e->getMessage());
            return false;
        }
    }

    // ─── Statistiques mensuelles (F7) ────────────────────────

    public function statistiquesMois(int $idMedecin = null): array
    {
        $whereM = $idMedecin ? 'AND p.id_medecin = :id' : '';
        $params = [];
        if ($idMedecin) $params[':id'] = $idMedecin;

        $sql = "SELECT
                    COUNT(r.id_rdv)                                          AS total_rdv,
                    SUM(CASE WHEN r.statut = 'termine'  THEN 1 ELSE 0 END)  AS patients_consultes,
                    SUM(CASE WHEN r.statut = 'annule'   THEN 1 ELSE 0 END)  AS rdv_annules,
                    SUM(CASE WHEN r.statut = 'absent'   THEN 1 ELSE 0 END)  AS rdv_absents,
                    AVG(TIMESTAMPDIFF(MINUTE, c.heure_debut, c.heure_fin))  AS duree_moyenne
                FROM rendez_vous r
                JOIN creneaux c    ON c.id_creneau   = r.id_creneau
                JOIN planning p    ON p.id_planning   = c.id_planning
                WHERE MONTH(p.date_planning) = MONTH(CURDATE())
                  AND YEAR(p.date_planning)  = YEAR(CURDATE())
                $whereM";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch() ?: [];
    }
}
