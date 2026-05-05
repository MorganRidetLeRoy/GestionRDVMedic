<?php
// services/EncryptionService.php
require_once __DIR__ . '/../config/database.php';

class EncryptionService {
    private static $key = null;
    private static $method = 'AES-256-CBC';
    private static $ivLength = 16; // Longueur du vecteur d'initialisation pour AES-256-CBC

    // Initialiser la clé de chiffrement (à appeler au démarrage)
    public static function init() {
        global $pdo;
        $stmt = $pdo->prepare("SELECT key_value FROM encryption_keys WHERE key_name = 'default' LIMIT 1");
        $stmt->execute();
        $key = $stmt->fetchColumn();

        if (!$key) {
            // Générer une nouvelle clé si elle n'existe pas
            $key = bin2hex(openssl_random_pseudo_bytes(32)); // 256 bits
            $stmt = $pdo->prepare("INSERT INTO encryption_keys (key_name, key_value) VALUES ('default', ?)");
            $stmt->execute([$key]);
        }

        self::$key = hex2bin($key);
    }

    // Chiffrer des données
    public static function encrypt($data) {
        if (self::$key === null) {
            self::init();
        }

        $iv = openssl_random_pseudo_bytes(self::$ivLength);
        $encrypted = openssl_encrypt(
            $data,
            self::$method,
            self::$key,
            OPENSSL_RAW_DATA,
            $iv
        );

        // Combiner l'IV et les données chiffrées pour le stockage
        return base64_encode($iv . $encrypted);
    }

    // Déchiffrer des données
    public static function decrypt($encryptedData) {
        if (self::$key === null) {
            self::init();
        }

        $data = base64_decode($encryptedData);
        $iv = substr($data, 0, self::$ivLength);
        $encrypted = substr($data, self::$ivLength);

        return openssl_decrypt(
            $encrypted,
            self::$method,
            self::$key,
            OPENSSL_RAW_DATA,
            $iv
        );
    }

    // Chiffrer un tableau de données (ex: nom, prénom, email)
    public static function encryptArray(array $data) {
        $encrypted = [];
        foreach ($data as $key => $value) {
            $encrypted[$key] = self::encrypt($value);
        }
        return $encrypted;
    }

    // Déchiffrer un tableau de données
    public static function decryptArray(array $encryptedData) {
        $decrypted = [];
        foreach ($encryptedData as $key => $value) {
            $decrypted[$key] = self::decrypt($value);
        }
        return $decrypted;
    }
}
?>