<!-- views/admin/reset_user_password.php -->
<?php require __DIR__ . '/../layout.php'; ?>

<h1>Réinitialiser le mot de passe</h1>
<p>Êtes-vous sûr de vouloir réinitialiser le mot de passe de l'utilisateur <strong><?= htmlspecialchars($user->getEmail()) ?></strong> ?</p>
<p>Un nouveau mot de passe sera généré et envoyé par email à l'utilisateur.</p>

<form method="POST" action="/admin/users/reset-password/<?= $user->getId() ?>">
    <button type="submit">Oui, réinitialiser</button>
    <a href="/admin/users">Annuler</a>
</form>