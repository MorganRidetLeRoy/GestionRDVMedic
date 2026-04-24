<?php
/**
 * Gestion des Patients - F1, F3, F4
 * F1 : Créer une fiche patient (secrétaire)
 * F3 : Modifier les informations administratives (secrétaire)
 * F4 : Bloquer l'accès aux informations médicales pour la secrétaire
 */

session_start();

// ─────────────────────────────────────────────
// CONFIGURATION BASE DE DONNÉES
// ─────────────────────────────────────────────
define('DB_HOST', 'localhost');
define('DB_NAME', 'gestion_patients');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    }
    return $pdo;
}

// ─────────────────────────────────────────────
// RÔLES UTILISATEURS
// ─────────────────────────────────────────────
define('ROLE_SECRETAIRE', 'secretaire');
define('ROLE_PRATICIEN',  'praticien');

/**
 * Retourne le rôle de l'utilisateur connecté.
 * En production, remplacer par la vraie logique d'authentification.
 */
function getRoleUtilisateur(): string {
    return $_SESSION['role'] ?? ROLE_SECRETAIRE;
}

/**
 * Vérifie qu'un rôle requis est bien celui de l'utilisateur connecté.
 */
function verifierRole(string $roleRequis): void {
    if (getRoleUtilisateur() !== $roleRequis) {
        http_response_code(403);
        die(json_encode([
            'succes'  => false,
            'message' => 'Accès refusé : vous n\'avez pas les droits nécessaires.',
        ]));
    }
}

// ─────────────────────────────────────────────
// VALIDATION & NETTOYAGE
// ─────────────────────────────────────────────
function nettoyerChaine(string $valeur): string {
    return htmlspecialchars(strip_tags(trim($valeur)), ENT_QUOTES, 'UTF-8');
}

function validerEmail(string $email): bool {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validerTelephone(string $tel): bool {
    // Accepte les formats : 0612345678 / +33612345678 / 06 12 34 56 78
    return (bool) preg_match('/^(\+33|0033|0)[1-9](\s?[0-9]{2}){4}$/', preg_replace('/[\s.\-]/', '', $tel));
}

// ─────────────────────────────────────────────
// F1 – CRÉER UNE FICHE PATIENT
// ─────────────────────────────────────────────
/**
 * Crée un nouveau patient avec ses informations administratives.
 * Réservé à la secrétaire.
 *
 * @param array $donnees ['nom', 'prenom', 'telephone', 'email']
 * @return array ['succes' => bool, 'message' => string, 'patient_id' => int|null]
 */
function creerFichePatient(array $donnees): array {
    // Contrôle d'accès – F4 : seule la secrétaire peut créer
    verifierRole(ROLE_SECRETAIRE);

    // Nettoyage
    $nom       = nettoyerChaine($donnees['nom']       ?? '');
    $prenom    = nettoyerChaine($donnees['prenom']     ?? '');
    $telephone = nettoyerChaine($donnees['telephone']  ?? '');
    $email     = nettoyerChaine($donnees['email']      ?? '');

    // Validation des champs obligatoires
    $erreurs = [];

    if (empty($nom)) {
        $erreurs[] = 'Le nom est obligatoire.';
    }
    if (empty($prenom)) {
        $erreurs[] = 'Le prénom est obligatoire.';
    }
    if (empty($telephone)) {
        $erreurs[] = 'Le téléphone est obligatoire.';
    } elseif (!validerTelephone($telephone)) {
        $erreurs[] = 'Le numéro de téléphone est invalide.';
    }
    if (empty($email)) {
        $erreurs[] = 'L\'email est obligatoire.';
    } elseif (!validerEmail($email)) {
        $erreurs[] = 'L\'adresse email est invalide.';
    }

    if (!empty($erreurs)) {
        return ['succes' => false, 'message' => implode(' ', $erreurs), 'patient_id' => null];
    }

    try {
        $pdo = getDB();

        // Vérifier si l'email existe déjà
        $stmt = $pdo->prepare('SELECT id FROM patients WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => $email]);
        if ($stmt->fetch()) {
            return ['succes' => false, 'message' => 'Un patient avec cet email existe déjà.', 'patient_id' => null];
        }

        // Insertion — seules les colonnes administratives sont renseignées ici
        $sql = 'INSERT INTO patients (nom, prenom, telephone, email, date_creation)
                VALUES (:nom, :prenom, :telephone, :email, NOW())';

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':nom'       => $nom,
            ':prenom'    => $prenom,
            ':telephone' => $telephone,
            ':email'     => $email,
        ]);

        $patientId = (int) $pdo->lastInsertId();

        return [
            'succes'     => true,
            'message'    => 'La fiche patient a été créée avec succès.',
            'patient_id' => $patientId,
        ];

    } catch (PDOException $e) {
        error_log('Erreur creerFichePatient : ' . $e->getMessage());
        return ['succes' => false, 'message' => 'Erreur serveur lors de la création du patient.', 'patient_id' => null];
    }
}

// ─────────────────────────────────────────────
// F3 – MODIFIER LES INFORMATIONS ADMINISTRATIVES
// ─────────────────────────────────────────────
/**
 * Modifie les informations administratives d'un patient existant.
 * Réservé à la secrétaire. Les données médicales ne sont jamais touchées.
 *
 * @param int   $patientId  Identifiant du patient
 * @param array $donnees    Champs à mettre à jour parmi : nom, prenom, telephone, email
 * @return array ['succes' => bool, 'message' => string]
 */
function modifierInfosAdministratives(int $patientId, array $donnees): array {
    // Contrôle d'accès
    verifierRole(ROLE_SECRETAIRE);

    if ($patientId <= 0) {
        return ['succes' => false, 'message' => 'Identifiant patient invalide.'];
    }

    // Colonnes autorisées pour la secrétaire (F4 : jamais de colonnes médicales)
    $colonnesAutorisees = ['nom', 'prenom', 'telephone', 'email'];

    $champsAMettreAJour = [];
    $parametres         = [':id' => $patientId];
    $erreurs            = [];

    foreach ($colonnesAutorisees as $champ) {
        if (!array_key_exists($champ, $donnees)) {
            continue; // Champ non fourni → on ne le modifie pas
        }

        $valeur = nettoyerChaine((string) $donnees[$champ]);

        switch ($champ) {
            case 'nom':
            case 'prenom':
                if (empty($valeur)) {
                    $erreurs[] = "Le champ « $champ » ne peut pas être vide.";
                } else {
                    $champsAMettreAJour[]       = "$champ = :$champ";
                    $parametres[":$champ"]      = $valeur;
                }
                break;

            case 'telephone':
                if (!validerTelephone($valeur)) {
                    $erreurs[] = 'Le numéro de téléphone est invalide.';
                } else {
                    $champsAMettreAJour[]  = 'telephone = :telephone';
                    $parametres[':telephone'] = $valeur;
                }
                break;

            case 'email':
                if (!validerEmail($valeur)) {
                    $erreurs[] = 'L\'adresse email est invalide.';
                } else {
                    $champsAMettreAJour[]  = 'email = :email';
                    $parametres[':email']  = $valeur;
                }
                break;
        }
    }

    if (!empty($erreurs)) {
        return ['succes' => false, 'message' => implode(' ', $erreurs)];
    }

    if (empty($champsAMettreAJour)) {
        return ['succes' => false, 'message' => 'Aucune donnée administrative à mettre à jour.'];
    }

    try {
        $pdo = getDB();

        // Vérifier que le patient existe
        $stmt = $pdo->prepare('SELECT id FROM patients WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $patientId]);
        if (!$stmt->fetch()) {
            return ['succes' => false, 'message' => 'Patient introuvable.'];
        }

        // Si l'email est modifié, vérifier qu'il n'appartient pas à un autre patient
        if (isset($parametres[':email'])) {
            $stmt = $pdo->prepare('SELECT id FROM patients WHERE email = :email AND id != :id LIMIT 1');
            $stmt->execute([':email' => $parametres[':email'], ':id' => $patientId]);
            if ($stmt->fetch()) {
                return ['succes' => false, 'message' => 'Cet email est déjà utilisé par un autre patient.'];
            }
        }

        // Mise à jour — uniquement les colonnes administratives
        $sql  = 'UPDATE patients SET ' . implode(', ', $champsAMettreAJour);
        $sql .= ', date_modification = NOW() WHERE id = :id';

        $stmt = $pdo->prepare($sql);
        $stmt->execute($parametres);

        return ['succes' => true, 'message' => 'Les informations administratives ont été mises à jour.'];

    } catch (PDOException $e) {
        error_log('Erreur modifierInfosAdministratives : ' . $e->getMessage());
        return ['succes' => false, 'message' => 'Erreur serveur lors de la mise à jour.'];
    }
}

// ─────────────────────────────────────────────
// F4 – BLOQUER L'ACCÈS AUX INFOS MÉDICALES
// ─────────────────────────────────────────────
/**
 * Tente d'accéder aux informations médicales d'un patient.
 * Si l'utilisateur est secrétaire → accès refusé (F4).
 * Si l'utilisateur est praticien  → accès autorisé.
 *
 * @param int $patientId
 * @return array ['succes' => bool, 'message' => string, 'donnees' => array|null]
 */
function accederInfosMedicales(int $patientId): array {
    $role = getRoleUtilisateur();

    // F4 : la secrétaire n'a JAMAIS accès aux données médicales
    if ($role === ROLE_SECRETAIRE) {
        return [
            'succes'  => false,
            'message' => 'Accès refusé : les informations médicales sont confidentielles et réservées aux praticiens.',
            'donnees' => null,
        ];
    }

    // Seul le praticien peut continuer
    if ($role !== ROLE_PRATICIEN) {
        return [
            'succes'  => false,
            'message' => 'Rôle inconnu. Accès refusé.',
            'donnees' => null,
        ];
    }

    if ($patientId <= 0) {
        return ['succes' => false, 'message' => 'Identifiant patient invalide.', 'donnees' => null];
    }

    try {
        $pdo  = getDB();
        $stmt = $pdo->prepare(
            'SELECT antecedents, allergies, traitements, notes_medicales
             FROM patients
             WHERE id = :id
             LIMIT 1'
        );
        $stmt->execute([':id' => $patientId]);
        $donnees = $stmt->fetch();

        if (!$donnees) {
            return ['succes' => false, 'message' => 'Patient introuvable.', 'donnees' => null];
        }

        return [
            'succes'  => true,
            'message' => 'Informations médicales récupérées.',
            'donnees' => $donnees,
        ];

    } catch (PDOException $e) {
        error_log('Erreur accederInfosMedicales : ' . $e->getMessage());
        return ['succes' => false, 'message' => 'Erreur serveur.', 'donnees' => null];
    }
}

// ─────────────────────────────────────────────
// ROUTEUR HTTP SIMPLE (appels via POST/JSON)
// ─────────────────────────────────────────────
if (php_sapi_name() !== 'cli' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');

    $input  = json_decode(file_get_contents('php://input'), true) ?? [];
    $action = $input['action'] ?? '';

    switch ($action) {
        // F1
        case 'creer_patient':
            echo json_encode(creerFichePatient($input['donnees'] ?? []));
            break;

        // F3
        case 'modifier_patient':
            $id = (int) ($input['patient_id'] ?? 0);
            echo json_encode(modifierInfosAdministratives($id, $input['donnees'] ?? []));
            break;

        // F4
        case 'voir_medical':
            $id = (int) ($input['patient_id'] ?? 0);
            echo json_encode(accederInfosMedicales($id));
            break;

        default:
            http_response_code(400);
            echo json_encode(['succes' => false, 'message' => 'Action inconnue.']);
    }
    exit;
}
