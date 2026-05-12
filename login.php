<?php
// --- FICHIER : login.php ---
// Ce fichier gère la connexion des utilisateurs (secrétaire, médecin, administrateur)
// en vérifiant leurs identifiants dans la base de données.
// Si les identifiants sont corrects, l'utilisateur est redirigé vers une page spécifique en fonction de son rôle.

// Inclusion du fichier de connexion à la base de données
// Ce fichier contient la fonction getConnexion() qui retourne un objet PDO pour interagir avec la base de données.
require_once './database/connexion_database.php';

// --- VÉRIFICATION DE LA MÉTHODE DE LA REQUÊTE ---
// On vérifie si la requête HTTP est de type POST (formulaire soumis).
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // --- RÉCUPÉRATION DES DONNÉES DU FORMULAIRE ---
    // On récupère les valeurs des champs 'role', 'username' et 'password' depuis $_POST.
    // L'opérateur ?? permet de définir une valeur par défaut (chaîne vide) si le champ n'existe pas.
    $role = $_POST['role'] ?? '';
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // --- VALIDATION DES CHAMPS OBLIGATOIRES ---
    // Si l'un des champs est vide, on arrête l'exécution et on affiche un message d'erreur.
    if (empty($role) || empty($username) || empty($password)) {
        die("Tous les champs sont obligatoires.");
    }

    try {
        // --- CONNEXION À LA BASE DE DONNÉES ---
        // On récupère une instance PDO pour interagir avec la base de données.
        $pdo = getConnexion();

        // --- REQUÊTE SQL POUR RÉCUPÉRER L'UTILISATEUR ---
        // On prépare une requête SQL pour sélectionner l'utilisateur dont le nom d'utilisateur et le rôle correspondent.
        // Utilisation de requêtes préparées pour éviter les injections SQL.
        $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE username = :username AND role = :role");

        // Exécution de la requête avec les paramètres fournis par l'utilisateur.
        $stmt->execute(['username' => $username, 'role' => $role]);

        // Récupération de l'utilisateur sous forme de tableau associatif.
        $user = $stmt->fetch();

        // --- VÉRIFICATION DES IDENTIFIANTS ---
        // Si un utilisateur est trouvé ET que le mot de passe saisi correspond au mot de passe haché en base de données,
        // alors les identifiants sont corrects.
        if ($user && password_verify($password, $user['password'])) {

            // --- REDIRECTION EN FONCTION DU RÔLE ---
            // Selon le rôle de l'utilisateur, on le redirige vers une page spécifique.
            // La redirection se fait via le fichier routeur.php, qui gérera l'action demandée.
            switch ($role) {
                case 'secretaire':
                    // Redirection vers la page d'accueil pour les secrétaires.
                    header('Location: routeur.php?action=index');
                    exit(); // Arrête l'exécution du script après la redirection.

                case 'medecin':
                    // Redirection vers la vue spécifique pour les médecins.
                    header('Location: routeur.php?action=vue_medecin');
                    exit();

                case 'administrateur':
                    // Redirection vers la vue spécifique pour les administrateurs.
                    header('Location: routeur.php?action=vue_admin');
                    exit();

                default:
                    // Cas par défaut : redirection vers la page d'accueil.
                    header('Location: routeur.php?action=index');
                    exit();
            }

        } else {
            // Si les identifiants sont incorrects, on affiche un message d'erreur.
            echo "Identifiants incorrects.";
        }

    } catch (PDOException $e) {
        // En cas d'erreur avec la base de données, on affiche un message et on arrête l'exécution.
        die("Erreur de connexion : " . $e->getMessage());
    }
}
?>
