<!-- views/secretary/cancel_appointment.php -->
<?php require __DIR__ . '/../layout.php'; ?>

<h1>Annuler un rendez-vous</h1>

<?php if (isset($appointment)): ?>
    <p>Êtes-vous sûr de vouloir annuler le rendez-vous suivant ?</p>

    <div class="appointment-details">
        <p><strong>Patient :</strong>
            <?php
            $patient = Patient::findById($appointment->getPatientId());
            echo htmlspecialchars($patient->getFirstName() . ' ' . $patient->getLastName());
            ?>
        </p>
        <p><strong>Praticien :</strong>
            <?php
            $practitioner = User::findById($appointment->getPractitionerId());
            echo htmlspecialchars($practitioner->getEmail());
            ?>
        </p>
        <p><strong>Date :</strong> <?= date('d/m/Y à H:i', strtotime($appointment->getDate())) ?></p>
        <p><strong>Motif :</strong> <?= htmlspecialchars($appointment->getReason() ?? 'Non spécifié') ?></p>
    </div>

    <form method="POST" action="/secretary/appointments/cancel/<?= $appointment->getId() ?>">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
        <button type="submit" class="button">Oui, annuler ce rendez-vous</button>
        <a href="/secretary/appointments" class="button">Annuler</a>
    </form>
<?php else: ?>
    <p>Rendez-vous introuvable.</p>
    <p><a href="/secretary/appointments">Retour à la liste des rendez-vous</a></p>
<?php endif; ?>