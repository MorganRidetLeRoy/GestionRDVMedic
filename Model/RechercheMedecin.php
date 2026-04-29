<?php 
//  ______________________________________________________________________________________________________________
// | Contrôller de la page de recherche d'agenda des médecins et les fiches des patients ainsi que l'ajoût de RDV |
// | Reçois une requête http (GET/POST + paramètres)                                                              |
// |                                                                                                              | 
// | Contients les fonctions:                                                                                     |
// |   - Recherches médecins                                                                                      |
// |   - Ajoût de rendez vous (pour les médecins, donc modifications de leurs emploie du temps/agenda)            |
// |______________________________________________________________________________________________________________|

class RechercheMedecin
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function rechercherMedecin(string $termeLike): array|false
    {
        // ⚠️ Il est préférable de stocker la clé dans une constante, une variable d'environnement ou un fichier de config sécurisé
        define('AES_KEY', 'Clé De Chiffrement78513');
        $key = AES_KEY; //Attention pas de $ pas de guillemets

        // Utilisation correcte de AES_DECRYPT avec la clé en chaîne directe (les paramètres PDO ne fonctionnent pas toujours dans ce contexte)
        $sql = "SELECT 
                    id_medecin, 
                    CAST(AES_DECRYPT(nom, $key) AS CHAR) AS nom, 
                    CAST(AES_DECRYPT(prenom, $key) AS CHAR) AS prenom, 
                    CAST(AES_DECRYPT(email_pro, $key) AS CHAR) AS email_pro,
                    genre
                FROM medecin
                WHERE AES_DECRYPT(nom, $key) LIKE ?
                   OR AES_DECRYPT(prenom, $key) LIKE ?
                   OR AES_DECRYPT(email_pro, $key) LIKE ?
                ORDER BY nom ASC";

        $query = $this->db->prepare($sql);

        $query->execute([$termeLike, $termeLike, $termeLike]);

        return $query->fetchAll(PDO::FETCH_ASSOC);
    }
}
