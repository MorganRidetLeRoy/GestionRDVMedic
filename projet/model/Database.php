<?php
// model/Database.php

// Définition des constantes pour la base de données
define('DB_HOST', 'localhost');       // Remplace par ton hôte MySQL
define('DB_USER', 'root'); // Remplace par ton utilisateur MySQL
define('DB_PASS', '475Ju56n@'); // Remplace par ton mot de passe MySQL
define('DB_NAME', 'cabinet_medical2');  // Remplace par le nom de ta base de données

class Database {
    private $host;
    private $user;
    private $password;
    private $database;
    private $conn;

    public function __construct() {
        $this->host = DB_HOST;
        $this->user = DB_USER;
        $this->password = DB_PASS;
        $this->database = DB_NAME;

        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->database . ";charset=utf8mb4",
                $this->user,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        } catch (PDOException $e) {
            die("Erreur de connexion à la base de données : " . $e->getMessage());
        }
    }

    public function getConnection() {
        return $this->conn;
    }
}
?>
