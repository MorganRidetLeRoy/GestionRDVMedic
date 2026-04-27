<?php
// =========================================================
// Views/shared/layout.php
// En-tête commun à toutes les vues (après authentification)
// Appel : include avec $pageTitle et $activePage définis
// =========================================================
if (!isset($pageTitle)) $pageTitle = 'Cabinet Médical';
if (!isset($activePage)) $activePage = '';

$role  = $_SESSION['role']  ?? '';
$login = $_SESSION['login'] ?? '';
$initials = strtoupper(substr($login, 0, 2));

// Flash messages
$flashSuccess = $_SESSION['flash_success'] ?? null; unset($_SESSION['flash_success']);
$flashError   = $_SESSION['flash_error']   ?? null; unset($_SESSION['flash_error']);

// Menus par rôle
$menus = [
  'admin' => [
    ['icon'=>'🏠','label'=>'Tableau de bord','action'=>'/Controllers/AdminController.php?action=index','key'=>'dashboard'],
    ['icon'=>'👥','label'=>'Comptes actifs',  'action'=>'/Controllers/AdminController.php?action=listeComptes','key'=>'comptes'],
    ['icon'=>'➕','label'=>'Créer un compte', 'action'=>'/Controllers/AdminController.php?action=creerCompteForm','key'=>'creer'],
    ['icon'=>'🔑','label'=>'Mon mot de passe','action'=>'/Controllers/AdminController.php?action=changerMdpForm','key'=>'mdp'],
  ],
  'secretaire' => [
    ['icon'=>'🏠','label'=>'Tableau de bord','action'=>'/Controllers/SecretaireController.php?action=index','key'=>'dashboard'],
    ['icon'=>'📅','label'=>'Agenda médecins', 'action'=>'/Controllers/SecretaireController.php?action=agendaMedecin','key'=>'agenda'],
    ['icon'=>'➕','label'=>'Nouveau patient',  'action'=>'/Controllers/SecretaireController.php?action=creerPatientForm','key'=>'creer_patient'],
    ['icon'=>'🔍','label'=>'Rechercher patient','action'=>'/Controllers/SecretaireController.php?action=rechercherPatient','key'=>'recherche_patient'],
    ['icon'=>'🩺','label'=>'Rechercher médecin','action'=>'/Controllers/SecretaireController.php?action=rechercherMedecin','key'=>'recherche_medecin'],
    ['icon'=>'📋','label'=>'Nouveau RDV',     'action'=>'/Controllers/SecretaireController.php?action=prendreRdvForm','key'=>'rdv'],
  ],
  'praticien' => [
    ['icon'=>'🏠','label'=>'Tableau de bord','action'=>'/Controllers/MedecinController.php?action=index','key'=>'dashboard'],
    ['icon'=>'📅','label'=>'Mon agenda',      'action'=>'/Controllers/MedecinController.php?action=monAgenda','key'=>'mon_agenda'],
    ['icon'=>'📆','label'=>'Agenda praticien','action'=>'/Controllers/MedecinController.php?action=agendaMedecin','key'=>'agenda'],
    ['icon'=>'🔍','label'=>'Rechercher patient','action'=>'/Controllers/MedecinController.php?action=rechercherPatient','key'=>'recherche'],
  ],
  'patient' => [
    ['icon'=>'📅','label'=>'Mes rendez-vous','action'=>'/Controllers/PatientController.php?action=index','key'=>'rdv'],
    ['icon'=>'👤','label'=>'Mon profil',      'action'=>'/Controllers/PatientController.php?action=profil','key'=>'profil'],
    ['icon'=>'🔑','label'=>'Mon mot de passe','action'=>'/Controllers/PatientController.php?action=changerMdpForm','key'=>'mdp'],
  ],
];

$roleLabel = ['admin'=>'Administrateur','secretaire'=>'Secrétaire','praticien'=>'Praticien','patient'=>'Patient'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle) ?> — Cabinet Médical</title>
  <link rel="stylesheet" href="/public/css/style.css">
</head>
<body>
<div class="app-layout">

  <!-- Sidebar -->
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-logo">
      <div class="sidebar-logo-icon">🏥</div>
      <div class="sidebar-logo-text">
        Cabinet Médical
        <small><?= htmlspecialchars($roleLabel[$role] ?? $role) ?></small>
      </div>
    </div>

    <nav class="sidebar-nav">
      <div class="nav-section">
        <div class="nav-section-title">Navigation</div>
        <?php foreach (($menus[$role] ?? []) as $item): ?>
          <a href="<?= $item['action'] ?>"
             class="nav-item <?= $activePage === $item['key'] ? 'active' : '' ?>">
            <span class="icon"><?= $item['icon'] ?></span>
            <?= htmlspecialchars($item['label']) ?>
          </a>
        <?php endforeach ?>
      </div>
    </nav>

    <div class="sidebar-footer">
      <div class="sidebar-user">
        <div class="sidebar-avatar"><?= htmlspecialchars($initials) ?></div>
        <div>
          <div style="font-size:13px;font-weight:600;color:#fff"><?= htmlspecialchars($login) ?></div>
          <a href="/Controllers/AuthController.php?action=deconnexion"
             style="font-size:11px;color:rgba(255,255,255,.55)">Se déconnecter</a>
        </div>
      </div>
    </div>
  </aside>

  <!-- Main -->
  <div class="main-content">
    <header class="topbar">
      <span class="topbar-title"><?= htmlspecialchars($pageTitle) ?></span>
      <div class="topbar-right">
        <span class="topbar-user">👤 <?= htmlspecialchars($login) ?></span>
        <a href="/Controllers/AuthController.php?action=deconnexion" class="btn btn-ghost btn-sm">Déconnexion</a>
      </div>
    </header>

    <div class="page-body">
      <?php if ($flashSuccess): ?>
        <div class="flash flash-success">✅ <?= $flashSuccess ?></div>
      <?php endif ?>
      <?php if ($flashError): ?>
        <div class="flash flash-error">❌ <?= htmlspecialchars($flashError) ?></div>
      <?php endif ?>
    </div><!-- /page-body -->
    
  </div><!-- /main-content -->
  
</div><!-- /app-layout -->

<script>
// Ferme sidebar en mobile si clic à l'extérieur
document.addEventListener('click', function(e) {
  const sidebar = document.getElementById('sidebar');
  if (sidebar && window.innerWidth <= 768) {
    if (!sidebar.contains(e.target) && !e.target.closest('#menuBtn')) {
      sidebar.classList.remove('open');
    }
  }
});
</script>
</body>
</html>
