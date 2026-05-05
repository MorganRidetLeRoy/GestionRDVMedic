<?php
// models/DatabaseManager.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../services/EncryptionService.php';

class DatabaseManager {
    // Initialiser le service de chiffrement
    public static function init() {
        EncryptionService::init();
    }

    // Incrémenter automatiquement les informations dans la BD (US-35)
    public static function autoIncrementData($table, $data) {
        global $pdo;

        // Chiffrer les données sensibles avant insertion
        $sensitiveFields = ['first_name', 'last_name', 'email', 'phone', 'content', 'title', 'reason'];
        foreach ($data as $key => $value) {
            if (in_array($key, $sensitiveFields) && !empty($value)) {
                $data[$key] = EncryptionService::encrypt($value);
            }
        }

        // Préparer les champs et les valeurs pour l'insertion
        $fields = [];
        $values = [];
        $placeholders = [];

        foreach ($data as $key => $value) {
            $fields[] = $key;
            $placeholders[] = "?";
            $values[] = $value;
        }

        $fieldsStr = implode(', ', $fields);
        $placeholdersStr = implode(', ', $placeholders);

        // Exécuter la requête
        $stmt = $pdo->prepare("INSERT INTO $table ($fieldsStr) VALUES ($placeholdersStr)");
        $stmt->execute($values);

        return $pdo->lastInsertId();
    }

    // Récupérer des données avec déchiffrement automatique
    public static function getDecryptedData($table, $conditions = [], $fields = '*') {
        global $pdo;

        $where = '';
        $params = [];

        if (!empty($conditions)) {
            $whereParts = [];
            foreach ($conditions as $key => $value) {
                $whereParts[] = "$key = ?";
                $params[] = $value;
            }
            $where = " WHERE " . implode(' AND ', $whereParts);
        }

        $stmt = $pdo->prepare("SELECT $fields FROM $table$where");
        $stmt->execute($params);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Déchiffrer les données sensibles
        $sensitiveFields = ['first_name', 'last_name', 'email', 'phone', 'content', 'title', 'reason'];
        foreach ($results as &$row) {
            foreach ($row as $key => &$value) {
                if (in_array($key, $sensitiveFields) && !empty($value)) {
                    $value = EncryptionService::decrypt($value);
                }
            }
        }

        return $results;
    }

    // Mettre à jour des données avec chiffrement automatique
    public static function updateDecryptedData($table, $data, $conditions) {
        global $pdo;

        // Chiffrer les données sensibles
        $sensitiveFields = ['first_name', 'last_name', 'email', 'phone', 'content', 'title', 'reason'];
        foreach ($data as $key => $value) {
            if (in_array($key, $sensitiveFields) && !empty($value)) {
                $data[$key] = EncryptionService::encrypt($value);
            }
        }

        // Préparer la requête UPDATE
        $setParts = [];
        $params = [];

        foreach ($data as $key => $value) {
            $setParts[] = "$key = ?";
            $params[] = $value;
        }

        $whereParts = [];
        foreach ($conditions as $key => $value) {
            $whereParts[] = "$key = ?";
            $params[] = $value;
        }

        $setStr = implode(', ', $setParts);
        $whereStr = implode(' AND ', $whereParts);

        $stmt = $pdo->prepare("UPDATE $table SET $setStr WHERE $whereStr");
        return $stmt->execute($params);
    }

    // Lister toutes les tables de la BD (US-36)
    public static function getAllTables() {
        global $pdo;
        $stmt = $pdo->query("SHOW TABLES");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    // Obtenir la structure d'une table (US-36)
    public static function getTableStructure($tableName) {
        global $pdo;
        $stmt = $pdo->query("DESCRIBE $tableName");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Exporter le schéma de la BD (US-36)
    public static function exportSchema() {
        $tables = self::getAllTables();
        $schema = "";

        foreach ($tables as $table) {
            $schema .= "-- Table: $table\n";
            $schema .= "CREATE TABLE $table (\n";

            $structure = self::getTableStructure($table);
            foreach ($structure as $column) {
                $schema .= "  " . $column['Field'] . " " . $column['Type'];
                if (!empty($column['Null'])) {
                    $schema .= " " . $column['Null'];
                }
                if (!empty($column['Key'])) {
                    $schema .= " " . $column['Key'];
                }
                if (!empty($column['Default'])) {
                    $schema .= " DEFAULT " . $column['Default'];
                }
                if (!empty($column['Extra'])) {
                    $schema .= " " . $column['Extra'];
                }
                $schema .= ",\n";
            }
            $schema = rtrim($schema, ",\n") . "\n);\n\n";
        }

        return $schema;
    }
}
?>