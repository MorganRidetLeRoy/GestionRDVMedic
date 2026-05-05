<?php
// controllers/AdminController.php
require_once __DIR__ . '/../models/Admin.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Auth.php';

class AdminController {
    // Vérifier que l'utilisateur est un admin local
    private function checkAdmin() {
        if (!Auth::isLoggedIn() || !isset($_SESSION['user']) || !$_SESSION['user']['role'] === 'admin_local') {
            header('Location: /login');
            exit;
        }
    }

    // Afficher le tableau de bord admin
    public function showDashboard() {
        $this->checkAdmin();
        require __DIR__ . '/../views/admin/dashboard.php';
    }

    // Afficher la liste des utilisateurs actifs (US-25)
    public function showUsersList() {
        $this->checkAdmin();
        $users = Admin::getActiveUsers();
        require __DIR__ . '/../views/admin/users_list.php';
    }

    // Afficher le formulaire de désactivation d'un utilisateur (US-23)
    public function showDisableUserForm($userId) {
        $this->checkAdmin();
        $user = User::findById($userId);
        if (!$user) {
            header('Location: /admin/users');
            exit;
        }
        require __DIR__ . '/../views/admin/disable_user.php';
    }

    // Désactiver un utilisateur (US-23)
    public function disableUser($userId) {
        $this->checkAdmin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (Admin::disableUser($userId)) {
                $success = "Utilisateur désactivé avec succès.";
            } else {
                $error = "Erreur lors de la désactivation de l'utilisateur.";
            }
            $users = Admin::getActiveUsers();
            require __DIR__ . '/../views/admin/users_list.php';
        } else {
            $this->showDisableUserForm($userId);
        }
    }

    // Afficher le formulaire de réinitialisation de mot de passe (US-24)
    public function showResetPasswordForm($userId) {
        $this->checkAdmin();
        $user = User::findById($userId);
        if (!$user) {
            header('Location: /admin/users');
            exit;
        }
        require __DIR__ . '/../views/admin/reset_user_password.php';
    }

    // Réinitialiser le mot de passe d'un utilisateur (US-24)
    public function resetUserPassword($userId) {
        $this->checkAdmin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $user = User::findById($userId);
            if ($user) {
                $newPassword = $user->resetPassword();
                $success = "Mot de passe réinitialisé avec succès. Le nouvel identifiant a été envoyé par email.";
            } else {
                $error = "Utilisateur introuvable.";
            }
            $users = Admin::getActiveUsers();
            require __DIR__ . '/../views/admin/users_list.php';
        } else {
            $this->showResetPasswordForm($userId);
        }
    }
}
?>