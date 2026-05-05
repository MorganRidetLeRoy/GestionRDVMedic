<!-- views/emails/appointment_confirmation.php -->
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Confirmation de rendez-vous</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .button { background: #28a745; color: #fff; padding: 10px 20px; text-decoration: none; border-radius: 5px; }
        .footer { margin-top: 20px; font-size: 12px; color: #777; }
        .appointment-details { background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Confirmation de votre rendez-vous</h1>
        <p>Bonjour <?= htmlspecialchars($patient->getFirstName() . ' ' . $patient->getLastName()) ?>,</p>

        <p>Votre rendez-vous avec <strong><?= htmlspecialchars($practitioner->getEmail()) ?></strong> a été confirmé.</p>

        <div class="appointment-details">
            <h2>Détails du rendez-vous</h2>
            <p><strong>Date :</strong> <?= $appointmentDate ?></p>
            <p><strong>Motif :</strong> <?= htmlspecialchars($appointment->getReason() ?? 'Non spécifié') ?></p>
        </div>

        <p>Vous pouvez vous connecter à votre espace pour consulter vos rendez-vous :</p>
        <p><a href="<?= $loginUrl ?>" class="button">Se connecter</a></p>

        <p>Si vous ne pouvez pas assister à ce rendez-vous, veuillez nous contacter au plus tôt.</p>

        <div class="footer">
            <p>Cordialement,<br>L'équipe Gestion Rendez-Vous</p>
        </div>
    </div>
</body>
</html>