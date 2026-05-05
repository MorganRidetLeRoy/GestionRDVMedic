<!-- views/practitioner/medical_records/create_record.php -->
<?php require __DIR__ . '/../../layout.php'; ?>

<h1>Créer un dossier médical pour <?= htmlspecialchars($patient->getFirstName() . ' ' . $patient->getLastName()) ?></h1>
<?php if (isset($success)): ?>
    <p style="color: green;"><?= $success ?></p>
<?php endif; ?>
<?php if (isset($error)): ?>
    <p style="color: red;"><?= $error ?></p>
<?php endif; ?>

<form method="POST" action="/practitioner/medical-records/create/<?= $patient->getId() ?>">
    <h2>Première note médicale (optionnelle)</h2>
    <div>
        <label>Type de note :</label>
        <select name="note_type" required>
            <option value="antecedent">Antécédent</option>
            <option value="treatment">Traitement</option>
            <option value="consultation">Consultation</option>
            <option value="other">Autre</option>
        </select>
    </div>
    <div>
        <label>Titre :</label>
        <input type="text" name="title" placeholder="Titre de la note">
    </div>
    <div>
        <label>Contenu :</label>
        <textarea name="content" rows="5" placeholder="Détails de la note..." required></textarea>
    </div>
    <button type="submit">Créer le dossier médical</button>
</form>

<p><a href="/practitioner/medical-records">Retour à la liste des dossiers</a></p>