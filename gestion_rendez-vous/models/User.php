<?php
// models/User.php
require_once __DIR__ . '/../config/database.php';

class User {
    private $id;
    private $email;
    private $password;
    private $role;
    private $created_at;
    private $last_activity;
    private $is_active;
    private $last_password_reset;

    public function __construct($id, $email, $password, $role, $created_at, $last_activity, $is_active = true, $last_password_reset = null) {
        $this->id = $id;
        $this->email = $email;
        $this->password = $password;
        $this->role = $role;
        $this->created_at = $created_at;
        $this->last_activity = $last_activity;
        $this->is_active = $is_active;
        $this->last_password_reset = $last_password_reset;
    }

    // Getters
    public function getId() { return $this->id; }
    public function getEmail() { return $this->email; }
    public function getPassword() { return $this->password; }
    public function getRole() { return $this->role; }
    public function getIsActive() { return $this->is_active; }
    public function getLastPasswordReset() { return $this->last_password_reset; }

    // Créer un utilisateur (US-03)
    public static function create($email, $password, $role) {
        global $pdo;
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

        // Vérifier qu'il n'y a qu'un seul admin local (US-26)
        if ($role === 'admin_local') {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'admin_local'");
            $stmt->execute();
            $adminCount = $stmt->fetchColumn();
            if ($adminCount > 0) {
                throw new Exception("Il ne peut y avoir qu'un seul admin local.");
            }
        }

        $stmt = $pdo->prepare("
            INSERT INTO users (email, password, role, is_active, last_password_reset)
            VALUES (?, ?, ?, TRUE, NULL)
        ");
        $stmt->execute([$email, $hashedPassword, $role]);
        return $pdo->lastInsertId();
    }

    // Trouver un utilisateur par email
    public static function findByEmail($email) {
        global $pdo;
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            return new User(
                $user['id'], $user['email'], $user['password'], $user['role'],
                $user['created_at'], $user['last_activity'], $user['is_active'], $user['last_password_reset']
            );
        }
        return null;
    }

    // Trouver un utilisateur par ID
    public static function findById($id) {
        global $pdo;
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            return new User(
                $user['id'], $user['email'], $user['password'], $user['role'],
                $user['created_at'], $user['last_activity'], $user['is_active'], $user['last_password_reset']
            );
        }
        return null;
    }

    // Lister tous les utilisateurs actifs (US-25)
    public static function getAllActive() {
        global $pdo;
        $stmt = $pdo->prepare("SELECT * FROM users WHERE is_active = TRUE");
        $stmt->execute();
        $users = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $users[] = new User(
                $row['id'], $row['email'], $row['password'], $row['role'],
                $row['created_at'], $row['last_activity'], $row['is_active'], $row['last_password_reset']
            );
        }
        return $users;
    }

    // Désactiver un utilisateur (US-23)
    public function disable() {
        global $pdo;
        $stmt = $pdo->prepare("UPDATE users SET is_active = FALSE WHERE id = ?");
        $stmt->execute([$this->id]);
        return true;
    }

    // Réactiver un utilisateur
    public function enable() {
        global $pdo;
        $stmt = $pdo->prepare("UPDATE users SET is_active = TRUE WHERE id = ?");
        $stmt->execute([$this->id]);
        return true;
    }

    // Réinitialiser le mot de passe (US-24)
    public function resetPassword() {
        global $pdo;
        $newPassword = bin2hex(random_bytes(8)); // Générer un mot de passe aléatoire
        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("UPDATE users SET password = ?, last_password_reset = NOW() WHERE id = ?");
        $stmt->execute([$hashedPassword, $this->id]);

        // Envoyer l'email avec le nouveau mot de passe
        require_once __DIR__ . '/../config/mail.php';
        sendResetPasswordEmail($this->email, $newPassword);

        return $newPassword;
    }

    // Mettre à jour le mot de passe (US-08)
    public function updatePassword($newPassword) {
        global $pdo;
        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("UPDATE users SET password = ?, last_password_reset = NOW() WHERE id = ?");
        $stmt->execute([$hashedPassword, $this->id]);
        return true;
    }

    // Mettre à jour la dernière activité (US-06)
    public function updateLastActivity() {
        global $pdo;
        $stmt = $pdo->prepare("UPDATE users SET last_activity = NOW() WHERE id = ?");
        $stmt->execute([$this->id]);
        return true;
    }

    // Vérifier si l'utilisateur est un admin local
    public function isAdminLocal() {
        return $this->role === 'admin_local';
    }

    // Compter le nombre d'admins locaux (US-26)
    public static function countAdminLocal() {
        global $pdo;
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'admin_local'");
        $stmt->execute();
        return $stmt->fetchColumn();
    }

    // Dans models/User.php, ajoutez :
    public static function getPractitioners() {
        global $pdo;
        $stmt = $pdo->prepare("SELECT * FROM users WHERE role = 'praticien'");
        $stmt->execute();
        $users = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $users[] = new User($row['id'], $row['email'], $row['password'], $row['role'], $row['created_at'], $row['last_activity']);
        }
        return $users;
    }

    public static function getById($id) {
        global $pdo;
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            return new User($user['id'], $user['email'], $user['password'], $user['role'], $user['created_at'], $user['last_activity']);
        }
        return null;
    }
}
?>