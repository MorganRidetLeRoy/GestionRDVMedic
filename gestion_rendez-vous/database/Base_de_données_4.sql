-- Table des dossiers médicaux (US-27, US-28, US-29)
CREATE TABLE medical_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    created_by INT NOT NULL, -- ID du praticien qui a créé le dossier
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
);

-- Table des notes médicales (antécédents, traitements, etc.)
CREATE TABLE medical_notes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    medical_record_id INT NOT NULL,
    note_type ENUM('antecedent', 'treatment', 'consultation', 'other') NOT NULL,
    title VARCHAR(255),
    content TEXT NOT NULL,
    created_by INT NOT NULL, -- ID du praticien qui a ajouté la note
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (medical_record_id) REFERENCES medical_records(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
);

-- Mise à jour de la table `patients` pour ajouter un flag d'accès au dossier
ALTER TABLE patients ADD COLUMN has_medical_record BOOLEAN DEFAULT FALSE;