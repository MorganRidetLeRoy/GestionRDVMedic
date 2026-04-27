<?php
// =========================================================
// Controllers/AdminController.php
// Administration — comptes, réinitialisations, liste
// =========================================================
require_once __DIR__ . '/../Controllers/AuthController.php';
require_once __DIR__ . '/../Models/UtilisateurModel.php';
require_once __DIR__ . '/../Models/MedecinModel.php';
require_once __DIR__ . '/../Models/EmailModel.php';

class AdminController
{
    private UtilisateurModel $users;
    private MedecinModel     $medecins;
    private EmailModel       $email;

    public function __construct()
    {
        AuthController::exigerRole('admin');
        $this->users    = new UtilisateurModel();
        $this->medecins = new MedecinModel();
        $this->email    = new EmailModel();
    }

    private function xss(string $v): string
    {
        return htmlspecialchars(strip_tags(trim($v)), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    private function csrfValide(): bool
    {
        $token = $_POST['csrf_token'] ?? '';
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

    // ─── Dashboard ───────────────────────────────────────────

    public function index(): void
    {
        $comptes = $this->users->listerActifs();
        require __DIR__ . '/../Views/admin/dashboard.php';
    }

    // ─── F3 Admin — Liste des comptes actifs ─────────────────

    public function listeComptes(): void
    {
        $comptes = $this->users->listerActifs();
        require __DIR__ . '/../Views/admin/liste_comptes.php';
    }

    // ─── F3 Admin — Créer un compte praticien/secrétaire ─────

    public function creerCompteForm(): void
    {
        require __DIR__ . '/../Views/admin/creer_compte.php';
    }

    public function creerCompte(): void
    {
        if (!$this->csrfValide()) { $this->flash('error', 'Token CSRF invalide.'); $this->rediriger('creerCompteForm'); return; }

        $login = $this->xss($_POST['login'] ?? '');
        $role  = $_POST['role'] ?? '';

        // F4 Admin — Rôle admin unique et jamais pour patient
        if (!in_array($role, ['secretaire', 'praticien'], true)) {
            $this->flash('error', 'Rôle invalide. Seuls secrétaire et praticien peuvent être créés ici.');
            $this->rediriger('creerCompteForm');
            return;
        }

        // Mot de passe temporaire aléatoire
        $mdpTemporaire = $this->genererMdpTemporaire();

        try {
            $id = $this->users->creer($login, $mdpTemporaire, $role);
            // Si praticien, on peut ajouter les infos médecin ici (simplifié)
            $this->flash('success', "Compte créé. Login : {$login} | MDP temporaire : {$mdpTemporaire}");
            $this->rediriger('listeComptes');
        } catch (Exception $e) {
            $this->flash('error', 'Login déjà utilisé ou erreur lors de la création.');
            $this->rediriger('creerCompteForm');
        }
    }

    // ─── F1 Admin — Désactiver un compte ─────────────────────

    public function desactiverCompte(): void
    {
        if (!$this->csrfValide()) { $this->flash('error', 'Token CSRF invalide.'); $this->rediriger('listeComptes'); return; }

        $id = (int) ($_POST['user_id'] ?? 0);
        if ($id === $_SESSION['user_id']) {
            $this->flash('error', 'Vous ne pouvez pas désactiver votre propre compte.');
            $this->rediriger('listeComptes');
            return;
        }

        $ok = $this->users->desactiver($id);
        $this->flash($ok ? 'success' : 'error', $ok ? 'Compte désactivé.' : 'Erreur lors de la désactivation.');
        $this->rediriger('listeComptes');
    }

    // ─── F2 Admin — Réinitialiser le mot de passe ────────────

    public function reinitialiserMdp(): void
    {
        if (!$this->csrfValide()) { $this->flash('error', 'Token CSRF invalide.'); $this->rediriger('listeComptes'); return; }

        $id   = (int) ($_POST['user_id'] ?? 0);
        $user = $this->users->trouverParId($id);
        if (!$user) { $this->flash('error', 'Utilisateur introuvable.'); $this->rediriger('listeComptes'); return; }

        $nouveauMdp = $this->genererMdpTemporaire();
        $ok = $this->users->reinitialiserMotDePasse($id, $nouveauMdp);

        $this->flash(
            $ok ? 'success' : 'error',
            $ok ? "Mot de passe réinitialisé pour {$user['login']} : <strong>{$nouveauMdp}</strong>" : 'Erreur lors de la réinitialisation.'
        );
        $this->rediriger('listeComptes');
    }

    // ─── Changer son propre mot de passe (F8 auth) ───────────

    public function changerMdpForm(): void
    {
        require __DIR__ . '/../Views/shared/changer_mdp.php';
    }

    public function changerMdp(): void
    {
        if (!$this->csrfValide()) { $this->flash('error', 'Token CSRF invalide.'); $this->rediriger('changerMdpForm'); return; }

        $ancienMdp  = $_POST['ancien_mdp']    ?? '';
        $nouveauMdp = $_POST['nouveau_mdp']   ?? '';
        $confirm    = $_POST['confirm_mdp']   ?? '';

        if ($nouveauMdp !== $confirm) { $this->flash('error', 'Les mots de passe ne correspondent pas.'); $this->rediriger('changerMdpForm'); return; }
        if (strlen($nouveauMdp) < 8)  { $this->flash('error', 'Le mot de passe doit contenir au moins 8 caractères.'); $this->rediriger('changerMdpForm'); return; }

        $result = $this->users->changerMotDePasse($_SESSION['user_id'], $ancienMdp, $nouveauMdp);
        $this->flash($result['succes'] ? 'success' : 'error', $result['message']);
        $this->rediriger($result['succes'] ? 'index' : 'changerMdpForm');
    }

    // ─── Helpers ─────────────────────────────────────────────

    private function genererMdpTemporaire(): string
    {
        $chars = 'abcdefghijkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789!@#';
        $mdp   = '';
        for ($i = 0; $i < 10; $i++) {
            $mdp .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $mdp;
    }

    private function flash(string $type, string $message): void
    {
        $_SESSION['flash_' . $type] = $message;
    }

    private function rediriger(string $action): void
    {
        header('Location: /Controllers/AdminController.php?action=' . $action);
        exit;
    }
}

// ─── Routeur ─────────────────────────────────────────────
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'] ?? '')) {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $ctrl   = new AdminController();
    $action = $_GET['action'] ?? 'index';
    $method = $_SERVER['REQUEST_METHOD'];

    $routes = [
        'index'            => ['GET',  'index'],
        'listeComptes'     => ['GET',  'listeComptes'],
        'creerCompteForm'  => ['GET',  'creerCompteForm'],
        'creerCompte'      => ['POST', 'creerCompte'],
        'desactiverCompte' => ['POST', 'desactiverCompte'],
        'reinitialiserMdp' => ['POST', 'reinitialiserMdp'],
        'changerMdpForm'   => ['GET',  'changerMdpForm'],
        'changerMdp'       => ['POST', 'changerMdp'],
    ];

    if (isset($routes[$action])) {
        [$methodeAttendue, $nomMethode] = $routes[$action];
        if ($method !== $methodeAttendue) { http_response_code(405); die('Méthode HTTP non autorisée.'); }
        $ctrl->$nomMethode();
    } else {
        $ctrl->index();
    }
}
