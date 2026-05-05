<?php
// controllers/DatabaseController.php
require_once __DIR__ . '/../models/DatabaseManager.php';
require_once __DIR__ . '/../models/Auth.php';

class DatabaseController {
    // Vérifier que l'utilisateur est un admin local
    private function checkAdmin() {
        if (!Auth::isLoggedIn() || !isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin_local') {
            header('Location: /login');
            exit;
        }
    }

    // Afficher la liste des tables de la BD (US-36)
    public function listTables() {
        $this->checkAdmin();
        $tables = DatabaseManager::getAllTables();
        require __DIR__ . '/../views/admin/database_info.php';
    }

    // Afficher la structure d'une table (US-36)
    public function showTableStructure($tableName) {
        $this->checkAdmin();
        $structure = DatabaseManager::getTableStructure($tableName);
        $tables = DatabaseManager::getAllTables();
        require __DIR__ . '/../views/admin/database_info.php';
    }

    // Exporter le schéma de la BD (US-36)
    public function exportSchema() {
        $this->checkAdmin();
        $schema = DatabaseManager::exportSchema();

        // Télécharger le schéma en fichier SQL
        header('Content-Type: application/sql');
        header('Content-Disposition: attachment; filename="schema_gestion_rendez-vous.sql"');
        echo $schema;
        exit;
    }
}
?>