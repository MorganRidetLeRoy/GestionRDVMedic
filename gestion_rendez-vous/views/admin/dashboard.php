<!-- views/admin/dashboard.php -->
<?php require __DIR__ . '/../layout.php'; ?>

<h1>Tableau de bord - Admin Local</h1>
<p>Bienvenue, <?= htmlspecialchars($_SESSION['user']['email']) ?> !</p>

<h2>Actions rapides</h2>
<ul>
    <li><a href="/admin/users">Gérer les utilisateurs</a></li>
    <li><a href="/admin/users/create">Créer un nouvel utilisateur</a></li>
    <li><a href="/stats">Statistiques</a></li>
</ul>