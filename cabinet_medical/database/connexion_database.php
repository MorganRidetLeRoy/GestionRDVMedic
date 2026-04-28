<?php
// =========================================================
// database/connexion_database.php
// Connexion PDO sécurisée à la base de données
// =========================================================

/**
 * Établit et retourne une connexion PDO à la base de données
 * Utilise un pattern singleton pour éviter les reconnexions inutiles
 *
 * @return PDO Objet de connexion à la base de données
 */
function getConnexion(): PDO
{
    // Variable statique pour stocker l'instance de PDO
    // Permet de réutiliser la même connexion tout au long de l'exécution du script
    static $pdo = null;

    // Si une connexion existe déjà, la retourne directement
    if ($pdo !== null) return $pdo;

    // --- Configuration de la connexion ---
    $host    = 'localhost';          // Adresse du serveur de base de données
    $dbname  = 'cabinet_medical';    // Nom de la base de données
    $user    = 'root';               // Nom d'utilisateur MySQL
    $pass    = '475Ju56n@';          // Mot de passe MySQL (à configurer selon l'environnement de production)
    $charset = 'utf8mb4';            // Jeu de caractères pour supporter les caractères spéciaux et les emojis

    // DSN (Data Source Name) : chaîne de connexion PDO
    // Contient les informations de base pour se connecter à la base de données
    $dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";

    // Options de configuration PDO pour une connexion optimale et sécurisée
    $options = [
        // Mode de rapport d'erreurs : lance des exceptions en cas d'erreur
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,

        // Mode de récupération par défaut : retourne les résultats sous forme de tableau associatif
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,

        // Désactive l'émulation des requêtes préparées pour une meilleure sécurité
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        // Création d'une nouvelle instance PDO avec les paramètres de connexion
        $pdo = new PDO($dsn, $user, $pass, $options);

        // Retourne l'objet PDO pour une utilisation ultérieure
        return $pdo;

    } catch (PDOException $e) {
        // En cas d'erreur de connexion :
        // Enregistre l'erreur dans les logs du serveur
        error_log('Erreur connexion BDD : ' . $e->getMessage());

        // Affiche un message d'erreur générique à l'utilisateur (format JSON pour les appels AJAX)
        die(json_encode([
            'succes'  => false,
            'message' => 'Erreur de connexion à la base de données.'
        ]));
    }
}
