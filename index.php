<?php
// index.php
require_once './database/connexion_database.php';

// Récupération des statistiques (exemple)
try {
    $pdo = getConnexion();
    // Exemple : Récupérer le nombre total de rendez-vous
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM rendez_vous");
    $totalRdv = $stmt->fetch()['total'];
    // Remplacez les valeurs statiques par des requêtes SQL réelles
    $patientsConsultes = 95;
    $rdvAnnules = 15;
    $rdvRetard = 8;
    $dureeMoyenne = 25;
    $noteMoyenne = 4.2;
    $delaiMoyen = 3;
} catch (PDOException $e) {
    die("Erreur : " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Gestion de Rendez-vous - Cabinet Médical</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* [Vos styles CSS ici, identiques à votre version] */
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 0; background-color: #f8f9fa; color: #333; }
        .container { width: 95%; max-width: 1200px; margin: 0 auto; padding: 20px; }
        header { text-align: center; margin-bottom: 30px; padding: 15px 0; background-color: #e9ecef; border-radius: 8px; }
        h1 { color: #2c3e50; margin: 0; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background-color: white; border-radius: 8px; padding: 20px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); text-align: center; }
        .stat-card h3 { margin-top: 0; color: #3498db; font-size: 16px; }
        .stat-value { font-size: 24px; font-weight: bold; color: #2c3e50; margin: 10px 0; }
        .charts { display: grid; grid-template-columns: repeat(auto-fit, minmax(500px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .chart-container { background-color: white; border-radius: 8px; padding: 20px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); }
        .comments { background-color: white; border-radius: 8px; padding: 20px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); margin-bottom: 30px; }
        .comments h3 { color: #3498db; margin-top: 0; }
        .comment-keywords { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 15px; }
        .keyword { background-color: #e9f7fe; color: #3498db; padding: 5px 10px; border-radius: 20px; font-size: 14px; display: flex; align-items: center; gap: 5px; }
        .keyword-count { background-color: #3498db; color: white; border-radius: 10px; padding: 2px 6px; font-size: 12px; }
        .login-btn { position: fixed; top: 20px; right: 20px; padding: 10px 20px; background-color: #3498db; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; z-index: 1000; }
        .login-btn:hover { background-color: #2980b9; }
        .login-modal { display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background-color: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2); z-index: 1001; width: 350px; }
        .login-modal input, .login-modal select, .login-modal button { display: block; width: 100%; margin-bottom: 15px; padding: 10px; border: 1px solid #ddd; border-radius: 5px; }
        .login-modal button { background-color: #2ecc71; color: white; border: none; cursor: pointer; padding: 12px; }
        .login-modal button:hover { background-color: #27ae60; }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Gestion des Rendez-vous - Cabinet Médical</h1>
        </header>

        <!-- Cartes de statistiques clés (dynamiques) -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Rendez-vous du mois</h3>
                <div class="stat-value"><?= $totalRdv ?></div>
                <p>Total des rendez-vous pris</p>
            </div>
            <div class="stat-card">
                <h3>Patients consultés</h3>
                <div class="stat-value"><?= $patientsConsultes ?></div>
                <p>Nombre de consultations réalisées</p>
            </div>
            <div class="stat-card">
                <h3>Rendez-vous annulés</h3>
                <div class="stat-value"><?= $rdvAnnules ?></div>
                <p>Nombre d'annulations ce mois</p>
            </div>
            <div class="stat-card">
                <h3>Rendez-vous en retard</h3>
                <div class="stat-value"><?= $rdvRetard ?></div>
                <p>Patients arrivés en retard</p>
            </div>
            <div class="stat-card">
                <h3>Durée moyenne des consultations</h3>
                <div class="stat-value"><?= $dureeMoyenne ?> min</div>
                <p>Temps moyen par consultation</p>
            </div>
            <div class="stat-card">
                <h3>Note moyenne des avis</h3>
                <div class="stat-value"><?= $noteMoyenne ?>/5</div>
                <p>Satisfaction des patients</p>
            </div>
            <div class="stat-card">
                <h3>Délai moyen de prise de RDV</h3>
                <div class="stat-value"><?= $delaiMoyen ?> jours</div>
                <p>Temps entre demande et consultation</p>
            </div>
        </div>

        <!-- Graphique des rendez-vous -->
        <div class="charts">
            <div class="chart-container">
                <h3>Statistiques des rendez-vous (mois en cours)</h3>
                <canvas id="rdvStatsChart"></canvas>
            </div>
        </div>

        <!-- Commentaires des patients -->
        <div class="comments">
            <h3>Mots-clés des retours patients (ce mois)</h3>
            <div class="comment-keywords">
                <span class="keyword">Rendez-vous rapide et efficace <span class="keyword-count">42</span></span>
                <span class="keyword">Médecin à l'écoute <span class="keyword-count">38</span></span>
                <span class="keyword">Attente un peu longue <span class="keyword-count">25</span></span>
                <span class="keyword">Accueil chaleureux <span class="keyword-count">30</span></span>
                <span class="keyword">Rendez-vous lent <span class="keyword-count">12</span></span>
                <span class="keyword">Manque de ponctualité <span class="keyword-count">18</span></span>
                <span class="keyword">Explications claires <span class="keyword-count">28</span></span>
            </div>
        </div>

        <!-- Bouton de connexion -->
        <button class="login-btn" onclick="document.getElementById('loginModal').style.display='block'">Se connecter</button>

        <!-- Modal de connexion -->
        <div id="loginModal" class="login-modal">
            <h2>Connexion</h2>
            <form action="login.php" method="post">
                <select name="role" required>
                    <option value="">Sélectionnez votre rôle</option>
                    <option value="secretaire">Secrétaire</option>
                    <option value="medecin">Médecin</option>
                    <option value="administrateur">Administrateur</option>
                </select>
                <input name="username" type="text" placeholder="Nom d'utilisateur" required>
                <input name="password" type="password" placeholder="Mot de passe" required>
                <button type="submit">Se connecter</button>
            </form>
        </div>
    </div>

    <!-- Script pour le graphique et la modale -->
    <script>
        const ctx = document.getElementById('rdvStatsChart').getContext('2d');
        const data = {
            labels: ['Rendez-vous', 'Patients consultés', 'Annulations', 'Retards', 'Durée moyenne', 'Note moyenne', 'Délai moyen'],
            datasets: [{
                label: 'Statistiques',
                data: [<?= $totalRdv ?>, <?= $patientsConsultes ?>, <?= $rdvAnnules ?>, <?= $rdvRetard ?>, <?= $dureeMoyenne ?>, <?= $noteMoyenne ?>, <?= $delaiMoyen ?>],
                backgroundColor: [
                    'rgba(255, 99, 132, 1)',
                    'rgba(54, 162, 235, 1)',
                    'rgba(255, 206, 86, 1)',
                    'rgba(75, 192, 192, 1)',
                    'rgba(153, 102, 255, 1)',
                    'rgba(255, 159, 64, 1)',
                    'rgba(201, 203, 207, 1)'
                ],
                borderWidth: 1
            }]
        };
        const config = {
            type: 'bar',
            data: data,
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false },
                    title: { display: true, text: 'Statistiques détaillées des rendez-vous', font: { size: 16 } },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.label || '';
                                if (label) label += ': ';
                                if (context.parsed.y !== null) {
                                    label += context.parsed.y;
                                    if (context.dataIndex === 3) label += ' min';
                                }
                                return label;
                            }
                        }
                    }
                },
                scales: {
                    y: { beginAtZero: true, title: { display: true, text: 'Nombre / Durée' } },
                    x: { title: { display: true, text: 'Catégories' } }
                }
            }
        };
        new Chart(ctx, config);

        // Fermeture de la modale
        window.onclick = function(event) {
            const modal = document.getElementById('loginModal');
            if (event.target == modal) modal.style.display = 'none';
        };
    </script>
</body>
</html>
