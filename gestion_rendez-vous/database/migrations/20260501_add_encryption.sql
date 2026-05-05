-- Ajouter une colonne pour stocker les données chiffrées (exemple pour les patients)
ALTER TABLE patients ADD COLUMN encrypted_data TEXT;

-- Ajouter une clé de chiffrement par défaut (à générer via PHP)
INSERT INTO encryption_keys (key_name, key_value) VALUES ('default', 'votre_clé_chiffrée_ici');
-- Note : La clé doit être générée via PHP avec openssl_random_pseudo_bytes()