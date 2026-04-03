<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Résumé de la journée - <?= htmlspecialchars($data['date'], ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="container">
        <h1>Résumé de la journée - <?= htmlspecialchars($data['date'], ENT_QUOTES, 'UTF-8') ?></h1>

        <!-- Filtres -->
        <div class="filters">
            <form method="GET" action="/public/index.php">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                <label for="date">Date :</label>
                <input type="date" name="date" id="date" value="<?= htmlspecialchars($data['date'], ENT_QUOTES, 'UTF-8') ?>" required>

                <label for="medecin_id">Médecin :</label>
                <select name="medecin_id" id="medecin_id">
                    <option value="">Tous les médecins</option>
                    <?php foreach ($data['medecinsTop'] as $medecin): ?>
                        <option value="<?= $medecin['id_medecin'] ?>" <?= $data['medecin_id'] == $medecin['id_medecin'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($medecin['nom_complet'], ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <button type="submit">Filtrer</button>
                <!-- SÉCURITÉ : CSRF - Formulaire POST pour l'export -->
                <form method="POST" action="../index.php" style="display: inline;">
                    <input type="hidden" name="date" value="<?= htmlspecialchars($data['date'], ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="medecin_id" value="<?= $data['medecin_id'] ?>">
                    <input type="hidden" name="export" value="csv">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                    <button type="submit" class="button">Exporter en CSV</button>
                </form>
            </form>
        </div>

        <!-- Statistiques des RDV -->
        <div class="stats-card">
            <h2>Statistiques des RDV</h2>
            <div class="stats-grid">
                <div class="stat-item">
                    <p>RDV prévus : <strong><?= $data['rdvStats']['totalRDV'] ?></strong></p>
                    <?php if (isset($data['comparaison']['hier'])): ?>
                        <p class="comparaison">
                            <?= $data['rdvStats']['totalRDV'] - $data['comparaison']['hier']['totalRDV'] >= 0 ? '+' : '' ?>
                            <?= $data['rdvStats']['totalRDV'] - $data['comparaison']['hier']['totalRDV'] ?>
                            par rapport à hier
                        </p>
                    <?php endif; ?>
                </div>
                <div class="stat-item">
                    <p>RDV réalisés : <strong><?= $data['rdvStats']['rdvRealises'] ?></strong></p>
                </div>
                <div class="stat-item">
                    <p>RDV annulés : <strong><?= $data['rdvStats']['rdvAnnules'] ?></strong></p>
                </div>
                <div class="stat-item">
                    <p>RDV absents : <strong><?= $data['rdvStats']['rdvAbsents'] ?></strong></p>
                </div>
                <div class="stat-item">
                    <p>Taux de présence : <strong><?= $data['tauxPresence'] ?>%</strong></p>
                </div>
            </div>
        </div>

        <!-- Avis des patients -->
        <div class="stats-card">
            <h2>Avis des patients</h2>
            <div class="stats-grid">
                <div class="stat-item">
                    <p>Satisfaits : <strong><?= $data['avisStats']['satisfaits'] ?></strong></p>
                </div>
                <div class="stat-item">
                    <p>Insatisfaits : <strong><?= $data['avisStats']['insatisfaits'] ?></strong></p>
                </div>
                <div class="stat-item">
                    <p>Avis manquants : <strong><?= $data['avisStats']['avisManquants'] ?></strong></p>
                </div>
            </div>
            <canvas id="avisChart" width="400" height="200"></canvas>
        </div>

        <!-- Autres statistiques -->
        <div class="stats-card">
            <h2>Autres statistiques</h2>
            <div class="stats-grid">
                <div class="stat-item">
                    <p>Temps moyen de consultation : <strong><?= round($data['tempsMoyen']) ?> minutes</strong></p>
                </div>
            </div>
        </div>

        <!-- Médecins les plus sollicités -->
        <div class="stats-card">
            <h2>Médecins les plus sollicités</h2>
            <table>
                <thead>
                    <tr>
                        <th>Médecin</th>
                        <th>Nombre de RDV</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data['medecinsTop'] as $medecin): ?>
                        <tr>
                            <td><?= htmlspecialchars($medecin['nom_complet'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= $medecin['nombre_rdv'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <canvas id="medecinsChart" width="400" height="300"></canvas>
        </div>
    </div>

    <!-- Scripts pour les graphiques -->
    <script>
        // Graphique des avis
        const avisCtx = document.getElementById('avisChart').getContext('2d');
        const avisChart = new Chart(avisCtx, {
            type: 'pie',
            data: {
                labels: [
                    'Satisfaits (<?= $data['avisStats']['satisfaits'] ?>)',
                    'Insatisfaits (<?= $data['avisStats']['insatisfaits'] ?>)',
                    'Avis manquants (<?= $data['avisStats']['avisManquants'] ?>)'
                ],
                datasets: [{
                    data: [
                        <?= $data['avisStats']['satisfaits'] ?>,
                        <?= $data['avisStats']['insatisfaits'] ?>,
                        <?= $data['avisStats']['avisManquants'] ?>
                    ],
                    backgroundColor: [
                        'rgba(75, 192, 192, 0.7)',  // Vert pour satisfaits
                        'rgba(255, 99, 132, 0.7)', // Rouge pour insatisfaits
                        'rgba(201, 203, 207, 0.7)' // Gris pour manquants
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'top' },
                    title: {
                        display: true,
                        text: 'Répartition des avis patients (' +
                              <?= $data['avisStats']['satisfaits'] + $data['avisStats']['insatisfaits'] + $data['avisStats']['avisManquants'] ?> +
                              ' total)'
                    }
                }
            }
        });

        // Graphique des médecins
        const medecinsCtx = document.getElementById('medecinsChart').getContext('2d');
        const medecinsChart = new Chart(medecinsCtx, {
            type: 'bar',
            data: {
                labels: [
                    <?php foreach ($data['medecinsTop'] as $medecin): ?>
                        '<?= htmlspecialchars($medecin['nom_complet'], ENT_QUOTES, 'UTF-8') ?>',
                    <?php endforeach; ?>
                ],
                datasets: [{
                    label: 'Nombre de RDV',
                    data: [
                        <?php foreach ($data['medecinsTop'] as $medecin): ?>
                            <?= $medecin['nombre_rdv'] ?>,
                        <?php endforeach; ?>
                    ],
                    backgroundColor: 'rgba(54, 162, 235, 0.7)'
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'top' },
                    title: { display: true, text: 'Médecins les plus sollicités' }
                },
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
    </script>
</body>
</html>
