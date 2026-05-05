<!-- views/layout.php -->
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?php
        // Titre dynamique selon la page
        if (isset($title)) {
            echo htmlspecialchars($title) . ' - ';
        }
        ?>
        Gestion Rendez-Vous - Cabinet Médical
    </title>

    <!-- Intégration du CSS principal -->
    <link rel="stylesheet" href="/assets/css/style.css">

    <!-- Intégration de Font Awesome pour les icônes (optionnel mais recommandé) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Favicon (optionnel) -->
    <link rel="icon" type="image/png" href="/assets/images/favicon.png" />
</head>
<body>
    <header>
        <div class="container">
            <h1>
                <i class="fas fa-hospital-alt"></i>
                Gestion <span>Rendez-Vous</span>
            </h1>
            <nav>
                <?php if (Auth::isLoggedIn()): ?>
                    <?php if (isset($_SESSION['user'])): ?>
                        <!-- Menu pour les utilisateurs (secrétaire, praticien, admin) -->
                        <a href="/<?= $_SESSION['user']['role'] ?>/appointments">
                            <i class="fas fa-calendar-alt"></i> Rendez-vous
                        </a>
                        <?php if ($_SESSION['user']['role'] === 'admin_local'): ?>
                            <a href="/admin">
                                <i class="fas fa-user-cog"></i> Administration
                            </a>
                        <?php endif; ?>
                        <?php if ($_SESSION['user']['role'] === 'praticien'): ?>
                            <a href="/practitioner/medical-records">
                                <i class="fas fa-folder-medical"></i> Dossiers Médicaux
                            </a>
                        <?php endif; ?>
                        <a href="/admin/database">
                            <i class="fas fa-database"></i> Base de Données
                        </a>
                    <?php elseif (isset($_SESSION['patient'])): ?>
                        <!-- Menu pour les patients -->
                        <a href="/patient/appointments">
                            <i class="fas fa-calendar-check"></i> Mes Rendez-vous
                        </a>
                    <?php endif; ?>
                    <a href="/reset-password">
                        <i class="fas fa-key"></i> Modifier le mot de passe
                    </a>
                    <a href="/logout">
                        <i class="fas fa-sign-out-alt"></i> Déconnexion
                    </a>
                <?php else: ?>
                    <!-- Menu pour les visiteurs non connectés -->
                    <a href="/login">
                        <i class="fas fa-sign-in-alt"></i> Connexion
                    </a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <main class="container">
        <!-- Affichage des messages de succès/erreur -->
        <?php if (isset($success)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?= $success ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?= $error ?>
            </div>
        <?php endif; ?>

        <!-- Contenu dynamique -->
        <?php if (isset($content)): ?>
            <?php require $content; ?>
        <?php endif; ?>
    </main>

    <footer>
        <div class="container">
            <p>
                &copy; <?= date('Y') ?> - Application de gestion de rendez-vous |
                <i class="fas fa-heartbeat"></i> Cabinet Médical
            </p>
        </div>
    </footer>

    <!-- Intégration de JavaScript (optionnel pour les fonctionnalités avancées) -->
    <script src="/assets/js/main.js"></script>
</body>
</html>