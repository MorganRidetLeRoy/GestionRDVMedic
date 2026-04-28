<?php
// =========================================================
// Controllers/SecretaireController.php
// Toutes les actions accessibles à la secrétaire
// =========================================================

// Inclusion des fichiers nécessaires : contrôleur d'authentification et modèles de données
require_once __DIR__ . './AuthController.php';
require_once __DIR__ . './../Models/PatientModel.php';
require_once __DIR__ . './../Models/MedecinModel.php';
require_once __DIR__ . './../Models/RendezVousModel.php';
require_once __DIR__ . './../Models/EmailModel.php';
require_once __DIR__ . './../Models/UtilisateurModel.php';

class SecretaireController
{
    // Déclaration des propriétés pour interagir avec les modèles de données
    private PatientModel     $patients;    // Modèle pour gérer les patients
    private MedecinModel     $medecins;    // Modèle pour gérer les médecins
    private RendezVousModel  $rdv;         // Modèle pour gérer les rendez-vous
    private EmailModel       $email;       // Modèle pour gérer l'envoi d'emails
    private UtilisateurModel $users;       // Modèle pour gérer les utilisateurs

    public function __construct()
    {
        // Vérifie que l'utilisateur connecté a le rôle 'secretaire' ou 'admin'
        AuthController::exigerRole('secretaire', 'admin');

        // Initialisation des modèles
        $this->patients = new PatientModel();
        $this->medecins = new MedecinModel();
        $this->rdv      = new RendezVousModel();
        $this->email    = new EmailModel();
        $this->users    = new UtilisateurModel();
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

    /**
     * Affiche le tableau de bord de la secrétaire avec les statistiques mensuelles des rendez-vous
     */
    public function index(): void
    {
        // Récupère les statistiques mensuelles des rendez-vous
        $stats = $this->rdv->statistiquesMois();
        require __DIR__ . './../Views/secretaire/dashboard.php';
    }

    // ─── Gestion Patients ─────────────────────────────────────

    /**
     * F1 — Affiche le formulaire de création de fiche patient
     */
    public function creerPatientForm(): void
    {
        require __DIR__ . './../Views/secretaire/creer_patient.php';
    }

    /**
     * F1 — Traite la création de fiche patient (POST)
     */
    public function creerPatient(): void
    {
        // Vérifie la validité du token CSRF
        if (!$this->csrfValide()) { 
            $this->flash('error', 'Token CSRF invalide.'); 
            $this->rediriger('creerPatientForm'); 
            return; 
        }

        // Nettoie et récupère les données du formulaire
        $donnees = [
            'nom'       => $this->xss($_POST['nom']       ?? ''),
            'prenom'    => $this->xss($_POST['prenom']    ?? ''),
            'telephone' => $this->xss($_POST['telephone'] ?? ''),
            'email'     => $this->xss($_POST['email']     ?? ''),
        ];

        // Crée le patient dans la base de données
        $result = $this->patients->creer($donnees);

         // Affiche un message de succès ou d'erreur et redirige
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
        // Nettoie le terme de recherche et recherche les patients correspondants
        $terme    = $this->xss($_GET['q'] ?? '');
        $patients = $terme ? $this->patients->rechercher($terme) : [];
        require __DIR__ . './../Views/secretaire/recherche_patient.php';
    }

    /**
     * F3/F4 — Fiche d'un patient (infos admin uniquement pour la secrétaire)
     */
    public function fichePatient(): void
    {
        // Récupère l'ID du patient depuis l'URL
        $id      = (int) ($_GET['id'] ?? 0);

        // Récupère les informations administratives du patient
        $patient = $this->patients->trouverInfosAdmin($id);
        if (!$patient) {

            // Si le patient n'existe pas, affiche un message d'erreur et redirige
            $this->flash('error', 'Patient introuvable.'); 
            $this->rediriger('rechercherPatient'); 
            return; 

        }
        $rdvs = $this->rdv->rdvParPatient($id);
        require __DIR__ . './../Views/secretaire/fiche_patient.php';
    }

    /**
     * F3 — Traite la modification des infos admin (POST)
     */
    public function modifierPatient(): void
    {
        if (!$this->csrfValide()) { 
            $this->flash('error', 'Token CSRF invalide.'); 
            $this->rediriger('index'); 
            return; 
        }

        // Récupère l'ID du patient et nettoie les données du formulaire
        $id      = (int) ($_POST['patient_id'] ?? 0);
        $donnees = [
            'nom'       => $this->xss($_POST['nom']       ?? ''),
            'prenom'    => $this->xss($_POST['prenom']    ?? ''),
            'telephone' => $this->xss($_POST['telephone'] ?? ''),
            'email'     => $this->xss($_POST['email']     ?? ''),
        ];

        // Met à jour les informations administratives du patient
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
        // Récupère l'ID du patient depuis l'URL
        $idPatient = (int) ($_GET['patient_id'] ?? 0);

        // Récupère les informations du patient si l'ID est valide
        $patient   = $idPatient ? $this->patients->trouverInfosAdmin($idPatient) : null;

        // Récupère la liste de tous les médecins et des motifs de rendez-vous
        $medecins  = $this->medecins->listerTous();
        $motifs    = $this->listerMotifs();
        require __DIR__ . './../Views/secretaire/prendre_rdv.php';
    }

    /**
     * F1/F2 RDV — Traite la prise de RDV (POST)
     */
    public function prendreRdv(): void
    {
        // Vérifie la validité du token CSRF
        if (!$this->csrfValide()) { 
            $this->flash('error', 'Token CSRF invalide.'); 
            $this->rediriger('index'); 
            return; 
        }

        // Récupère les IDs du créneau, du patient et du motif depuis le formulaire
        $idCreneau = (int) ($_POST['id_creneau'] ?? 0);
        $idPatient = (int) ($_POST['id_patient'] ?? 0);
        $idMotif   = (int) ($_POST['id_motif']   ?? 0) ?: null;

        // Vérifie que les données obligatoires sont présentes
        if (!$idCreneau || !$idPatient) {
            $this->flash('error', 'Données manquantes pour le rendez-vous.');
            $this->rediriger('prendreRdvForm');
            return;
        }

         // Crée le rendez-vous dans la base de données
        $result = $this->rdv->creer($idCreneau, $idPatient, $idMotif);

        // Si le rendez-vous est créé avec succès, envoie un email de confirmation
        if ($result['succes']) {

            // Envoi email de confirmation
            $patient = $this->patients->trouverInfosAdmin($idPatient);

            if ($patient) {
                $rdv = $this->rdv->trouverParId($result['id_rdv']);     // Récupère les détails du rendez-vous
                if ($rdv) {
                    $this->email->envoyerConfirmationRdv(               // Envoie un email de confirmation au patient
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
        // Récupère l'ID du rendez-vous depuis l'URL
        $idRdv    = (int) ($_GET['id'] ?? 0);

        // Récupère les informations du rendez-vous
        $rdv      = $this->rdv->trouverParId($idRdv);

        // Récupère la liste de tous les médecins
        $medecins = $this->medecins->listerTous();

        require __DIR__ . './../Views/secretaire/modifier_rdv.php';
    }

    /**
     * F3 RDV — Traite la modification (POST)
     */
    public function modifierRdv(): void
    {
        // Vérifie la validité du token CSRF
        if (!$this->csrfValide()) { 
            $this->flash('error', 'Token CSRF invalide.'); 
            $this->rediriger('index'); 
            return; 
        }

        // Récupère les IDs du rendez-vous et du nouveau créneau depuis le formulaire
        $idRdv     = (int) ($_POST['id_rdv']      ?? 0);
        $idCreneau = (int) ($_POST['id_creneau']  ?? 0);

        // Met à jour le rendez-vous avec le nouveau créneau
        $result    = $this->rdv->modifier($idRdv, $idCreneau);

        // Affiche un message de succès ou d'erreur et redirige
        $this->flash($result['succes'] ? 'success' : 'error', $result['message']);
        $this->rediriger('agendaMedecin');
    }

    /**
     * F4 RDV — Annuler un rendez-vous
     */
    public function annulerRdv(): void
    {
        // Vérifie la validité du token CSRF
        if (!$this->csrfValide()) { 
            $this->flash('error', 'Token CSRF invalide.'); 
            $this->rediriger('index'); 
            return; 
        }

        // Récupère l'ID du rendez-vous à annuler
        $idRdv  = (int) ($_POST['id_rdv'] ?? 0);

        // Annule le rendez-vous
        $result = $this->rdv->annuler($idRdv);

        // Affiche un message de succès ou d'erreur et redirige
        $this->flash($result['succes'] ? 'success' : 'error', $result['message']);
        $this->rediriger('agendaMedecin');
    }

    /**
     * Vue agenda d'un médecin (F6 RDV)
     */
    public function agendaMedecin(): void
    {
        // Récupère l'ID du médecin, la date de début et de fin depuis l'URL
        $idMedecin  = (int) ($_GET['medecin_id'] ?? 0);
        $dateDebut  = $_GET['debut']  ?? date('Y-m-d');
        $dateFin    = $_GET['fin']    ?? date('Y-m-d', strtotime('+6 days'));

        // Récupère la liste de tous les médecins
        $medecins   = $this->medecins->listerTous();

        // Récupère les rendez-vous et créneaux du médecin sélectionné
        $rdvs       = $idMedecin ? $this->rdv->rdvParMedecin($idMedecin, $dateDebut, $dateFin) : [];
        $creneaux   = $idMedecin ? $this->medecins->agendaSemaine($idMedecin, $dateDebut, $dateFin) : [];

        require __DIR__ . './../Views/secretaire/agenda_medecin.php';
    }

    /**
     * Recherche médecins
     */
    public function rechercherMedecin(): void
    {
        // Nettoie le terme de recherche et recherche les médecins correspondants
        $terme    = $this->xss($_GET['q'] ?? '');
        $medecins = $terme ? $this->medecins->rechercher($terme) : $this->medecins->listerTous();
        require __DIR__ . './../Views/secretaire/recherche_medecin.php';
    }

    /**
     * Créneaux disponibles (AJAX)
     */
    public function creneauxDisponibles(): void
    {
        // Définit le type de contenu pour une réponse JSON
        header('Content-Type: application/json');

        // Récupère l'ID du médecin et la date depuis l'URL
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
     * Récupère la liste des motifs de rendez-vous depuis la base de données
     * @return array Tableau des motifs triés par libellé
     */
    private function listerMotifs(): array
    {
        $pdo  = getConnexion();
        $stmt = $pdo->query('SELECT * FROM motifs ORDER BY libelle');
        return $stmt->fetchAll();
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
        header('Location: ./SecretaireController.php?action=' . $action);
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
    $ctrl   = new SecretaireController();

    // Récupère l'action et la méthode HTTP depuis l'URL
    $action = $_GET['action'] ?? 'index';
    $method = $_SERVER['REQUEST_METHOD'];

    // Définit les routes disponibles et les méthodes HTTP autorisées
    $routes = [
        'index'              => ['GET',  'index'],                  // Affiche le tableau de bord
        'creerPatientForm'   => ['GET',  'creerPatientForm'],       // Affiche le formulaire de création de patient
        'creerPatient'       => ['POST', 'creerPatient'],           // Traite la création de patient
        'rechercherPatient'  => ['GET',  'rechercherPatient'],      // Recherche un patient
        'fichePatient'       => ['GET',  'fichePatient'],           // Affiche la fiche d'un patient
        'modifierPatient'    => ['POST', 'modifierPatient'],        // Traite la modification d'un patient
        'prendreRdvForm'     => ['GET',  'prendreRdvForm'],         // Affiche le formulaire de prise de rendez-vous
        'prendreRdv'         => ['POST', 'prendreRdv'],             // Traite la prise de rendez-vous
        'modifierRdvForm'    => ['GET',  'modifierRdvForm'],        // Affiche le formulaire de modification de rendez-vous
        'modifierRdv'        => ['POST', 'modifierRdv'],            // Traite la modification de rendez-vous
        'annulerRdv'         => ['POST', 'annulerRdv'],             // Annule un rendez-vous
        'agendaMedecin'      => ['GET',  'agendaMedecin'],          // Affiche l'agenda d'un médecin
        'rechercherMedecin'  => ['GET',  'rechercherMedecin'],      // Recherche un médecin
        'creneauxDisponibles'=> ['GET',  'creneauxDisponibles'],    // Retourne les créneaux disponibles (AJAX)
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
