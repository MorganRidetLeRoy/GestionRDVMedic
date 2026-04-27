<?php
// =========================================================
// database/connexion_database.php
// Connexion PDO sécurisée à la base de données
// =========================================================

function getConnexion(): PDO
{
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    $host    = 'localhost';
    $dbname  = 'cabinet_medical';
    $user    = 'root';
    $pass    = '';           // À configurer selon l'environnement
    $charset = 'utf8mb4';

    $dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        $pdo = new PDO($dsn, $user, $pass, $options);
        return $pdo;
    } catch (PDOException $e) {
        error_log('Erreur connexion BDD : ' . $e->getMessage());
        die(json_encode(['succes' => false, 'message' => 'Erreur de connexion à la base de données.']));
    }
}
