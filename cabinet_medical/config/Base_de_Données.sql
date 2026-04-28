-- ============================================================
-- BASE DE DONNÉES - CABINET MÉDICAL
-- Structure MVC - Avec chiffrement AES des données sensibles
-- ============================================================

CREATE DATABASE IF NOT EXISTS cabinet_medical        --Crée une base de données nommée "cabinet_medical" si elle n'existe pas déjà.
    CHARACTER SET utf8mb4                            --Utilise le jeu de caractères "utf8mb4" pour supporter les caractères spéciaux et les emojis.
    COLLATE utf8mb4_unicode_ci; 

USE cabinet_medical;

-- ─── TABLE UTILISATEURS (connexion / rôles) ─────────────────
CREATE TABLE IF NOT EXISTS utilisateurs (
    id            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    login         VARCHAR(100)    NOT NULL UNIQUE,
    mot_de_passe  VARCHAR(255)    NOT NULL,          -- stocké via password_hash()
    role          ENUM('admin','secretaire','praticien','patient') NOT NULL,
    actif         TINYINT(1)      NOT NULL DEFAULT 1,
    created_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_login (login),
    INDEX idx_role  (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── TABLE MEDECINS ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS medecins (
    id_medecin      INT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_utilisateur  INT UNSIGNED NOT NULL,
    nom             BLOB         NOT NULL,   -- chiffré AES
    prenom          BLOB         NOT NULL,   -- chiffré AES
    email_pro       BLOB         NOT NULL,   -- chiffré AES
    telephone       BLOB,                    -- chiffré AES
    genre           ENUM('M','F','Autre')    DEFAULT 'M',
    date_naissance  BLOB,                    -- chiffré AES
    PRIMARY KEY (id_medecin),
    FOREIGN KEY (id_utilisateur) REFERENCES utilisateurs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── TABLE SPÉCIALITÉS ──────────────────────────────────────
CREATE TABLE IF NOT EXISTS specialites (
    id_specialite INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    libelle       VARCHAR(100)    NOT NULL,
    code_acte     VARCHAR(20),
    PRIMARY KEY (id_specialite)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── TABLE LIAISON MÉDECIN ↔ SPÉCIALITÉ ─────────────────────
CREATE TABLE IF NOT EXISTS specialite_medecin (
    id_medecin    INT UNSIGNED NOT NULL,
    id_specialite INT UNSIGNED NOT NULL,
    principale    TINYINT(1)   NOT NULL DEFAULT 0,
    PRIMARY KEY (id_medecin, id_specialite),
    FOREIGN KEY (id_medecin)    REFERENCES medecins(id_medecin)       ON DELETE CASCADE,
    FOREIGN KEY (id_specialite) REFERENCES specialites(id_specialite) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── TABLE PATIENTS ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS patients (
    id                INT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_utilisateur    INT UNSIGNED,                  -- NULL avant création de compte
    -- Informations administratives
    nom               VARCHAR(100) NOT NULL,
    prenom            VARCHAR(100) NOT NULL,
    telephone         VARCHAR(20)  NOT NULL,
    email             VARCHAR(255) NOT NULL UNIQUE,
    -- Informations médicales (inaccessibles à la secrétaire)
    antecedents       TEXT         DEFAULT NULL,
    allergies         TEXT         DEFAULT NULL,
    traitements       TEXT         DEFAULT NULL,
    notes_medicales   TEXT         DEFAULT NULL,
    -- Métadonnées
    date_creation     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    date_modification DATETIME     DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_email (email),
    INDEX idx_nom_prenom (nom, prenom),
    FOREIGN KEY (id_utilisateur) REFERENCES utilisateurs(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── TABLE MOTIFS ───────────────────────────────────────────
CREATE TABLE IF NOT EXISTS motifs (
    id_motif       INT UNSIGNED NOT NULL AUTO_INCREMENT,
    libelle        VARCHAR(100) NOT NULL,
    duree_estimee  INT          NOT NULL DEFAULT 20,
    urgence        TINYINT(1)   NOT NULL DEFAULT 0,
    PRIMARY KEY (id_motif)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── TABLE PLANNING ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS planning (
    id_planning    INT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_medecin     INT UNSIGNED NOT NULL,
    date_planning  DATE         NOT NULL,
    heure_debut    TIME         NOT NULL DEFAULT '08:00:00',
    heure_fin      TIME         NOT NULL DEFAULT '18:00:00',
    duree_creneau  INT          NOT NULL DEFAULT 20,
    actif          TINYINT(1)   NOT NULL DEFAULT 1,
    PRIMARY KEY (id_planning),
    UNIQUE KEY uk_medecin_date (id_medecin, date_planning),
    FOREIGN KEY (id_medecin) REFERENCES medecins(id_medecin) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── TABLE CRÉNEAUX ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS creneaux (
    id_creneau  INT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_planning INT UNSIGNED NOT NULL,
    heure_debut TIME         NOT NULL,
    heure_fin   TIME         NOT NULL,
    statut      ENUM('disponible','reserve','bloque','passe') NOT NULL DEFAULT 'disponible',
    PRIMARY KEY (id_creneau),
    FOREIGN KEY (id_planning) REFERENCES planning(id_planning) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── TABLE RENDEZ-VOUS ──────────────────────────────────────
CREATE TABLE IF NOT EXISTS rendez_vous (
    id_rdv        INT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_creneau    INT UNSIGNED NOT NULL UNIQUE,
    id_patient    INT UNSIGNED,
    id_motif      INT UNSIGNED,
    notes_medecin BLOB,                                   -- chiffré AES
    statut        ENUM('confirme','annule','absent','termine') NOT NULL DEFAULT 'confirme',
    date_creation DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_rdv),
    FOREIGN KEY (id_creneau) REFERENCES creneaux(id_creneau)  ON DELETE RESTRICT,
    FOREIGN KEY (id_patient) REFERENCES patients(id)           ON DELETE SET NULL,
    FOREIGN KEY (id_motif)   REFERENCES motifs(id_motif)       ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── TABLE CONGÉS ───────────────────────────────────────────
CREATE TABLE IF NOT EXISTS conges (
    id_conge   INT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_medecin INT UNSIGNED NOT NULL,
    date_debut DATE         NOT NULL,
    date_fin   DATE         NOT NULL,
    motif      VARCHAR(100),
    PRIMARY KEY (id_conge),
    FOREIGN KEY (id_medecin) REFERENCES medecins(id_medecin) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── TABLE SESSIONS (déconnexion automatique) ───────────────
CREATE TABLE IF NOT EXISTS sessions_actives (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_user      INT UNSIGNED NOT NULL,
    token        VARCHAR(255) NOT NULL UNIQUE,
    derniere_act DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY (id_user) REFERENCES utilisateurs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TRIGGERS — Chiffrement AES automatique
-- ============================================================
DELIMITER //

-- ── Médecins : INSERT ───────────────────────────────────────
CREATE TRIGGER trg_medecins_before_insert
BEFORE INSERT ON medecins
FOR EACH ROW
BEGIN
    SET NEW.nom            = AES_ENCRYPT(NEW.nom,            'CléCabinetMédical2024!');
    SET NEW.prenom         = AES_ENCRYPT(NEW.prenom,         'CléCabinetMédical2024!');
    SET NEW.email_pro      = AES_ENCRYPT(NEW.email_pro,      'CléCabinetMédical2024!');
    SET NEW.telephone      = AES_ENCRYPT(NEW.telephone,      'CléCabinetMédical2024!');
    SET NEW.date_naissance = AES_ENCRYPT(NEW.date_naissance, 'CléCabinetMédical2024!');
END//

-- ── Médecins : UPDATE ───────────────────────────────────────
CREATE TRIGGER trg_medecins_before_update
BEFORE UPDATE ON medecins
FOR EACH ROW
BEGIN
    IF NEW.nom            != OLD.nom            THEN SET NEW.nom            = AES_ENCRYPT(NEW.nom,            'CléCabinetMédical2024!'); END IF;
    IF NEW.prenom         != OLD.prenom         THEN SET NEW.prenom         = AES_ENCRYPT(NEW.prenom,         'CléCabinetMédical2024!'); END IF;
    IF NEW.email_pro      != OLD.email_pro      THEN SET NEW.email_pro      = AES_ENCRYPT(NEW.email_pro,      'CléCabinetMédical2024!'); END IF;
    IF NEW.telephone      != OLD.telephone      THEN SET NEW.telephone      = AES_ENCRYPT(NEW.telephone,      'CléCabinetMédical2024!'); END IF;
    IF NEW.date_naissance != OLD.date_naissance THEN SET NEW.date_naissance = AES_ENCRYPT(NEW.date_naissance, 'CléCabinetMédical2024!'); END IF;
END//

-- ── Rendez-vous : INSERT ────────────────────────────────────
CREATE TRIGGER trg_rdv_before_insert
BEFORE INSERT ON rendez_vous
FOR EACH ROW
BEGIN
    IF NEW.notes_medecin IS NOT NULL THEN
        SET NEW.notes_medecin = AES_ENCRYPT(NEW.notes_medecin, 'CléCabinetMédical2024!');
    END IF;
END//

-- ── Rendez-vous : UPDATE ────────────────────────────────────
CREATE TRIGGER trg_rdv_before_update
BEFORE UPDATE ON rendez_vous
FOR EACH ROW
BEGIN
    IF NEW.notes_medecin != OLD.notes_medecin THEN
        SET NEW.notes_medecin = AES_ENCRYPT(NEW.notes_medecin, 'CléCabinetMédical2024!');
    END IF;
END//

DELIMITER ;

-- ============================================================
-- DONNÉES INITIALES
-- ============================================================

-- Motifs de consultation
INSERT INTO motifs (libelle, duree_estimee, urgence) VALUES
    ('Consultation générale', 20, 0),
    ('Urgence', 15, 1),
    ('Suivi traitement', 15, 0),
    ('Bilan de santé', 45, 0),
    ('Vaccination', 10, 0),
    ('Renouvellement ordonnance', 10, 0);

-- Spécialités
INSERT INTO specialites (libelle, code_acte) VALUES
    ('Médecine générale', 'MG'),
    ('Cardiologie', 'CARD'),
    ('Dermatologie', 'DERM'),
    ('Pédiatrie', 'PED'),
    ('Gynécologie', 'GYN');

-- Admin (mot de passe: Admin2024!)
INSERT INTO utilisateurs (login, mot_de_passe, role) VALUES
    ('admin', '$2y$12$exampleAdminHashedPassword12345', 'admin');
