<?php
// controllers/UserController.php
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Auth.php';
require_once __DIR__ . '/../models/Admin.php';

class UserController {
    // Afficher le formulaire de création d'utilisateur
    public function showCreateUser() {
        if (!Auth::isLoggedIn() || !isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin_local') {
            header('Location: /login');
            exit;
        }
        require __DIR__ . '/../views/admin/create_user.php';
    }

    // Créer un utilisateur (US-03)
    public function createUser() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = $_POST['email'];
            $password = $_POST['password'];
            $role = $_POST['role'];

            try {
                if (User::create($email, $password, $role)) {
                    $success = "Utilisateur créé avec succès !";
                } else {
                    $error = "Erreur lors de la création de l'utilisateur.";
                }
            } catch (Exception $e) {
                $error = $e->getMessage();
            }

            require __DIR__ . '/../views/admin/create_user.php';
        } else {
            $this->showCreateUser();
        }
    }
}
?>