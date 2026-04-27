<?php
// =========================================================
// Controllers/SecretaireController.php
// Toutes les actions accessibles à la secrétaire
// =========================================================
require_once __DIR__ . '/../Controllers/AuthController.php';
require_once __DIR__ . '/../Models/PatientModel.php';
require_once __DIR__ . '/../Models/MedecinModel.php';
require_once __DIR__ . '/../Models/RendezVousModel.php';
require_once __DIR__ . '/../Models/EmailModel.php';
require_once __DIR__ . '/../Models/UtilisateurModel.php';

class SecretaireController
{
    private PatientModel     $patients;
    private MedecinModel     $medecins;
    private RendezVousModel  $rdv;
    private EmailModel       $email;
    private UtilisateurModel $users;

    public function __construct()
    {
        AuthController::exigerRole('secretaire', 'admin');
        $this->patients = new PatientModel();
        $this->medecins = new MedecinModel();
        $this->rdv      = new RendezVousModel();
        $this->email    = new EmailModel();
        $this->users    = new UtilisateurModel();
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
        $stats = $this->rdv->statistiquesMois();
        require __DIR__ . '/../Views/secretaire/dashboard.php';
    }

    // ─── Gestion Patients ─────────────────────────────────────

    /**
     * F1 — Affiche le formulaire de création de fiche patient
     */
    public function creerPatientForm(): void
    {
        require __DIR__ . '/../Views/secretaire/creer_patient.php';
    }

    /**
     * F1 — Traite la création de fiche patient (POST)
     */
    public function creerPatient(): void
    {
        if (!$this->csrfValide()) { $this->flash('error', 'Token CSRF invalide.'); $this->rediriger('creerPatientForm'); return; }

        $donnees = [
            'nom'       => $this->xss($_POST['nom']       ?? ''),
            'prenom'    => $this->xss($_POST['prenom']    ?? ''),
            'telephone' => $this->xss($_POST['telephone'] ?? ''),
            'email'     => $this->xss($_POST['email']     ?? ''),
        ];

        $result = $this->patients->creer($donnees);

        if ($result['succes']) {
            $this->flash('success', $result['message']);
            $this->rediriger('fichePatient&id=' . $result['patient_id']);
        } else {
            $this->flash('error', $result['message']);
            $this->rediriger('creerPatientForm');
        }
    }

    /**
     * F2 — Recherche un patient
     */
    public function rechercherPatient(): void
    {
        $terme    = $this->xss($_GET['q'] ?? '');
        $patients = $terme ? $this->patients->rechercher($terme) : [];
        require __DIR__ . '/../Views/secretaire/recherche_patient.php';
    }

    /**
     * F3/F4 — Fiche d'un patient (infos admin uniquement pour la secrétaire)
     */
    public function fichePatient(): void
    {
        $id      = (int) ($_GET['id'] ?? 0);
        $patient = $this->patients->trouverInfosAdmin($id);
        if (!$patient) { $this->flash('error', 'Patient introuvable.'); $this->rediriger('rechercherPatient'); return; }
        $rdvs = $this->rdv->rdvParPatient($id);
        require __DIR__ . '/../Views/secretaire/fiche_patient.php';
    }

    /**
     * F3 — Traite la modification des infos admin (POST)
     */
    public function modifierPatient(): void
    {
        if (!$this->csrfValide()) { $this->flash('error', 'Token CSRF invalide.'); $this->rediriger('index'); return; }

        $id      = (int) ($_POST['patient_id'] ?? 0);
        $donnees = [
            'nom'       => $this->xss($_POST['nom']       ?? ''),
            'prenom'    => $this->xss($_POST['prenom']    ?? ''),
            'telephone' => $this->xss($_POST['telephone'] ?? ''),
            'email'     => $this->xss($_POST['email']     ?? ''),
        ];

        $result = $this->patients->modifierInfosAdmin($id, $donnees);
        $this->flash($result['succes'] ? 'success' : 'error', $result['message']);
        $this->rediriger('fichePatient&id=' . $id);
    }

    // ─── Gestion Rendez-vous ──────────────────────────────────

    /**
     * F1/F2 RDV — Formulaire de prise de RDV
     */
    public function prendreRdvForm(): void
    {
        $idPatient = (int) ($_GET['patient_id'] ?? 0);
        $patient   = $idPatient ? $this->patients->trouverInfosAdmin($idPatient) : null;
        $medecins  = $this->medecins->listerTous();
        $motifs    = $this->listerMotifs();
        require __DIR__ . '/../Views/secretaire/prendre_rdv.php';
    }

    /**
     * F1/F2 RDV — Traite la prise de RDV (POST)
     */
    public function prendreRdv(): void
    {
        if (!$this->csrfValide()) { $this->flash('error', 'Token CSRF invalide.'); $this->rediriger('index'); return; }

        $idCreneau = (int) ($_POST['id_creneau'] ?? 0);
        $idPatient = (int) ($_POST['id_patient'] ?? 0);
        $idMotif   = (int) ($_POST['id_motif']   ?? 0) ?: null;

        if (!$idCreneau || !$idPatient) {
            $this->flash('error', 'Données manquantes pour le rendez-vous.');
            $this->rediriger('prendreRdvForm');
            return;
        }

        $result = $this->rdv->creer($idCreneau, $idPatient, $idMotif);

        if ($result['succes']) {
            // Envoi email de confirmation
            $patient = $this->patients->trouverInfosAdmin($idPatient);
            if ($patient) {
                $rdv = $this->rdv->trouverParId($result['id_rdv']);
                if ($rdv) {
                    $this->email->envoyerConfirmationRdv(
                        $patient['email'],
                        $patient['prenom'] . ' ' . $patient['nom'],
                        $rdv['date_planning'],
                        $rdv['heure_debut'],
                        'Praticien'
                    );
                }
            }
            $this->flash('success', 'Rendez-vous créé avec succès.');
        } else {
            $this->flash('error', $result['message']);
        }
        $this->rediriger('agendaMedecin');
    }

    /**
     * F3 RDV — Formulaire de modification
     */
    public function modifierRdvForm(): void
    {
        $idRdv    = (int) ($_GET['id'] ?? 0);
        $rdv      = $this->rdv->trouverParId($idRdv);
        $medecins = $this->medecins->listerTous();
        require __DIR__ . '/../Views/secretaire/modifier_rdv.php';
    }

    /**
     * F3 RDV — Traite la modification (POST)
     */
    public function modifierRdv(): void
    {
        if (!$this->csrfValide()) { $this->flash('error', 'Token CSRF invalide.'); $this->rediriger('index'); return; }

        $idRdv     = (int) ($_POST['id_rdv']      ?? 0);
        $idCreneau = (int) ($_POST['id_creneau']  ?? 0);
        $result    = $this->rdv->modifier($idRdv, $idCreneau);

        $this->flash($result['succes'] ? 'success' : 'error', $result['message']);
        $this->rediriger('agendaMedecin');
    }

    /**
     * F4 RDV — Annuler un rendez-vous
     */
    public function annulerRdv(): void
    {
        if (!$this->csrfValide()) { $this->flash('error', 'Token CSRF invalide.'); $this->rediriger('index'); return; }

        $idRdv  = (int) ($_POST['id_rdv'] ?? 0);
        $result = $this->rdv->annuler($idRdv);
        $this->flash($result['succes'] ? 'success' : 'error', $result['message']);
        $this->rediriger('agendaMedecin');
    }

    /**
     * Vue agenda d'un médecin (F6 RDV)
     */
    public function agendaMedecin(): void
    {
        $idMedecin  = (int) ($_GET['medecin_id'] ?? 0);
        $dateDebut  = $_GET['debut']  ?? date('Y-m-d');
        $dateFin    = $_GET['fin']    ?? date('Y-m-d', strtotime('+6 days'));
        $medecins   = $this->medecins->listerTous();
        $rdvs       = $idMedecin ? $this->rdv->rdvParMedecin($idMedecin, $dateDebut, $dateFin) : [];
        $creneaux   = $idMedecin ? $this->medecins->agendaSemaine($idMedecin, $dateDebut, $dateFin) : [];
        require __DIR__ . '/../Views/secretaire/agenda_medecin.php';
    }

    /**
     * Recherche médecins
     */
    public function rechercherMedecin(): void
    {
        $terme    = $this->xss($_GET['q'] ?? '');
        $medecins = $terme ? $this->medecins->rechercher($terme) : $this->medecins->listerTous();
        require __DIR__ . '/../Views/secretaire/recherche_medecin.php';
    }

    /**
     * Créneaux disponibles (AJAX)
     */
    public function creneauxDisponibles(): void
    {
        header('Content-Type: application/json');
        $idMedecin = (int) ($_GET['medecin_id'] ?? 0);
        $date      = $_GET['date'] ?? '';
        if (!$idMedecin || !$date) { echo json_encode([]); exit; }
        echo json_encode($this->medecins->creneauxDisponibles($idMedecin, $date));
        exit;
    }

    // ─── Helpers ─────────────────────────────────────────────

    private function listerMotifs(): array
    {
        $pdo  = getConnexion();
        $stmt = $pdo->query('SELECT * FROM motifs ORDER BY libelle');
        return $stmt->fetchAll();
    }

    private function flash(string $type, string $message): void
    {
        $_SESSION['flash_' . $type] = $message;
    }

    private function rediriger(string $action): void
    {
        header('Location: /Controllers/SecretaireController.php?action=' . $action);
        exit;
    }
}

// ─── Routeur ─────────────────────────────────────────────
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'] ?? '')) {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $ctrl   = new SecretaireController();
    $action = $_GET['action'] ?? 'index';
    $method = $_SERVER['REQUEST_METHOD'];

    $routes = [
        'index'              => ['GET',  'index'],
        'creerPatientForm'   => ['GET',  'creerPatientForm'],
        'creerPatient'       => ['POST', 'creerPatient'],
        'rechercherPatient'  => ['GET',  'rechercherPatient'],
        'fichePatient'       => ['GET',  'fichePatient'],
        'modifierPatient'    => ['POST', 'modifierPatient'],
        'prendreRdvForm'     => ['GET',  'prendreRdvForm'],
        'prendreRdv'         => ['POST', 'prendreRdv'],
        'modifierRdvForm'    => ['GET',  'modifierRdvForm'],
        'modifierRdv'        => ['POST', 'modifierRdv'],
        'annulerRdv'         => ['POST', 'annulerRdv'],
        'agendaMedecin'      => ['GET',  'agendaMedecin'],
        'rechercherMedecin'  => ['GET',  'rechercherMedecin'],
        'creneauxDisponibles'=> ['GET',  'creneauxDisponibles'],
    ];

    if (isset($routes[$action])) {
        [$methodeAttendue, $nomMethode] = $routes[$action];
        if ($method !== $methodeAttendue) { http_response_code(405); die('Méthode HTTP non autorisée.'); }
        $ctrl->$nomMethode();
    } else {
        $ctrl->index();
    }
}
