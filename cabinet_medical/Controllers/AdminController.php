<?php
// =========================================================
// Controllers/AdminController.php
// Administration — comptes, réinitialisations, liste
// =========================================================

// Inclusion des fichiers nécessaires pour l'authentification et les modèles.
require_once __DIR__ . './AuthController.php';
require_once __DIR__ . './../Models/UtilisateurModel.php';
require_once __DIR__ . './../Models/MedecinModel.php';
require_once __DIR__ . './../Models/EmailModel.php';

class AdminController
{
    // Déclaration des propriétés pour les modèles.
    private UtilisateurModel $users;
    private MedecinModel     $medecins;
    private EmailModel       $email;

    public function __construct()
    {
        // Vérifie que l'utilisateur est un administrateur.
        AuthController::exigerRole('admin');

        // Initialise les modèles
        $this->users    = new UtilisateurModel();
        $this->medecins = new MedecinModel();
        $this->email    = new EmailModel();
    }

    // Nettoie une chaîne de caractères pour éviter les attaques XSS.
    private function xss(string $v): string
    {
        // Supprime les balises HTML, les espaces superflus et convertit les caractères spéciaux.
        return htmlspecialchars(strip_tags(trim($v)), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    // Vérifie la validité du token CSRF pour protéger contre les attaques CSRF
    private function csrfValide(): bool
    {
        $token = $_POST['csrf_token'] ?? '';

        // Compare le token de la session avec celui du formulaire
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

    // ─── Dashboard ───────────────────────────────────────────

    // Affiche le tableau de bord avec la liste des comptes actifs.
    public function index(): void
    {
        $comptes = $this->users->listerActifs();               // Récupère la liste des comptes actifs
        require __DIR__ . './../Views/admin/dashboard.php';
    }

    // ─── F3 Admin — Liste des comptes actifs ─────────────────

    // Affiche la liste des comptes actifs.
    public function listeComptes(): void
    {
        $comptes = $this->users->listerActifs();
        require __DIR__ . './../Views/admin/liste_comptes.php';
    }

    // ─── F3 Admin — Créer un compte praticien/secrétaire ─────

     // Affiche le formulaire de création de compte.
    public function creerCompteForm(): void
    {
        require __DIR__ . './../Views/admin/creer_compte.php';
    }

    // Traite la création d'un compte praticien ou secrétaire.
    public function creerCompte(): void
    {
        // Vérifie la validité du token CSRF.
        if (!$this->csrfValide()) { 
            $this->flash('error', 'Token CSRF invalide.'); 
            $this->rediriger('creerCompteForm'); 
            return; 
        }

        // Nettoie et récupère le login et le rôle depuis le formulaire.
        $login = $this->xss($_POST['login'] ?? '');
        $role  = $_POST['role'] ?? '';

        // F4 Admin — Rôle admin unique et jamais pour patient.
        // Vérifie que le rôle est valide (seulement secrétaire ou praticien).
        if (!in_array($role, ['secretaire', 'praticien'], true)) {
            $this->flash('error', 'Rôle invalide. Seuls secrétaire et praticien peuvent être créés ici.');
            $this->rediriger('creerCompteForm');
            return;
        }

        // Génère un mot de passe temporaire aléatoire.
        $mdpTemporaire = $this->genererMdpTemporaire();

        try {

            // Crée le compte utilisateur
            $id = $this->users->creer($login, $mdpTemporaire, $role);

            // Affiche un message de succès avec le login et le mot de passe temporaire
            $this->flash('success', "Compte créé. Login : {$login} | MDP temporaire : {$mdpTemporaire}");
            $this->rediriger('listeComptes');

        } catch (Exception $e) {

            // Gère les erreurs (ex: login déjà utilisé)
            $this->flash('error', 'Login déjà utilisé ou erreur lors de la création.');
            $this->rediriger('creerCompteForm');

        }
    }

    // ─── F1 Admin — Désactiver un compte ─────────────────────

    public function desactiverCompte(): void
    {
        if (!$this->csrfValide()) { 
            $this->flash('error', 'Token CSRF invalide.'); 
            $this->rediriger('listeComptes'); 
            return; 
        }

        // Récupère l'ID de l'utilisateur à désactiver.
        $id = (int) ($_POST['user_id'] ?? 0);

        // Empêche la désactivation de son propre compte.
        if ($id === $_SESSION['user_id']) {
            $this->flash('error', 'Vous ne pouvez pas désactiver votre propre compte.');
            $this->rediriger('listeComptes');
            return;
        }

        // Désactive le compte.
        $ok = $this->users->desactiver($id);
        $this->flash($ok ? 'success' : 'error', $ok ? 'Compte désactivé.' : 'Erreur lors de la désactivation.');
        $this->rediriger('listeComptes');
    }

    // ─── F2 Admin — Réinitialiser le mot de passe ────────────

    public function reinitialiserMdp(): void
    {
        if (!$this->csrfValide()) { 
            $this->flash('error', 'Token CSRF invalide.');
            $this->rediriger('listeComptes'); 
            return; 
        }

        $id   = (int) ($_POST['user_id'] ?? 0);
        $user = $this->users->trouverParId($id);

        // Vérifie que l'utilisateur existe
        if (!$user) { 
            $this->flash('error', 'Utilisateur introuvable.'); 
            $this->rediriger('listeComptes'); 
            return; 
        }

        // Génère un nouveau mot de passe temporaire.
        $nouveauMdp = $this->genererMdpTemporaire();
        $ok = $this->users->reinitialiserMotDePasse($id, $nouveauMdp);

        // Affiche un message avec le nouveau mot de passe.
        $this->flash(
            $ok ? 'success' : 'error',
            $ok ? "Mot de passe réinitialisé pour {$user['login']} : <strong>{$nouveauMdp}</strong>" : 'Erreur lors de la réinitialisation.'
        );
        $this->rediriger('listeComptes');
    }

    // ─── Changer son propre mot de passe (F8 auth) ───────────

    public function changerMdpForm(): void
    {
        require __DIR__ . './../Views/admin/changer_mdp.php';
    }

    public function changerMdp(): void
    {
        if (!$this->csrfValide()) { 
            $this->flash('error', 'Token CSRF invalide.'); 
            $this->rediriger('changerMdpForm'); 
            return; 
        }

        // Récupère les mots de passe depuis le formulaire.
        $ancienMdp  = $_POST['ancien_mdp']    ?? '';
        $nouveauMdp = $_POST['nouveau_mdp']   ?? '';
        $confirm    = $_POST['confirm_mdp']   ?? '';

        // Vérifie que les nouveaux mots de passe correspondent.
        if ($nouveauMdp !== $confirm) { 
            $this->flash('error', 'Les mots de passe ne correspondent pas.'); 
            $this->rediriger('changerMdpForm'); 
            return; 
        }

        // Vérifie que le mot de passe a au moins 8 caractères
        if (strlen($nouveauMdp) < 8)  { 
            $this->flash('error', 'Le mot de passe doit contenir au moins 8 caractères.'); 
            $this->rediriger('changerMdpForm'); 
            return; 
        }

        // Change le mot de passe
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
            $mdp .= $chars[random_int(0, strlen($chars) - 1)]; // Ajoute un caractère
        }
        return $mdp;
    }

    // Stocke un message flash dans la session.
    private function flash(string $type, string $message): void
    {
        $_SESSION['flash_' . $type] = $message;
    }

    private function rediriger(string $action): void
    {
        header('Location: ./AdminController.php?action=' . $action);
        exit;
    }
}

// ─── Routeur ─────────────────────────────────────────────

// Gestion du routage si le fichier est appelé directement.
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'] ?? '')) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();                             // Démarre la session si ce n'est pas déjà fait
    }

    $ctrl   = new AdminController();        // Instancie le contrôleur
    $action = $_GET['action'] ?? 'index';   // Récupère l'action demandée
    $method = $_SERVER['REQUEST_METHOD'];   // Récupère la méthode HTTP

    // Définit les routes disponibles et les méthodes associées.
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

    // Vérifie si l'action demandée existe dans les routes.
    if (isset($routes[$action])) {
        [$methodeAttendue, $nomMethode] = $routes[$action];
        if ($method !== $methodeAttendue) {                   // Vérifie que la méthode HTTP est correcte.
            http_response_code(405); 
            die('Méthode HTTP non autorisée.'); 
        }
        $ctrl->$nomMethode();                                // Appelle la méthode correspondante.
    } else {
        $ctrl->index();                                      // Redirige vers l'action par défaut si l'action n'existe pas.
    }
}
