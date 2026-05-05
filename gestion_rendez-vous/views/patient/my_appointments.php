<!-- views/patient/my_appointments.php -->
<?php require __DIR__ . '/../layout.php'; ?>

<h1>Mes rendez-vous</h1>
<?php if (empty($appointments)): ?>
    <p>Vous n'avez aucun rendez-vous.</p>
<?php else: ?>
    <table border="1">
        <thead>
            <tr>
                <th>ID</th>
                <th>Praticien</th>
                <th>Date</th>
                <th>Statut</th>
                <th>Motif</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($appointments as $appointment): ?>
                <tr>
                    <td><?= $appointment->getId() ?></td>
                    <td>
                        <?php
                        $practitioner = User::getById($appointment->getPractitionerId());
                        echo htmlspecialchars($practitioner->getEmail());
                        ?>
                    </td>
                    <td><?= date('d/m/Y H:i', strtotime($appointment->getDate())) ?></td>
                    <td><?= ucfirst($appointment->getStatus()) ?></td>
                    <td><?= htmlspecialchars($appointment->getReason() ?? 'Non spécifié') ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <p><strong>Note :</strong> Pour annuler un rendez-vous, veuillez contacter la secrétaire par téléphone.</p>
<?php endif; ?>