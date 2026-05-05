<!-- views/auth/login.php -->
<?php require __DIR__ . '/../layout.php'; ?>

<h1>Connexion</h1>
<?php if (isset($error)): ?>
    <p style="color: red;"><?= $error ?></p>
<?php endif; ?>

<form method="POST" action="/login">
    <div>
        <label>Email :</label>
        <input type="email" name="email" required>
    </div>
    <div>
        <label>Mot de passe :</label>
        <input type="password" name="password" required>
    </div>
    <button type="submit">Se connecter</button>
</form>

<p>Si vous êtes un patient et que c'est votre première connexion, vérifiez vos emails pour vos identifiants.</p>