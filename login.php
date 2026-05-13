<?php
/**
 * =============================================
 * FICHIER: login.php (Version Sécurisée)
 * RÔLE: Gère la connexion des utilisateurs (secrétaire, médecin, administrateur)
 *       en vérifiant leurs identifiants dans la base de données.
 *       Si les identifiants sont corrects, l'utilisateur est redirigé vers une page spécifique en fonction de son rôle.
 *
 * STRATÉGIES DE SÉCURITÉ:
 * - Protection contre les injections SQL (PDO + requêtes préparées).
 * - Protection contre CSRF (token unique par session).
 * - Masquage des erreurs sensibles (logs sécurisés).
 * - Limitation des tentatives de connexion (anti Brute Force).
 * - Régénération de l'ID de session (anti Session Fixation).
 * - Vérification des entrées utilisateur.
 * =============================================
 */

// --- DÉMARRAGE DE LA SESSION (À PLACER AU DÉBUT) ---
// session_start() : Démarre ou reprend une session existante.
// Configuration des cookies de session pour une sécurité optimale.
session_set_cookie_params([
    'lifetime' => 3600,       // Durée de vie : 1 heure
    'path' => '/',
    'domain' => '',
    'secure' => true,         // HTTPS uniquement
    'httponly' => true,       // Inaccessible via JavaScript (protection XSS)
    'samesite' => 'Strict'    // Protection contre CSRF
]);
session_start();

// --- VÉRIFICATION DE LA MÉTHODE DE LA REQUÊTE HTTP ---
// Si la requête n'est pas de type POST, on arrête l'exécution.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Méthode non autorisée.");
}

// --- VÉRIFICATION DU TOKEN CSRF ---
// Vérifie si le token CSRF existe et correspond à celui stocké en session.
// $_SESSION['csrf_token'] ?? '' : Opérateur Null Coalescing (retourne '' si la clé n'existe pas).
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
    die("Erreur CSRF : Requête invalide.");
}

// --- RÉCUPÉRATION ET VALIDATION DES DONNÉES DU FORMULAIRE ---
// filter_input() : Filtre et valide les entrées utilisateur.
// FILTER_SANITIZE_STRING : Supprime les balises HTML et les caractères spéciaux.
// FILTER_SANITIZE_SPECIAL_CHARS : Alternative pour échapper les caractères spéciaux.
$role = filter_input(INPUT_POST, 'role', FILTER_SANITIZE_STRING) ?? '';
$username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING) ?? '';
$password = $_POST['password'] ?? ''; // Ne pas sanitize le mot de passe (hashage nécessaire)

// --- VALIDATION DES CHAMPS OBLIGATOIRES ---
if (empty($role) || empty($username) || empty($password)) {
    die("Tous les champs sont obligatoires.");
}

// --- VALIDATION DE LA COMPLEXITÉ DU MOT DE PASSE (Optionnel) ---
if (strlen($password) < 8) {
    die("Le mot de passe doit contenir au moins 8 caractères.");
}

// --- GESTION DES TENTATIVES DE CONNEXION (Anti Brute Force) ---
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
}

// Si le nombre de tentatives dépasse 5, bloquer temporairement.
if ($_SESSION['login_attempts'] >= 5) {
    die("Trop de tentatives de connexion. Veuillez réessayer dans 15 minutes.");
}

try {
    // --- CONNEXION À LA BASE DE DONNÉES ---
    require_once './database/connexion_database.php';
    $pdo = getConnexion();

    // --- REQUÊTE SQL PRÉPARÉE (Protection contre les injections SQL) ---
    // Utilisation de paramètres nommés (:username, :role) pour éviter les injections.
    $stmt = $pdo->prepare("SELECT id, username, password, role FROM utilisateurs WHERE username = :username AND role = :role");
    $stmt->execute(['username' => $username, 'role' => $role]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // --- VÉRIFICATION DES IDENTIFIANTS ---
    // password_verify() : Vérifie si le mot de passe correspond au hash stocké en base.
    if ($user && password_verify($password, $user['password'])) {
        // --- CONNEXION RÉUSSIE ---
        // Réinitialise le compteur de tentatives.
        $_SESSION['login_attempts'] = 0;

        // Régénère l'ID de session pour éviter les attaques de fixation de session.
        session_regenerate_id(true);

        // Stocke les informations de l'utilisateur en session.
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];

        // --- REDIRECTION EN FONCTION DU RÔLE ---
        switch ($role) {
            case 'secretaire':
                header('Location: routeur.php?action=index');
                exit();
            case 'medecin':
                header('Location: routeur.php?action=vue_medecin');
                exit();
            case 'administrateur':
                header('Location: routeur.php?action=vue_admin');
                exit();
            default:
                header('Location: routeur.php?action=index');
                exit();
        }
    } else {
        // --- IDENTIFIANTS INCORRECTS ---
        $_SESSION['login_attempts']++; // Incrémente le compteur de tentatives.
        die("Identifiants incorrects.");
    }

} catch (PDOException $e) {
    // --- GESTION DES ERREURS (Sécurisée) ---
    // Enregistre l'erreur dans les logs du serveur (sans l'afficher à l'utilisateur).
    error_log("Erreur de connexion dans login.php: " . $e->getMessage());
    die("Une erreur est survenue. Veuillez réessayer plus tard.");
}
?>
