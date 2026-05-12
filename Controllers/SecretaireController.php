<?php
// controller/SecretaireController.php

class SecretaireController
{
    private RechercheMedecin $medecinRecherche;

    public function __construct(PDO $db)
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
