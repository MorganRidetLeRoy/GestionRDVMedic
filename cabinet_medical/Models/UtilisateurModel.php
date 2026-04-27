<?php
// =========================================================
// Models/UtilisateurModel.php
// Gestion des utilisateurs : auth, sessions, CRUD
// =========================================================
require_once __DIR__ . '/../database/connexion_database.php';

class UtilisateurModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = getConnexion();
    }

    // ─── Authentification ────────────────────────────────────

    /**
     * Vérifie login + mot de passe, retourne l'utilisateur ou null
     */
    public function authentifier(string $login, string $password): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, login, mot_de_passe, role, actif
             FROM utilisateurs
             WHERE login = :login
             LIMIT 1'
        );
        $stmt->execute([':login' => $login]);
        $user = $stmt->fetch();

        if (!$user || !$user['actif']) return null;
        if (!password_verify($password, $user['mot_de_passe'])) return null;

        return $user;
    }

    /**
     * Crée un utilisateur avec mot de passe hashé
     */
    public function creer(string $login, string $password, string $role): int
    {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->db->prepare(
            'INSERT INTO utilisateurs (login, mot_de_passe, role)
             VALUES (:login, :mdp, :role)'
        );
        $stmt->execute([':login' => $login, ':mdp' => $hash, ':role' => $role]);
        return (int) $this->db->lastInsertId();
    }

    /**
     * Récupère tous les comptes actifs (admin)
     */
    public function listerActifs(): array
    {
        $stmt = $this->db->prepare(
            'SELECT id, login, role, created_at
             FROM utilisateurs
             WHERE actif = 1 AND role != :role
             ORDER BY role, login'
        );
        $stmt->execute([':role' => 'patient']);
        return $stmt->fetchAll();
    }

    /**
     * Désactive un compte (admin)
     */
    public function desactiver(int $id): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE utilisateurs SET actif = 0 WHERE id = :id'
        );
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Réinitialise le mot de passe (admin)
     */
    public function reinitialiserMotDePasse(int $id, string $nouveauMdp): bool
    {
        $hash = password_hash($nouveauMdp, PASSWORD_DEFAULT);
        $stmt = $this->db->prepare(
            'UPDATE utilisateurs SET mot_de_passe = :mdp WHERE id = :id'
        );
        $stmt->execute([':mdp' => $hash, ':id' => $id]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Modifie son propre mot de passe
     */
    public function changerMotDePasse(int $id, string $ancienMdp, string $nouveauMdp): array
    {
        $stmt = $this->db->prepare('SELECT mot_de_passe FROM utilisateurs WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($ancienMdp, $user['mot_de_passe'])) {
            return ['succes' => false, 'message' => 'Mot de passe actuel incorrect.'];
        }

        $hash = password_hash($nouveauMdp, PASSWORD_DEFAULT);
        $stmt = $this->db->prepare('UPDATE utilisateurs SET mot_de_passe = :mdp WHERE id = :id');
        $stmt->execute([':mdp' => $hash, ':id' => $id]);
        return ['succes' => true, 'message' => 'Mot de passe modifié avec succès.'];
    }

    /**
     * Récupère un utilisateur par ID
     */
    public function trouverParId(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT id, login, role, actif FROM utilisateurs WHERE id = :id');
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }

    // ─── Sessions actives (déconnexion auto F6) ──────────────

    public function enregistrerSession(int $userId, string $token): void
    {
        $this->db->prepare(
            'INSERT INTO sessions_actives (id_user, token) VALUES (:uid, :tok)
             ON DUPLICATE KEY UPDATE derniere_act = NOW()'
        )->execute([':uid' => $userId, ':tok' => $token]);
    }

    public function actualiserSession(string $token): void
    {
        $this->db->prepare(
            'UPDATE sessions_actives SET derniere_act = NOW() WHERE token = :tok'
        )->execute([':tok' => $token]);
    }

    public function supprimerSession(string $token): void
    {
        $this->db->prepare('DELETE FROM sessions_actives WHERE token = :tok')
                 ->execute([':tok' => $token]);
    }

    /**
     * Supprime les sessions inactives depuis plus de N minutes (F6)
     */
    public function nettoyerSessionsInactives(int $minutes = 30): void
    {
        $this->db->prepare(
            'DELETE FROM sessions_actives
             WHERE derniere_act < DATE_SUB(NOW(), INTERVAL :min MINUTE)'
        )->execute([':min' => $minutes]);
    }
}
