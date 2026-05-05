<!-- views/patient/appointment-details.php -->
<?php require __DIR__ . '/../layout.php'; ?>

<?php if (isset($appointment)): ?>
    <h1>Détails du rendez-vous</h1>

    <div class="appointment-details">
        <p><strong>Date :</strong> <?= date('d/m/Y à H:i', strtotime($appointment->getDate())) ?></p>
        <p><strong>Statut :</strong> <?= ucfirst($appointment->getStatus()) ?></p>
        <p><strong>Praticien :</strong>
            <?php
            $practitioner = User::findById($appointment->getPractitionerId());
            echo htmlspecialchars($practitioner->getEmail());
            ?>
        </p>
        <?php if ($appointment->getReason()): ?>
            <p><strong>Motif :</strong> <?= htmlspecialchars($appointment->getReason()) ?></p>
        <?php endif; ?>
    </div>

    <div class="appointment-actions">
        <p><em>Pour annuler ce rendez-vous, veuillez contacter la secrétaire par téléphone (US-22).</em></p>
    </div>

    <p>
        <a href="/patient/appointments" class="button">Retour à mes rendez-vous</a>
    </p>
<?php else: ?>
    <h1>Rendez-vous introuvable</h1>
    <p>Le rendez-vous que vous cherchez n'existe pas ou n'est pas accessible.</p>
    <p><a href="/patient/appointments" class="button">Retour à mes rendez-vous</a></p>
<?php endif; ?>