<!-- views/practitioner/appointments.php -->
<?php require __DIR__ . '/../layout.php'; ?>

<h1>Mes rendez-vous</h1>
<?php if (isset($success)): ?>
    <p style="color: green;"><?= $success ?></p>
<?php endif; ?>

<table border="1">
    <thead>
        <tr>
            <th>ID</th>
            <th>Patient</th>
            <th>Date</th>
            <th>Statut</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($appointments as $item): ?>
            <tr>
                <td><?= $item['appointment']->getId() ?></td>
                <td><?= htmlspecialchars($item['patient_first_name'] . ' ' . $item['patient_last_name']) ?></td>
                <td><?= date('d/m/Y H:i', strtotime($item['appointment']->getDate())) ?></td>
                <td><?= ucfirst($item['appointment']->getStatus()) ?></td>
                <td>
                    <a href="/practitioner/appointments/cancel/<?= $item['appointment']->getId() ?>" onclick="return confirm('Êtes-vous sûr de vouloir annuler ce rendez-vous ?')">Annuler</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<p><a href="/practitioner/schedule">Gérer mes créneaux horaires</a></p>