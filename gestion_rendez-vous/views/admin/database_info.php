<!-- views/admin/database_info.php -->
<?php require __DIR__ . '/../layout.php'; ?>

<h1>Informations sur la base de données</h1>

<h2>Tables disponibles</h2>
<ul>
    <?php foreach ($tables as $table): ?>
        <li>
            <a href="/admin/database/table/<?= htmlspecialchars($table) ?>">
                <?= htmlspecialchars($table) ?>
            </a>
        </li>
    <?php endforeach; ?>
</ul>

<?php if (isset($structure)): ?>
    <h2>Structure de la table <?= htmlspecialchars($tableName) ?></h2>
    <table border="1">
        <thead>
            <tr>
                <th>Champ</th>
                <th>Type</th>
                <th>Null</th>
                <th>Clé</th>
                <th>Par défaut</th>
                <th>Extra</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($structure as $column): ?>
                <tr>
                    <td><?= htmlspecialchars($column['Field']) ?></td>
                    <td><?= htmlspecialchars($column['Type']) ?></td>
                    <td><?= htmlspecialchars($column['Null']) ?></td>
                    <td><?= htmlspecialchars($column['Key']) ?></td>
                    <td><?= htmlspecialchars($column['Default'] ?? '') ?></td>
                    <td><?= htmlspecialchars($column['Extra'] ?? '') ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<p>
    <a href="/admin/database/export" class="button">Exporter le schéma SQL</a>
    <a href="/admin" class="button">Retour au tableau de bord</a>
</p>