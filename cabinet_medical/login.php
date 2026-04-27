<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Connexion — Cabinet Médical</title>
  <link rel="stylesheet" href="/public/css/style.css">
</head>
<body>

<div class="auth-page">
  <div class="auth-card">

    <div class="auth-logo">🏥</div>
    <h1>Cabinet Médical</h1>
    <p class="auth-subtitle">Connectez-vous à votre espace</p>

    <?php if (!empty($_GET['erreur'])): ?>
      <div class="flash flash-error">❌ <?= htmlspecialchars($_GET['erreur']) ?></div>
    <?php endif ?>

    <?php if (!empty($_SESSION['flash_error'])): ?>
      <div class="flash flash-error">❌ <?= htmlspecialchars($_SESSION['flash_error']) ?></div>
      <?php unset($_SESSION['flash_error']); ?>
    <?php endif ?>

    <form action="/Controllers/AuthController.php?action=connexion" method="POST" autocomplete="off">
      <?= AuthController::csrfField() ?>

      <div class="form-group">
        <label for="username">Identifiant</label>
        <input class="form-control" type="text" id="username" name="username"
               placeholder="Votre identifiant" required autocomplete="username">
      </div>

      <div class="form-group">
        <label for="password">Mot de passe</label>
        <input class="form-control" type="password" id="password" name="password"
               placeholder="••••••••" required autocomplete="current-password">
      </div>

      <button type="submit" class="btn btn-primary btn-block" style="margin-top:.5rem">
        Se connecter
      </button>
    </form>

    <div class="auth-footer" style="margin-top:1.5rem;font-size:12px;color:var(--text-muted)">
      <p>Accès réservé au personnel du cabinet.</p>
      <p>Pour toute assistance, contactez l'administrateur.</p>
    </div>

  </div>
</div>

</body>
</html>
