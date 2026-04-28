<?php
// =========================================================
// Controllers/AuthController.php
// Authentification — connexion, déconnexion, inscription
// Sécurités : SQLi (PDO), XSS (htmlspecialchars), CSRF, sessions
// =========================================================
require_once __DIR__ . './../Models/UtilisateurModel.php';

class AuthController
{
    private UtilisateurModel $model;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->model = new UtilisateurModel();
    }

    // ─── Protection CSRF ─────────────────────────────────────

    // Génère un token CSRF unique pour protéger les formulaires.
    private function genererTokenCSRF(): string
    {
        if (empty($_SESSION['csrf_token'])) {

            // Génère un token aléatoire de 32 octets et le convertit en hexadécimal.
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    // Valide le token CSRF soumis dans un formulaire.
    private function validerCSRF(string $token): bool
    {
        // Vérifie que le token existe dans la session et qu'il correspond à celui soumis.
        return isset($_SESSION['csrf_token'])
            && hash_equals($_SESSION['csrf_token'], $token);
    }

    // ─── Protection XSS ──────────────────────────────────────

    // Nettoie une chaîne de caractères pour éviter les attaques XSS.
    private function xss(string $v): string
    {
        // Supprime les balises HTML, les espaces superflus, et convertit les caractères spéciaux en entités HTML.
        return htmlspecialchars(strip_tags(trim($v)), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    // ─── Routes ──────────────────────────────────────────────

    /**
     * GET — Affiche la page de connexion (index)
     */
    public function index(): void
    {
        // Redirige l'utilisateur selon son rôle s'il est déjà connecté.
        if ($this->estConnecte()) {
            $this->redirigerSelonRole($_SESSION['role']);
            return;
        }

        // Génère un token CSRF pour le formulaire de connexion.
        $csrf = $this->genererTokenCSRF();
        require __DIR__ . './../login.php';
    }

    /**
     * POST — Traitement du formulaire de connexion
     */
    public function connexion(): void
    {
        // Vérifie la validité du token CSRF.
        if (!$this->validerCSRF($_POST['csrf_token'] ?? '')) {
            $this->erreur('Token CSRF invalide.', 'login.php');
            return;
        }

        // Nettoie et récupère les identifiants soumis.
        $login    = $this->xss($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        // Vérifie que les champs ne sont pas vides.
        if (empty($login) || empty($password)) {
            $this->erreur('Identifiants manquants.', 'login.php');
            return;
        }

         // Nettoie les sessions inactives (F6 : fonctionnalité de gestion des sessions).
        $this->model->nettoyerSessionsInactives(30);

        // Authentifie l'utilisateur.
        $user = $this->model->authentifier($login, $password);
        if (!$user) {
            $this->erreur('Identifiant ou mot de passe incorrect.', 'login.php');
            return;
        }

        // Régénère l'ID de session pour éviter la fixation de session.
        session_regenerate_id(true);
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['login']     = $user['login'];
        $_SESSION['role']      = $user['role'];
        $_SESSION['last_act']  = time();          // Met à jour le timestamp de la dernière activité.
        unset($_SESSION['csrf_token']);

        // Enregistre la session active dans la base de données (F6).
        $token = bin2hex(random_bytes(32));
        $_SESSION['session_token'] = $token;
        $this->model->enregistrerSession($user['id'], $token);

        // Redirige l'utilisateur selon son rôle.
        $this->redirigerSelonRole($user['role']);
    }

    /**
     * GET — Déconnexion
     */
    public function deconnexion(): void
    {
        // Supprime la session active de la base de données si elle existe.
        if (!empty($_SESSION['session_token'])) {
            $this->model->supprimerSession($_SESSION['session_token']);
        }
        session_destroy();
        header('Location: ./../login.php');
        exit;
    }

    /**
     * Vérifie et rafraîchit l'inactivité (à appeler dans les autres controllers)
     */
    public static function verifierSession(int $maxInactivite = 1800): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user_id'])) {

            // Redirige vers la page de connexion si l'utilisateur n'est pas connecté.
            header('Location: ./../login.php?erreur=' . urlencode('Session expirée. Veuillez vous reconnecter.'));
            exit;
        }

        // Vérifie si la session a expiré à cause de l'inactivité.
        if (isset($_SESSION['last_act']) && (time() - $_SESSION['last_act']) > $maxInactivite) {
            session_destroy();
            header('Location: ./../login.php?erreur=' . urlencode('Session expirée pour inactivité.'));
            exit;
        }
        $_SESSION['last_act'] = time();   // Met à jour le timestamp de la dernière activité.
    }

    /**
     * Vérifie qu'un rôle requis est présent — protège les routes
     */
    public static function exigerRole(string ...$roles): void
    {
        self::verifierSession();                                    // Vérifie d'abord la validité de la session.
        if (!in_array($_SESSION['role'] ?? '', $roles, true)) {     // Vérifie si le rôle de l'utilisateur est autorisé.
            http_response_code(403);                                // Accès interdit.
            require __DIR__ . './../Views/auth/403.php';
            exit;
        }
    }

    // ─── Helpers ─────────────────────────────────────────────

    // Vérifie si l'utilisateur est connecté.
    private function estConnecte(): bool
    {
        return !empty($_SESSION['user_id']);
    }

    // Redirige l'utilisateur selon son rôle.
    private function redirigerSelonRole(string $role): void
    {
        // Tableau associatif pour mapper les rôles aux URLs de redirection.
        $map = [
            'admin'      => './AdminController.php?action=index',
            'secretaire' => './SecretaireController.php?action=index',
            'praticien'  => './MedecinController.php?action=index',
            'patient'    => './PatientController.php?action=index',
        ];
        header('Location: ' . ($map[$role] ?? './../login.php'));    // Redirige vers l'URL correspondante.
        exit;
    }

    // Affiche un message d'erreur et redirige.
    private function erreur(string $msg, string $retour): void
    {
        $_SESSION['flash_error'] = $msg;      // Stocke le message d'erreur dans la session.
        header('Location: ./../login.php');
        exit;
    }

    /**
     * Génère un champ caché CSRF pour les formulaires.
     */
    public static function csrfField(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Génère un token CSRF s'il n'existe pas.
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        // Échappe le token pour éviter les problèmes XSS.
        $token = htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8');

        // Retourne un champ caché HTML avec le token.
        return "<input type='hidden' name='csrf_token' value='{$token}'>";
    }
}
