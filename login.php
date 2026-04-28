<?php
// login.php
require_once './database/connexion_database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role = $_POST['role'] ?? '';
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($role) || empty($username) || empty($password)) {
        die("Tous les champs sont obligatoires.");
    }

    try {
        $pdo = getConnexion();
        $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE username = :username AND role = :role");
        $stmt->execute(['username' => $username, 'role' => $role]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Redirection selon le rôle
            switch ($role) {
                case 'secretaire':
                    header('Location: ./Views/vueSecretaire.html');
                    exit();
                case 'medecin':
                    header('Location: ./Views/vueMedecin.php');
                    exit();
                case 'administrateur':
                    header('Location: ./Views/vueAdmin.php');
                    exit();
                default:
                    header('Location: index.php');
                    exit();
            }
        } else {
            echo "Identifiants incorrects.";
        }
    } catch (PDOException $e) {
        die("Erreur de connexion : " . $e->getMessage());
    }
}
?>
