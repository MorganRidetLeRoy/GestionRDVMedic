<?php
// test_minimal.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Chemin ABSOLU
require_once '/var/www/html/projet/model/Appointement.php';
require_once '/var/www/html/projet/config/config.php';
require_once '/var/www/html/projet/model/Database.php';

try {
    $appointment = new Appointment();
    echo "✅ Le modèle Appointment est chargé avec succès !";
} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage();
}
?>
