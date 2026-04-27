<?php
// =========================================================
// Controllers/PatientController.php
// Espace patient — consultation RDV, profil
// F7/F8 RDV : voir ses RDV, pas de modification possible
// =========================================================
require_once __DIR__ . '/../Controllers/AuthController.php';
require_once __DIR__ . '/../Models/PatientModel.php';
require_once __DIR__ . '/../Models/RendezVousModel.php';
require_once __DIR__ . '/../Models/UtilisateurModel.php';

class PatientController
{
    private PatientModel     $patients;
    private RendezVousModel  $rdv;
    private UtilisateurModel $users;

    public function __construct()
    {
        AuthController::exigerRole('patient');
        $this->patients = new PatientModel();
        $this->rdv      = new RendezVousModel();
        $this->users    = new UtilisateurModel();
    }

    private function csrfValide(): bool
    {
        $token = $_POST['csrf_token'] ?? '';
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

    // ─── F7 RDV — Liste des RDV du patient ───────────────────

    public function index(): void
    {
        $patient = $this->obtenirPatientConnecte();
        $rdvs    = $patient ? $this->rdv->rdvParPatient($patient['id']) : [];
        require __DIR__ . '/../Views/patient/mes_rdv.php';
    }

    // ─── Profil patient ───────────────────────────────────────

    public function profil(): void
    {
        $patient = $this->obtenirPatientConnecte();
        require __DIR__ . '/../Views/patient/profil.php';
    }

    // ─── Changer son mot de passe (F8 auth) ──────────────────

    public function changerMdpForm(): void
    {
        require __DIR__ . '/../Views/shared/changer_mdp.php';
    }

    public function changerMdp(): void
    {
        if (!$this->csrfValide()) { $this->flash('error', 'Token CSRF invalide.'); $this->rediriger('changerMdpForm'); return; }

        $ancienMdp  = $_POST['ancien_mdp']   ?? '';
        $nouveauMdp = $_POST['nouveau_mdp']  ?? '';
        $confirm    = $_POST['confirm_mdp']  ?? '';

        if ($nouveauMdp !== $confirm) { $this->flash('error', 'Les mots de passe ne correspondent pas.'); $this->rediriger('changerMdpForm'); return; }
        if (strlen($nouveauMdp) < 8)  { $this->flash('error', 'Le mot de passe doit contenir au moins 8 caractères.'); $this->rediriger('changerMdpForm'); return; }

        $result = $this->users->changerMotDePasse($_SESSION['user_id'], $ancienMdp, $nouveauMdp);
        $this->flash($result['succes'] ? 'success' : 'error', $result['message']);
        $this->rediriger($result['succes'] ? 'index' : 'changerMdpForm');
    }

    // ─── Helpers ─────────────────────────────────────────────

    private function obtenirPatientConnecte(): ?array
    {
        // Trouve le patient via l'id_utilisateur stocké en session
        $pdo  = getConnexion();
        $stmt = $pdo->prepare('SELECT * FROM patients WHERE id_utilisateur = :uid LIMIT 1');
        $stmt->execute([':uid' => $_SESSION['user_id']]);
        return $stmt->fetch() ?: null;
    }

    private function flash(string $type, string $message): void
    {
        $_SESSION['flash_' . $type] = $message;
    }

    private function rediriger(string $action): void
    {
        header('Location: /Controllers/PatientController.php?action=' . $action);
        exit;
    }
}

// ─── Routeur ─────────────────────────────────────────────
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'] ?? '')) {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $ctrl   = new PatientController();
    $action = $_GET['action'] ?? 'index';
    $method = $_SERVER['REQUEST_METHOD'];

    $routes = [
        'index'          => ['GET',  'index'],
        'profil'         => ['GET',  'profil'],
        'changerMdpForm' => ['GET',  'changerMdpForm'],
        'changerMdp'     => ['POST', 'changerMdp'],
    ];

    if (isset($routes[$action])) {
        [$methodeAttendue, $nomMethode] = $routes[$action];
        if ($method !== $methodeAttendue) { http_response_code(405); die('Méthode HTTP non autorisée.'); }
        $ctrl->$nomMethode();
    } else {
        $ctrl->index();
    }
}
