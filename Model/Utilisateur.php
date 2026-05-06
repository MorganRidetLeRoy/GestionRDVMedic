<?php
// app/Models/Utilisateur.php
class Utilisateur {
    private PDO $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    /**
     * Authentifie un utilisateur.
     * @param string $username
     * @param string $password
     * @param string $role
     * @return array|false
     */
    public function authentifier(string $username, string $password, string $role) {
        $stmt = $this->db->prepare("SELECT * FROM utilisateurs WHERE username = :username AND role = :role");
        $stmt->execute([
            ':username' => $username,
            ':role' => $role
        ]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            return $user;
        }

        return false;
    }
}
?>
