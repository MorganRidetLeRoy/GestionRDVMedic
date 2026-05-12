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
    // On utilise le typage PHP 7.4+ pour garantir que $db est bien une instance de PDO
    private PDO $db; 

    // La constante de classe est le meilleur endroit pour stocker la clé si elle est "en dur".
    // Le mot-clé 'private' empêche d'y accéder hors de cette classe.
    private const AES_KEY = 'Chiffrer'; 

    public function __construct(PDO $db) 
    { 
        // Injection de dépendance : on reçoit la connexion déjà établie
        $this->db = $db; 
    }

	public function rechercherMedecin(string $terme): array|false 
	{ 
		//Les infos pour les testes Dupont, Jean, jean.dupont@cabinet.fr, 0102030405, M, 1980-05-12
		// On prépare le terme pour le LIKE (ex: "du" devient "%du%")
		$search = '%' . $terme . '%';

		$sql = "SELECT  
					id_medecin,  
					CAST(AES_DECRYPT(nom, :key) AS CHAR) AS nom,  
					CAST(AES_DECRYPT(prenom, :key) AS CHAR) AS prenom,  
					CAST(AES_DECRYPT(email_pro, :key) AS CHAR) AS email_pro, 
					genre 
				FROM medecin 
				-- Utilisation du LIKE pour une recherche partielle
				WHERE CAST(AES_DECRYPT(nom, :key) AS CHAR) LIKE :terme  
					OR CAST(AES_DECRYPT(prenom, :key) AS CHAR) LIKE :terme  
					OR CAST(AES_DECRYPT(email_pro, :key) AS CHAR) LIKE :terme  
				ORDER BY nom ASC"; 

		$query = $this->db->prepare($sql); 

		try {
			$query->execute([
				'key'   => self::AES_KEY,
				'terme' => $search
			]); 
        
			return $query->fetchAll(PDO::FETCH_ASSOC);

		} catch (PDOException $e) {
			// En production, log l'erreur plutôt que de l'afficher
			return false;
		}
	} 
}
