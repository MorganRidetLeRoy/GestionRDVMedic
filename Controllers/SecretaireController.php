<?php
// controller/SecretaireController.php
/**
* Ici seront les commentaires syntaxiques
* 
* 1. Structure de classe et propriétés
*
*    class : Mot-clé pour définir un objet. Tout ce qui est entre les accolade { } appartient à cette objet.
*    private : Modificateur de visibilité. La variable $medecinRecherche n'est accessible qu'à l'intérieur de cette classe.
*    RechercheMedecin (Type mapping): C'est le typage. On précise que cette variable doit obligatoirement être une instance de la classe RechercheMedecin.
*    $this : Pseudo-variable qui représente l'instance actuelle de l'objet. On l'utilise pour accèder aux propriétés ou méthodes internes de la classe
*
* 2. Le constructeur et l'injection
*    
*    $__construct : Une "méthode magique". Elle s'exécute automatiquement lors du new SecretaireController().
*    PDO $db : On force l'argument à être un objet de type PDO (PHP Data Objects). C'est ce qu'on appelle l'injection de dépendances.
*    -> (Opérateur d'objet): Utilisé pour accéder à une méthode ou une propriété d'un objet.
*
*
*
*
*
*
*
*
*
*
*/
class SecretaireController //nouvelle class qui sera réservé au secrétaire
{
    private RechercheMedecin $medecinRecherche; //appel la fonction RechercheMedecin et on attend un objet précis

    public function __construct(PDO $db) // la fonction qui s'execute automatiquement dès qu'on crée le controller. Elle reçois
    {
        $this->medecinRecherche = new RechercheMedecin($db);
    }

    public function index(): void
    {
        // Initialisation par défaut pour éviter des erreurs dans la vue si elle attend ces variables
        $terme = '';
        $resultats = null; 
        require_once __DIR__ . '/../Views/secretaire/index.php';
    }

    public function rechercheMedecin(): void
    {
        // 1. On récupère et nettoie le terme
        $terme = trim($_GET['terme'] ?? '');

        if (empty($terme)) {
            // Au lieu de die(), on peut rediriger ou afficher une erreur propre
            $erreur = "Veuillez saisir un nom ou un prénom.";
            $resultats = [];
            require_once __DIR__ . '/../Views/secretaire/rechercheMedecin.php';
            return;
        }

        // 2. On appelle le modèle. 
        // Note : Si ton modèle ajoute déjà les '%', ne les mets pas ici.
        // Si ton modèle ne les ajoute pas, on les garde.
        $resultats = $this->medecinRecherche->rechercherMedecin($terme);

        // 3. Sécurité : si le modèle renvoie false (erreur SQL), on transforme en tableau vide
        if ($resultats === false) {
            $resultats = [];
            $erreur = "Une erreur est survenue lors de la recherche.";
        }

        // 4. Passage à la vue
        // Les variables $terme, $resultats et $erreur sont "extraites" dans la vue ici
        require_once __DIR__ . '/../Views/Secretaire/rechercheMedecin.php';
    }

    public function ajouterRendezVous(): void
    {
        // Future logique d'ajout
    }
}
