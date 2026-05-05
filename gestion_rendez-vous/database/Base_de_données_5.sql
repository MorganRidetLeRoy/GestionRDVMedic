-- Table des notifications (optionnelle, pour l'historique)
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,              -- ID du patient ou utilisateur concerné
    patient_id INT NULL,           -- ID du patient (si différent de user_id)
    type ENUM('new_account', 'appointment_confirmation', 'appointment_reminder') NOT NULL,
    content TEXT,                  -- Contenu du message
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('sent', 'failed') DEFAULT 'sent',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE SET NULL
);