<!-- views/practitioner/medical_records/view_record.php -->
<?php require __DIR__ . '/../../layout.php'; ?>

<h1>Dossier médical de
    <?php
    $patient = Patient::findById($record->getPatientId());
    echo htmlspecialchars($patient->getFirstName() . ' ' . $patient->getLastName());
    ?>
</h1>
<?php if (isset($success)): ?>
    <p style="color: green;"><?= $success ?></p>
<?php endif; ?>
<?php if (isset($error)): ?>
    <p style="color: red;"><?= $error ?></p>
<?php endif; ?>

<p><strong>Créé le :</strong> <?= date('d/m/Y H:i', strtotime($record->getCreatedAt())) ?></p>
<p><strong>Dernière mise à jour :</strong> <?= date('d/m/Y H:i', strtotime($record->getUpdatedAt())) ?></p>

<h2>Notes médicales</h2>
<?php if (empty($notes)): ?>
    <p>Aucune note médicale pour ce dossier.</p>
<?php else: ?>
    <table border="1">
        <thead>
            <tr>
                <th>Type</th>
                <th>Titre</th>
                <th>Contenu</th>
                <th>Créé par</th>
                <th>Date</th>
                <?php if ($_SESSION['user']['role'] === 'praticien'): ?>
                    <th>Actions</th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($notes as $note): ?>
                <tr>
                    <td><?= ucfirst($note->getNoteType()) ?></td>
                    <td><?= htmlspecialchars($note->getTitle() ?? 'Sans titre') ?></td>
                    <td><?= nl2br(htmlspecialchars($note->getContent())) ?></td>
                    <td>
                        <?php
                        $creator = User::findById($note->getCreatedBy());
                        echo htmlspecialchars($creator->getEmail());
                        ?>
                    </td>
                    <td><?= date('d/m/Y H:i', strtotime($note->getCreatedAt())) ?></td>
                    <?php if ($_SESSION['user']['role'] === 'praticien'): ?>
                        <td>
                            <a href="/practitioner/medical-records/<?= $record->getId() ?>/notes/<?= $note->getId() ?>/edit">Modifier</a>
                            <a href="/practitioner/medical-records/<?= $record->getId() ?>/notes/<?= $note->getId() ?>/delete" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette note ?')">Supprimer</a>
                        </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php if ($_SESSION['user']['role'] === 'praticien'): ?>
    <p>
        <a href="/practitioner/medical-records/<?= $record->getId() ?>/notes/add">Ajouter une note</a>
    </p>
<?php endif; ?>

<p><a href="/practitioner/medical-records">Retour à la liste des dossiers</a></p>