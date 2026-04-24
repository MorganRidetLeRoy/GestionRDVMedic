<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion de Rendez-vous - Cabinet Médical</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; /* Définit la police d'écriture globale */
            margin: 0; /* Supprime les marges par défaut du navigateur */
            padding: 0; /* Supprime les marges par défaut du navigateur */
            background-color: #f8f9fa; /* Couleur de fond gris très clair */
            color: #333; 
        }
        
        .container { /* Centre le contenu avec une largeur max */
            width: 95%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        header { 
            text-align: center;
            margin-bottom: 30px;
            padding: 15px 0;
            background-color: #e9ecef; /* Style du bandeau de titre */
            border-radius: 8px; 
        }
        
        h1 {
            color: #2c3e50;
            margin: 0;
        }
        
        /* Système de grille : crée des colonnes automatiques de minimum 280px */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px; /* Espace entre les cartes de statistiques */
            margin-bottom: 30px;
            
        }
        .stat-card {
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); /* Ajoute une ombre légère */
            text-align: center;
        }
        
        .stat-card h3 {
            margin-top: 0;
            color: #3498db;
            font-size: 16px;
        }
        
        .stat-value { /* Met en valeur le chiffre */
            font-size: 24px;
            font-weight: bold;
            color: #2c3e50;
            margin: 10px 0;
        }
        
        .charts {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .chart-container {
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        
        .comments {
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }
        
        .comments h3 {
            color: #3498db;
            margin-top: 0;
        }
        
        .comment-keywords {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 15px;
        }
        
        /* Style des bulles de mots-clés */
        .keyword {
            background-color: #e9f7fe;
            color: #3498db;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .keyword-count {
            background-color: #3498db;
            color: white;
            border-radius: 10px;
            padding: 2px 6px;
            font-size: 12px;
        }
        
        /* Positionnement du bouton de connexion en haut à droite */
        .login-btn {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 10px 20px;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 5px; /* Assure qu'il reste au-dessus de tout */
            cursor: pointer;
            font-size: 16px;
            z-index: 1000; /* Assure qu'il reste au-dessus de tout */
        }
        
        .login-btn:hover {
            background-color: #2980b9;
        }
        
        /* Style de la fenêtre surgissante (Modale) */
        .login-modal {
            display: none; /* Cachée par défaut */
            position: fixed; /* Centre la fenêtre */
            top: 50%; /* Centre la fenêtre */
            left: 50%; /* Centre la fenêtre */
            transform: translate(-50%, -50%); /* Ajustement parfait du centrage */
            background-color: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            z-index: 1001;
            width: 350px;
        }
        
        /* Formate les champs de saisie */
        .login-modal input, .login-modal select, .login-modal button {
            display: block;
            width: 100%;
            margin-bottom: 15px;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .login-modal button {
            background-color: #2ecc71;
            color: white;
            border: none;
            cursor: pointer;
            padding: 12px;
        }
        .login-modal button:hover {
            background-color: #27ae60;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Gestion des Rendez-vous - Cabinet Médical</h1>
        </header>

        <!-- Cartes de statistiques clés -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Rendez-vous du mois</h3>
                <div class="stat-value"><?php echo 180; ?></div>
                <p>Total des rendez-vous pris</p>
            </div>
            <div class="stat-card">
                <h3>Patients consultés</h3>
                <div class="stat-value"><?php echo 150; ?></div>
                <p>Nombre de consultations réalisées</p>
            </div>
            <div class="stat-card">
                <h3>Rendez-vous annulés</h3>
                <div class="stat-value"><?php echo 12; ?></div>
                <p>Nombre d'annulations ce mois</p>
            </div>
            <div class="stat-card">
                <h3>Rendez-vous en retard</h3>
                <div class="stat-value"><?php echo 25; ?></div>
                <p>Patients arrivés en retard</p>
            </div>
            <div class="stat-card">
                <h3>Durée moyenne des consultations</h3>
                <div class="stat-value"><?php echo "22 min"; ?></div>
                <p>Temps moyen par consultation</p>
            </div>
            <div class="stat-card">
                <h3>Note moyenne des avis</h3>
                <div class="stat-value"><?php echo "4.2/5"; ?></div>
                <p>Satisfaction des patients</p>
            </div>
            <div class="stat-card">
                <h3>Délai moyen de prise de RDV</h3>
                <div class="stat-value"><?php echo "3 jours"; ?></div>
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

        <!-- Commentaires des patients avec quantification -->
        <div class="comments">
            <h3>Mots-clés des retours patients (ce mois)</h3>
            <div class="comment-keywords">
                <span class="keyword">
                    Rendez-vous rapide et efficace
                    <span class="keyword-count">42</span>
                </span>
                <span class="keyword">
                    Médecin à l'écoute
                    <span class="keyword-count">38</span>
                </span>
                <span class="keyword">
                    Attente un peu longue
                    <span class="keyword-count">25</span>
                </span>
                <span class="keyword">
                    Accueil chaleureux
                    <span class="keyword-count">30</span>
                </span>
                <span class="keyword">
                    Rendez-vous lent
                    <span class="keyword-count">12</span>
                </span>
                <span class="keyword">
                    Manque de ponctualité
                    <span class="keyword-count">18</span>
                </span>
                <span class="keyword">
                    Explications claires
                    <span class="keyword-count">28</span>
                </span>
            </div>
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
            <input type="text" name="username" placeholder="Nom d'utilisateur" required>
            <input type="password" name="password" placeholder="Mot de passe" required>
            <button type="submit">Se connecter</button>
        </form>
    </div>

    <!-- Script pour le graphique -->
    <script>
        // Données pour le graphique "Statistiques des rendez-vous"
        const labels = [
            'Rendez-vous pris',
            'Patients consultés',
            'Rendez-vous annulés',
            'Rendez-vous en retard',
            'Durée moyenne (min)'
        ];
        
        // Définition des données et des couleurs
        const data = {
            labels: labels,
            datasets: [{
                label: 'Rendez-vous pris', // Légende unique et claire
                data: [180, 150, 12, 25, 22], // Valeurs numériques pour les barres
                backgroundColor: [ // Couleurs de fond avec transparence
                    'rgba(54, 162, 235, 0.5)',    // Rendez-vous pris
                    'rgba(75, 192, 192, 0.5)',   // Patients consultés
                    'rgba(255, 99, 132, 0.5)',   // Rendez-vous annulés
                    'rgba(255, 206, 86, 0.5)',   // Rendez-vous en retard
                    'rgba(153, 102, 255, 0.5)'  // Durée moyenne
                ],
                borderColor: [
                    'rgba(54, 162, 235, 1)',
                    'rgba(75, 192, 192, 1)',
                    'rgba(255, 99, 132, 1)',
                    'rgba(255, 206, 86, 1)',
                    'rgba(153, 102, 255, 1)'
                ],
                borderWidth: 1 // Épaisseur de la bordure des barres
            }]
        };
        
        // Configuration globale du graphique
        const config = {
            type: 'bar', // Définit le type : diagramme en barres
            data: data,
            options: {
                responsive: true, // S'adapte à la taille de l'écran
                plugins: {
                    legend: {
                        display: false, // Cache la légende automatique
                    },
                    title: {
                        display: true,
                        text: 'Statistiques détaillées des rendez-vous',
                        font: {
                            size: 16
                        }
                    },
                    tooltip: { // Personnalise les bulles d'info au survol
                        callbacks: {
                            label: function(context) {
                                let label = context.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.parsed.y !== null) {
                                    label += context.parsed.y;
                                    // Ajouter l'unité si nécessaire
                                    if (context.dataIndex === 4) {
                                        label += ' min'; // Ajoute "min" pour la durée
                                    }
                                }
                                return label;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Nombre / Durée'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Catégories'
                        }
                    }
                }
            }
        };
        
        // Initialisation : on lie l'objet Chart au canvas HTML via son ID
        const ctx = document.getElementById('rdvStatsChart').getContext('2d');
        new Chart(ctx, config);

        // Gestion de la fermeture de la modale : si on clique n'importe où sur la fenêtre
        window.onclick = function(event) {
            const modal = document.getElementById('loginModal');
            // Si la cible du clic est le fond de la modale (et non le formulaire intérieur)
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    </script>
</body>
</html>
