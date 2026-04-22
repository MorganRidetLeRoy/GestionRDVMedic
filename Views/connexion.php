<?php
//===========================================
// Vue/connexion
// Page de connexion au formulaire principale
// ==========================================
// 

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
    <title>Connexion</title>
</head>
<body>
    <div class="wrap">
        <div class="card">
            <div class="logo">
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 2a5 5 0 1 1 0 10A5 5 0 0 1 12 2zm0 12c5.33 0 8 2.67 8 4v2H4v-2c0-1.33 2.67-4 8-4z"/>
                </svg>
            </div>

            <h2>Connexion</h2>
            <p class="subtitle">Bienvenue, entrez vos identifiants</p>

            <form action="/ma-page-de-traitement" method="POST">
                <div class="field">
                    <label for="username">Nom d'utilisateur</label>
                    <input type="text" id="username" name="username" placeholder="votre@email.com" required>
                </div>
                <div class="field">
                    <label for="password">Mot de passe</label>
                    <input type="password" id="password" name="password" placeholder="••••••••" required>
                </div>

                <a href="#" class="forgot">Mot de passe oublié ?</a>

                <button type="submit" class="btn">Se connecter</button>
            </form>

            <div class="divider"><span>ou</span></div>

            <p class="footer">Pas encore de compte ? <a href="identification.html">S'inscrire</a></p>
        </div>
    </div>
</body>
</html>