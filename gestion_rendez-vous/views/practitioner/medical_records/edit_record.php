<!-- views/practitioner/medical_records/edit_record.php -->
<?php require __DIR__ . '/../../layout.php'; ?>

<h1>Modifier une note médicale</h1>
<?php if (isset($error)): ?>
    <p style="color: red;"><?= $error ?></p>
<?php endif; ?>

<?php if (isset($note) && isset($record)): ?>
    <form method="POST" action="/practitioner/medical-records/<?= $record->getId() ?>/notes/<?= $note->getId() ?>/edit">
        <div>
            <label>Titre :</label>
            <input type="text" name="title" value="<?= htmlspecialchars($note->getTitle() ?? '') ?>" placeholder="Titre de la note">
        </div>
        <div>
            <label>Type de note :</label>
            <select name="note_type" disabled>
                <option value="antecedent" <?= ($note->getNoteType() === 'antecedent') ? 'selected' : '' ?>>Antécédent</option>
                <option value="treatment" <?= ($note->getNoteType() === 'treatment') ? 'selected' : '' ?>>Traitement</option>
                <option value="consultation" <?= ($note->getNoteType() === 'consultation') ? 'selected' : '' ?>>Consultation</option>
                <option value="other" <?= ($note->getNoteType() === 'other') ? 'selected' : '' ?>>Autre</option>
            </select>
        </div>
        <div>
            <label>Contenu :</label>
            <textarea name="content" rows="10" placeholder="Détails de la note..." required><?= htmlspecialchars($note->getContent()) ?></textarea>
        </div>
        <button type="submit" class="button">Modifier la note</button>
    </form>

    <p>
        <a href="/practitioner/medical-records/<?= $record->getId() ?>" class="button">Retour au dossier médical</a>
    </p>
<?php else: ?>
    <p>Note ou dossier médical introuvable.</p>
    <p><a href="/practitioner/medical-records" class="button">Retour à la liste des dossiers</a></p>
<?php endif; ?>