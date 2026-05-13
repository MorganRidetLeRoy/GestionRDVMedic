<?php
// =============================================
// FICHIER: login.php
// RÔLE: Gère la connexion des utilisateurs (secrétaire, médecin, administrateur)
//       en vérifiant leurs identifiants dans la base de données.
//       Si les identifiants sont corrects, l'utilisateur est redirigé vers une page spécifique en fonction de son rôle.
// =============================================

// --- INCLUSION DU FICHIER DE CONNEXION À LA BASE DE DONNÉES ---
// require_once : Inclut et évalue un fichier PHP UNE SEULE FOIS (évite les inclusions multiples).
// Si le fichier n'existe pas, une erreur fatale est générée.
// __DIR__ : Constante magique qui retourne le chemin absolu du dossier contenant ce fichier.
// './database/connexion_database.php' : Chemin relatif vers le fichier de connexion.
// Ce fichier contient la fonction getConnexion() qui retourne un objet PDO pour interagir avec la base de données.
require_once './database/connexion_database.php';

// =============================================
// VÉRIFICATION DE LA MÉTHODE DE LA REQUÊTE HTTP
// =============================================
// $_SERVER : Superglobale PHP qui contient des informations sur le serveur et la requête HTTP.
// 'REQUEST_METHOD' : Clé qui contient la méthode HTTP utilisée (GET, POST, PUT, DELETE, etc.).
// === : Opérateur de comparaison stricte (vérifie la valeur ET le type).
// 'POST' : Méthode HTTP utilisée pour soumettre un formulaire.
// Ce bloc vérifie si la requête HTTP est de type POST (formulaire soumis).
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // =============================================
    // RÉCUPÉRATION DES DONNÉES DU FORMULAIRE
    // =============================================
    // $_POST : Superglobale PHP qui contient les données envoyées via la méthode POST.
    // $_POST['role'] : Récupère la valeur du champ 'role' du formulaire.
    // ?? : Opérateur "Null Coalescing" (retourne la première valeur non nulle).
    // Si $_POST['role'] n'existe pas ou est null, la valeur par défaut '' (chaîne vide) est utilisée.
    // Cet opérateur évite d'avoir des erreurs "Undefined index" si le champ n'existe pas.
    $role = $_POST['role'] ?? '';
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // =============================================
    // VALIDATION DES CHAMPS OBLIGATOIRES
    // =============================================
    // empty() : Fonction PHP qui vérifie si une variable est vide (0, '', null, false, [], etc.).
    // || : Opérateur logique "OU" (retourne true si au moins une des conditions est vraie).
    // Ce bloc vérifie si l'un des champs (role, username, password) est vide.
    if (empty($role) || empty($username) || empty($password)) {
        // die() : Fonction qui affiche un message et arrête immédiatement l'exécution du script.
        die("Tous les champs sont obligatoires.");
    }

    try {
        // =============================================
        // CONNEXION À LA BASE DE DONNÉES
        // =============================================
        // getConnexion() : Fonction définie dans connexion_database.php qui retourne une instance PDO.
        // PDO : PHP Data Objects, une extension pour interagir avec les bases de données de manière sécurisée.
        $pdo = getConnexion();

        // =============================================
        // REQUÊTE SQL POUR RÉCUPÉRER L'UTILISATEUR
        // =============================================
        // prepare() : Méthode de PDO qui prépare une requête SQL pour l'exécution.
        // :username et :role : Paramètres nommés (placeholders) pour éviter les injections SQL.
        // La requête sélectionne toutes les colonnes (*) de la table 'utilisateurs'
        // où le username et le role correspondent aux valeurs fournies.
        $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE username = :username AND role = :role");

        // =============================================
        // EXÉCUTION DE LA REQUÊTE AVEC LES PARAMÈTRES
        // =============================================
        // execute() : Méthode de PDOStatement qui exécute la requête préparée.
        // ['username' => $username, 'role' => $role] : Tableau associatif qui associe les paramètres nommés aux valeurs.
        // Cela permet de lier les valeurs aux placeholders (:username, :role) de manière sécurisée.
        $stmt->execute(['username' => $username, 'role' => $role]);

        // =============================================
        // RÉCUPÉRATION DE L'UTILISATEUR
        // =============================================
        // fetch() : Méthode de PDOStatement qui récupère la ligne suivante du résultat sous forme de tableau associatif.
        // Si aucun utilisateur n'est trouvé, fetch() retourne false.
        // $user : Contient les données de l'utilisateur (username, password, role, etc.) ou false si aucun utilisateur n'est trouvé.
        $user = $stmt->fetch();

        // =============================================
        // VÉRIFICATION DES IDENTIFIANTS
        // =============================================
        // $user : Vérifie si un utilisateur a été trouvé (différent de false).
        // password_verify() : Fonction PHP qui vérifie si un mot de passe correspond à un hash.
        //   - Premier argument : Mot de passe saisi par l'utilisateur (en clair).
        //   - Deuxième argument : Mot de passe haché stocké en base de données ($user['password']).
        //   - Retourne true si le mot de passe correspond au hash, false sinon.
        // Ce bloc vérifie si un utilisateur a été trouvé ET si le mot de passe saisi correspond au hash en base de données.
        if ($user && password_verify($password, $user['password'])) {

            // =============================================
            // DÉMARRAGE DE LA SESSION (À AJOUTER POUR STOCKER L'UTILISATEUR)
            // =============================================
            // session_start() : Démarre ou reprend une session existante.
            // Cela permet de stocker des informations sur l'utilisateur connecté (ex: son ID, son rôle).
            // À ajouter ici pour que les redirections puissent accéder à $_SESSION.
            // session_start();
            // $_SESSION['username'] = $user['username'];
            // $_SESSION['role'] = $user['role'];

            // =============================================
            // REDIRECTION EN FONCTION DU RÔLE
            // =============================================
            // switch : Structure de contrôle qui permet de tester une variable contre plusieurs cas.
            // $role : Variable testée dans le switch.
            // Chaque case correspond à un rôle possible (secretaire, medecin, administrateur).
            switch ($role) {
                case 'secretaire':
                    // header() : Fonction PHP qui envoie un en-tête HTTP brut.
                    // 'Location: routeur.php?action=index' : En-tête HTTP qui redirige le navigateur vers cette URL.
                    // La redirection se fait vers le routeur avec l'action 'index' pour les secrétaires.
                    header('Location: routeur.php?action=index');

                    // exit() : Fonction qui arrête immédiatement l'exécution du script.
                    // Cela évite que le code suivant soit exécuté après la redirection.
                    exit();

                case 'medecin':
                    // Redirection vers la vue spécifique pour les médecins.
                    header('Location: routeur.php?action=vue_medecin');
                    exit();

                case 'administrateur':
                    // Redirection vers la vue spécifique pour les administrateurs.
                    header('Location: routeur.php?action=vue_admin');
                    exit();

                // default : Cas par défaut si aucun des cas précédents ne correspond.
                default:
                    // Redirection vers la page d'accueil par défaut.
                    header('Location: routeur.php?action=index');
                    exit();
            }

        } else {
            // =============================================
            // IDENTIFIANTS INCORRECTS
            // =============================================
            // echo : Affiche un message à l'écran.
            // Ce message s'affiche si aucun utilisateur n'est trouvé ou si le mot de passe est incorrect.
            echo "Identifiants incorrects.";
        }

    } catch (PDOException $e) {
        // =============================================
        // GESTION DES ERREURS DE BASE DE DONNÉES
        // =============================================
        // catch : Bloc qui capture les exceptions levées dans le bloc try.
        // PDOException : Classe d'exception spécifique à PDO (erreur de base de données).
        // $e : Variable qui contient l'objet exception avec les détails de l'erreur.
        // $e->getMessage() : Méthode qui retourne le message d'erreur de l'exception.
        // die() : Affiche le message d'erreur et arrête l'exécution du script.
        die("Erreur de connexion : " . $e->getMessage());
    }
}
?>
