<!-- views/secretary/medical_records/view_record.php -->
<?php require __DIR__ . '/../../layout.php'; ?>

<h1>Dossier médical de
    <?php
    $patient = Patient::findById($record->getPatientId());
    echo htmlspecialchars($patient->getFirstName() . ' ' . $patient->getLastName());
    ?>
</h1>
<p><em>Accès en lecture seule (blocage technique pour garantir la confidentialité).</em></p>

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
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<p><a href="/secretary/appointments">Retour aux rendez-vous</a></p>