<?php
// =========================================================
// Models/RendezVousModel.php
// Gestion des rendez-vous — F1-F8 (gestion RDV)
// Protection race condition via transactions + FOR UPDATE
// =========================================================
require_once __DIR__ . './../database/connexion_database.php';

class RendezVousModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = getConnexion();
    }

    // ─── F1/F2 : Créer un RDV (sécurisé anti-race condition) ─

    /**
     * Réserve un créneau et crée le RDV atomiquement
     * Protection RACE CONDITION : FOR UPDATE + transaction
     */
    public function creer(int $idCreneau, int $idPatient, ?int $idMotif = null, ?string $notes = null): array
    {
        try {
            $this->db->beginTransaction();

            // Verrouillage de la ligne — empêche toute réservation simultanée
            $stmt = $this->db->prepare(
                "SELECT id_creneau, statut
                 FROM creneaux
                 WHERE id_creneau = :id AND statut = 'disponible'
                 FOR UPDATE"
            );
            $stmt->execute([':id' => $idCreneau]);
            if ($stmt->rowCount() === 0) {
                $this->db->rollBack();
                return ['succes' => false, 'message' => 'Ce créneau n\'est plus disponible.'];
            }

            // Insertion du RDV
            $stmtRdv = $this->db->prepare(
                'INSERT INTO rendez_vous (id_creneau, id_patient, id_motif, statut)
                 VALUES (:creneau, :patient, :motif, \'confirme\')'
            );
            $stmtRdv->execute([
                ':creneau' => $idCreneau,
                ':patient' => $idPatient,
                ':motif'   => $idMotif,
            ]);

            // Mise à jour du statut du créneau
            $this->db->prepare("UPDATE creneaux SET statut = 'reserve' WHERE id_creneau = :id")
                     ->execute([':id' => $idCreneau]);

            $idRdv = (int) $this->db->lastInsertId();
            $this->db->commit();

            return ['succes' => true, 'message' => 'Rendez-vous créé avec succès.', 'id_rdv' => $idRdv];

        } catch (Exception $e) {
            $this->db->rollBack();
            error_log('Erreur creer RDV : ' . $e->getMessage());
            return ['succes' => false, 'message' => 'Erreur lors de la création du rendez-vous.'];
        }
    }

    // ─── F3 : Modifier un RDV ────────────────────────────────

    /**
     * Modifie la date/heure (créneau) d'un RDV existant
     */
    public function modifier(int $idRdv, int $nouveauCreneau): array
    {
        try {
            $this->db->beginTransaction();

            // Récupérer l'ancien créneau
            $stmt = $this->db->prepare('SELECT id_creneau FROM rendez_vous WHERE id_rdv = :id FOR UPDATE');
            $stmt->execute([':id' => $idRdv]);
            $rdv = $stmt->fetch();
            if (!$rdv) {
                $this->db->rollBack();
                return ['succes' => false, 'message' => 'Rendez-vous introuvable.'];
            }
            $ancienCreneau = $rdv['id_creneau'];

            // Vérifier nouveau créneau disponible
            $stmt = $this->db->prepare(
                "SELECT id_creneau FROM creneaux WHERE id_creneau = :id AND statut = 'disponible' FOR UPDATE"
            );
            $stmt->execute([':id' => $nouveauCreneau]);
            if ($stmt->rowCount() === 0) {
                $this->db->rollBack();
                return ['succes' => false, 'message' => 'Le nouveau créneau n\'est pas disponible.'];
            }

            // Libérer l'ancien créneau
            $this->db->prepare("UPDATE creneaux SET statut = 'disponible' WHERE id_creneau = :id")
                     ->execute([':id' => $ancienCreneau]);

            // Réserver le nouveau
            $this->db->prepare("UPDATE creneaux SET statut = 'reserve' WHERE id_creneau = :id")
                     ->execute([':id' => $nouveauCreneau]);

            // Modifier le RDV
            $this->db->prepare('UPDATE rendez_vous SET id_creneau = :c WHERE id_rdv = :id')
                     ->execute([':c' => $nouveauCreneau, ':id' => $idRdv]);

            $this->db->commit();
            return ['succes' => true, 'message' => 'Rendez-vous modifié.'];

        } catch (Exception $e) {
            $this->db->rollBack();
            error_log('Erreur modifier RDV : ' . $e->getMessage());
            return ['succes' => false, 'message' => 'Erreur lors de la modification.'];
        }
    }

    // ─── F4 : Annuler un RDV ─────────────────────────────────

    public function annuler(int $idRdv): array
    {
        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare('SELECT id_creneau FROM rendez_vous WHERE id_rdv = :id FOR UPDATE');
            $stmt->execute([':id' => $idRdv]);
            $rdv = $stmt->fetch();
            if (!$rdv) {
                $this->db->rollBack();
                return ['succes' => false, 'message' => 'Rendez-vous introuvable.'];
            }

            $this->db->prepare("UPDATE rendez_vous SET statut = 'annule' WHERE id_rdv = :id")
                     ->execute([':id' => $idRdv]);
            $this->db->prepare("UPDATE creneaux SET statut = 'disponible' WHERE id_creneau = :id")
                     ->execute([':id' => $rdv['id_creneau']]);

            $this->db->commit();
            return ['succes' => true, 'message' => 'Rendez-vous annulé.'];

        } catch (Exception $e) {
            $this->db->rollBack();
            error_log('Erreur annuler RDV : ' . $e->getMessage());
            return ['succes' => false, 'message' => 'Erreur lors de l\'annulation.'];
        }
    }

    // ─── F6 : Consulter l'agenda d'un praticien ──────────────

    public function rdvParMedecin(int $idMedecin, string $dateDebut, string $dateFin): array
    {
        $stmt = $this->db->prepare(
            'SELECT r.id_rdv, r.statut, r.date_creation,
                    c.heure_debut, c.heure_fin,
                    p.date_planning,
                    pat.id AS patient_id, pat.nom AS patient_nom, pat.prenom AS patient_prenom,
                    m.libelle AS motif
             FROM rendez_vous r
             JOIN creneaux c   ON c.id_creneau = r.id_creneau
             JOIN planning pl  ON pl.id_planning = c.id_planning
             JOIN planning p   ON p.id_planning = c.id_planning
             LEFT JOIN patients pat ON pat.id = r.id_patient
             LEFT JOIN motifs m     ON m.id_motif = r.id_motif
             WHERE pl.id_medecin = :id
               AND p.date_planning BETWEEN :debut AND :fin
               AND r.statut != \'annule\'
             ORDER BY p.date_planning, c.heure_debut'
        );
        $stmt->execute([':id' => $idMedecin, ':debut' => $dateDebut, ':fin' => $dateFin]);
        return $stmt->fetchAll();
    }

    // ─── F7 : RDV d'un patient ───────────────────────────────

    public function rdvParPatient(int $idPatient): array
    {
        $stmt = $this->db->prepare(
            'SELECT r.id_rdv, r.statut, r.date_creation,
                    c.heure_debut, c.heure_fin,
                    p.date_planning,
                    AES_DECRYPT(med.nom,    \'CléCabinetMédical2024!\') AS medecin_nom,
                    AES_DECRYPT(med.prenom, \'CléCabinetMédical2024!\') AS medecin_prenom,
                    m.libelle AS motif
             FROM rendez_vous r
             JOIN creneaux c     ON c.id_creneau = r.id_creneau
             JOIN planning p     ON p.id_planning = c.id_planning
             JOIN medecins med   ON med.id_medecin = p.id_medecin
             LEFT JOIN motifs m  ON m.id_motif = r.id_motif
             WHERE r.id_patient = :id
             ORDER BY p.date_planning DESC, c.heure_debut DESC'
        );
        $stmt->execute([':id' => $idPatient]);
        return $stmt->fetchAll();
    }

    // ─── Statistiques (F7 auth) ──────────────────────────────

    public function statistiquesMois(): array
    {
        $stmt = $this->db->query(
            "SELECT
                COUNT(r.id_rdv)                                         AS total_rdv,
                SUM(CASE WHEN r.statut = 'termine'  THEN 1 ELSE 0 END) AS consultes,
                SUM(CASE WHEN r.statut = 'annule'   THEN 1 ELSE 0 END) AS annules,
                SUM(CASE WHEN r.statut = 'absent'   THEN 1 ELSE 0 END) AS absents,
                ROUND(AVG(TIMESTAMPDIFF(MINUTE, c.heure_debut, c.heure_fin)), 0) AS duree_moy
             FROM rendez_vous r
             JOIN creneaux c  ON c.id_creneau = r.id_creneau
             JOIN planning p  ON p.id_planning = c.id_planning
             WHERE MONTH(p.date_planning) = MONTH(CURDATE())
               AND YEAR(p.date_planning)  = YEAR(CURDATE())"
        );
        return $stmt->fetch() ?: [];
    }

    public function trouverParId(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT r.*, c.heure_debut, c.heure_fin, p.date_planning, p.id_medecin
             FROM rendez_vous r
             JOIN creneaux c ON c.id_creneau = r.id_creneau
             JOIN planning p ON p.id_planning = c.id_planning
             WHERE r.id_rdv = :id LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }
}
