<?php
// config/mail.php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

// Fonction générique pour envoyer un email
function sendEmail($to, $subject, $body) {
    $mail = new PHPMailer(true);
    try {
        // Configuration SMTP (à adapter selon votre hébergeur)
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com'; // ou votre serveur SMTP
        $mail->SMTPAuth = true;
        $mail->Username = 'votre_email@gmail.com';
        $mail->Password = 'votre_mot_de_passe_app'; // Mot de passe ou clé d'application
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Expéditeur et destinataire
        $mail->setFrom('votre_email@gmail.com', 'Gestion Rendez-Vous');
        $mail->addAddress($to);

        // Contenu
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;
        $mail->AltBody = strip_tags($body); // Version texte brut

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Erreur d'envoi d'email : " . $mail->ErrorInfo);
        return false;
    }
}
?>