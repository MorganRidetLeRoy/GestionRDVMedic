<!-- views/patient/first_login.php -->
<?php require __DIR__ . '/../layout.php'; ?>

<h1>Bienvenue, <?= htmlspecialchars($_SESSION['patient']['first_name'] . ' ' . $_SESSION['patient']['last_name']) ?> !</h1>

<div class="welcome-message">
    <p>C'est votre première connexion. Nous vous recommandons de <strong>modifier votre mot de passe temporaire</strong> pour sécuriser votre compte.</p>

    <?php if (isset($_SESSION['patient']) && $_SESSION['patient']['temporary_password']): ?>
        <div class="alert">
            <p>⚠️ Votre mot de passe est temporaire. Veuillez le modifier pour accéder à toutes les fonctionnalités.</p>
            <p><a href="/reset-password" class="button">Modifier mon mot de passe</a></p>
        </div>
    <?php endif; ?>

    <h2>Vos rendez-vous</h2>
    <?php
    require_once __DIR__ . '/../models/Appointment.php';
    $appointments = Appointment::getByPatient($_SESSION['patient']['id']);
    ?>

    <?php if (!empty($appointments)): ?>
        <table border="1" class="appointments-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Praticien</th>
                    <th>Statut</th>
                    <th>Motif</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($appointments as $appointment): ?>
                    <tr>
                        <td><?= date('d/m/Y à H:i', strtotime($appointment->getDate())) ?></td>
                        <td>
                            <?php
                            $practitioner = User::findById($appointment->getPractitionerId());
                            echo htmlspecialchars($practitioner->getEmail());
                            ?>
                        </td>
                        <td><?= ucfirst($appointment->getStatus()) ?></td>
                        <td><?= htmlspecialchars($appointment->getReason() ?? 'Non spécifié') ?></td>
                        <td>
                            <a href="/patient/appointments/<?= $appointment->getId() ?>" class="button">Voir les détails</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>Vous n'avez aucun rendez-vous programmé.</p>
    <?php endif; ?>
</div>