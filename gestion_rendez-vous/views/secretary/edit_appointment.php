<!-- views/secretary/edit_appointment.php -->
<?php require __DIR__ . '/../layout.php'; ?>

<h1>Modifier le rendez-vous #<?= $appointment->getId() ?></h1>
<?php if (isset($success)): ?>
    <p style="color: green;"><?= $success ?></p>
<?php endif; ?>
<?php if (isset($error)): ?>
    <p style="color: red;"><?= $error ?></p>
<?php endif; ?>

<form method="POST" action="/secretary/appointments/edit/<?= $appointment->getId() ?>">
    <div>
        <label>Patient :</label>
        <select name="patient_id" required>
            <?php foreach ($patients as $patient): ?>
                <option value="<?= $patient->getId() ?>" <?= ($patient->getId() == $appointment->getPatientId()) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($patient->getFirstName() . ' ' . $patient->getLastName()) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div>
        <label>Praticien :</label>
        <select name="practitioner_id" required>
            <?php foreach ($practitioners as $practitioner): ?>
                <option value="<?= $practitioner->getId() ?>" <?= ($practitioner->getId() == $appointment->getPractitionerId()) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($practitioner->getEmail()) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div>
        <label>Date :</label>
        <input type="date" name="date" value="<?= date('Y-m-d', strtotime($appointment->getDate())) ?>" required>
    </div>

    <div>
        <label>Heure :</label>
        <input type="time" name="time" value="<?= date('H:i', strtotime($appointment->getDate())) ?>" required>
    </div>

    <div>
        <label>Motif (optionnel) :</label>
        <textarea name="reason"><?= htmlspecialchars($appointment->getReason()) ?></textarea>
    </div>

    <button type="submit">Modifier</button>
</form>