<!-- views/practitioner/medical_records/add_note.php -->
<?php require __DIR__ . '/../../layout.php'; ?>

<h1>Ajouter une note au dossier médical</h1>
<?php if (isset($error)): ?>
    <p style="color: red;"><?= $error ?></p>
<?php endif; ?>

<form method="POST" action="/practitioner/medical-records/<?= $record->getId() ?>/notes/add">
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
    <button type="submit">Ajouter la note</button>
</form>

<p><a href="/practitioner/medical-records/<?= $record->getId() ?>">Retour au dossier médical</a></p>