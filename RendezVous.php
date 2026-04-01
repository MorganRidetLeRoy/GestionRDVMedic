<?php

//================================================================
// Cette page est pour prendre les rendez vous.
// Les rendez vous sont directment pris par les secrétaires
//================================================================

require_Once __DIR__ . '/';

class RDV
{
    private PDO $db
    public function __construct()
    {
        // Le  model obtient sa propre connexion PDO
        $this->db = getConnexion();
    }

    //________________________________
    // Read -- Lecture des données
    //________________________________

    /**
     * retourne tous les rendez vous avec leur catégorie.
     * La jointure LEFT JOIN conserve les rendez vous sans catégorie
     */

    public function findAll(): array
    {
        // Requête SQL pour voir tous les médecins et rendez vous
        //$sql = "SELECT "

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id => $id']);
        return $stmt->fetch();
    }
}


?>