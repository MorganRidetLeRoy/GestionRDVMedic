<!DOCTYPE html>
<html lang="fr">
<head>
  <!-- Définit l'encodage des caractères en UTF-8 pour une compatibilité maximale -->
  <meta charset="UTF-8">

  <!-- Assure un rendu correct et un zoom tactile adapté sur les appareils mobiles (responsive design) -->
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <title>Connexion — Cabinet Médical</title>
  <link rel="stylesheet" href="./public/css/style.css">
</head>
<body>

<!-- Conteneur principal de la page de connexion -->
  <div class="auth-page">
    <!-- Carte/cadre qui contient le formulaire de connexion -->
    <div class="auth-card">

    <!-- Logo/icône du cabinet médical -->
    <div class="auth-logo">🏥</div>
    <h1>Cabinet Médical</h1>
    <p class="auth-subtitle">Connectez-vous à votre espace</p>

    <?php if (!empty($_GET['erreur'])): ?>                                                <!-- Vérifie s'il y a un message d'erreur dans les paramètres de l'URL -->
      <div class="flash flash-error">❌ <?= htmlspecialchars($_GET['erreur']) ?></div>    <!-- Affiche le message d'erreur de l'URL, il évite les attaques XSS -->
    <?php endif ?>

    <?php if (!empty($_SESSION['flash_error'])): ?>                                              <!-- Vérifie s'il y a un message d'erreur stocké dans la session -->
      <div class="flash flash-error">❌ <?= htmlspecialchars($_SESSION['flash_error']) ?></div>  <!-- Affiche le message d'erreur de la session -->
      <?php unset($_SESSION['flash_error']); ?>                                                  <!-- Supprime le message d'erreur de la session après l'avoir affiché -->
    <?php endif ?>

    <!-- Formulaire pour soumettre les identifiants de connexion, envoie les données à AuthController.php -->
    <form action="./Controllers/AuthController.php?action=connexion" method="POST" autocomplete="off">
      <?= AuthController::csrfField() ?>    <!-- Génère un champ de jeton CSRF pour se protéger contre les attaques CSRF -->

      <div class="form-group">
        <label for="username">Identifiant</label>
        <input class="form-control" type="text" id="username" name="username"
               placeholder="Votre identifiant" required autocomplete="username">   <!-- Champ de saisie pour l'identifiant, requis et avec autocomplétion -->
      </div>

      <div class="form-group">
        <label for="password">Mot de passe</label>
        <input class="form-control" type="password" id="password" name="password"
               placeholder="••••••••" required autocomplete="current-password">    <!-- Champ de saisie pour le mot de passe, requis et avec autocomplétion -->
      </div>

      <button type="submit" class="btn btn-primary btn-block" style="margin-top:.5rem">
        Se connecter
      </button>
    </form>

    <!-- Section de pied de page avec des informations supplémentaires -->
    <div class="auth-footer" style="margin-top:1.5rem;font-size:12px;color:var(--text-muted)">
      <p>Accès réservé au personnel du cabinet.</p>
      <p>Pour toute assistance, contactez l'administrateur.</p>
    </div>

  </div>
</div>

</body>
</html>
