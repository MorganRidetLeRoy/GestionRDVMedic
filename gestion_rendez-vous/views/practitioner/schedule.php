<!-- views/practitioner/schedule.php -->
<?php require __DIR__ . '/../layout.php'; ?>

<h1>Gérer mes créneaux horaires</h1>
<?php if (isset($success)): ?>
    <p style="color: green;"><?= $success ?></p>
<?php endif; ?>
<?php if (isset($error)): ?>
    <p style="color: red;"><?= $error ?></p>
<?php endif; ?>

<h2>Ajouter un créneau horaire</h2>
<form method="POST" action="/practitioner/schedule">
    <div>
        <label>Jour de la semaine :</label>
        <select name="day_of_week" required>
            <option value="0">Dimanche</option>
            <option value="1">Lundi</option>
            <option value="2">Mardi</option>
            <option value="3">Mercredi</option>
            <option value="4">Jeudi</option>
            <option value="5">Vendredi</option>
            <option value="6">Samedi</option>
        </select>
    </div>

    <div>
        <label>Heure de début :</label>
        <input type="time" name="start_time" required>
    </div>

    <div>
        <label>Heure de fin :</label>
        <input type="time" name="end_time" required>
    </div>

    <button type="submit">Ajouter</button>
</form>

<h2>Mes créneaux existants</h2>
<table border="1">
    <thead>
        <tr>
            <th>Jour</th>
            <th>Heure de début</th>
            <th>Heure de fin</th>
            <th>Disponible</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $days = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
        foreach ($slots as $slot): ?>
            <tr>
                <td><?= $days[$slot->getDayOfWeek()] ?></td>
                <td><?= $slot->getStartTime() ?></td>
                <td><?= $slot->getEndTime() ?></td>
                <td><?= $slot->isAvailable() ? 'Oui' : 'Non' ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>