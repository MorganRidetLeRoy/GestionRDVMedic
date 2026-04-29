<?php
//Ici sera le controllers de la page des secretaires

class SecretaireController
{
    private RechercheMedecin $medecinRecherche;

    public function __construct(PDO $db)
    {
        $this->medecinRecherche = new RechercheMedecin($db);
    }

    public function index(): void
    {
        // Afficher la vue principale de la secrétaire
        require_once __DIR__ . '/../Views/secretaire/index.php';
    }

    public function rechercheMedecin(): void
    {
        $terme = trim($_GET['terme'] ?? '');

        if (empty($terme)) {
            http_response_code(400);
            die('Terme de recherche manquant.');
        }

        $resultats = $this->medecinRecherche->rechercherMedecin('%' . $terme . '%');
        // Passer les résultats à la vue
        require_once __DIR__ . '/../Views/secretaire/recherche.php';
    }

    //ublic fonction ajouterRendezVous():variant_mode

}