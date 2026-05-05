<?php
// public/scripts/send_reminders.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Appointment.php';

$count = Appointment::sendReminders();
echo "Rappels envoyés pour $count rendez-vous.\n";
?>