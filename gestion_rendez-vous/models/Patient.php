<?php
// models/Patient.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../services/EncryptionService.php';
require_once __DIR__ . '/../models/DatabaseManager.php';

class Patient {
    private $id;
    private $email;
    private $password;
    private $first_name;
    private $last_name;
    private $phone;
    private $temporary_password;
    private $has_medical_record;
    private $created_at;
    private $updated_at;

    public function __construct($id, $email, $password, $first_name, $last_name, $phone, $temporary_password, $has_medical_record, $created_at, $updated_at) {
        $this->id = $id;
        $this->email = $email;
        $this->password = $password;
        $this->first_name = $first_name;
        $this->last_name = $last_name;
        $this->phone = $phone;
        $this->temporary_password = $temporary_password;
        $this->has_medical_record = $has_medical_record;
        $this->created_at = $created_at;
        $this->updated_at = $updated_at;
    }

    // Getters
    public function getId() { return $this->id; }
    public function getEmail() { return $this->email; }
    public function getPassword() { return $this->password; }
    public function getFirstName() { return $this->first_name; }
    public function getLastName() { return $this->last_name; }
    public function getPhone() { return $this->phone; }
    public function isTemporaryPassword() { return $this->temporary_password; }
    public function hasMedicalRecord() { return $this->has_medical_record; }
    public function getCreatedAt() { return $this->created_at; }
    public function getUpdatedAt() { return $this->updated_at; }

    // Créer un patient (US-35 : incrémentation automatique)
    public static function create($email, $first_name, $last_name, $phone = null) {
        $data = [
            'email' => $email,
            'password' => password_hash(bin2hex(random_bytes(8)), PASSWORD_BCRYPT),
            'first_name' => $first_name,
            'last_name' => $last_name,
            'phone' => $phone,
            'temporary_password' => true,
            'has_medical_record' => false,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        $patientId = DatabaseManager::autoIncrementData('patients', $data);

        // Envoyer l'email avec les identifiants (US-31)
        $patient = self::findById($patientId);
        if ($patient) {
            require_once __DIR__ . '/../services/EmailService.php';
            EmailService::sendNewAccountEmail($patient, bin2hex(random_bytes(8)));
        }

        return $patientId;
    }

    // Trouver un patient par ID (avec déchiffrement)
    public static function findById($id) {
        $results = DatabaseManager::getDecryptedData('patients', ['id' => $id]);
        if (!empty($results)) {
            $patient = $results[0];
            return new Patient(
                $patient['id'], $patient['email'], $patient['password'],
                $patient['first_name'], $patient['last_name'], $patient['phone'],
                $patient['temporary_password'], $patient['has_medical_record'],
                $patient['created_at'], $patient['updated_at']
            );
        }
        return null;
    }

    // Trouver un patient par email (avec déchiffrement)
    public static function findByEmail($email) {
        // Chiffrer l'email pour la recherche (car il est stocké chiffré)
        $encryptedEmail = EncryptionService::encrypt($email);
        $results = DatabaseManager::getDecryptedData('patients', ['email' => $encryptedEmail]);
        if (!empty($results)) {
            $patient = $results[0];
            return new Patient(
                $patient['id'], $patient['email'], $patient['password'],
                $patient['first_name'], $patient['last_name'], $patient['phone'],
                $patient['temporary_password'], $patient['has_medical_record'],
                $patient['created_at'], $patient['updated_at']
            );
        }
        return null;
    }

    // Dans models/Patient.php, ajoutez :
    public function hasMedicalRecord() {
        return $this->has_medical_record;
    }

    // Lister tous les patients (avec déchiffrement)
    public static function getAll() {
        $results = DatabaseManager::getDecryptedData('patients');
        $patients = [];
        foreach ($results as $row) {
            $patients[] = new Patient(
                $row['id'], $row['email'], $row['password'],
                $row['first_name'], $row['last_name'], $row['phone'],
                $row['temporary_password'], $row['has_medical_record'],
                $row['created_at'], $row['updated_at']
            );
        }
        return $patients;
    }

    // Mettre à jour un patient
    public function update($data) {
        $conditions = ['id' => $this->id];
        return DatabaseManager::updateDecryptedData('patients', $data, $conditions);
    }
}
?>