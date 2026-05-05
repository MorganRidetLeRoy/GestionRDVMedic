<?php
// controllers/PasswordController.php
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Patient.php';
require_once __DIR__ . '/../models/Auth.php';

class PasswordController {
    // Vérifie que l'utilisateur est connecté
    private function checkLoggedIn() {
        if (!Auth::isLoggedIn()) {
            header('Location: /login');
            exit;
        }
    }

    // Affiche le formulaire de modification du mot de passe
    public function showResetPasswordForm() {
        $this->checkLoggedIn();
        require __DIR__ . '/../views/auth/reset_password.php';
    }

    // Modifie le mot de passe (US-08)
    public function resetPassword() {
        $this->checkLoggedIn();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $currentPassword = $_POST['current_password'] ?? null;
            $newPassword = $_POST['new_password'];
            $confirmPassword = $_POST['confirm_password'];

            // Validation des champs
            if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
                $error = "Tous les champs sont obligatoires.";
                require __DIR__ . '/../views/auth/reset_password.php';
                return;
            }

            // Vérifie que les nouveaux mots de passe correspondent
            if ($newPassword !== $confirmPassword) {
                $error = "Les nouveaux mots de passe ne correspondent pas.";
                require __DIR__ . '/../views/auth/reset_password.php';
                return;
            }

            // Récupère l'utilisateur ou le patient connecté
            $user = null;
            $isPatient = false;
            if (isset($_SESSION['user'])) {
                $user = User::findByEmail($_SESSION['user']['email']);
            } elseif (isset($_SESSION['patient'])) {
                $user = Patient::findByEmail($_SESSION['patient']['email']);
                $isPatient = true;
            }

            // Vérifie le mot de passe actuel
            if (!$user || !password_verify($currentPassword, $user->getPassword())) {
                $error = "Le mot de passe actuel est incorrect.";
                require __DIR__ . '/../views/auth/reset_password.php';
                return;
            }

            // Met à jour le mot de passe
            if ($isPatient) {
                $user->updatePassword($newPassword);
                // Met à jour le flag temporary_password
                $user->setTemporaryPassword(false);
            } else {
                $user->updatePassword($newPassword);
            }

            $success = "Votre mot de passe a été modifié avec succès !";
            require __DIR__ . '/../views/auth/reset_password.php';
        } else {
            $this->showResetPasswordForm();
        }
    }
}
?>