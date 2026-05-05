<!-- views/admin/stats.php -->
<?php require __DIR__ . '/../layout.php'; ?>

<h1>Statistiques mensuelles</h1>
<form method="GET" action="/stats">
    <select name="month">
        <?php for ($m = 1; $m <= 12; $m++): ?>
            <option value="<?= str_pad($m, 2, '0', STR_PAD_LEFT) ?>" <?= ($m == $month) ? 'selected' : '' ?>>
                <?= date("F", mktime(0, 0, 0, $m, 1)) ?>
            </option>
        <?php endfor; ?>
    </select>
    <select name="year">
        <?php for ($y = 2020; $y <= 2030; $y++): ?>
            <option value="<?= $y ?>" <?= ($y == $year) ? 'selected' : '' ?>>
                <?= $y ?>
            </option>
        <?php endfor; ?>
    </select>
    <button type="submit">Afficher</button>
</form>

<?php if ($stats): ?>
    <h2>Statistiques pour <?= date("F Y", strtotime("$year-$month-01")) ?></h2>
    <table border="1">
        <tr>
            <th>Nombre total de rendez-vous</th>
            <td><?= $stats['total_appointments'] ?></td>
        </tr>
        <tr>
            <th>Rendez-vous complétés</th>
            <td><?= $stats['completed_appointments'] ?></td>
        </tr>
        <tr>
            <th>Rendez-vous annulés</th>
            <td><?= $stats['cancelled_appointments'] ?></td>
        </tr>
        <tr>
            <th>Patients uniques</th>
            <td><?= $stats['unique_patients'] ?></td>
        </tr>
        <tr>
            <th>Praticiens uniques</th>
            <td><?= $stats['unique_practitioners'] ?></td>
        </tr>
    </table>
<?php endif; ?>