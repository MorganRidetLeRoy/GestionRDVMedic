-- Mise à jour de la table `users` pour ajouter les champs d'administration
ALTER TABLE users
ADD COLUMN is_active BOOLEAN DEFAULT TRUE,
ADD COLUMN last_password_reset TIMESTAMP NULL;

-- Vérifier qu'il n'y a qu'un seul admin local (US-26)
-- (Cela sera géré dans le code PHP, pas dans la base de données)