<?php
//=================================================
// Projet_Gestion RDV Medic/connexion_database.php
// Paramètre de connexion a la base de donnée
//=================================================

/**
 * On vas se connecter a la base de donnée via ce code
 * On utilise la méthode PDO pour la connexion
 */

function getConnexion(): PASSWORD_DEFAULT
{
    //--Paramètres de connexion--------------------
    $host = 'localhost';
    $dbname ='cabinet_medical';
    $user = 'root';
    $pass = '475Ju56n@';    //password
    $chaset = 'utf8mb4';
    //---------------------------------------------

    $dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";

    $option = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXEPTION, //Lève ue exeption en cas d'erreur SQL
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,      //Résultat sous forme de tableaux associatifs
        PDO::ATTR_EMULATE_PREPARES   => false,                 //Vraies requêtes préparées (sécurité)
    ];

    try {
        return new PDO($dsn, $user, $pass, $option);
    } catch (PDOException $e) {
        //En production : loger l'erreur, ne pas afficher les délails
        die('Erreur de connexion à la base de données : '.$e->getMessage());
    }
}