<?php
// public/index.php
session_start();
require_once __DIR__ . '/../config/database.php';

// Initialiser le service de chiffrement (US-34)
DatabaseManager::init();

// Vérifier et désactiver les utilisateurs inactifs (US-06)
User::disconnectInactiveUsers(30);

// Vérifier qu'il n'y a qu'un seul admin local (US-26)
Admin::ensureSingleAdminLocal();

// Routing amélioré
$request = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// Extraire les paramètres de l'URL
$params = explode('/', trim($request, '/'));
$action = $params[0] ?? '';
$subAction = $params[1] ?? '';
$subSubAction = $params[2] ?? null;
$id = $params[3] ?? null;

// Dispatcher vers les contrôleurs appropriés
switch ($action) {
    // Authentification
    case 'login':
        require __DIR__ . '/../controllers/AuthController.php';
        $controller = new AuthController();
        $controller->login();
        break;

    case 'logout':
        require __DIR__ . '/../controllers/AuthController.php';
        $controller = new AuthController();
        $controller->logout();
        break;

    case 'reset-password':
        require __DIR__ . '/../controllers/AuthController.php';
        $controller = new AuthController();
        $controller->resetPassword();
        break;
    
    case 'reset-password':
        require __DIR__ . '/../controllers/PasswordController.php';
        $controller = new PasswordController();
        $controller->resetPassword();
        break;

    // Administration (US-23 à US-26)
    case 'admin':
        require __DIR__ . '/../controllers/AdminController.php';
        $controller = new AdminController();

        if (empty($subAction)) {
            $controller->showDashboard();
        } elseif ($subAction === 'users') {
            if (empty($id)) {
                $controller->showUsersList();
            } elseif ($id === 'create') {
                require __DIR__ . '/../controllers/UserController.php';
                $userController = new UserController();
                $userController->createUser();
            } elseif ($id === 'disable' && isset($params[2])) {
                $controller->disableUser($params[2]);
            } elseif ($id === 'reset-password' && isset($params[2])) {
                $controller->resetUserPassword($params[2]);
            }
        }
        break;

    // Gestion des utilisateurs (Admin)
    case 'admin':
        if ($subAction === 'users' && $id === 'create') {
            require __DIR__ . '/../controllers/UserController.php';
            $controller = new UserController();
            $controller->createUser();
        }
        break;

    // Dans public/index.php, ajoutez dans le switch :
    case 'admin':
        require __DIR__ . '/../controllers/AdminController.php';
        $controller = new AdminController();

        if ($subAction === 'send-reminders') {
            require __DIR__ . '/../controllers/AppointmentController.php';
            $appointmentController = new AppointmentController();
            $appointmentController->sendReminders();
        }
        break;

    // Gestion de la base de données (US-36)
    case 'admin':
        if ($subAction === 'database') {
            require __DIR__ . '/../controllers/DatabaseController.php';
            $controller = new DatabaseController();

            if (empty($subSubAction)) {
                $controller->listTables();
            } elseif ($subSubAction === 'table' && isset($id)) {
                $controller->showTableStructure($id);
            } elseif ($subSubAction === 'export') {
                $controller->exportSchema();
            }
        }
        break;

    // Gestion des rendez-vous (Secrétaire)
    case 'secretary':
    require __DIR__ . '/../controllers/AppointmentController.php';
    $controller = new AppointmentController();

    if ($subAction === 'appointments') {
        if (empty($id)) {
            $controller->listSecretaryAppointments();
        } elseif ($id === 'create') {
            $controller->createAppointment();
        } elseif ($id === 'edit' && isset($params[2])) {
            $controller->editAppointment($params[2]);
        } elseif ($id === 'cancel' && isset($params[2])) {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $controller->cancelAppointment($params[2]);
            } else {
                $appointment = Appointment::findById($params[2]);
                require __DIR__ . '/../views/secretary/cancel_appointment.php';
            }
        }
    }
    break;

    // Gestion des dossiers médicaux (Secrétaire - US-29)
    case 'secretary':
        require __DIR__ . '/../controllers/MedicalRecordController.php';
        $controller = new MedicalRecordController();

        if ($subAction === 'medical-records' && is_numeric($subSubAction)) {
            $controller->viewRecordAsSecretary($subSubAction);
        } elseif ($subAction === 'appointments') {
            require __DIR__ . '/../controllers/AppointmentController.php';
            $appointmentController = new AppointmentController();
            $appointmentController->listSecretaryAppointments();
        }
        break;

    // Gestion des dossiers médicaux (Praticien)
    case 'practitioner':
        if ($subAction === 'medical-records') {
            require __DIR__ . '/../controllers/MedicalRecordController.php';
            $controller = new MedicalRecordController();

            if (empty($id)) {
                $controller->listRecords();
            } elseif ($id === 'create' && isset($params[2])) {
                $controller->createRecord($params[2]);
            } elseif (is_numeric($id)) {
                if (empty($params[2])) {
                    $controller->viewRecord($id);
                } elseif ($params[2] === 'notes') {
                    if (empty($params[3])) {
                        $controller->showAddNoteForm($id);
                    } elseif ($params[3] === 'add') {
                        $controller->addNote($id);
                    } elseif ($params[3] === 'edit' && isset($params[4])) {
                        $controller->showEditNoteForm($params[4]);
                    } elseif ($params[3] === 'delete' && isset($params[4])) {
                        $controller->deleteNote($params[4]);
                    }
                }
            }
        } elseif ($subAction === 'appointments') {
            require __DIR__ . '/../controllers/AppointmentController.php';
            $appointmentController = new AppointmentController();
            $appointmentController->listPractitionerAppointments();
        } elseif ($subAction === 'schedule') {
            require __DIR__ . '/../controllers/AppointmentController.php';
            $appointmentController = new AppointmentController();
            if ($method === 'POST') {
                $appointmentController->updateSchedule();
            } else {
                $appointmentController->showScheduleForm();
            }
        }
        break;

    // Gestion des rendez-vous (Patient)
    case 'patient':
    require __DIR__ . '/../controllers/AppointmentController.php';
    $controller = new AppointmentController();

    if ($subAction === 'appointments') {
        if (empty($id)) {
            $controller->listPatientAppointments();
        } else {
            $appointment = Appointment::findById($id);
            require __DIR__ . '/../views/patient/appointment-details.php';
        }
    }
    break;

    // Statistiques (US-07)
    case 'stats':
        require __DIR__ . '/../controllers/StatsController.php';
        $controller = new StatsController();
        $controller->showMonthlyStats();
        break;

    // Dashboard
    case 'dashboard':
        if (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'admin_local') {
            header('Location: /admin');
        } elseif (isset($_SESSION['user'])) {
            header('Location: /' . $_SESSION['user']['role'] . '/appointments');
        } else {
            header('Location: /login');
        }
        break;

    // Page par défaut
    default:
        if (Auth::isLoggedIn()) {
            if (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'admin_local') {
                header('Location: /admin');
            } elseif (isset($_SESSION['user'])) {
                header('Location: /' . $_SESSION['user']['role'] . '/appointments');
            } else {
                header('Location: /patient/appointments');
            }
        } else {
            header('Location: /login');
        }
        break;
}
?>