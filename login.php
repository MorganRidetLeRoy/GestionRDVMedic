<?php
// On récupère les données envoyées par le formulaire (index.php)
$role = $_POST['role'] ?? '';
$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

// Dans une application réelle, on vérifierait ici le mot de passe avec la base de données.
// Pour l'instant, nous gérons uniquement la redirection par rôle.

switch ($role) {
    case 'secretaire':
        header('Location: ./Views/vueSecretaire.html');
        exit();
    case 'medecin':
        // Vous pourrez créer vueMedecin.php plus tard
        header('Location: ......'); 
        exit();
    case 'administrateur':
        header('Location: ......');
        exit();
    default:
        header('Location: index.php');
        exit();
}
?>
