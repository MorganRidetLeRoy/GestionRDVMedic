<!-- views/emails/appointment_reminder.php -->
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Rappel de rendez-vous</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .button { background: #ffc107; color: #000; padding: 10px 20px; text-decoration: none; border-radius: 5px; }
        .footer { margin-top: 20px; font-size: 12px; color: #777; }
        .appointment-details { background: #fff3cd; padding: 15px; border-radius: 5px; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Rappel : Votre rendez-vous est demain</h1>
        <p>Bonjour <?= htmlspecialchars($patient->getFirstName() . ' ' . $patient->getLastName()) ?>,</p>

        <p>Ce message est un rappel pour votre rendez-vous de demain avec <strong><?= htmlspecialchars($practitioner->getEmail()) ?></strong>.</p>

        <div class="appointment-details">
            <h2>Détails du rendez-vous</h2>
            <p><strong>Date :</strong> <?= $appointmentDate ?></p>
            <p><strong>Motif :</strong> <?= htmlspecialchars($appointment->getReason() ?? 'Non spécifié') ?></p>
        </div>

        <p>N'oubliez pas de vous présenter à l'heure convenue. Si vous ne pouvez plus venir, merci de nous prévenir.</p>
        <p><a href="<?= $loginUrl ?>" class="button">Se connecter à mon espace</a></p>

        <div class="footer">
            <p>Cordialement,<br>L'équipe Gestion Rendez-Vous</p>
        </div>
    </div>
</body>
</html>