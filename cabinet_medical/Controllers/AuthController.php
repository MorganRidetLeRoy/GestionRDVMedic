<?php
// =========================================================
// Controllers/AuthController.php
// Authentification — connexion, déconnexion, inscription
// Sécurités : SQLi (PDO), XSS (htmlspecialchars), CSRF, sessions
// =========================================================
require_once __DIR__ . '/../Models/UtilisateurModel.php';

class AuthController
{
    private UtilisateurModel $model;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $this->model = new UtilisateurModel();
    }

    // ─── Protection CSRF ─────────────────────────────────────

    private function genererTokenCSRF(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    private function validerCSRF(string $token): bool
    {
        return isset($_SESSION['csrf_token'])
            && hash_equals($_SESSION['csrf_token'], $token);
    }

    // ─── Protection XSS ──────────────────────────────────────

    private function xss(string $v): string
    {
        return htmlspecialchars(strip_tags(trim($v)), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    // ─── Routes ──────────────────────────────────────────────

    /**
     * GET — Affiche la page de connexion (index)
     */
    public function index(): void
    {
        if ($this->estConnecte()) {
            $this->redirigerSelonRole($_SESSION['role']);
            return;
        }
        $csrf = $this->genererTokenCSRF();
        require __DIR__ . '/../Views/auth/login.php';
    }

    /**
     * POST — Traitement du formulaire de connexion
     */
    public function connexion(): void
    {
        // CSRF
        if (!$this->validerCSRF($_POST['csrf_token'] ?? '')) {
            $this->erreur('Token CSRF invalide.', 'login.php');
            return;
        }

        $login    = $this->xss($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($login) || empty($password)) {
            $this->erreur('Identifiants manquants.', 'login.php');
            return;
        }

        // Nettoyage sessions inactives (F6)
        $this->model->nettoyerSessionsInactives(30);

        $user = $this->model->authentifier($login, $password);
        if (!$user) {
            $this->erreur('Identifiant ou mot de passe incorrect.', 'login.php');
            return;
        }

        // Régénère l'ID de session (fixation de session)
        session_regenerate_id(true);
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['login']     = $user['login'];
        $_SESSION['role']      = $user['role'];
        $_SESSION['last_act']  = time();
        unset($_SESSION['csrf_token']);

        // Enregistrement session active (F6)
        $token = bin2hex(random_bytes(32));
        $_SESSION['session_token'] = $token;
        $this->model->enregistrerSession($user['id'], $token);

        $this->redirigerSelonRole($user['role']);
    }

    /**
     * GET — Déconnexion
     */
    public function deconnexion(): void
    {
        if (!empty($_SESSION['session_token'])) {
            $this->model->supprimerSession($_SESSION['session_token']);
        }
        session_destroy();
        header('Location: /index.php');
        exit;
    }

    /**
     * Vérifie et rafraîchit l'inactivité (à appeler dans les autres controllers)
     */
    public static function verifierSession(int $maxInactivite = 1800): void
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['user_id'])) {
            header('Location: /index.php?erreur=' . urlencode('Session expirée. Veuillez vous reconnecter.'));
            exit;
        }
        if (isset($_SESSION['last_act']) && (time() - $_SESSION['last_act']) > $maxInactivite) {
            session_destroy();
            header('Location: /index.php?erreur=' . urlencode('Session expirée pour inactivité.'));
            exit;
        }
        $_SESSION['last_act'] = time();
    }

    /**
     * Vérifie qu'un rôle requis est présent — protège les routes
     */
    public static function exigerRole(string ...$roles): void
    {
        self::verifierSession();
        if (!in_array($_SESSION['role'] ?? '', $roles, true)) {
            http_response_code(403);
            require __DIR__ . '/../Views/shared/403.php';
            exit;
        }
    }

    // ─── Helpers ─────────────────────────────────────────────

    private function estConnecte(): bool
    {
        return !empty($_SESSION['user_id']);
    }

    private function redirigerSelonRole(string $role): void
    {
        $map = [
            'admin'      => '/Controllers/AdminController.php?action=index',
            'secretaire' => '/Controllers/SecretaireController.php?action=index',
            'praticien'  => '/Controllers/MedecinController.php?action=index',
            'patient'    => '/Controllers/PatientController.php?action=index',
        ];
        header('Location: ' . ($map[$role] ?? '/index.php'));
        exit;
    }

    private function erreur(string $msg, string $retour): void
    {
        $_SESSION['flash_error'] = $msg;
        header('Location: /index.php');
        exit;
    }

    /**
     * Génère un token CSRF pour les formulaires (utilisé dans les vues)
     */
    public static function csrfField(): string
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        $token = htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8');
        return "<input type='hidden' name='csrf_token' value='{$token}'>";
    }
}
