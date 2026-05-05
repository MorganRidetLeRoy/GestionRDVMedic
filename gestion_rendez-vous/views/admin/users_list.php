<!-- views/admin/users_list.php -->
<?php require __DIR__ . '/../layout.php'; ?>

<h1>Liste des comptes actifs</h1>
<?php if (isset($success)): ?>
    <p style="color: green;"><?= $success ?></p>
<?php endif; ?>
<?php if (isset($error)): ?>
    <p style="color: red;"><?= $error ?></p>
<?php endif; ?>

<table border="1">
    <thead>
        <tr>
            <th>ID</th>
            <th>Email</th>
            <th>Rôle</th>
            <th>Date de création</th>
            <th>Dernière activité</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($users as $user): ?>
            <tr>
                <td><?= $user->getId() ?></td>
                <td><?= htmlspecialchars($user->getEmail()) ?></td>
                <td><?= htmlspecialchars($user->getRole()) ?></td>
                <td><?= date('d/m/Y H:i', strtotime($user->getCreatedAt())) ?></td>
                <td><?= $user->getLastActivity() ? date('d/m/Y H:i', strtotime($user->getLastActivity())) : 'Jamais' ?></td>
                <td>
                    <a href="/admin/users/disable/<?= $user->getId() ?>">Désactiver</a>
                    <a href="/admin/users/reset-password/<?= $user->getId() ?>">Réinitialiser MDP</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<p><a href="/admin">Retour au tableau de bord</a></p>