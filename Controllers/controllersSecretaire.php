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
    public function recherche_medecin(string $terme): array|false
    {
    // On définit la clé (Idéalement, utilise une constante ou une variable d'environnement)
    $key = 'Clé de Chiffrement78513';

    $sql = "SELECT 
                id_medecin, 
                AES_DECRYPT(nom, :key) AS nom, 
                AES_DECRYPT(prenom, :key) AS prenom, 
                AES_DECRYPT(email_pro, :key) AS email_pro,
                genre
            FROM medecin
            HAVING nom LIKE :terme 
               OR prenom LIKE :terme 
               OR email_pro LIKE :terme
            ORDER BY nom ASC";

    $query = $this->db->prepare($sql);
    $query->execute([
        'key'   => $key,
        'terme' => '%' . $terme . '%'
    ]);
    
    return $query->fetchAll(PDO::FETCH_ASSOC);
    }
}
