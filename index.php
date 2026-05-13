<?php
// --- FICHIER : index.php ---
// Ce fichier est la page principale d'un tableau de bord pour la gestion des rendez-vous d'un cabinet médical.
// Il affiche des statistiques, des graphiques et une interface de connexion.

// Inclusion du fichier de connexion à la base de données
require_once './database/connexion_database.php';

// --- RÉCUPÉRATION DES STATISTIQUES DEPUIS LA BASE DE DONNÉES ---
try {
    // Appel de la fonction getConnexion() pour obtenir une instance PDO
    $pdo = getConnexion();

    // Requête SQL pour compter le nombre total de rendez-vous dans la table "rendez_vous"
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM rendez_vous");
    $totalRdv = $stmt->fetch()['total']; // Récupération du résultat sous forme de tableau associatif

    // --- DONNÉES STATIQUES ---
    $patientsConsultes = 150;    // Nombre de patients consultés ce mois
    $rdvAnnules = 12;           // Nombre de rendez-vous annulés
    $rdvRetard = 25;             // Nombre de rendez-vous en retard
    $dureeMoyenne = 22;         // Durée moyenne des consultations (en minutes)
    $noteMoyenne = 4.2;         // Note moyenne des avis patients (sur 5)
    $delaiMoyen = 3;            // Délai moyen pour obtenir un rendez-vous (en jours)

} catch (PDOException $e) {
    // En cas d'erreur avec la base de données, on affiche un message et on arrête l'exécution du script.
    die("Erreur : " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Gestion de Rendez-vous - Cabinet Médical</title>

    <!-- Intégration de la bibliothèque Chart.js pour les graphiques -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Section CSS : Styles pour la page -->
    <style>
        /* --- STYLES GLOBAUX ---
           - Police par défaut : Segoe UI (ou alternatives)
           - Couleur de fond : gris très clair (#f8f9fa)
           - Couleur de texte : gris foncé (#333) */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f8f9fa;
            color: #333;
        }

        /* --- CONTAINER PRINCIPAL ---
           - Largeur maximale : 1200px
           - Centré horizontalement avec un padding de 20px */
        .container {
            width: 95%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        /* --- EN-TÊTE ---
           - Texte centré, fond gris clair, bordure arrondie */
        header {
            text-align: center;
            margin-bottom: 30px;
            padding: 15px 0;
            background-color: #e9ecef;
            border-radius: 8px;
        }

        /* Titre principal : couleur bleu foncé (#2c3e50) */
        h1 {
            color: #2c3e50;
            margin: 0;
        }

        /* --- GRILLE DES STATISTIQUES ---
           - Affichage en grille responsive (1 à 4 colonnes selon la taille de l'écran)
           - Espacement de 20px entre les cartes */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        /* --- CARTES DE STATISTIQUES ---
           - Fond blanc, ombre légère, bordure arrondie
           - Texte centré */
        .stat-card {
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        /* Titre des cartes : bleu (#3498db), taille 16px */
        .stat-card h3 {
            margin-top: 0;
            color: #3498db;
            font-size: 16px;
        }

        /* Valeur de la statistique : taille 24px, gras, couleur bleu foncé */
        .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: #2c3e50;
            margin: 10px 0;
        }

        /* --- SECTION DES GRAPHIQUES ---
           - Grille responsive (1 ou 2 colonnes selon la taille de l'écran)
           - Espacement de 20px entre les graphiques */
        .charts {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        /* Conteneur des graphiques : fond blanc, ombre légère, bordure arrondie */
        .chart-container {
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        /* --- SECTION DES COMMENTAIRES ---
           - Fond blanc, ombre légère, bordure arrondie */
        .comments {
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        /* Titre des commentaires : bleu (#3498db) */
        .comments h3 {
            color: #3498db;
            margin-top: 0;
        }

        /* --- MOTS-CLÉS DES COMMENTAIRES ---
           - Affichage en flexbox pour un enroulement automatique
           - Espacement de 10px entre les mots-clés */
        .comment-keywords {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 15px;
        }

        /* Style des mots-clés :
           - Fond bleu très clair (#e9f7fe)
           - Texte bleu (#3498db)
           - Bordure arrondie (20px) */
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

        /* Compteur des mots-clés :
           - Fond bleu (#3498db)
           - Texte blanc
           - Bordure arrondie (10px) */
        .keyword-count {
            background-color: #3498db;
            color: white;
            border-radius: 10px;
            padding: 2px 6px;
            font-size: 12px;
        }

        /* --- BOUTON DE CONNEXION ---
           - Position fixe en haut à droite
           - Fond bleu (#3498db), texte blanc
           - Bordure arrondie (5px) */
        .login-btn {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 10px 20px;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            z-index: 1000;
        }

        /* Effet de survol pour le bouton */
        .login-btn:hover {
            background-color: #2980b9;
        }

        /* --- MODALE DE CONNEXION ---
           - Masquée par défaut (display: none)
           - Position fixe au centre de l'écran
           - Fond blanc, ombre, bordure arrondie */
        .login-modal {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            z-index: 1001;
            width: 350px;
        }

        /* Style des champs de formulaire dans la modale :
           - Largeur à 100%, marge en bas de 15px
           - Bordure grise (#ddd), bordure arrondie (5px) */
        .login-modal input,
        .login-modal select,
        .login-modal button {
            display: block;
            width: 100%;
            margin-bottom: 15px;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        /* Style du bouton de soumission :
           - Fond vert (#2ecc71), texte blanc */
        .login-modal button {
            background-color: #2ecc71;
            color: white;
            border: none;
            cursor: pointer;
            padding: 12px;
        }

        /* Effet de survol pour le bouton de soumission */
        .login-modal button:hover {
            background-color: #27ae60;
        }
    </style>
</head>

<body>
    <!-- CONTAINER PRINCIPAL : Contient tout le contenu de la page -->
    <div class="container">
        <!-- EN-TÊTE : Titre de la page -->
        <header>
            <h1>Gestion des Rendez-vous - Cabinet Médical</h1>
        </header>

        <!-- --- SECTION DES STATISTIQUES (DYNAMIQUES) ---
             Chaque carte affiche une statistique clé avec :
             - Un titre (h3)
             - Une valeur (stat-value)
             - Une description (p) -->
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

        <!-- --- SECTION DES GRAPHIQUES ---
             Contient un graphique en barres (Chart.js) pour visualiser les statistiques. -->
        <div class="charts">
            <div class="chart-container">
                <h3>Statistiques des rendez-vous (mois en cours)</h3>
                <!-- Canvas pour le graphique Chart.js -->
                <canvas id="rdvStatsChart"></canvas>
            </div>
        </div>

        <!-- --- SECTION DES COMMENTAIRES DES PATIENTS ---
             Affiche une liste de mots-clés extraits des retours patients, avec un compteur pour chaque. -->
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

        <!-- --- BOUTON DE CONNEXION ---
             Ouvre la modale de connexion au clic. -->
        <button class="login-btn" onclick="document.getElementById('loginModal').style.display='block'">Se connecter</button>

        <!-- --- MODALE DE CONNEXION ---
             Formulaire de connexion avec :
             - Sélection du rôle (secrétaire, médecin, administrateur)
             - Champs pour le nom d'utilisateur et le mot de passe
             - Bouton de soumission -->
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

    <!-- --- SCRIPT POUR LE GRAPHIQUE ET LA MODALE ---
         - Initialisation du graphique Chart.js avec les données PHP.
         - Gestion de la fermeture de la modale au clic en dehors. -->
    <script>
        // Récupération du contexte du canvas pour le graphique
        const ctx = document.getElementById('rdvStatsChart').getContext('2d');

        // Données du graphique :
        // - labels : Noms des catégories (ex: "Rendez-vous", "Patients consultés", etc.)
        // - datasets : Contient les valeurs à afficher (récupérées depuis PHP)
        const data = {
            labels: [
                'Rendez-vous',
                'Patients consultés',
                'Annulations',
                'Retards',
                'Durée moyenne',
                'Note moyenne',
                'Délai moyen'
            ],
            datasets: [{
                label: 'Statistiques',
                // Données dynamiques injectées depuis PHP
                data: [
                    <?= $totalRdv ?>,
                    <?= $patientsConsultes ?>,
                    <?= $rdvAnnules ?>,
                    <?= $rdvRetard ?>,
                    <?= $dureeMoyenne ?>,
                    <?= $noteMoyenne ?>,
                    <?= $delaiMoyen ?>
                ],
                // Couleurs de fond pour chaque barre du graphique
                backgroundColor: [
                    'rgba(255, 99, 132, 1)',   // Rouge
                    'rgba(54, 162, 235, 1)',   // Bleu
                    'rgba(255, 206, 86, 1)',  // Jaune
                    'rgba(75, 192, 192, 1)',  // Vert
                    'rgba(153, 102, 255, 1)', // Violet
                    'rgba(255, 159, 64, 1)',  // Orange
                    'rgba(201, 203, 207, 1)'  // Gris
                ],
                borderWidth: 1
            }]
        };

        // Configuration du graphique :
        // - type : "bar" (graphique en barres)
        // - options : Personnalisation de l'affichage (légende, titre, axes, etc.)
        const config = {
            type: 'bar',
            data: data,
            options: {
                responsive: true, // Le graphique s'adapte à la taille de l'écran
                plugins: {
                    legend: { display: false }, // Masque la légende
                    title: {
                        display: true,
                        text: 'Statistiques détaillées des rendez-vous',
                        font: { size: 16 }
                    },
                    tooltip: {
                        // Personnalisation des infobulles (tooltips)
                        callbacks: {
                            label: function(context) {
                                let label = context.label || '';
                                if (label) label += ': ';
                                if (context.parsed.y !== null) {
                                    label += context.parsed.y;
                                    // Ajoute "min" pour la durée moyenne (index 4)
                                    if (context.dataIndex === 4) label += ' min';
                                }
                                return label;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true, // L'axe Y commence à 0
                        title: { display: true, text: 'Nombre / Durée' }
                    },
                    x: {
                        title: { display: true, text: 'Catégories' }
                    }
                }
            }
        };

        // Création du graphique avec les données et la configuration
        new Chart(ctx, config);

        // --- GESTION DE LA MODALE DE CONNEXION ---
        // Ferme la modale si l'utilisateur clique en dehors de celle-ci
        window.onclick = function(event) {
            const modal = document.getElementById('loginModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        };
    </script>
</body>
</html>
