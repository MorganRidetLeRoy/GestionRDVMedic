<?php
// models/Auth.php
require_once __DIR__ . '/User.php';
require_once __DIR__ . '/Patient.php';

class Auth {
    // Connexion (US-01, US-02)
    public static function login($email, $password) {
        $user = User::findByEmail($email);
        if ($user && password_verify($password, $user->getPassword())) {
            session_start();
            $_SESSION['user'] = [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'role' => $user->getRole()
            ];
            $user->updateLastActivity();
            return true;
        }

        $patient = Patient::findByEmail($email);
        if ($patient && password_verify($password, $patient->getPassword())) {
            session_start();
            $_SESSION['patient'] = [
                'id' => $patient->getId(),
                'email' => $patient->getEmail(),
                'first_name' => $patient->getFirstName(),
                'last_name' => $patient->getLastName()
            ];
            return true;
        }

        return false;
    }

    // Déconnexion
    public static function logout() {
        session_start();
        session_unset();
        session_destroy();
        return true;
    }

    // Vérifier si l'utilisateur est connecté
    public static function isLoggedIn() {
        session_start();
        return isset($_SESSION['user']) || isset($_SESSION['patient']);
    }

    // Vérifier le rôle de l'utilisateur
    public static function isAdmin() {
        session_start();
        return isset($_SESSION['user']) && $_SESSION['user']['role'] === 'admin_local';
    }

    // Vérifier si le patient a un mot de passe temporaire (US-05)
    public static function hasTemporaryPassword($patientId) {
        $patient = Patient::findByEmail($_SESSION['patient']['email']);
        return $patient && $patient->isTemporaryPassword();
    }
}
?>