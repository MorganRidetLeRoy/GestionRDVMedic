<?php
// =========================================================
// Models/EmailModel.php
// Envoi d'emails — notifications, confirmations, rappels
// =========================================================

class EmailModel
{
    private string $from = 'noreply@cabinet-medical.fr';
    private string $fromName = 'Cabinet Médical';
    
    private function envoyer(string $to, string $sujet, string $corps): bool
    {
        $headers  = "From: {$this->fromName} <{$this->from}>\r\n";
        $headers .= "Reply-To: {$this->from}\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

        $result = mail($to, $sujet, $corps, $headers);
        if (!$result) {
            error_log("Échec envoi email à $to : $sujet");
        }
        return $result;
    }

    private function template(string $titre, string $contenu): string
    {
        return "<!DOCTYPE html>
<html lang='fr'>
<head><meta charset='UTF-8'><style>
body{font-family:'Segoe UI',sans-serif;background:#f0f4f8;margin:0;padding:20px}
.card{background:#fff;border-radius:12px;max-width:560px;margin:0 auto;padding:32px;box-shadow:0 2px 12px rgba(0,0,0,.08)}
.header{background:#4f46e5;color:#fff;border-radius:8px;padding:20px;text-align:center;margin-bottom:24px}
.header h1{margin:0;font-size:20px}
.content{color:#374151;line-height:1.6}
.badge{display:inline-block;background:#ede9fe;color:#4f46e5;padding:4px 12px;border-radius:20px;font-size:14px;font-weight:600}
.footer{margin-top:24px;padding-top:16px;border-top:1px solid #e5e7eb;font-size:12px;color:#9ca3af;text-align:center}
</style></head>
<body>
<div class='card'>
  <div class='header'><h1>🏥 Cabinet Médical</h1></div>
  <div class='content'>
    <h2 style='color:#1f2937;margin-top:0'>{$titre}</h2>
    {$contenu}
  </div>
  <div class='footer'>Cabinet Médical — Ne pas répondre à cet email.</div>
</div>
</body></html>";
    }

    // ─── F1 Notifications : Identifiants nouveau patient ─────

    public function envoyerIdentifiants(string $email, string $login, string $mdpTemporaire): bool
    {
        $contenu = "<p>Bonjour,</p>
<p>Votre compte patient a été créé suite à votre premier rendez-vous au cabinet.</p>
<p><strong>Vos identifiants de connexion :</strong></p>
<table style='width:100%;border-collapse:collapse;margin:16px 0'>
  <tr><td style='padding:8px;background:#f9fafb;font-weight:600'>Identifiant</td><td style='padding:8px'>{$login}</td></tr>
  <tr><td style='padding:8px;background:#f9fafb;font-weight:600'>Mot de passe temporaire</td><td style='padding:8px'><span class='badge'>{$mdpTemporaire}</span></td></tr>
</table>
<p style='color:#dc2626'>⚠️ Veuillez changer votre mot de passe dès votre première connexion.</p>
<p>Vous pouvez vous connecter sur notre portail patient pour consulter vos rendez-vous.</p>";

        return $this->envoyer($email, 'Vos identifiants — Cabinet Médical', $this->template('Bienvenue !', $contenu));
    }

    // ─── F2 Notifications : Confirmation RDV ─────────────────

    public function envoyerConfirmationRdv(string $email, string $nomPatient, string $date, string $heure, string $medecin): bool
    {
        $contenu = "<p>Bonjour {$nomPatient},</p>
<p>Votre rendez-vous au cabinet a bien été enregistré.</p>
<table style='width:100%;border-collapse:collapse;margin:16px 0'>
  <tr><td style='padding:8px;background:#f9fafb;font-weight:600'>Date</td><td style='padding:8px'>{$date}</td></tr>
  <tr><td style='padding:8px;background:#f9fafb;font-weight:600'>Heure</td><td style='padding:8px'>{$heure}</td></tr>
  <tr><td style='padding:8px;background:#f9fafb;font-weight:600'>Praticien</td><td style='padding:8px'>Dr. {$medecin}</td></tr>
</table>
<p>Pour modifier ou annuler, veuillez appeler le cabinet directement.</p>";

        return $this->envoyer($email, 'Confirmation de rendez-vous — Cabinet Médical', $this->template('Rendez-vous confirmé ✅', $contenu));
    }

    // ─── F3 Notifications : Rappel 24h avant ─────────────────

    public function envoyerRappelRdv(string $email, string $nomPatient, string $date, string $heure, string $medecin): bool
    {
        $contenu = "<p>Bonjour {$nomPatient},</p>
<p>Rappel : vous avez un rendez-vous demain au cabinet médical.</p>
<table style='width:100%;border-collapse:collapse;margin:16px 0'>
  <tr><td style='padding:8px;background:#fef3c7;font-weight:600'>📅 Date</td><td style='padding:8px'>{$date}</td></tr>
  <tr><td style='padding:8px;background:#fef3c7;font-weight:600'>🕐 Heure</td><td style='padding:8px'>{$heure}</td></tr>
  <tr><td style='padding:8px;background:#fef3c7;font-weight:600'>👨‍⚕️ Praticien</td><td style='padding:8px'>Dr. {$medecin}</td></tr>
</table>
<p>En cas d'empêchement, merci de nous contacter le plus tôt possible.</p>";

        return $this->envoyer($email, 'Rappel de rendez-vous demain — Cabinet Médical', $this->template('Rappel de rendez-vous ⏰', $contenu));
    }
}
