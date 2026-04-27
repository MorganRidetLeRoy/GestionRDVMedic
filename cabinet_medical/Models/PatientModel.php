<?php
// =========================================================
// Models/PatientModel.php
// CRUD patients — F1, F2, F3, F4, F5, F6 (gestion patients)
// =========================================================
require_once __DIR__ . '/../database/connexion_database.php';

class PatientModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = getConnexion();
    }

    // ─── Validation ──────────────────────────────────────────

    private function nettoyerChaine(string $v): string
    {
        return htmlspecialchars(strip_tags(trim($v)), ENT_QUOTES, 'UTF-8');
    }

    private function validerEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    private function validerTelephone(string $tel): bool
    {
        return (bool) preg_match(
            '/^(\+33|0033|0)[1-9](\s?[0-9]{2}){4}$/',
            preg_replace('/[\s.\-]/', '', $tel)
        );
    }

    // ─── F1 : Créer une fiche patient ────────────────────────

    public function creer(array $d): array
    {
        $nom       = $this->nettoyerChaine($d['nom']       ?? '');
        $prenom    = $this->nettoyerChaine($d['prenom']    ?? '');
        $telephone = $this->nettoyerChaine($d['telephone'] ?? '');
        $email     = $this->nettoyerChaine($d['email']     ?? '');

        $erreurs = [];
        if (empty($nom))       $erreurs[] = 'Le nom est obligatoire.';
        if (empty($prenom))    $erreurs[] = 'Le prénom est obligatoire.';
        if (!$this->validerTelephone($telephone)) $erreurs[] = 'Numéro de téléphone invalide.';
        if (!$this->validerEmail($email))         $erreurs[] = 'Adresse email invalide.';

        if ($erreurs) return ['succes' => false, 'message' => implode(' ', $erreurs), 'patient_id' => null];

        // Email unique ?
        $stmt = $this->db->prepare('SELECT id FROM patients WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => $email]);
        if ($stmt->fetch()) return ['succes' => false, 'message' => 'Un patient avec cet email existe déjà.', 'patient_id' => null];

        $stmt = $this->db->prepare(
            'INSERT INTO patients (nom, prenom, telephone, email, date_creation)
             VALUES (:nom, :prenom, :telephone, :email, NOW())'
        );
        $stmt->execute([':nom' => $nom, ':prenom' => $prenom, ':telephone' => $telephone, ':email' => $email]);

        return ['succes' => true, 'message' => 'Fiche patient créée avec succès.', 'patient_id' => (int) $this->db->lastInsertId()];
    }

    // ─── F2 : Rechercher par nom/prénom ──────────────────────

    public function rechercher(string $terme): array
    {
        $terme = '%' . $this->nettoyerChaine($terme) . '%';
        $stmt  = $this->db->prepare(
            'SELECT id, nom, prenom, telephone, email, date_creation
             FROM patients
             WHERE nom LIKE :t OR prenom LIKE :t
             ORDER BY nom, prenom
             LIMIT 50'
        );
        $stmt->execute([':t' => $terme]);
        return $stmt->fetchAll();
    }

    // ─── F3 : Modifier les infos administratives ─────────────

    public function modifierInfosAdmin(int $id, array $d): array
    {
        if ($id <= 0) return ['succes' => false, 'message' => 'ID patient invalide.'];

        $colonnesAutorisees = ['nom', 'prenom', 'telephone', 'email'];
        $sets = []; $params = [':id' => $id]; $erreurs = [];

        foreach ($colonnesAutorisees as $champ) {
            if (!array_key_exists($champ, $d)) continue;
            $valeur = $this->nettoyerChaine((string) $d[$champ]);

            switch ($champ) {
                case 'nom': case 'prenom':
                    if (empty($valeur)) $erreurs[] = "Le champ $champ ne peut pas être vide.";
                    else { $sets[] = "$champ = :$champ"; $params[":$champ"] = $valeur; }
                    break;
                case 'telephone':
                    if (!$this->validerTelephone($valeur)) $erreurs[] = 'Numéro de téléphone invalide.';
                    else { $sets[] = 'telephone = :telephone'; $params[':telephone'] = $valeur; }
                    break;
                case 'email':
                    if (!$this->validerEmail($valeur)) $erreurs[] = 'Email invalide.';
                    else {
                        // Email pris par un autre ?
                        $s = $this->db->prepare('SELECT id FROM patients WHERE email = :e AND id != :id LIMIT 1');
                        $s->execute([':e' => $valeur, ':id' => $id]);
                        if ($s->fetch()) $erreurs[] = 'Cet email est déjà utilisé.';
                        else { $sets[] = 'email = :email'; $params[':email'] = $valeur; }
                    }
                    break;
            }
        }

        if ($erreurs) return ['succes' => false, 'message' => implode(' ', $erreurs)];
        if (empty($sets)) return ['succes' => false, 'message' => 'Aucune donnée à mettre à jour.'];

        // Patient existe ?
        $s = $this->db->prepare('SELECT id FROM patients WHERE id = :id LIMIT 1');
        $s->execute([':id' => $id]);
        if (!$s->fetch()) return ['succes' => false, 'message' => 'Patient introuvable.'];

        $sql  = 'UPDATE patients SET ' . implode(', ', $sets) . ', date_modification = NOW() WHERE id = :id';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return ['succes' => true, 'message' => 'Informations mises à jour.'];
    }

    // ─── F4 : Infos admin seules (secrétaire) ────────────────

    public function trouverInfosAdmin(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, nom, prenom, telephone, email, date_creation, date_modification
             FROM patients WHERE id = :id LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }

    // ─── F5 : Fiche complète (praticien) ─────────────────────

    public function trouverFicheComplete(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, nom, prenom, telephone, email,
                    antecedents, allergies, traitements, notes_medicales,
                    date_creation, date_modification
             FROM patients WHERE id = :id LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }

    // ─── F6 : Modifier infos médicales (praticien) ───────────

    public function modifierInfosMedicales(int $id, array $d): array
    {
        if ($id <= 0) return ['succes' => false, 'message' => 'ID patient invalide.'];

        $colonnesMedicales = ['antecedents', 'allergies', 'traitements', 'notes_medicales'];
        $sets = []; $params = [':id' => $id];

        foreach ($colonnesMedicales as $col) {
            if (array_key_exists($col, $d)) {
                $sets[] = "$col = :$col";
                $params[":$col"] = strip_tags(trim($d[$col] ?? ''));
            }
        }

        // Aussi les colonnes admin
        $colonnesAdmin = ['nom', 'prenom', 'telephone', 'email'];
        foreach ($colonnesAdmin as $col) {
            if (array_key_exists($col, $d)) {
                $sets[] = "$col = :$col";
                $params[":$col"] = $this->nettoyerChaine($d[$col] ?? '');
            }
        }

        if (empty($sets)) return ['succes' => false, 'message' => 'Aucune donnée à mettre à jour.'];

        $sql  = 'UPDATE patients SET ' . implode(', ', $sets) . ', date_modification = NOW() WHERE id = :id';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return ['succes' => true, 'message' => 'Fiche patient mise à jour.'];
    }

    // ─── Tous les patients (liste) ───────────────────────────

    public function listerTous(): array
    {
        $stmt = $this->db->query(
            'SELECT id, nom, prenom, telephone, email, date_creation
             FROM patients ORDER BY nom, prenom'
        );
        return $stmt->fetchAll();
    }

    // ─── Associer un compte utilisateur à un patient ─────────

    public function associerUtilisateur(int $patientId, int $userId): void
    {
        $this->db->prepare('UPDATE patients SET id_utilisateur = :uid WHERE id = :id')
                 ->execute([':uid' => $userId, ':id' => $patientId]);
    }

    // ─── Trouver patient par email ───────────────────────────

    public function trouverParEmail(string $email): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM patients WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => $email]);
        return $stmt->fetch() ?: null;
    }
}
