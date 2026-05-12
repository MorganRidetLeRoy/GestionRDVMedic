<?php
// --- CONFIGURATION DES ERREURS PHP ---
// Active l'affichage des erreurs PHP pour le débogage.
// Cela permet de voir les erreurs directement dans le navigateur au lieu de les masquer.
ini_set('display_errors', 1);          // Affiche les erreurs à l'écran
ini_set('display_startup_errors', 1); // Affiche les erreurs de démarrage (ex: erreurs dans php.ini)
error_reporting(E_ALL);               // Rapport toutes les erreurs (E_ALL = toutes les catégories d'erreurs)

/*
 ______________________________________________________________________________________________________________
| Routage de la page Secretaire                                                                                |
| Reçoit une requête HTTP (GET/POST + paramètres)                                                              |
|                                                                                                              |
| Ce que font les routes:                                                                                      |
|   - index -> renvoie vers la page vueSecretaire (profil)                                                     |
|   - recherche_medecin -> exécutera la recherche des médecins                                               |
|   - ajouter_rdv -> ajoutera un rendez-vous                                                                   |
|______________________________________________________________________________________________________________|
*/

// --- DÉMARRAGE DE LA SESSION ---
// Permet d'utiliser la superglobale $_SESSION pour stocker des informations entre les requêtes HTTP.
// Exemple : stocker l'ID de l'utilisateur connecté, son rôle, etc.
session_start();

// --- INCLUSION DES FICHIERS NÉCESSAIRES ---
// __DIR__ est une constante magique qui retourne le chemin absolu du dossier contenant ce fichier.
// On inclut les fichiers suivants :
require_once __DIR__ . '/../database/connexion_database.php';  // Fichier pour se connecter à la base de données (contient getConnexion())
require_once __DIR__ . '/../Model/RechercheMedecin.php';       // Modèle pour gérer la recherche des médecins (logique métier)
require_once __DIR__ . '/../Controllers/SecretaireController.php'; // Contrôleur pour gérer les actions de la secrétaire

// --- RÉCUPÉRATION DES PARAMÈTRES DE LA REQUÊTE ---
$action = $_GET['action'] ?? 'index';  // Récupère l'action depuis l'URL (ex: ?action=recherche_medecin). Si non définie, valeur par défaut = 'index'
$methode = $_SERVER['REQUEST_METHOD']; // Récupère la méthode HTTP utilisée (GET, POST, etc.)
$pdo = getConnexion();                  // Récupère une instance PDO pour interagir avec la base de données
$controller = new SecretaireController($pdo); // Instancie le contrôleur en lui passant la connexion PDO

// --- TABLE DE ROUTAGE ---
// Définit les actions disponibles et les méthodes HTTP associées.
// Chaque clé du tableau est une action possible (ex: 'index', 'recherche_medecin').
// Chaque valeur est un tableau contenant :
//   - La méthode HTTP attendue (ex: 'GET', 'POST')
//   - Le nom de la méthode du contrôleur à appeler (ex: 'index', 'rechercheMedecin')
$routes = [
    'index' => ['GET', 'index'],                     // Action 'index' : doit utiliser GET et appeler la méthode 'index' du contrôleur
    'recherche_medecin' => ['GET', 'rechercheMedecin'], // Action 'recherche_medecin' : doit utiliser GET et appeler 'rechercheMedecin'
    'ajouter_rdv' => ['POST', 'ajouterRendezVous'],  // Action 'ajouter_rdv' : doit utiliser POST et appeler 'ajouterRendezVous'
];

// --- VÉRIFICATION DE L'ACTION DEMANDÉE ---
// On vérifie si l'action demandée ($action) existe dans le tableau $routes.
if (isset($routes[$action])) {

    // --- EXTRACTION DES INFORMATIONS DE ROUTAGE ---
    // On récupère la méthode HTTP attendue et le nom de la méthode du contrôleur à appeler.
    // Exemple : pour $action = 'recherche_medecin', on obtient :
    //   $methodeAttendue = 'GET'
    //   $nomMethode = 'rechercheMedecin'
    [$methodeAttendue, $nomMethode] = $routes[$action];

    // --- VÉRIFICATION DE LA MÉTHODE HTTP ---
    // On vérifie que la méthode HTTP de la requête ($methode) correspond à celle attendue ($methodeAttendue).
    // Si ce n'est pas le cas, on retourne une erreur 405 (Method Not Allowed).
    if ($methode !== $methodeAttendue) {
        http_response_code(405); // Code HTTP 405 = "Méthode non autorisée"
        die('Méthode HTTP non autorisée pour cette action.'); // Arrête l'exécution du script et affiche un message
    }

    // --- APPEL DYNAMIQUE DE LA MÉTHODE DU CONTRÔLEUR ---
    // On appelle la méthode du contrôleur dont le nom est stocké dans $nomMethode.
    // Exemple : si $nomMethode = 'rechercheMedecin', on appelle $controller->rechercheMedecin()
    $controller->$nomMethode();

} else {
    // --- ACTION INCONNUE ---
    // Si l'action demandée n'existe pas dans $routes, on redirige vers l'action par défaut ('index').
    $controller->index();
}
