<?php
$pageTitle  = 'Tableau de bord';
$activePage = 'dashboard';
require __DIR__ . '/../shared/layout_top.php';
?>

<div class="page-header">
  <div>
    <h1>Tableau de bord</h1>
    <p>Bienvenue, <?= htmlspecialchars($_SESSION['login']) ?> · <?= date('l d F Y') ?></p>
  </div>
  <div style="display:flex;gap:.5rem">
    <a href="/Controllers/SecretaireController.php?action=creerPatientForm" class="btn btn-primary">
      ➕ Nouveau patient
    </a>
    <a href="/Controllers/SecretaireController.php?action=prendreRdvForm" class="btn btn-ghost">
      📅 Prendre RDV
    </a>
  </div>
</div>

<!-- Statistiques du mois -->
<div class="stats-grid">
  <div class="stat-card accent">
    <span class="stat-label">RDV ce mois</span>
    <span class="stat-value"><?= (int)($stats['total_rdv'] ?? 0) ?></span>
    <span class="stat-note">Total des rendez-vous</span>
  </div>
  <div class="stat-card">
    <span class="stat-label">Consultés</span>
    <span class="stat-value" style="color:var(--success)"><?= (int)($stats['consultes'] ?? 0) ?></span>
    <span class="stat-note">Patients vus</span>
  </div>
  <div class="stat-card">
    <span class="stat-label">Annulés</span>
    <span class="stat-value" style="color:var(--danger)"><?= (int)($stats['annules'] ?? 0) ?></span>
    <span class="stat-note">Ce mois-ci</span>
  </div>
  <div class="stat-card">
    <span class="stat-label">Absents</span>
    <span class="stat-value" style="color:var(--warning)"><?= (int)($stats['absents'] ?? 0) ?></span>
    <span class="stat-note">Patients non venus</span>
  </div>
  <div class="stat-card">
    <span class="stat-label">Durée moy.</span>
    <span class="stat-value"><?= round($stats['duree_moy'] ?? 22) ?> <small style="font-size:14px">min</small></span>
    <span class="stat-note">Par consultation</span>
  </div>
</div>

<!-- Actions rapides -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:1rem">

  <div class="card">
    <div class="card-header">
      <span class="card-title">🔍 Recherche rapide</span>
    </div>
    <form action="/Controllers/SecretaireController.php?action=rechercherPatient" method="GET">
      <input type="hidden" name="action" value="rechercherPatient">
      <div class="search-bar">
        <input type="text" name="q" class="form-control" placeholder="Nom ou prénom du patient…">
        <button class="btn btn-primary" type="submit">Chercher</button>
      </div>
    </form>
    <form action="/Controllers/SecretaireController.php?action=rechercherMedecin" method="GET" style="margin-top:.5rem">
      <input type="hidden" name="action" value="rechercherMedecin">
      <div class="search-bar">
        <input type="text" name="q" class="form-control" placeholder="Nom d'un médecin…">
        <button class="btn btn-ghost" type="submit">🩺 Médecin</button>
      </div>
    </form>
  </div>

  <div class="card">
    <div class="card-header">
      <span class="card-title">⚡ Actions rapides</span>
    </div>
    <div style="display:flex;flex-direction:column;gap:.6rem">
      <a href="/Controllers/SecretaireController.php?action=creerPatientForm" class="btn btn-ghost btn-block">
        👤 Créer une fiche patient
      </a>
      <a href="/Controllers/SecretaireController.php?action=prendreRdvForm" class="btn btn-ghost btn-block">
        📋 Prendre un rendez-vous
      </a>
      <a href="/Controllers/SecretaireController.php?action=agendaMedecin" class="btn btn-ghost btn-block">
        📅 Consulter un agenda
      </a>
    </div>
  </div>

</div>

<?php require __DIR__ . '/../shared/layout_bottom.php'; ?>
