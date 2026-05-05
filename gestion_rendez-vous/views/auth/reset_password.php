<!-- views/auth/reset_password.php -->
<?php require __DIR__ . '/../layout.php'; ?>

<h1>Modifier le mot de passe</h1>
<?php if (isset($success)): ?>
    <p style="color: green;"><?= $success ?></p>
<?php endif; ?>
<?php if (isset($error)): ?>
    <p style="color: red;"><?= $error ?></p>
<?php endif; ?>

<form method="POST" action="/reset-password">
    <div>
        <label>Nouveau mot de passe :</label>
        <input type="password" name="new_password" required>
    </div>
    <div>
        <label>Confirmer le mot de passe :</label>
        <input type="password" name="confirm_password" required>
    </div>
    <button type="submit">Modifier</button>
</form>