-- ─────────────────────────────────────────────
-- SCHÉMA SQL – Gestion des patients
-- ─────────────────────────────────────────────

CREATE DATABASE IF NOT EXISTS gestion_patients
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE gestion_patients;

CREATE TABLE IF NOT EXISTS patients (
    id                  INT UNSIGNED    NOT NULL AUTO_INCREMENT,

    -- Informations administratives (accessibles par la secrétaire)
    nom                 VARCHAR(100)    NOT NULL,
    prenom              VARCHAR(100)    NOT NULL,
    telephone           VARCHAR(20)     NOT NULL,
    email               VARCHAR(255)    NOT NULL UNIQUE,

    -- Informations médicales (F4 : inaccessibles à la secrétaire)
    antecedents         TEXT            DEFAULT NULL,
    allergies           TEXT            DEFAULT NULL,
    traitements         TEXT            DEFAULT NULL,
    notes_medicales     TEXT            DEFAULT NULL,

    -- Métadonnées
    date_creation       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    date_modification   DATETIME        DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des utilisateurs (secrétaire / praticien)
CREATE TABLE IF NOT EXISTS utilisateurs (
    id          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    login       VARCHAR(100)    NOT NULL UNIQUE,
    mot_de_passe VARCHAR(255)   NOT NULL,   -- stocké hashé (password_hash)
    role        ENUM('secretaire','praticien') NOT NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Données de test
INSERT INTO utilisateurs (login, mot_de_passe, role) VALUES
    ('secretaire1', '$2y$12$exampleHashedPassword1234567890', 'secretaire'),
    ('dr_martin',   '$2y$12$exampleHashedPassword0987654321', 'praticien');
