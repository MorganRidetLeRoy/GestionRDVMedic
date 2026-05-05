<!-- views/practitioner/patient_appointments.php -->
<?php require __DIR__ . '/../layout.php'; ?>

<h1>Rendez-vous du patient : <?= htmlspecialchars($patient->getFirstName() . ' ' . $patient->getLastName()) ?></h1>

<?php if (isset($success)): ?>
    <p style="color: green;"><?= $success ?></p>
<?php endif; ?>

<?php if (!empty($appointments)): ?>
    <table border="1" class="appointments-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Date</th>
                <th>Statut</th>
                <th>Motif</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($appointments as $appointment): ?>
                <tr>
                    <td><?= $appointment->getId() ?></td>
                    <td><?= date('d/m/Y à H:i', strtotime($appointment->getDate())) ?></td>
                    <td><?= ucfirst($appointment->getStatus()) ?></td>
                    <td><?= htmlspecialchars($appointment->getReason() ?? 'Non spécifié') ?></td>
                    <td>
                        <a href="/practitioner/appointments/cancel/<?= $appointment->getId() ?>" onclick="return confirm('Êtes-vous sûr de vouloir annuler ce rendez-vous ?')">Annuler</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php else: ?>
    <p>Ce patient n'a aucun rendez-vous.</p>
<?php endif; ?>

<p>
    <a href="/practitioner/appointments" class="button">Retour à mes rendez-vous</a>
    <a href="/practitioner/medical-records/<?= $patient->getId() ?>/create" class="button">Créer un dossier médical</a>
</p>