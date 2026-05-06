<?php 
//  ______________________________________________________________________________________________________________
// | Index de la page de recherche d'agenda des médecins et les fiches des patients ainsi que l'ajoût de RDV      |
// | Reçois une requête http (GET/POST + paramètres)                                                              |
// |                                                                                                              | 
// | Ce que font les routes:                                                                                      |
// |   - index -> renvoie vers la page vueSecretaire (profil)                                                     |
// |   - recherche_medecin -> executera de la recherche des médecins                                              |
// |   - ajouter_rdv -> ajoutera un patient                                                                       |
// |______________________________________________________________________________________________________________|

// routeur.php
session_start();

require_once __DIR__ . '/../database/connexion_database.php';
require_once __DIR__ . '/../Model/RechercheMedecin.php';
require_once __DIR__ . '/../Controllers/SecretaireController.php';
require_once __DIR__ . '/../Controllers/MedecinController.php';
require_once __DIR__ . '/../Controllers/AdminController.php';

$action = $_GET['action'] ?? 'index';
$methode = $_SERVER['REQUEST_METHOD'];
$pdo = connexion_database();

// Initialisation des contrôleurs
$secretaireController = new SecretaireController($pdo);
$medecinController = new MedecinController($pdo);
$adminController = new AdminController($pdo);

// Table de routage : action → [méthode HTTP, contrôleur, méthode]
$routes = [
    'index' => ['GET', $secretaireController, 'index'], // Page par défaut (secrétaire)
    'recherche_medecin' => ['GET', $secretaireController, 'rechercheMedecin'],
    'ajouter_rdv' => ['POST', $secretaireController, 'ajouterRendezVous'],
    'vue_medecin' => ['GET', $medecinController, 'index'], // Page médecin
    'vue_admin' => ['GET', $adminController, 'index'], // Page admin
];

if (isset($routes[$action])) {
    [$methodeAttendue, $controller, $nomMethode] = $routes[$action];

    // Vérification de la méthode HTTP
    if ($methode !== $methodeAttendue) {
        http_response_code(405);
        die('Méthode HTTP non autorisée pour cette action.');
    }

    // Appel dynamique de la méthode du contrôleur
    $controller->$nomMethode();
} else {
    // Action inconnue → redirection vers la page par défaut (secrétaire)
    $secretaireController->index();
}
?>
