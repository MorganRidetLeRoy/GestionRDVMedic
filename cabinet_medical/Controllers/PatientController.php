<?php
// =========================================================
// Controllers/PatientController.php
// Espace patient — consultation RDV, profil
// F7/F8 RDV : voir ses RDV, pas de modification possible
// =========================================================

// Inclusion des fichiers nécessaires : contrôleur d'authentification et modèles de données.
require_once __DIR__ . './AuthController.php';
require_once __DIR__ . './../Models/PatientModel.php';
require_once __DIR__ . './../Models/RendezVousModel.php';
require_once __DIR__ . './../Models/UtilisateurModel.php';

class PatientController
{
    // Déclaration des propriétés pour interagir avec les modèles de données.
    private PatientModel     $patients;
    private RendezVousModel  $rdv;
    private UtilisateurModel $users;

    public function __construct()
    {
        // Vérifie que l'utilisateur connecté a le rôle 'patient'
        AuthController::exigerRole('patient');
        $this->patients = new PatientModel();
        $this->rdv      = new RendezVousModel();
        $this->users    = new UtilisateurModel();
    }

    /**
     * Vérifie la validité du token CSRF pour sécuriser les formulaires
     * @return bool Vrai si le token est valide, faux sinon
     */
    private function csrfValide(): bool
    {
        $token = $_POST['csrf_token'] ?? '';
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

    // ─── F7 RDV — Liste des RDV du patient ───────────────────

    public function index(): void
    {
        // Récupère les informations du patient connecté
        $patient = $this->obtenirPatientConnecte();

        // Récupère les rendez-vous du patient s'il existe
        $rdvs    = $patient ? $this->rdv->rdvParPatient($patient['id']) : [];
        require __DIR__ . './../Views/patient/mes_rdv.php';
    }

    // ─── Profil patient ───────────────────────────────────────

    public function profil(): void
    {
        // Récupère les informations du patient connecté.
        $patient = $this->obtenirPatientConnecte();
        require __DIR__ . './../Views/patient/profil.php';
    }

    // ─── Changer son mot de passe (F8 auth) ──────────────────

    /**
     * Affiche le formulaire de changement de mot de passe
     */
    public function changerMdpForm(): void
    {
        require __DIR__ . './../Views/patient/changer_mdp.php';
    }

     /**
     * Traite la demande des modifications du formulaire de changement de mot de passe.
     */
    public function changerMdp(): void
    {
        // Vérifie la validité du token CSRF.
        if (!$this->csrfValide()) { 
            $this->flash('error', 'Token CSRF invalide.'); 
            $this->rediriger('changerMdpForm'); 
            return; 
        }

        // Récupère les mots de passe saisis dans le formulaire
        $ancienMdp  = $_POST['ancien_mdp']   ?? '';
        $nouveauMdp = $_POST['nouveau_mdp']  ?? '';
        $confirm    = $_POST['confirm_mdp']  ?? '';

        // Vérifie que les nouveaux mots de passe correspondent
        if ($nouveauMdp !== $confirm) { 
            $this->flash('error', 'Les mots de passe ne correspondent pas.'); 
            $this->rediriger('changerMdpForm'); 
            return; 
        }

        // Vérifie que le nouveau mot de passe a au moins 8 caractères
        if (strlen($nouveauMdp) < 8)  { 
            $this->flash('error', 'Le mot de passe doit contenir au moins 8 caractères.'); 
            $this->rediriger('changerMdpForm'); 
            return; 
        }

         // Change le mot de passe de l'utilisateur connecté
        $result = $this->users->changerMotDePasse($_SESSION['user_id'], $ancienMdp, $nouveauMdp);

        // Affiche un message de succès ou d'erreur et redirige
        $this->flash($result['succes'] ? 'success' : 'error', $result['message']);
        $this->rediriger($result['succes'] ? 'index' : 'changerMdpForm');
    }

    // ─── Helpers ─────────────────────────────────────────────

    /**
     * Récupère les informations du patient connecté depuis la base de données
     * @return array|null Tableau associatif des informations du patient, ou null si non trouvé
     */
    private function obtenirPatientConnecte(): ?array
    {
        // Prépare et exécute une requête SQL pour trouver le patient via l'id_utilisateur stocké en session
        $pdo  = getConnexion();
        $stmt = $pdo->prepare('SELECT * FROM patients WHERE id_utilisateur = :uid LIMIT 1');
        $stmt->execute([':uid' => $_SESSION['user_id']]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Stocke un message flash en session pour affichage après redirection
     * @param string $type Type de message (success, error, etc.)
     * @param string $message Contenu du message
     */
    private function flash(string $type, string $message): void
    {
        $_SESSION['flash_' . $type] = $message;
    }

    /**
     * Redirige vers une action spécifique du contrôleur
     * @param string $action Nom de l'action
     */
    private function rediriger(string $action): void
    {
        header('Location: ./PatientController.php?action=' . $action);
        exit;
    }
}

// ─── Routeur ─────────────────────────────────────────────

// Vérifie si le script est appelé directement (et non inclus)
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'] ?? '')) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Instancie le contrôleur
    $ctrl   = new PatientController();

    // Récupère l'action et la méthode HTTP depuis l'URL
    $action = $_GET['action'] ?? 'index';
    $method = $_SERVER['REQUEST_METHOD'];

    // Définit les routes disponibles et les méthodes HTTP autorisées
    $routes = [
        'index'          => ['GET',  'index'],              // Affiche la liste des rendez-vous
        'profil'         => ['GET',  'profil'],             // Affiche le profil du patient
        'changerMdpForm' => ['GET',  'changerMdpForm'],     // Affiche le formulaire de changement de mot de passe
        'changerMdp'     => ['POST', 'changerMdp'],         // Traite la soumission du formulaire de changement de mot de passe
    ];

    // Vérifie si l'action existe et si la méthode HTTP est autorisée
    if (isset($routes[$action])) {
        [$methodeAttendue, $nomMethode] = $routes[$action];
        if ($method !== $methodeAttendue) { 
            http_response_code(405); 
            die('Méthode HTTP non autorisée.'); 
        }
        $ctrl->$nomMethode();
    } else {
        $ctrl->index();
    }
}
