<!-- views/admin/disable_user.php -->
<?php require __DIR__ . '/../layout.php'; ?>

<h1>Désactiver un utilisateur</h1>
<p>Êtes-vous sûr de vouloir désactiver l'utilisateur <strong><?= htmlspecialchars($user->getEmail()) ?></strong> (rôle : <?= htmlspecialchars($user->getRole()) ?>) ?</p>

<form method="POST" action="/admin/users/disable/<?= $user->getId() ?>">
    <button type="submit">Oui, désactiver</button>
    <a href="/admin/users">Annuler</a>
</form>