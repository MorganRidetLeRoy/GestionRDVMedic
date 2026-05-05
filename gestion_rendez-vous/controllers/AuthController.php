<?php
// controllers/AuthController.php
require_once __DIR__ . '/../models/Auth.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Patient.php';

class AuthController {
    // Afficher le formulaire de connexion
    public function showLogin() {
        require __DIR__ . '/../views/auth/login.php';
    }

    // Traiter la connexion (US-01, US-02)
    public function login() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = $_POST['email'];
            $password = $_POST['password'];

            if (Auth::login($email, $password)) {
                if (isset($_SESSION['user'])) {
                    header('Location: /dashboard');
                } else {
                    header('Location: /patient/dashboard');
                }
                exit;
            } else {
                $error = "Identifiants incorrects";
                require __DIR__ . '/../views/auth/login.php';
            }
        } else {
            $this->showLogin();
        }
    }

    // Déconnexion
    public function logout() {
        Auth::logout();
        header('Location: /login');
        exit;
    }

    // Afficher le formulaire de modification du mot de passe (US-08)
    public function showResetPassword() {
        if (!Auth::isLoggedIn()) {
            header('Location: /login');
            exit;
        }
        require __DIR__ . '/../views/auth/reset_password.php';
    }

    // Traiter la modification du mot de passe (US-08)
    public function resetPassword() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $newPassword = $_POST['new_password'];
            $confirmPassword = $_POST['confirm_password'];

            if ($newPassword !== $confirmPassword) {
                $error = "Les mots de passe ne correspondent pas";
                require __DIR__ . '/../views/auth/reset_password.php';
                return;
            }

            if (isset($_SESSION['user'])) {
                $user = User::findByEmail($_SESSION['user']['email']);
                $user->updatePassword($newPassword);
            } elseif (isset($_SESSION['patient'])) {
                $patient = Patient::findByEmail($_SESSION['patient']['email']);
                $patient->updatePassword($newPassword);
            }

            $success = "Mot de passe modifié avec succès";
            require __DIR__ . '/../views/auth/reset_password.php';
        } else {
            $this->showResetPassword();
        }
    }
}
?>