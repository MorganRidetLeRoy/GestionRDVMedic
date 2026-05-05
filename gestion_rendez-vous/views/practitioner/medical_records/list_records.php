<!-- views/practitioner/medical_records/list_records.php -->
<?php require __DIR__ . '/../../layout.php'; ?>

<h1>Mes dossiers médicaux</h1>
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
            <th>Patient</th>
            <th>Créé le</th>
            <th>Dernière mise à jour</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($records as $record): ?>
            <tr>
                <td><?= $record->getId() ?></td>
                <td>
                    <?php
                    $patient = Patient::findById($record->getPatientId());
                    echo htmlspecialchars($patient->getFirstName() . ' ' . $patient->getLastName());
                    ?>
                </td>
                <td><?= date('d/m/Y H:i', strtotime($record->getCreatedAt())) ?></td>
                <td><?= date('d/m/Y H:i', strtotime($record->getUpdatedAt())) ?></td>
                <td>
                    <a href="/practitioner/medical-records/<?= $record->getId() ?>">Voir</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<p><a href="/practitioner/appointments">Retour aux rendez-vous</a></p>