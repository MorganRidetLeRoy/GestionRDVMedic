<?php
// Active l'affichage des erreurs (À DÉSACTIVER EN PRODUCTION !)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// index.php (à la racine du projet)
// Démarre la session
require_once __DIR__ . '/model/Database.php';
require_once __DIR__ . '/model/Appointement.php';

session_start();

if (!isset($_SESSION['user_id'])) {
    // Pour le développement, on utilise un utilisateur par défaut
    $_SESSION['user_id'] = 1;
    $_SESSION['role'] = 'medecin'; // Rôle par défaut
}

// Récupère les informations de l'utilisateur connecté
$userId = $_SESSION['user_id'];
$userRole = $_SESSION['role'] ?? 'patient';

// Initialise la classe Appointment
$appointment = new Appointment();

// Récupère la date de début de la semaine (lundi)
$weekStartDate = isset($_GET['week_start']) ? $_GET['week_start'] : date('Y-m-d', strtotime('monday this week'));
$weekStartTimestamp = strtotime($weekStartDate);

// Récupère les rendez-vous pour l'agenda détaillé
$appointments = $appointment->getAppointments();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agenda Médical</title>
    <!-- Chemin vers les fichiers CSS -->
    <link rel="stylesheet" href="view/style.css">
    <link rel="stylesheet" href="view/css/timetable.css">
    <!-- FullCalendar depuis CDN -->
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js'></script>
</head>
<body>
    <div class="container">
        <!-- Champ caché pour l'ID du médecin -->
        <input type="hidden" id="idMedecin" value="<?php echo htmlspecialchars($userId); ?>">

        <!-- Section d'administration (uniquement pour les médecins et admins) -->
        <?php if (in_array($userRole, ['medecin', 'admin'])): ?>
            <div class="admin-section">
                <h3>⚙️ Outils d'administration</h3>
                <form id="generateSlotsForm" class="admin-form">
                    <input type="hidden" name="action" value="generateSlots">
                    <div>
                        <label for="admin_id_medecin">Médecin:</label>
                        <select id="admin_id_medecin" name="id_medecin" required>
                            <option value="<?php echo htmlspecialchars($userId); ?>" selected>
                                Moi (ID: <?php echo htmlspecialchars($userId); ?>)
                            </option>
                            <!-- Option pour les admins: sélectionner un autre médecin -->
                            <?php if ($userRole === 'admin'): ?>
                                <?php
                                $db = new Database();
                                $conn = $db->getConnection();
                                $query = "SELECT id_medecin, AES_DECRYPT(nom, 'Clé de Chiffrement78513') as nom,
                                                AES_DECRYPT(prenom, 'Clé de Chiffrement78513') as prenom
                                          FROM medecin";
                                $stmt = $conn->prepare($query);
                                $stmt->execute();
                                $medecins = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                foreach ($medecins as $medecin) {
                                    if ($medecin['id_medecin'] != $userId) {
                                        echo '<option value="' . htmlspecialchars($medecin['id_medecin']) . '">'
                                             . htmlspecialchars($medecin['prenom'] . ' ' . $medecin['nom']) . '</option>';
                                    }
                                }
                                ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div>
                        <label for="admin_date_debut">Date de début:</label>
                        <input type="date" id="admin_date_debut" name="date_debut" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div>
                        <label for="admin_date_fin">Date de fin:</label>
                        <input type="date" id="admin_date_fin" name="date_fin" value="<?php echo date('Y-m-d', strtotime('+1 month')); ?>" required>
                    </div>
                    <div>
                        <label for="admin_heure_debut">Heure de début:</label>
                        <input type="time" id="admin_heure_debut" name="heure_debut" value="09:00:00" required>
                    </div>
                    <div>
                        <label for="admin_heure_fin">Heure de fin:</label>
                        <input type="time" id="admin_heure_fin" name="heure_fin" value="18:00:00" required>
                    </div>
                    <div>
                        <label for="admin_duree_creneau">Durée (min):</label>
                        <select id="admin_duree_creneau" name="duree_creneau" required>
                            <option value="15">15</option>
                            <option value="20">20</option>
                            <option value="30" selected>30</option>
                            <option value="45">45</option>
                            <option value="60">60</option>
                        </select>
                    </div>
                    <div>
                        <button type="submit">Générer les créneaux</button>
                    </div>
                </form>
                <div id="generateSlotsNotification"></div>
            </div>
        <?php endif; ?>

        <!-- Section de recherche -->
        <div class="search-section">
            <input type="text" id="searchInput" placeholder="Rechercher un patient...">
            <button id="searchButton">Rechercher</button>
        </div>

        <!-- Formulaire de prise de rendez-vous -->
        <div class="appointment-form">
            <h2>📅 Prendre un Rendez-vous</h2>
            <div id="notification"></div>
            <form id="createAppointmentForm">
                <div class="form-group">
                    <label for="nom">Nom *</label>
                    <input type="text" id="nom" name="nom" placeholder="Votre nom" required>
                </div>
                <div class="form-group">
                    <label for="prenom">Prénom *</label>
                    <input type="text" id="prenom" name="prenom" placeholder="Votre prénom" required>
                </div>
                <div class="form-group">
                    <label for="telephone">Téléphone *</label>
                    <input type="tel" id="telephone" name="telephone" placeholder="0612345678" pattern="[0-9]{10}" required>
                    <small>Format : 10 chiffres (ex: 0612345678)</small>
                </div>
                <div class="form-group">
                    <label for="datePicker">Choisir une date *</label>
                    <input type="date" id="datePicker" name="date_rdv" required min="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="form-group">
                    <label>Créneaux disponibles</label>
                    <div id="slotsContainer">
                        <p>Veuillez choisir une date pour voir les créneaux libres.</p>
                    </div>
                </div>
                <input type="hidden" id="selectedSlotId" name="id_creneau" required>
                <button type="submit" id="submitBtn" disabled class="submit-btn">
                    ✅ Confirmer le rendez-vous
                </button>
            </form>
        </div>

        <!-- Liste des rendez-vous -->
        <div class="appointments-list">
            <h2>📋 Liste des Rendez-vous</h2>
            <div id="appointmentsList"></div>
        </div>

        <!-- Gros agenda détaillé -->
        <div class="detailed-agenda">
            <h2>📅 Agenda détaillé</h2>
            <div class="week-navigation">
                <button onclick="changeWeek(-1)">← Semaine précédente</button>
                <span>
                    Semaine du
                    <strong>
                        <?php echo date('d/m/Y', $weekStartTimestamp); ?>
                    </strong>
                    au
                    <strong>
                        <?php echo date('d/m/Y', strtotime('+6 days', $weekStartTimestamp)); ?>
                    </strong>
                </span>
                <button onclick="changeWeek(1)">Semaine suivante →</button>
            </div>
            <table>
                <thead>
                    <tr>
                        <th class="time-slot">Heure</th>
                        <?php
                        // Génère les en-têtes pour chaque jour de la semaine (Lundi → Dimanche)
                        $days = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];
                        $currentDate = $weekStartTimestamp;
                        for ($i = 0; $i < 7; $i++) {
                            $date = date('d/m/Y', $currentDate);
                            $dayName = $days[$i];
                            echo "<th class='date-header'>$dayName<br><small>$date</small></th>";
                            $currentDate = strtotime('+1 day', $currentDate);
                        }
                        ?>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Génère les lignes pour chaque heure (de 8h à 19h)
                    for ($hour = 8; $hour <= 19; $hour++) {
                        $timeSlot = sprintf("%02d:00 - %02d:30", $hour, $hour + 1);
                        if ($hour == 19) {
                            $timeSlot = sprintf("%02d:00 - %02d:00", $hour, $hour + 1);
                        }
                        echo "<tr>";
                        echo "<td class='time-slot'>$timeSlot</td>";

                        // Pour chaque jour de la semaine
                        $currentDate = $weekStartTimestamp;
                        for ($day = 0; $day < 7; $day++) {
                            $date = date('Y-m-d', $currentDate);
                            $dayName = $days[$day];
                            $timeStart = sprintf("%02d:00:00", $hour);
                            $timeEnd = sprintf("%02d:30:00", $hour);
                            if ($hour == 19) {
                                $timeEnd = sprintf("%02d:00:00", $hour + 1);
                            }

                            // Vérifie si le créneau est occupé
                            $slotStatus = 'free-slot';
                            $slotText = 'Disponible';
                            $isBooked = false;

                            // Vérifie si c'est l'heure du déjeuner (12h-14h)
                            if ($hour >= 12 && $hour < 14) {
                                $slotStatus = 'break-slot';
                                $slotText = 'Pause déjeuner';
                            }
                            // Vérifie si c'est le week-end (samedi/dimanche)
                            elseif ($day >= 5) {
                                $slotStatus = 'break-slot';
                                $slotText = 'Fermé';
                            } else {
                                // Récupère les créneaux occupés depuis la base de données
                                foreach ($appointments as $appointmentData) {
                                    $appointmentDate = date('Y-m-d', strtotime($appointmentData['date_planning'] ?? 'now'));
                                    $appointmentStartTime = date('H:i:s', strtotime($appointmentData['heure_debut'] ?? '00:00:00'));

                                    if ($appointmentDate === $date && $appointmentStartTime === $timeStart) {
                                        $slotStatus = 'booked-slot';
                                        $slotText = htmlspecialchars($appointmentData['prenom_patient'] . ' ' . $appointmentData['nom_patient']);
                                        $isBooked = true;
                                        break;
                                    }
                                }
                            }

                            // Affiche le créneau
                            echo "<td class='$slotStatus' data-date='$date' data-time='$timeStart'>$slotText</td>";
                            $currentDate = strtotime('+1 day', $currentDate);
                        }
                        echo "</tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <!-- Calendrier FullCalendar -->
        <div id="calendar"></div>
    </div>

    <!-- Variables globales pour le JS -->
    <script>
        const userId = <?php echo json_encode($userId); ?>;
        const userRole = <?php echo json_encode($userRole); ?>;
        const weekStartDate = <?php echo json_encode($weekStartDate); ?>;

        // Fonction pour changer de semaine
        function changeWeek(delta) {
            const currentDate = new Date(weekStartDate);
            currentDate.setDate(currentDate.getDate() + (delta * 7));
            const newWeekStart = currentDate.toISOString().split('T')[0];
            window.location.search = `?week_start=${newWeekStart}`;
        }
    </script>
    <!-- Chemin relatif vers le JS dans view/ -->
    <script src="view/script.js"></script>
</body>
</html>
