<?php
// models/Admin.php
require_once __DIR__ . '/../models/User.php';

class Admin {
    // Vérifier qu'il n'y a qu'un seul admin local (US-26)
    public static function ensureSingleAdminLocal() {
        $adminCount = User::countAdminLocal();
        if ($adminCount > 1) {
            // Désactiver tous les admins sauf le premier
            global $pdo;
            $stmt = $pdo->prepare("
                UPDATE users
                SET role = 'praticien'
                WHERE role = 'admin_local'
                ORDER BY created_at ASC
                LIMIT 1, 1000
            ");
            $stmt->execute();
        }
    }

    // Lister les utilisateurs actifs (US-25)
    public static function getActiveUsers() {
        return User::getAllActive();
    }

    // Désactiver un utilisateur (US-23)
    public static function disableUser($userId) {
        $user = User::findById($userId);
        if ($user && $user->getRole() !== 'admin_local') {
            return $user->disable();
        }
        return false;
    }

    // Réinitialiser le mot de passe d'un utilisateur (US-24)
    public static function resetUserPassword($userId) {
        $user = User::findById($userId);
        if ($user) {
            return $user->resetPassword();
        }
        return false;
    }
}
?>