<?php
// =========================================================
// Controllers/MedecinController.php
// Actions réservées au praticien
// =========================================================

// Inclusion des fichiers nécessaires : contrôleur d'authentification et modèles de données.
require_once __DIR__ . './AuthController.php';
require_once __DIR__ . './../Models/PatientModel.php';
require_once __DIR__ . './../Models/MedecinModel.php';
require_once __DIR__ . './../Models/RendezVousModel.php';

class MedecinController
{
    // Déclaration des propriétés pour interagir avec les modèles de données.
    private PatientModel    $patients;
    private MedecinModel    $medecins;
    private RendezVousModel $rdv;

    public function __construct()
    {
        // Vérifie que l'utilisateur connecté a le rôle 'praticien' ou 'admin'
        AuthController::exigerRole('praticien', 'admin');

        // Initialisation des modèles
        $this->patients = new PatientModel();
        $this->medecins = new MedecinModel();
        $this->rdv      = new RendezVousModel();
    }

    /**
     * Nettoie une chaîne de caractères pour éviter les attaques XSS
     * @param string $v Chaîne à nettoyer
     * @return string Chaîne nettoyée
     */
    private function xss(string $v): string
    {
        return htmlspecialchars(strip_tags(trim($v)), ENT_QUOTES | ENT_HTML5, 'UTF-8');
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

    // ─── Dashboard ───────────────────────────────────────────

    public function index(): void
    {
        // Récupère les informations du médecin connecté.
        $medecin = $this->medecins->trouverParUtilisateur($_SESSION['user_id']);

        // Récupère les statistiques mensuelles si le médecin existe.
        $stats   = $medecin ? $this->medecins->statistiquesMois($medecin['id_medecin']) : [];
        require __DIR__ . './../Views/medecin/dashboard.php';
    }

    // ─── Patients ─────────────────────────────────────────────

    /**
     * F2 — Rechercher un patient
     */
    public function rechercherPatient(): void
    {
        // Nettoie le terme de recherche et recherche les patients correspondants.
        $terme    = $this->xss($_GET['q'] ?? '');
        $patients = $terme ? $this->patients->rechercher($terme) : [];
        require __DIR__ . './../Views/medecin/recherche_patient.php';
    }

    /**
     * F5 — Fiche complète patient (admin + médical)
     */
    public function ficheComplete(): void
    {
         // Récupère l'ID du patient depuis l'URL
        $id      = (int) ($_GET['id'] ?? 0);

        // Récupère la fiche complète du patient
        $patient = $this->patients->trouverFicheComplete($id);
        if (!$patient) {                                           // Si le patient n'existe pas, affiche un message d'erreur et redirige.
            $this->flash('error', 'Patient introuvable.'); 
            $this->rediriger('rechercherPatient'); 
            return; 
        }
        $rdvs = $this->rdv->rdvParPatient($id);
        require __DIR__ . './../Views/medecin/fiche_complete.php';
    }

    /**
     * F6 — Modifier infos complètes (admin + médical)
     */
    public function modifierPatient(): void
    {
        if (!$this->csrfValide()) { 
            $this->flash('error', 'Token CSRF invalide.'); 
            $this->rediriger('index'); 
            return; 
        }

        // Récupère l'ID du patient et nettoie les données du formulaire.
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

         // Met à jour les informations médicales du patient.
        $result = $this->patients->modifierInfosMedicales($id, $donnees);

        // Affiche un message de succès ou d'erreur et redirige.
        $this->flash($result['succes'] ? 'success' : 'error', $result['message']);
        $this->rediriger('ficheComplete&id=' . $id);
    }

    // ─── Agenda ──────────────────────────────────────────────

    /**
     * F6 RDV — Consulter l'agenda d'un praticien
     */
    public function agendaMedecin(): void
    {
        // Récupère l'ID du médecin, la date de début et de fin depuis l'URL.
        $idMedecin = (int) ($_GET['medecin_id'] ?? 0);
        $dateDebut = $_GET['debut'] ?? date('Y-m-d');
        $dateFin   = $_GET['fin']   ?? date('Y-m-d', strtotime('+6 days'));

        // Récupère la liste de tous les médecins
        $medecins  = $this->medecins->listerTous();

        // Récupère les rendez-vous et créneaux du médecin sélectionn.
        $rdvs      = $idMedecin ? $this->rdv->rdvParMedecin($idMedecin, $dateDebut, $dateFin) : [];
        $creneaux  = $idMedecin ? $this->medecins->agendaSemaine($idMedecin, $dateDebut, $dateFin) : [];
        require __DIR__ . './../Views/medecin/agenda.php';
    }

    /**
     * F5 RDV — Prendre un RDV pour un patient
     */
    public function prendreRdv(): void
    {
        if (!$this->csrfValide()) {
            $this->flash('error', 'Token CSRF invalide.'); 
            $this->rediriger('index'); 
            return; 
        }

         // Récupère les IDs du créneau, du patient et du motif depuis le formulaire.
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
        // Récupère les informations du médecin connecté
        $medecin   = $this->medecins->trouverParUtilisateur($_SESSION['user_id']);

         // Définit la période par défaut (7 jours)
        $dateDebut = $_GET['debut'] ?? date('Y-m-d');
        $dateFin   = $_GET['fin']   ?? date('Y-m-d', strtotime('+6 days'));

        // Récupère les créneaux de l'agenda du médecin.
        $creneaux  = $medecin ? $this->medecins->agendaSemaine($medecin['id_medecin'], $dateDebut, $dateFin) : [];
        require __DIR__ . './../Views/medecin/mon_agenda.php';
    }

    /**
     * Créneaux disponibles (AJAX)
     */
    public function creneauxDisponibles(): void
    {
        // Définit le type de contenu pour une réponse JSON
        header('Content-Type: application/json');
        $idMedecin = (int) ($_GET['medecin_id'] ?? 0);
        $date      = $_GET['date'] ?? '';

        // Si les paramètres sont manquants, retourne un tableau vide
        if (!$idMedecin || !$date) { 
            echo json_encode([]); 
            exit; 
        }

        // Retourne les créneaux disponibles au format JSON
        echo json_encode($this->medecins->creneauxDisponibles($idMedecin, $date));
        exit;
    }

    // ─── Helpers ─────────────────────────────────────────────

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
        header('Location: ./MedecinController.php?action=' . $action);
        exit;
    }
}

// ─── Routeur ─────────────────────────────────────────────

// Vérifie si le script est appelé directement (et non inclus).
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'] ?? '')) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $ctrl   = new MedecinController();
    $action = $_GET['action'] ?? 'index';
    $method = $_SERVER['REQUEST_METHOD'];

    // Définit les routes disponibles et les méthodes HTTP autorisées.
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

    // Vérifie si l'action existe et si la méthode HTTP est autorisée.
    if (isset($routes[$action])) {
        [$methodeAttendue, $nomMethode] = $routes[$action];
        if ($method !== $methodeAttendue) { http_response_code(405); die('Méthode HTTP non autorisée.'); }
        $ctrl->$nomMethode();
    } else {
        $ctrl->index();
    }
}
