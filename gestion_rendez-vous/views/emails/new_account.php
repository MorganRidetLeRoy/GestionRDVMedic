<!-- views/emails/new_account.php -->
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Vos identifiants</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .button { background: #007bff; color: #fff; padding: 10px 20px; text-decoration: none; border-radius: 5px; }
        .footer { margin-top: 20px; font-size: 12px; color: #777; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Bienvenue sur Gestion Rendez-Vous</h1>
        <p>Bonjour <?= htmlspecialchars($patient->getFirstName() . ' ' . $patient->getLastName()) ?>,</p>
        <p>Votre compte a été créé avec succès. Voici vos identifiants pour vous connecter :</p>

        <table>
            <tr>
                <td><strong>Email :</strong></td>
                <td><?= htmlspecialchars($patient->getEmail()) ?></td>
            </tr>
            <tr>
                <td><strong>Mot de passe temporaire :</strong></td>
                <td><?= htmlspecialchars($temporaryPassword) ?></td>
            </tr>
        </table>

        <p>Nous vous recommandons de modifier votre mot de passe après votre première connexion.</p>
        <p><a href="<?= $loginUrl ?>" class="button">Se connecter</a></p>

        <p>Si vous n'avez pas demandé la création de ce compte, veuillez ignorer cet email.</p>

        <div class="footer">
            <p>Cordialement,<br>L'équipe Gestion Rendez-Vous</p>
        </div>
    </div>
</body>
</html>