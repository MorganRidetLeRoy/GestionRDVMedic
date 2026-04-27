<?php
$pageTitle  = 'Créer une fiche patient';
$activePage = 'creer_patient';
require __DIR__ . '/../shared/layout_top.php';
?>

<div class="page-header">
  <div>
    <h1>Nouvelle fiche patient</h1>
    <p>Renseignez les informations administratives du patient</p>
  </div>
  <a href="/Controllers/SecretaireController.php?action=index" class="btn btn-ghost">← Retour</a>
</div>

<div style="max-width:600px">
  <div class="card">
    <div class="card-header">
      <span class="card-title">📋 Informations administratives</span>
      <span class="badge badge-primary">F1</span>
    </div>

    <form action="/Controllers/SecretaireController.php?action=creerPatient" method="POST">
      <?= AuthController::csrfField() ?>

      <div class="form-row">
        <div class="form-group">
          <label for="nom">Nom <span style="color:var(--danger)">*</span></label>
          <input class="form-control" type="text" id="nom" name="nom"
                 placeholder="DUPONT" required value="<?= htmlspecialchars($_POST['nom'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label for="prenom">Prénom <span style="color:var(--danger)">*</span></label>
          <input class="form-control" type="text" id="prenom" name="prenom"
                 placeholder="Jean" required value="<?= htmlspecialchars($_POST['prenom'] ?? '') ?>">
        </div>
      </div>

      <div class="form-group">
        <label for="telephone">Téléphone <span style="color:var(--danger)">*</span></label>
        <input class="form-control" type="tel" id="telephone" name="telephone"
               placeholder="06 12 34 56 78" required value="<?= htmlspecialchars($_POST['telephone'] ?? '') ?>">
        <small style="color:var(--text-muted);font-size:12px">Format : 06 12 34 56 78 ou +33 6 12 34 56 78</small>
      </div>

      <div class="form-group">
        <label for="email">Email <span style="color:var(--danger)">*</span></label>
        <input class="form-control" type="email" id="email" name="email"
               placeholder="jean.dupont@email.fr" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
      </div>

      <div style="display:flex;gap:.75rem;margin-top:1.25rem">
        <button type="submit" class="btn btn-primary">✅ Créer la fiche</button>
        <a href="/Controllers/SecretaireController.php?action=index" class="btn btn-ghost">Annuler</a>
      </div>
    </form>

  </div>

  <div class="card" style="margin-top:1rem;background:var(--primary-light);border-color:var(--primary)">
    <p style="font-size:13px;color:var(--primary-dark)">
      🔒 <strong>Confidentialité :</strong> Seules les informations administratives sont saisies ici.
      Les données médicales (antécédents, allergies, traitements) sont réservées au praticien.
    </p>
  </div>
</div>

<?php require __DIR__ . '/../shared/layout_bottom.php'; ?>
