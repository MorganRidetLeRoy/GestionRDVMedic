<!-- views/secretary/appointments.php -->
<?php require __DIR__ . '/../layout.php'; ?>

<h1>Liste des rendez-vous</h1>
<?php if (isset($success)): ?>
    <p style="color: green;"><?= $success ?></p>
<?php endif; ?>

<table border="1">
    <thead>
        <tr>
            <th>ID</th>
            <th>Patient</th>
            <th>Praticien</th>
            <th>Date</th>
            <th>Statut</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($appointments as $appointment): ?>
            <tr>
                <td><?= $appointment->getId() ?></td>
                <td>
                    <?php
                    $patient = Patient::findById($appointment->getPatientId());
                    echo htmlspecialchars($patient->getFirstName() . ' ' . $patient->getLastName());
                    ?>
                </td>
                <td>
                    <?php
                    $practitioner = User::getById($appointment->getPractitionerId());
                    echo htmlspecialchars($practitioner->getEmail());
                    ?>
                </td>
                <td><?= date('d/m/Y H:i', strtotime($appointment->getDate())) ?></td>
                <td><?= ucfirst($appointment->getStatus()) ?></td>
                <td>
                    <a href="/secretary/appointments/edit/<?= $appointment->getId() ?>">Modifier</a>
                    <a href="/secretary/appointments/cancel/<?= $appointment->getId() ?>" onclick="return confirm('Êtes-vous sûr de vouloir annuler ce rendez-vous ?')">Annuler</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<p><a href="/secretary/appointments/create">Créer un nouveau rendez-vous</a></p>