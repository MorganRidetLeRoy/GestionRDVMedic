<?php
// services/EmailService.php
require_once __DIR__ . '/../config/mail.php';
require_once __DIR__ . '/../models/Notification.php';
require_once __DIR__ . '/../models/Patient.php';
require_once __DIR__ . '/../models/User.php';

class EmailService {
    // Envoyer un email de création de compte (US-31)
    public static function sendNewAccountEmail($patient, $temporaryPassword) {
        $subject = "Vos identifiants pour l'application Gestion Rendez-Vous";
        $body = self::renderEmailTemplate('new_account', [
            'patient' => $patient,
            'temporaryPassword' => $temporaryPassword,
            'loginUrl' => 'http://votre-site.com/login'
        ]);

        $success = sendEmail($patient->getEmail(), $subject, $body);

        // Enregistrer la notification (optionnel)
        if ($success) {
            Notification::create(null, $patient->getId(), 'new_account', $subject, 'sent');
        } else {
            Notification::create(null, $patient->getId(), 'new_account', $subject, 'failed');
        }

        return $success;
    }

    // Envoyer un email de confirmation de rendez-vous (US-32)
    public static function sendAppointmentConfirmationEmail($appointment) {
        $patient = Patient::findById($appointment->getPatientId());
        $practitioner = User::findById($appointment->getPractitionerId());

        $subject = "Confirmation de votre rendez-vous";
        $body = self::renderEmailTemplate('appointment_confirmation', [
            'appointment' => $appointment,
            'patient' => $patient,
            'practitioner' => $practitioner,
            'appointmentDate' => date('d/m/Y à H:i', strtotime($appointment->getDate())),
            'loginUrl' => 'http://votre-site.com/login'
        ]);

        $success = sendEmail($patient->getEmail(), $subject, $body);

        // Enregistrer la notification
        if ($success) {
            Notification::create(null, $patient->getId(), 'appointment_confirmation', $subject, 'sent');
        } else {
            Notification::create(null, $patient->getId(), 'appointment_confirmation', $subject, 'failed');
        }

        return $success;
    }

    // Envoyer un email de rappel de rendez-vous (US-33)
    public static function sendAppointmentReminderEmail($appointment) {
        $patient = Patient::findById($appointment->getPatientId());
        $practitioner = User::findById($appointment->getPractitionerId());

        $subject = "Rappel : Votre rendez-vous est demain";
        $body = self::renderEmailTemplate('appointment_reminder', [
            'appointment' => $appointment,
            'patient' => $patient,
            'practitioner' => $practitioner,
            'appointmentDate' => date('d/m/Y à H:i', strtotime($appointment->getDate())),
            'loginUrl' => 'http://votre-site.com/login'
        ]);

        $success = sendEmail($patient->getEmail(), $subject, $body);

        // Enregistrer la notification
        if ($success) {
            Notification::create(null, $patient->getId(), 'appointment_reminder', $subject, 'sent');
        } else {
            Notification::create(null, $patient->getId(), 'appointment_reminder', $subject, 'failed');
        }

        return $success;
    }

    // Rendre un template d'email
    private static function renderEmailTemplate($templateName, $data) {
        $templatePath = __DIR__ . '/../views/emails/' . $templateName . '.php';
        if (!file_exists($templatePath)) {
            throw new Exception("Template d'email introuvable : " . $templateName);
        }

        ob_start();
        extract($data);
        require $templatePath;
        return ob_get_clean();
    }
}
?>