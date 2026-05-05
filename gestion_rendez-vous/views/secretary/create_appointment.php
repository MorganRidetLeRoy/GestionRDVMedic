<!-- views/secretary/create_appointment.php -->
<?php require __DIR__ . '/../layout.php'; ?>

<h1>Créer un rendez-vous</h1>
<?php if (isset($success)): ?>
    <p style="color: green;"><?= $success ?></p>
<?php endif; ?>
<?php if (isset($error)): ?>
    <p style="color: red;"><?= $error ?></p>
<?php endif; ?>

<form method="POST" action="/secretary/appointments/create">
    <div>
        <label>Patient :</label>
        <select name="patient_id" required>
            <option value="">-- Sélectionner un patient --</option>
            <?php foreach ($patients as $patient): ?>
                <option value="<?= $patient->getId() ?>">
                    <?= htmlspecialchars($patient->getFirstName() . ' ' . $patient->getLastName()) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div>
        <label>Praticien :</label>
        <select name="practitioner_id" required>
            <option value="">-- Sélectionner un praticien --</option>
            <?php foreach ($practitioners as $practitioner): ?>
                <option value="<?= $practitioner->getId() ?>">
                    <?= htmlspecialchars($practitioner->getEmail()) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div>
        <label>Date :</label>
        <input type="date" name="date" required>
    </div>

    <div>
        <label>Heure :</label>
        <select name="time" required>
            <option value="">-- Sélectionner une heure --</option>
            <?php
            // Générer des créneaux de 8h à 18h par défaut (à améliorer avec les créneaux réels)
            for ($hour = 8; $hour < 18; $hour++) {
                for ($minute = 0; $minute < 60; $minute += 30) {
                    $time = sprintf("%02d:%02d", $hour, $minute);
                    echo "<option value=\"$time\">$time</option>";
                }
            }
            ?>
        </select>
    </div>

    <div>
        <label>Motif (optionnel) :</label>
        <textarea name="reason"></textarea>
    </div>

    <button type="submit">Créer le rendez-vous</button>
</form>