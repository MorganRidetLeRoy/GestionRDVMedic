<?pĥp 
//  ______________________________________________________________________________________________________________
// | Index de la page de recherche d'agenda des médecins et les fiches des patients ainsi que l'ajoût de RDV      |
// | Reçois une requête http (GET/POST + paramètres)                                                              |
// |                                                                                                              | 
// | Ce que font les routes:                                                                                      |
// |   - index -> renvoie vers la page vueSecretaire (profil)                                                     |
// |   - recherche_medecin -> executera de la recherche des médecins                                              |
// |   - ajouter_rdv -> ajoutera un patient                                                                       |
// |______________________________________________________________________________________________________________|

session_start();

require_once __DIR__ . '/../database/conexion_database.php';


$action     = $_GET['action'] ?? 'index';
$methode    = $_SERVER['REQUEST_METHOD'];
$controller = new SecretaireController();

// Table de routage : action → méthode du contrôleur
// Les actions 'ajouter_rdv' exigent POST

$routes = [
    'index'  => ['GET',  'index'],                     // Doit afficher la page ou le secrétaire se trouve
    'recherche_medecin' => ['GET','rechercheMedecin'], //Devra executer la recherche du medecin
    'ajouter_rdv' => ['POST', 'ajouterRendezVous'],    //Executera l'ajoût d'un RDV sur l'agenda d'un médecin
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