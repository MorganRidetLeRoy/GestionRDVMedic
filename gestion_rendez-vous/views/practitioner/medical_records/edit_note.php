<!-- views/practitioner/medical_records/edit_note.php -->
<?php require __DIR__ . '/../../layout.php'; ?>

<h1>Modifier la note</h1>
<?php if (isset($error)): ?>
    <p style="color: red;"><?= $error ?></p>
<?php endif; ?>

<form method="POST" action="/practitioner/medical-records/notes/<?= $note->getId() ?>/edit">
    <div>
        <label>Titre :</label>
        <input type="text" name="title" value="<?= htmlspecialchars($note->getTitle() ?? '') ?>" placeholder="Titre de la note">
    </div>
    <div>
        <label>Contenu :</label>
        <textarea name="content" rows="5" placeholder="Détails de la note..." required><?= htmlspecialchars($note->getContent()) ?></textarea>
    </div>
    <button type="submit">Modifier la note</button>
</form>

<p><a href="/practitioner/medical-records/<?= $note->getMedicalRecordId() ?>">Retour au dossier médical</a></p>