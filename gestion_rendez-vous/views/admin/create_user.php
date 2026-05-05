<!-- views/admin/create_user.php -->
<?php require __DIR__ . '/../layout.php'; ?>

<h1>Créer un nouvel utilisateur</h1>
<?php if (isset($success)): ?>
    <p style="color: green;"><?= $success ?></p>
<?php endif; ?>
<?php if (isset($error)): ?>
    <p style="color: red;"><?= $error ?></p>
<?php endif; ?>

<form method="POST" action="/admin/users/create">
    <div>
        <label>Email :</label>
        <input type="email" name="email" required>
    </div>
    <div>
        <label>Mot de passe :</label>
        <input type="password" name="password" required>
    </div>
    <div>
        <label>Rôle :</label>
        <select name="role" required>
            <option value="secrétaire">Secrétaire</option>
            <option value="praticien">Praticien</option>
            <?php
            // Vérifier s'il y a déjà un admin local (US-26)
            $adminCount = User::countAdminLocal();
            if ($adminCount === 0): ?>
                <option value="admin_local">Admin Local</option>
            <?php endif; ?>
        </select>
    </div>
    <button type="submit">Créer</button>
</form>