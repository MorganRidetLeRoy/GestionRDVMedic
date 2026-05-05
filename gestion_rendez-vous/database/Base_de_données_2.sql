-- Mise à jour de la table `users` pour ajouter le téléphone (nécessaire pour US-18)
ALTER TABLE users ADD COLUMN phone VARCHAR(20);

-- Mise à jour de la table `patients` pour ajouter le téléphone
ALTER TABLE patients ADD COLUMN phone VARCHAR(20);

-- Table des rendez-vous (remplace la version précédente pour plus de détails)
DROP TABLE IF EXISTS appointments;
CREATE TABLE appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    practitioner_id INT NOT NULL,
    date DATETIME NOT NULL,
    status ENUM('scheduled', 'confirmed', 'cancelled', 'completed') DEFAULT 'scheduled',
    reason TEXT, -- Motif du rendez-vous (US-15, US-16)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    FOREIGN KEY (praticien_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Table pour les créneaux horaires des praticiens (US-20)
CREATE TABLE practitioner_slots (
    id INT AUTO_INCREMENT PRIMARY KEY,
    practitioner_id INT NOT NULL,
    day_of_week TINYINT NOT NULL, -- 0 (dimanche) à 6 (samedi)
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    is_available BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (practitioner_id) REFERENCES users(id) ON DELETE CASCADE
);