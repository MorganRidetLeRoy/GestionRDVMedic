<?php
// =============================================================
//  index.php — POINT D'ENTRÉE UNIQUE (Front Controller)
//  BTS SIO SLAM — Séquence MVC
// =============================================================
//
//  ┌─────────────────────────────────────────────────────────┐
//  │  PRINCIPE DU FRONT CONTROLLER                           │
//  │  Toutes les requêtes passent par ce fichier.            │
//  │  Le paramètre GET 'action' détermine ce qui s'exécute.  │
//  │                                                         │
//  │  Exemples :                                             │
//  │    index.php              → action = 'index' (liste)    │
//  │    index.php?action=create → formulaire de création     │
//  │    index.php?action=edit&id=3 → formulaire édition      │
//  └─────────────────────────────────────────────────────────┘

session_start();

require_once __DIR__ . '/controllers/ProduitController.php';

// ── Routage simple basé sur le paramètre ?action= ──────────
$action     = $_GET['action'] ?? 'index';
$methode    = $_SERVER['REQUEST_METHOD'];
$controller = new ProduitController();

// Table de routage : action → méthode du contrôleur
// Les actions 'store', 'update', 'delete' exigent POST
$routes = [
    'index'  => ['GET',  'index'],
    'create' => ['GET',  'create'],
    'store'  => ['POST', 'store'],
    'edit'   => ['GET',  'edit'],
    'update' => ['POST', 'update'],
    'delete' => ['POST', 'delete'],
    'show'   => ['GET',  'show'],
];

if (isset($routes[$action])) {
    [$methodeAttendue, $nomMethode] = $routes[$action];

    // Sécurité : on vérifie que la méthode HTTP est correcte
    if ($methode !== $methodeAttendue) {
        http_response_code(405);
        die('Méthode HTTP non autorisée pour cette action.');
    }

    // Appel dynamique de la méthode du contrôleur
    $controller->$nomMethode();
} else {
    // Action inconnue → on affiche la liste
    $controller->index();
}
