<?pĥp 
//  ______________________________________________________________________________________________________________
// | Contrôller de la page de recherche d'agenda des médecins et les fiches des patients ainsi que l'ajoût de RDV |
// | Reçois une requête http (GET/POST + paramètres)                                                              |
// |                                                                                                              | 
// | Contients les fonctions:                                                                                     |
// |   - Recherches médecins                                                                                      |
// |   - Ajoût de rendez vous (pour les médecins, donc modifications de leurs emploie du temps/agenda)            |
// |______________________________________________________________________________________________________________|

require_once __DIR__ . '/../Model/Medecin.php';


class Secretaire
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function rechercheMedecin(string $terme): array|false
    {
        // ⚠️ Il est préférable de stocker la clé dans une constante, une variable d'environnement ou un fichier de config sécurisé
        $key = 'CleDeChiffrement78513';

        // Utilisation correcte de AES_DECRYPT avec la clé en chaîne directe (les paramètres PDO ne fonctionnent pas toujours dans ce contexte)
        $sql = "SELECT 
                    id_medecin, 
                    CAST(AES_DECRYPT(nom, :key) AS CHAR) AS nom, 
                    CAST(AES_DECRYPT(prenom, :key) AS CHAR) AS prenom, 
                    CAST(AES_DECRYPT(email_pro, :key) AS CHAR) AS email_pro,
                    genre
                FROM medecin
                WHERE AES_DECRYPT(nom, :key) LIKE :terme
                   OR AES_DECRYPT(prenom, :key) LIKE :terme
                   OR AES_DECRYPT(email_pro, :key) LIKE :terme
                ORDER BY nom ASC";

        $query = $this->db->prepare($sql);

        $query->execute([
            'key'   => $key,
            'terme' => '%' . $terme . '%'
        ]);

        return $query->fetchAll(PDO::FETCH_ASSOC);
    }
}
