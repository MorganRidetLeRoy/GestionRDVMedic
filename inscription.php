<?php
//===========================================
// Vue/connexion
// Page de connexion au formulaire principale
// ==========================================


session_start();

require_once __DIR__ . '/database/connexion.php';

class Connexion
{
    private PDO $db;

    public function __construct()
    {
        //Le Model obtient sa propre connexion PDO
        $this->db = getConnexion();
    }
}


?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title>Inscription</title>
</head>

<body>
    <div class="wrap">
        <div class="card">
            <div class="logo">
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 2a5 5 0 1 1 0 10A5 5 0 0 1 12 2zm0 12c5.33 0 8 2.67 8 4v2H4v-2c0-1.33 2.67-4 8-4z"/>
                </svg>
            
            </div>
            <h2>Créer un compte</h2>
            <p class="subtitle">Rejoignez-nous, c'est gratuit !</p>
            <form action="/inscription" method="POST">
                <div class="field">
                    <label for="fullname">Nom complet</label>
                    <input type="text" id="fullname" name="fullname" placeholder="Jean Dupont" required>
                </div>

                <div class="field">
                    <label for="email">Adresse e-mail</label>
                    <input type="email" id="email" name="email" placeholder="votre@email.com" required>
                </div>

                <div class="field">
                    <label for="password">Mot de passe</label>
                    <input type="password" id="password" name="password" placeholder="••••••••" required>
                </div>

                <div class="field">
                    <label for="confirm-password">Confirmer le mot de passe</label>
                    <input type="password" id="confirm-password" name="confirm_password" placeholder="••••••••" required>
                </div>
            
                <button type="submit" class="btn">Créer mon compte</button>
            </form>

            <div class="divider"><span>ou</span></div>
            <p class="footer">Déjà un compte ? <a href="login.html">Se connecter</a></p>
        </div>
    </div>
</body>
</html>