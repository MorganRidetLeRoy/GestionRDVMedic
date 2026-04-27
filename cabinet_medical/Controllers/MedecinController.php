<?php
// =========================================================
// Controllers/MedecinController.php
// Actions réservées au praticien
// =========================================================
require_once __DIR__ . '/../Controllers/AuthController.php';
require_once __DIR__ . '/../Models/PatientModel.php';
require_once __DIR__ . '/../Models/MedecinModel.php';
require_once __DIR__ . '/../Models/RendezVousModel.php';

class MedecinController
{
    private PatientModel    $patients;
    private MedecinModel    $medecins;
    private RendezVousModel $rdv;

    public function __construct()
    {
        AuthController::exigerRole('praticien', 'admin');
        $this->patients = new PatientModel();
        $this->medecins = new MedecinModel();
        $this->rdv      = new RendezVousModel();
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
        $medecin = $this->medecins->trouverParUtilisateur($_SESSION['user_id']);
        $stats   = $medecin ? $this->medecins->statistiquesMois($medecin['id_medecin']) : [];
        require __DIR__ . '/../Views/medecin/dashboard.php';
    }

    // ─── Patients ─────────────────────────────────────────────

    /**
     * F2 — Rechercher un patient
     */
    public function rechercherPatient(): void
    {
        $terme    = $this->xss($_GET['q'] ?? '');
        $patients = $terme ? $this->patients->rechercher($terme) : [];
        require __DIR__ . '/../Views/medecin/recherche_patient.php';
    }

    /**
     * F5 — Fiche complète patient (admin + médical)
     */
    public function ficheComplete(): void
    {
        $id      = (int) ($_GET['id'] ?? 0);
        $patient = $this->patients->trouverFicheComplete($id);
        if (!$patient) { $this->flash('error', 'Patient introuvable.'); $this->rediriger('rechercherPatient'); return; }
        $rdvs = $this->rdv->rdvParPatient($id);
        require __DIR__ . '/../Views/medecin/fiche_complete.php';
    }

    /**
     * F6 — Modifier infos complètes (admin + médical)
     */
    public function modifierPatient(): void
    {
        if (!$this->csrfValide()) { $this->flash('error', 'Token CSRF invalide.'); $this->rediriger('index'); return; }

        $id      = (int) ($_POST['patient_id'] ?? 0);
        $donnees = [
            'nom'             => $this->xss($_POST['nom']             ?? ''),
            'prenom'          => $this->xss($_POST['prenom']          ?? ''),
            'telephone'       => $this->xss($_POST['telephone']       ?? ''),
            'email'           => $this->xss($_POST['email']           ?? ''),
            'antecedents'     => strip_tags($_POST['antecedents']     ?? ''),
            'allergies'       => strip_tags($_POST['allergies']       ?? ''),
            'traitements'     => strip_tags($_POST['traitements']     ?? ''),
            'notes_medicales' => strip_tags($_POST['notes_medicales'] ?? ''),
        ];

        $result = $this->patients->modifierInfosMedicales($id, $donnees);
        $this->flash($result['succes'] ? 'success' : 'error', $result['message']);
        $this->rediriger('ficheComplete&id=' . $id);
    }

    // ─── Agenda ──────────────────────────────────────────────

    /**
     * F6 RDV — Consulter l'agenda d'un praticien
     */
    public function agendaMedecin(): void
    {
        $idMedecin = (int) ($_GET['medecin_id'] ?? 0);
        $dateDebut = $_GET['debut'] ?? date('Y-m-d');
        $dateFin   = $_GET['fin']   ?? date('Y-m-d', strtotime('+6 days'));
        $medecins  = $this->medecins->listerTous();
        $rdvs      = $idMedecin ? $this->rdv->rdvParMedecin($idMedecin, $dateDebut, $dateFin) : [];
        $creneaux  = $idMedecin ? $this->medecins->agendaSemaine($idMedecin, $dateDebut, $dateFin) : [];
        require __DIR__ . '/../Views/medecin/agenda.php';
    }

    /**
     * F5 RDV — Prendre un RDV pour un patient
     */
    public function prendreRdv(): void
    {
        if (!$this->csrfValide()) { $this->flash('error', 'Token CSRF invalide.'); $this->rediriger('index'); return; }

        $idCreneau = (int) ($_POST['id_creneau'] ?? 0);
        $idPatient = (int) ($_POST['id_patient'] ?? 0);
        $idMotif   = (int) ($_POST['id_motif']   ?? 0) ?: null;

        $result = $this->rdv->creer($idCreneau, $idPatient, $idMotif);
        $this->flash($result['succes'] ? 'success' : 'error', $result['message']);
        $this->rediriger('agendaMedecin');
    }

    /**
     * F4 RDV — Annuler un RDV
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
     * Mon agenda personnel
     */
    public function monAgenda(): void
    {
        $medecin   = $this->medecins->trouverParUtilisateur($_SESSION['user_id']);
        $dateDebut = $_GET['debut'] ?? date('Y-m-d');
        $dateFin   = $_GET['fin']   ?? date('Y-m-d', strtotime('+6 days'));
        $creneaux  = $medecin ? $this->medecins->agendaSemaine($medecin['id_medecin'], $dateDebut, $dateFin) : [];
        require __DIR__ . '/../Views/medecin/mon_agenda.php';
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

    private function flash(string $type, string $message): void
    {
        $_SESSION['flash_' . $type] = $message;
    }

    private function rediriger(string $action): void
    {
        header('Location: /Controllers/MedecinController.php?action=' . $action);
        exit;
    }
}

// ─── Routeur ─────────────────────────────────────────────
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'] ?? '')) {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $ctrl   = new MedecinController();
    $action = $_GET['action'] ?? 'index';
    $method = $_SERVER['REQUEST_METHOD'];

    $routes = [
        'index'               => ['GET',  'index'],
        'rechercherPatient'   => ['GET',  'rechercherPatient'],
        'ficheComplete'       => ['GET',  'ficheComplete'],
        'modifierPatient'     => ['POST', 'modifierPatient'],
        'agendaMedecin'       => ['GET',  'agendaMedecin'],
        'monAgenda'           => ['GET',  'monAgenda'],
        'prendreRdv'          => ['POST', 'prendreRdv'],
        'annulerRdv'          => ['POST', 'annulerRdv'],
        'creneauxDisponibles' => ['GET',  'creneauxDisponibles'],
    ];

    if (isset($routes[$action])) {
        [$methodeAttendue, $nomMethode] = $routes[$action];
        if ($method !== $methodeAttendue) { http_response_code(405); die('Méthode HTTP non autorisée.'); }
        $ctrl->$nomMethode();
    } else {
        $ctrl->index();
    }
}
