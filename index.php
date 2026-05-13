<?php
// =============================================
// FICHIER: index.php
// RÔLE: Page principale du tableau de bord pour un cabinet médical.
// AFFICHE: Statistiques, graphiques, mots-clés des patients, et interface de connexion.
// =============================================

// --- CONFIGURATION DES ERREURS PHP ---
// ini_set() : Fonction PHP qui modifie la configuration de PHP à l'exécution.
// 'display_errors' : Directive qui active/désactive l'affichage des erreurs à l'écran.
// 1 : Active l'option (équivalent à true).
ini_set('display_errors', 1);

// 'display_startup_errors' : Directive qui active/désactive l'affichage des erreurs de démarrage (ex: erreurs dans php.ini).
ini_set('display_startup_errors', 1);

// error_reporting() : Définit le niveau de rapport d'erreur.
// E_ALL : Constante qui représente TOUS les types d'erreurs (syntaxe, avertissements, erreurs fatales, etc.).
error_reporting(E_ALL);

// --- DÉMARRAGE DE LA SESSION ---
// session_start() : Démarre ou reprend une session existante.
// Permet d'utiliser la superglobale $_SESSION pour stocker des données entre les requêtes HTTP (ex: utilisateur connecté).
session_start();

// --- INCLUSION DES FICHIERS NÉCESSAIRES ---
// require_once : Inclut et évalue un fichier PHP UNE SEULE FOIS (évite les inclusions multiples).
// __DIR__ : Constante magique qui retourne le chemin absolu du dossier contenant ce fichier.
// '/../database/connexion_database.php' : Chemin relatif pour remonter d'un dossier et accéder à connexion_database.php.
// Ce fichier contient la fonction getConnexion() qui retourne un objet PDO pour interagir avec la base de données.
require_once __DIR__ . '/../database/connexion_database.php';

// =============================================
// RÉCUPÉRATION DES STATISTIQUES DEPUIS LA BASE DE DONNÉES
// =============================================
try {
    // getConnexion() : Fonction définie dans connexion_database.php qui retourne une instance PDO.
    // PDO : PHP Data Objects, une extension pour interagir avec les bases de données de manière sécurisée.
    $pdo = getConnexion();

    // --- REQUÊTE SQL POUR COMPTER LE NOMBRE TOTAL DE RENDEZ-VOUS ---
    // query() : Méthode de PDO qui exécute une requête SQL et retourne un objet PDOStatement.
    // "SELECT COUNT(*) as total FROM rendez_vous" : Requête SQL qui compte le nombre de lignes dans la table "rendez_vous".
    // COUNT(*) : Fonction SQL qui compte le nombre de lignes.
    // as total : Donne un alias "total" à la colonne résultante.
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM rendez_vous");

    // fetch() : Méthode de PDOStatement qui récupère la ligne suivante d'un jeu de résultats.
    // ['total'] : Accède à la valeur de la colonne "total" dans le tableau associatif retourné par fetch().
    // Ici, on récupère le nombre total de rendez-vous.
    $totalRdv = $stmt->fetch()['total'];

    // =============================================
    // DONNÉES STATIQUES (À REMPLACER PAR DES REQUÊTES SQL DYNAMIQUES)
    // =============================================
    // Ces valeurs sont codées en dur (hardcoded) et devraient idéalement provenir de requêtes SQL.
    // Le symbole = est l'opérateur d'affectation en PHP.
    $patientsConsultes = 150;    // Nombre de patients consultés ce mois
    $rdvAnnules = 12;           // Nombre de rendez-vous annulés
    $rdvRetard = 25;            // Nombre de rendez-vous en retard
    $dureeMoyenne = 22;         // Durée moyenne des consultations (en minutes)
    $noteMoyenne = 4.2;        // Note moyenne des avis patients (sur 5)
    $delaiMoyen = 3;            // Délai moyen pour obtenir un rendez-vous (en jours)

} catch (PDOException $e) {
    // --- GESTION DES ERREURS ---
    // catch : Bloc qui capture les exceptions levées dans le bloc try.
    // PDOException : Classe d'exception spécifique à PDO (erreur de base de données).
    // $e : Variable qui contient l'objet exception avec les détails de l'erreur.
    // die() : Fonction qui affiche un message et arrête l'exécution du script.
    // $e->getMessage() : Méthode qui retourne le message d'erreur de l'exception.
    die("Erreur : " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <!-- =============================================
         MÉTADONNÉES DE LA PAGE
         ============================================= -->
    <!-- meta charset : Définit l'encodage des caractères de la page. UTF-8 permet d'afficher tous les caractères (accents, symboles, etc.). -->
    <meta charset="UTF-8"/>
    <!-- meta viewport : Rend la page responsive en adaptant la largeur à l'appareil et en désactivant le zoom initial. -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Gestion de Rendez-vous - Cabinet Médical</title> <!-- Titre de la page affiché dans l'onglet du navigateur -->

    <!-- =============================================
         INTÉGRATION DE LA BIBLIOTHÈQUE CHART.JS
         ============================================= -->
    <!-- script src : Charge un script JavaScript externe depuis une URL.
         Chart.js est une bibliothèque pour créer des graphiques interactifs. -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- =============================================
         STYLES CSS
         ============================================= -->
    <style>
        /* =============================================
           STYLES GLOBAUX
           ============================================= */
        /* Sélecteur body : Applique les styles à toute la page. */
        body {
            /* font-family : Définit la police de caractères. 'Segoe UI' est la police principale, avec des alternatives. */
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0; /* Supprime les marges par défaut du navigateur. */
            padding: 0; /* Supprime les espacements internes par défaut. */
            background-color: #f8f9fa; /* Couleur de fond claire. */
            color: #333; /* Couleur de texte gris foncé. */
        }

        /* =============================================
           CONTAINER PRINCIPAL
           ============================================= */
        /* .container : Classe CSS pour le conteneur principal. */
        .container {
            width: 95%; /* Largeur relative à 95% de la largeur de l'écran. */
            max-width: 1200px; /* Largeur maximale de 1200px. */
            margin: 0 auto; /* Centre horizontalement (margin: 0 auto = margin-top: 0, margin-bottom: 0, margin-left: auto, margin-right: auto). */
            padding: 20px; /* Espacement interne de 20px. */
        }

        /* =============================================
           EN-TÊTE
           ============================================= */
        /* header : Balise HTML5 pour l'en-tête de la page. */
        header {
            text-align: center; /* Centre le texte horizontalement. */
            margin-bottom: 30px; /* Espacement en bas de 30px. */
            padding: 15px 0; /* Espacement interne vertical (haut et bas) de 15px. */
            background-color: #e9ecef; /* Fond gris clair. */
            border-radius: 8px; /* Coins arrondis de 8px. */
        }

        /* h1 : Balise de titre de niveau 1. */
        h1 {
            color: #2c3e50; /* Couleur bleu foncé. */
            margin: 0; /* Supprime les marges par défaut. */
        }

        /* =============================================
           GRILLE DES STATISTIQUES
           ============================================= */
        /* .stats-grid : Classe pour la grille des statistiques. */
        .stats-grid {
            /* display: grid : Utilise CSS Grid pour la mise en page. */
            display: grid;
            /* grid-template-columns : Définit les colonnes de la grille.
               repeat(auto-fit, minmax(280px, 1fr)) :
               - auto-fit : Crée autant de colonnes que possible.
               - minmax(280px, 1fr) : Chaque colonne a une largeur minimale de 280px et maximale de 1 fraction (1fr) de l'espace disponible. */
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px; /* Espacement de 20px entre les éléments de la grille. */
            margin-bottom: 30px; /* Espacement en bas de 30px. */
        }

        /* =============================================
           CARTES DE STATISTIQUES
           ============================================= */
        /* .stat-card : Classe pour chaque carte de statistique. */
        .stat-card {
            background-color: white; /* Fond blanc. */
            border-radius: 8px; /* Coins arrondis. */
            padding: 20px; /* Espacement interne. */
            /* box-shadow : Ombre portée. 0 2px 8px rgba(0, 0, 0, 0.1) = décalage horizontal 0, vertical 2px, flou 8px, couleur noire avec opacité 0.1. */
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            text-align: center; /* Centre le texte. */
        }

        /* .stat-card h3 : Sélecteur pour les titres des cartes. */
        .stat-card h3 {
            margin-top: 0; /* Supprime la marge supérieure. */
            color: #3498db; /* Couleur bleu clair. */
            font-size: 16px; /* Taille de la police. */
        }

        /* .stat-value : Classe pour les valeurs des statistiques. */
        .stat-value {
            font-size: 24px; /* Taille de la police. */
            font-weight: bold; /* Texte en gras. */
            color: #2c3e50; /* Couleur bleu foncé. */
            margin: 10px 0; /* Espacement vertical de 10px. */
        }

        /* =============================================
           SECTION DES GRAPHIQUES
           ============================================= */
        /* .charts : Classe pour la section des graphiques. */
        .charts {
            display: grid; /* Utilise CSS Grid. */
            /* repeat(auto-fit, minmax(500px, 1fr)) : Grille responsive avec des colonnes de 500px minimum. */
            grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
            gap: 20px; /* Espacement entre les éléments. */
            margin-bottom: 30px; /* Espacement en bas. */
        }

        /* .chart-container : Classe pour le conteneur du graphique. */
        .chart-container {
            background-color: white; /* Fond blanc. */
            border-radius: 8px; /* Coins arrondis. */
            padding: 20px; /* Espacement interne. */
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); /* Ombre légère. */
        }

        /* =============================================
           SECTION DES COMMENTAIRES
           ============================================= */
        /* .comments : Classe pour la section des commentaires. */
        .comments {
            background-color: white; /* Fond blanc. */
            border-radius: 8px; /* Coins arrondis. */
            padding: 20px; /* Espacement interne. */
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); /* Ombre légère. */
            margin-bottom: 30px; /* Espacement en bas. */
        }

        /* .comments h3 : Sélecteur pour le titre de la section des commentaires. */
        .comments h3 {
            color: #3498db; /* Couleur bleu clair. */
            margin-top: 0; /* Supprime la marge supérieure. */
        }

        /* =============================================
           MOTS-CLÉS DES COMMENTAIRES
           ============================================= */
        /* .comment-keywords : Classe pour le conteneur des mots-clés. */
        .comment-keywords {
            /* display: flex : Utilise Flexbox pour la mise en page. */
            display: flex;
            /* flex-wrap: wrap : Permet aux éléments de s'enrouler sur plusieurs lignes. */
            flex-wrap: wrap;
            gap: 10px; /* Espacement de 10px entre les éléments. */
            margin-top: 15px; /* Espacement en haut. */
        }

        /* .keyword : Classe pour chaque mot-clé. */
        .keyword {
            background-color: #e9f7fe; /* Fond bleu très clair. */
            color: #3498db; /* Texte bleu clair. */
            padding: 5px 10px; /* Espacement interne. */
            border-radius: 20px; /* Coins très arrondis. */
            font-size: 14px; /* Taille de la police. */
            /* display: flex : Utilise Flexbox pour aligner le texte et le compteur. */
            display: flex;
            /* align-items: center : Centre verticalement les éléments. */
            align-items: center;
            gap: 5px; /* Espacement entre le texte et le compteur. */
        }

        /* .keyword-count : Classe pour le compteur des mots-clés. */
        .keyword-count {
            background-color: #3498db; /* Fond bleu clair. */
            color: white; /* Texte blanc. */
            border-radius: 10px; /* Coins arrondis. */
            padding: 2px 6px; /* Espacement interne. */
            font-size: 12px; /* Taille de la police. */
        }

        /* =============================================
           BOUTON DE CONNEXION
           ============================================= */
        /* .login-btn : Classe pour le bouton de connexion. */
        .login-btn {
            /* position: fixed : Position fixe par rapport à la fenêtre du navigateur. */
            position: fixed;
            top: 20px; /* Distance par rapport au haut de la fenêtre. */
            right: 20px; /* Distance par rapport à la droite de la fenêtre. */
            padding: 10px 20px; /* Espacement interne. */
            background-color: #3498db; /* Fond bleu clair. */
            color: white; /* Texte blanc. */
            border: none; /* Supprime la bordure. */
            border-radius: 5px; /* Coins arrondis. */
            /* cursor: pointer : Change le curseur en "main" au survol. */
            cursor: pointer;
            font-size: 16px; /* Taille de la police. */
            /* z-index: 1000 : Définit la priorité d'affichage (plus la valeur est élevée, plus l'élément est au premier plan). */
            z-index: 1000;
        }

        /* .login-btn:hover : Sélecteur pour le bouton au survol. */
        .login-btn:hover {
            background-color: #2980b9; /* Fond bleu plus foncé au survol. */
        }

        /* =============================================
           MODALE DE CONNEXION
           ============================================= */
        /* .login-modal : Classe pour la fenêtre modale de connexion. */
        .login-modal {
            display: none; /* Masquée par défaut. */
            /* position: fixed : Position fixe par rapport à la fenêtre. */
            position: fixed;
            top: 50%; /* Positionnée à 50% du haut. */
            left: 50%; /* Positionnée à 50% de la gauche. */
            /* transform: translate(-50%, -50%) : Décale l'élément de -50% de sa propre largeur/hauteur pour le centrer. */
            transform: translate(-50%, -50%);
            background-color: white; /* Fond blanc. */
            padding: 30px; /* Espacement interne. */
            border-radius: 8px; /* Coins arrondis. */
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2); /* Ombre plus marquée. */
            z-index: 1001; /* Priorité d'affichage supérieure au bouton (1000). */
            width: 350px; /* Largeur fixe. */
        }

        /* .login-modal input, .login-modal select, .login-modal button : Sélecteur groupé pour les champs du formulaire. */
        .login-modal input,
        .login-modal select,
        .login-modal button {
            display: block; /* Chaque élément prend toute la largeur disponible. */
            width: 100%; /* Largeur à 100%. */
            margin-bottom: 15px; /* Espacement en bas. */
            padding: 10px; /* Espacement interne. */
            border: 1px solid #ddd; /* Bordure grise claire. */
            border-radius: 5px; /* Coins arrondis. */
        }

        /* .login-modal button : Sélecteur pour le bouton de soumission. */
        .login-modal button {
            background-color: #2ecc71; /* Fond vert. */
            color: white; /* Texte blanc. */
            border: none; /* Supprime la bordure. */
            cursor: pointer; /* Curseur en forme de main. */
            padding: 12px; /* Espacement interne. */
        }

        /* .login-modal button:hover : Sélecteur pour le bouton au survol. */
        .login-modal button:hover {
            background-color: #27ae60; /* Fond vert plus foncé. */
        }
    </style>
</head>

<body>
    <!-- =============================================
         CONTAINER PRINCIPAL
         ============================================= -->
    <!-- div.container : Conteneur principal qui contient tout le contenu de la page. -->
    <div class="container">
        <!-- =============================================
             EN-TÊTE
             ============================================= -->
        <!-- header : Balise HTML5 pour l'en-tête de la page. -->
        <header>
            <!-- h1 : Titre principal de la page. -->
            <h1>Gestion des Rendez-vous - Cabinet Médical</h1>
        </header>

        <!-- =============================================
             SECTION DES STATISTIQUES
             ============================================= -->
        <!-- div.stats-grid : Grille de cartes affichant les statistiques clés. -->
        <div class="stats-grid">
            <!-- div.stat-card : Carte pour le nombre total de rendez-vous. -->
            <div class="stat-card">
                <h3>Rendez-vous du mois</h3>
                <!-- <?= ?> : Syntaxe PHP pour echo (affiche la valeur de la variable). -->
                <!-- $totalRdv : Variable PHP contenant le nombre total de rendez-vous. -->
                <div class="stat-value"><?= $totalRdv ?></div>
                <p>Total des rendez-vous pris</p>
            </div>

            <!-- div.stat-card : Carte pour le nombre de patients consultés. -->
            <div class="stat-card">
                <h3>Patients consultés</h3>
                <!-- <?= $patientsConsultes ?> : Affiche la valeur de la variable $patientsConsultes. -->
                <div class="stat-value"><?= $patientsConsultes ?></div>
                <p>Nombre de consultations réalisées</p>
            </div>

            <!-- div.stat-card : Carte pour le nombre de rendez-vous annulés. -->
            <div class="stat-card">
                <h3>Rendez-vous annulés</h3>
                <div class="stat-value"><?= $rdvAnnules ?></div>
                <p>Nombre d'annulations ce mois</p>
            </div>

            <!-- div.stat-card : Carte pour le nombre de rendez-vous en retard. -->
            <div class="stat-card">
                <h3>Rendez-vous en retard</h3>
                <div class="stat-value"><?= $rdvRetard ?></div>
                <p>Patients arrivés en retard</p>
            </div>

            <!-- div.stat-card : Carte pour la durée moyenne des consultations. -->
            <div class="stat-card">
                <h3>Durée moyenne des consultations</h3>
                <!-- <?= $dureeMoyenne ?> min : Affiche la durée moyenne suivie de " min". -->
                <div class="stat-value"><?= $dureeMoyenne ?> min</div>
                <p>Temps moyen par consultation</p>
            </div>

            <!-- div.stat-card : Carte pour la note moyenne des avis. -->
            <div class="stat-card">
                <h3>Note moyenne des avis</h3>
                <!-- <?= $noteMoyenne ?>/5 : Affiche la note moyenne suivie de "/5". -->
                <div class="stat-value"><?= $noteMoyenne ?>/5</div>
                <p>Satisfaction des patients</p>
            </div>

            <!-- div.stat-card : Carte pour le délai moyen de prise de rendez-vous. -->
            <div class="stat-card">
                <h3>Délai moyen de prise de RDV</h3>
                <!-- <?= $delaiMoyen ?> jours : Affiche le délai moyen suivi de " jours". -->
                <div class="stat-value"><?= $delaiMoyen ?> jours</div>
                <p>Temps entre demande et consultation</p>
            </div>
        </div>

        <!-- =============================================
             SECTION DES GRAPHIQUES
             ============================================= -->
        <!-- div.charts : Conteneur pour le graphique des statistiques. -->
        <div class="charts">
            <div class="chart-container">
                <h3>Statistiques des rendez-vous (mois en cours)</h3>
                <!-- canvas : Balise HTML5 pour dessiner des graphiques (utilisée par Chart.js). -->
                <!-- id="rdvStatsChart" : Identifiant unique pour accéder à cet élément en JavaScript. -->
                <canvas id="rdvStatsChart"></canvas>
            </div>
        </div>

        <!-- =============================================
             SECTION DES COMMENTAIRES DES PATIENTS
             ============================================= -->
        <!-- div.comments : Conteneur pour les mots-clés des retours patients. -->
        <div class="comments">
            <h3>Mots-clés des retours patients (ce mois)</h3>
            <!-- div.comment-keywords : Conteneur pour la liste des mots-clés. -->
            <div class="comment-keywords">
                <!-- span.keyword : Un mot-clé avec son compteur. -->
                <span class="keyword">Rendez-vous rapide et efficace <span class="keyword-count">42</span></span>
                <span class="keyword">Médecin à l'écoute <span class="keyword-count">38</span></span>
                <span class="keyword">Attente un peu longue <span class="keyword-count">25</span></span>
                <span class="keyword">Accueil chaleureux <span class="keyword-count">30</span></span>
                <span class="keyword">Rendez-vous lent <span class="keyword-count">12</span></span>
                <span class="keyword">Manque de ponctualité <span class="keyword-count">18</span></span>
                <span class="keyword">Explications claires <span class="keyword-count">28</span></span>
            </div>
        </div>
    </div>

    <!-- =============================================
         BOUTON DE CONNEXION
         ============================================= -->
    <!-- button.login-btn : Bouton pour ouvrir la modale de connexion. -->
    <!-- onclick : Attribut HTML qui exécute du JavaScript lors du clic. -->
    <!-- document.getElementById('loginModal').style.display='block' : Affiche la modale en définissant display: block. -->
    <button class="login-btn" onclick="document.getElementById('loginModal').style.display='block'">Se connecter</button>

    <!-- =============================================
         MODALE DE CONNEXION
         ============================================= -->
    <!-- div#loginModal : Fenêtre modale pour le formulaire de connexion. -->
    <!-- id="loginModal" : Identifiant unique pour accéder à cet élément en JavaScript. -->
    <div id="loginModal" class="login-modal">
        <h2>Connexion</h2>
        <!-- form : Balise HTML pour un formulaire. -->
        <!-- action="login.php" : URL vers laquelle le formulaire sera envoyé. -->
        <!-- method="post" : Méthode HTTP utilisée pour envoyer les données (POST). -->
        <form action="login.php" method="post">
            <!-- select : Balise HTML pour une liste déroulante. -->
            <!-- name="role" : Nom du champ, utilisé pour accéder à la valeur en PHP via $_POST['role']. -->
            <!-- required : Attribut HTML5 qui rend le champ obligatoire. -->
            <select name="role" required>
                <!-- option : Balise HTML pour une option dans une liste déroulante. -->
                <!-- value="" : Valeur envoyée au serveur si cette option est sélectionnée. -->
                <option value="">Sélectionnez votre rôle</option>
                <option value="secretaire">Secrétaire</option>
                <option value="medecin">Médecin</option>
                <option value="administrateur">Administrateur</option>
            </select>

            <!-- input : Balise HTML pour un champ de saisie. -->
            <!-- type="text" : Champ de texte. -->
            <!-- name="username" : Nom du champ, utilisé pour accéder à la valeur en PHP via $_POST['username']. -->
            <!-- placeholder : Texte affiché dans le champ quand il est vide. -->
            <!-- required : Champ obligatoire. -->
            <input name="username" type="text" placeholder="Nom d'utilisateur" required>

            <!-- input type="password" : Champ de mot de passe (masqué). -->
            <input name="password" type="password" placeholder="Mot de passe" required>

            <!-- button type="submit" : Bouton pour soumettre le formulaire. -->
            <button type="submit">Se connecter</button>
        </form>
    </div>

    <!-- =============================================
         SCRIPT POUR LE GRAPHIQUE ET LA MODALE
         ============================================= -->
    <script>
        // =============================================
        // INITIALISATION DU GRAPHIQUE CHART.JS
        // =============================================

        // document.getElementById('rdvStatsChart') : Récupère l'élément HTML avec l'ID "rdvStatsChart".
        // getContext('2d') : Méthode de l'élément canvas qui retourne un contexte 2D pour dessiner.
        const ctx = document.getElementById('rdvStatsChart').getContext('2d');

        // =============================================
        // DONNÉES DU GRAPHIQUE
        // =============================================
        // const : Déclare une constante (variable non réassignable).
        // data : Objet JavaScript contenant les données du graphique.
        const data = {
            // labels : Tableau des étiquettes pour l'axe X (catégories).
            labels: [
                'Rendez-vous',
                'Patients consultés',
                'Annulations',
                'Retards',
                'Durée moyenne',
                'Note moyenne',
                'Délai moyen'
            ],
            // datasets : Tableau des jeux de données à afficher.
            datasets: [{
                label: 'Statistiques', // Légende du jeu de données.
                // data : Tableau des valeurs à afficher pour chaque catégorie.
                // <?= $totalRdv ?> : Injection de la variable PHP $totalRdv dans JavaScript.
                data: [
                    <?= $totalRdv ?>,
                    <?= $patientsConsultes ?>,
                    <?= $rdvAnnules ?>,
                    <?= $rdvRetard ?>,
                    <?= $dureeMoyenne ?>,
                    <?= $noteMoyenne ?>,
                    <?= $delaiMoyen ?>
                ],
                // backgroundColor : Tableau des couleurs de fond pour chaque barre.
                backgroundColor: [
                    'rgba(255, 99, 132, 1)',   // Rouge
                    'rgba(54, 162, 235, 1)',   // Bleu
                    'rgba(255, 206, 86, 1)',  // Jaune
                    'rgba(75, 192, 192, 1)',  // Vert
                    'rgba(153, 102, 255, 1)', // Violet
                    'rgba(255, 159, 64, 1)',  // Orange
                    'rgba(201, 203, 207, 1)'  // Gris
                ],
                borderWidth: 1 // Épaisseur de la bordure des barres.
            }]
        };

        // =============================================
        // CONFIGURATION DU GRAPHIQUE
        // =============================================
        // config : Objet JavaScript contenant la configuration du graphique.
        const config = {
            type: 'bar', // Type de graphique : barres.
            data: data, // Données à afficher.
            options: {
                responsive: true, // Le graphique s'adapte à la taille de son conteneur.
                plugins: {
                    legend: { display: false }, // Masque la légende.
                    title: {
                        display: true, // Affiche le titre.
                        text: 'Statistiques détaillées des rendez-vous', // Texte du titre.
                        font: { size: 16 } // Taille de la police du titre.
                    },
                    tooltip: {
                        // callbacks : Objet contenant des fonctions pour personnaliser les tooltips.
                        callbacks: {
                            // label : Fonction appelée pour générer le texte de l'infobulle.
                            label: function(context) {
                                // context : Objet contenant des informations sur l'élément survolé.
                                let label = context.label || ''; // Récupère l'étiquette ou une chaîne vide.
                                if (label) label += ': '; // Ajoute ": " si l'étiquette existe.
                                if (context.parsed.y !== null) {
                                    // context.parsed.y : Valeur de la donnée survolée.
                                    label += context.parsed.y;
                                    // Ajoute "min" pour la durée moyenne (index 4 dans le tableau data).
                                    if (context.dataIndex === 4) label += ' min';
                                }
                                return label; // Retourne le texte de l'infobulle.
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true, // L'axe Y commence à 0.
                        title: { display: true, text: 'Nombre / Durée' } // Titre de l'axe Y.
                    },
                    x: {
                        title: { display: true, text: 'Catégories' } // Titre de l'axe X.
                    }
                }
            }
        };

        // new Chart(ctx, config) : Crée une nouvelle instance de Chart avec le contexte et la configuration.
        new Chart(ctx, config);

        // =============================================
        // GESTION DE LA MODALE DE CONNEXION
        // =============================================
        // window.onclick : Événement déclenché lors d'un clic n'importe où dans la fenêtre.
        window.onclick = function(event) {
            // event : Objet contenant des informations sur l'événement (ex: position du clic).
            // document.getElementById('loginModal') : Récupère l'élément de la modale.
            const modal = document.getElementById('loginModal');
            // event.target == modal : Vérifie si le clic a été fait directement sur la modale (et non sur un enfant).
            if (event.target == modal) {
                modal.style.display = 'none'; // Masque la modale en définissant display: none.
            }
        };
    </script>
</body>
</html><?php
// =============================================
// FICHIER: index.php
// RÔLE: Page principale du tableau de bord pour la gestion des rendez-vous d'un cabinet médical.
// AFFICHE: Statistiques dynamiques, graphiques, mots-clés des patients, et interface de connexion.
// =============================================

// --- CONFIGURATION DES ERREURS ---
// Active l'affichage des erreurs pour le débogage (à désactiver en production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// --- DÉMARRAGE DE LA SESSION ---
// Permet d'utiliser $_SESSION pour stocker des informations entre les requêtes.
session_start();

// --- INCLUSION DES FICHIERS NÉCESSAIRES ---
require_once './database/connexion_database.php';

// =============================================
// RÉCUPÉRATION DES STATISTIQUES DEPUIS LA BASE DE DONNÉES
// =============================================
try {
    $pdo = getConnexion();

    // --- REQUÊTES SQL DYNAMIQUES ---
    // 1. Nombre total de rendez-vous ce mois
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM rendez_vous WHERE MONTH(date_rdv) = MONTH(CURRENT_DATE)");
    $totalRdv = $stmt->fetch()['total'];

    // 2. Nombre de patients consultés ce mois
    $stmt = $pdo->query("SELECT COUNT(DISTINCT id_patient) as patients FROM rendez_vous WHERE MONTH(date_rdv) = MONTH(CURRENT_DATE)");
    $patientsConsultes = $stmt->fetch()['patients'];

    // 3. Nombre de rendez-vous annulés ce mois
    $stmt = $pdo->query("SELECT COUNT(*) as annules FROM rendez_vous WHERE statut = 'annulé' AND MONTH(date_rdv) = MONTH(CURRENT_DATE)");
    $rdvAnnules = $stmt->fetch()['annules'];

    // 4. Nombre de rendez-vous en retard ce mois
    $stmt = $pdo->query("SELECT COUNT(*) as retards FROM rendez_vous WHERE retard = 1 AND MONTH(date_rdv) = MONTH(CURRENT_DATE)");
    $rdvRetard = $stmt->fetch()['retards'];

    // 5. Durée moyenne des consultations (en minutes)
    $stmt = $pdo->query("SELECT AVG(duree) as duree_moyenne FROM rendez_vous WHERE MONTH(date_rdv) = MONTH(CURRENT_DATE)");
    $dureeMoyenne = round($stmt->fetch()['duree_moyenne'], 0); // Arrondi à l'entier le plus proche

    // 6. Note moyenne des avis (sur 5)
    $stmt = $pdo->query("SELECT AVG(note) as note_moyenne FROM avis");
    $noteMoyenne = round($stmt->fetch()['note_moyenne'], 1); // Arrondi à 1 décimale

    // 7. Délai moyen de prise de rendez-vous (en jours)
    $stmt = $pdo->query("SELECT AVG(DATEDIFF(date_rdv, date_prise_rdv)) as delai_moyen FROM rendez_vous");
    $delaiMoyen = round($stmt->fetch()['delai_moyen'], 0);

    // 8. Récupération des mots-clés des commentaires (avec leur nombre d'occurrences)
    $stmt = $pdo->query("
        SELECT mot_cle, COUNT(*) as count
        FROM commentaires
        WHERE MONTH(date_commentaire) = MONTH(CURRENT_DATE)
        GROUP BY mot_cle
        ORDER BY count DESC
        LIMIT 7
    ");
    $keywords = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // --- GESTION DES ERREURS ---
    // Enregistre l'erreur dans un fichier log (pour le débogage en production)
    error_log("Erreur base de données dans index.php: " . $e->getMessage());

    // Affiche un message d'erreur générique (sans détails sensibles)
    die("Une erreur est survenue lors de la récupération des données. Veuillez réessayer plus tard.");
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <!-- =============================================
         MÉTADONNÉES DE LA PAGE
         ============================================= -->
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <meta name="description" content="Tableau de bord pour la gestion des rendez-vous médicaux"/>
    <title>Gestion de Rendez-vous - Cabinet Médical</title>

    <!-- =============================================
         INTÉGRATION DE CHART.JS (AVEC FALLBACK)
         ============================================= -->
    <!-- Chargement de Chart.js depuis un CDN avec fallback local si échec -->
    <script>
        // Vérifie si Chart.js est déjà chargé
        if (!window.Chart) {
            document.write('<script src="/js/chart.js"><\/script>');
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

    <!-- =============================================
         STYLES CSS
         ============================================= -->
    <style>
        /* =============================================
           STYLES GLOBAUX
           ============================================= */
        :root {
            --primary-color: #3498db;
            --primary-dark: #2980b9;
            --secondary-color: #2ecc71;
            --secondary-dark: #27ae60;
            --light-gray: #f8f9fa;
            --medium-gray: #e9ecef;
            --dark-gray: #333;
            --white: #ffffff;
            --shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            --shadow-modal: 0 4px 12px rgba(0, 0, 0, 0.2);
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background-color: var(--light-gray);
            color: var(--dark-gray);
            line-height: 1.6;
        }

        /* =============================================
           CONTAINER PRINCIPAL
           ============================================= */
        .container {
            width: 95%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        /* =============================================
           EN-TÊTE
           ============================================= */
        header {
            text-align: center;
            margin-bottom: 30px;
            padding: 15px 0;
            background-color: var(--medium-gray);
            border-radius: 8px;
        }

        h1 {
            color: var(--primary-color);
            margin: 0;
            font-size: 2rem;
        }

        /* =============================================
           GRILLE DES STATISTIQUES
           ============================================= */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        /* =============================================
           CARTES DE STATISTIQUES
           ============================================= */
        .stat-card {
            background-color: var(--white);
            border-radius: 8px;
            padding: 20px;
            box-shadow: var(--shadow);
            text-align: center;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .stat-card h3 {
            margin-top: 0;
            color: var(--primary-color);
            font-size: 16px;
        }

        .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: var(--primary-dark);
            margin: 10px 0;
        }

        /* =============================================
           SECTION DES GRAPHIQUES
           ============================================= */
        .charts {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .chart-container {
            background-color: var(--white);
            border-radius: 8px;
            padding: 20px;
            box-shadow: var(--shadow);
        }

        /* =============================================
           SECTION DES COMMENTAIRES
           ============================================= */
        .comments {
            background-color: var(--white);
            border-radius: 8px;
            padding: 20px;
            box-shadow: var(--shadow);
            margin-bottom: 30px;
        }

        .comments h3 {
            color: var(--primary-color);
            margin-top: 0;
        }

        /* =============================================
           MOTS-CLÉS DES COMMENTAIRES
           ============================================= */
        .comment-keywords {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 15px;
        }

        .keyword {
            background-color: rgba(52, 152, 219, 0.2);
            color: var(--primary-color);
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: background-color 0.3s ease;
        }

        .keyword:hover {
            background-color: rgba(52, 152, 219, 0.4);
        }

        .keyword-count {
            background-color: var(--primary-color);
            color: var(--white);
            border-radius: 10px;
            padding: 2px 6px;
            font-size: 12px;
        }

        /* =============================================
           BOUTON DE CONNEXION
           ============================================= */
        .login-btn {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 10px 20px;
            background-color: var(--primary-color);
            color: var(--white);
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            z-index: 1000;
            transition: background-color 0.3s ease;
        }

        .login-btn:hover {
            background-color: var(--primary-dark);
        }

        /* =============================================
           MODALE DE CONNEXION
           ============================================= */
        .login-modal {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: var(--white);
            padding: 30px;
            border-radius: 8px;
            box-shadow: var(--shadow-modal);
            z-index: 1001;
            width: 90%;
            max-width: 350px;
        }

        .login-modal h2 {
            color: var(--primary-color);
            margin-top: 0;
            text-align: center;
        }

        .login-modal input,
        .login-modal select,
        .login-modal button {
            display: block;
            width: 100%;
            margin-bottom: 15px;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }

        .login-modal button {
            background-color: var(--secondary-color);
            color: var(--white);
            border: none;
            cursor: pointer;
            padding: 12px;
            transition: background-color 0.3s ease;
        }

        .login-modal button:hover {
            background-color: var(--secondary-dark);
        }

        /* =============================================
           MESSAGES D'ERREUR
           ============================================= */
        .error-message {
            color: #e74c3c;
            text-align: center;
            margin: 10px 0;
            font-weight: bold;
        }

        /* =============================================
           RESPONSIVE
           ============================================= */
        @media (max-width: 768px) {
            .charts {
                grid-template-columns: 1fr;
            }

            .login-btn {
                width: auto;
                padding: 8px 16px;
                font-size: 14px;
            }
        }
    </style>
</head>

<body>
    <!-- =============================================
         CONTAINER PRINCIPAL
         ============================================= -->
    <div class="container">
        <!-- =============================================
             EN-TÊTE
             ============================================= -->
        <header>
            <h1>Gestion des Rendez-vous - Cabinet Médical</h1>
            <?php if (isset($_SESSION['username'])): ?>
                <p style="margin-top: 10px; color: var(--primary-dark);">
                    Connecté en tant que <strong><?= htmlspecialchars($_SESSION['role']) ?></strong>
                </p>
            <?php endif; ?>
        </header>

        <!-- =============================================
             MAIN CONTENT (BALISES SÉMANTIQUES)
             ============================================= -->
        <main>
            <!-- =============================================
                 SECTION DES STATISTIQUES
                 ============================================= -->
            <section aria-labelledby="stats-title">
                <h2 id="stats-title" style="display: none;">Statistiques du cabinet</h2>
                <div class="stats-grid">
                    <!-- Carte pour le nombre total de rendez-vous -->
                    <div class="stat-card">
                        <h3>Rendez-vous du mois</h3>
                        <div class="stat-value"><?= htmlspecialchars($totalRdv) ?></div>
                        <p>Total des rendez-vous pris</p>
                    </div>

                    <!-- Carte pour le nombre de patients consultés -->
                    <div class="stat-card">
                        <h3>Patients consultés</h3>
                        <div class="stat-value"><?= htmlspecialchars($patientsConsultes) ?></div>
                        <p>Nombre de consultations réalisées</p>
                    </div>

                    <!-- Carte pour le nombre de rendez-vous annulés -->
                    <div class="stat-card">
                        <h3>Rendez-vous annulés</h3>
                        <div class="stat-value"><?= htmlspecialchars($rdvAnnules) ?></div>
                        <p>Nombre d'annulations ce mois</p>
                    </div>

                    <!-- Carte pour le nombre de rendez-vous en retard -->
                    <div class="stat-card">
                        <h3>Rendez-vous en retard</h3>
                        <div class="stat-value"><?= htmlspecialchars($rdvRetard) ?></div>
                        <p>Patients arrivés en retard</p>
                    </div>

                    <!-- Carte pour la durée moyenne des consultations -->
                    <div class="stat-card">
                        <h3>Durée moyenne des consultations</h3>
                        <div class="stat-value"><?= htmlspecialchars($dureeMoyenne) ?> min</div>
                        <p>Temps moyen par consultation</p>
                    </div>

                    <!-- Carte pour la note moyenne des avis -->
                    <div class="stat-card">
                        <h3>Note moyenne des avis</h3>
                        <div class="stat-value"><?= htmlspecialchars($noteMoyenne) ?>/5</div>
                        <p>Satisfaction des patients</p>
                    </div>

                    <!-- Carte pour le délai moyen de prise de rendez-vous -->
                    <div class="stat-card">
                        <h3>Délai moyen de prise de RDV</h3>
                        <div class="stat-value"><?= htmlspecialchars($delaiMoyen) ?> jours</div>
                        <p>Temps entre demande et consultation</p>
                    </div>
                </div>
            </section>

            <!-- =============================================
                 SECTION DES GRAPHIQUES
                 ============================================= -->
            <section aria-labelledby="charts-title">
                <h2 id="charts-title" style="display: none;">Graphiques des statistiques</h2>
                <div class="charts">
                    <div class="chart-container">
                        <h3>Statistiques des rendez-vous (mois en cours)</h3>
                        <canvas id="rdvStatsChart"></canvas>
                    </div>
                </div>
            </section>

            <!-- =============================================
                 SECTION DES COMMENTAIRES DES PATIENTS
                 ============================================= -->
            <section aria-labelledby="comments-title">
                <h2 id="comments-title">Mots-clés des retours patients (ce mois)</h2>
                <div class="comments">
                    <div class="comment-keywords">
                        <?php if (empty($keywords)): ?>
                            <p>Aucun commentaire disponible pour ce mois.</p>
                        <?php else: ?>
                            <?php foreach ($keywords as $keyword): ?>
                                <span class="keyword">
                                    <?= htmlspecialchars($keyword['mot_cle']) ?>
                                    <span class="keyword-count"><?= htmlspecialchars($keyword['count']) ?></span>
                                </span>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <!-- =============================================
         BOUTON DE CONNEXION (MASQUÉ SI DÉJÀ CONNECTÉ)
         ============================================= -->
    <?php if (!isset($_SESSION['username'])): ?>
        <button class="login-btn" onclick="document.getElementById('loginModal').style.display='block'">
            Se connecter
        </button>
    <?php endif; ?>

    <!-- =============================================
         MODALE DE CONNEXION
         ============================================= -->
    <?php if (!isset($_SESSION['username'])): ?>
        <div id="loginModal" class="login-modal">
            <h2>Connexion</h2>
            <?php if (isset($_GET['error']) && $_GET['error'] == '1'): ?>
                <p class="error-message">Identifiants incorrects. Veuillez réessayer.</p>
            <?php endif; ?>
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
    <?php endif; ?>

    <!-- =============================================
         SCRIPT POUR LE GRAPHIQUE ET LA MODALE
         ============================================= -->
    <script>
        // =============================================
        // INITIALISATION DU GRAPHIQUE CHART.JS
        // =============================================
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('rdvStatsChart').getContext('2d');

            // Données du graphique (valeurs injectées depuis PHP)
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
                    data: [
                        <?= json_encode($totalRdv) ?>,
                        <?= json_encode($patientsConsultes) ?>,
                        <?= json_encode($rdvAnnules) ?>,
                        <?= json_encode($rdvRetard) ?>,
                        <?= json_encode($dureeMoyenne) ?>,
                        <?= json_encode($noteMoyenne) ?>,
                        <?= json_encode($delaiMoyen) ?>
                    ],
                    backgroundColor: [
                        'rgba(255, 99, 132, 0.7)',   // Rouge
                        'rgba(54, 162, 235, 0.7)',   // Bleu
                        'rgba(255, 206, 86, 0.7)',  // Jaune
                        'rgba(75, 192, 192, 0.7)',  // Vert
                        'rgba(153, 102, 255, 0.7)', // Violet
                        'rgba(255, 159, 64, 0.7)',  // Orange
                        'rgba(201, 203, 207, 0.7)'  // Gris
                    ],
                    borderColor: [
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

            // Configuration du graphique
            const config = {
                type: 'bar',
                data: data,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        title: {
                            display: true,
                            text: 'Statistiques détaillées des rendez-vous',
                            font: { size: 16 }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.label || '';
                                    if (label) label += ': ';
                                    if (context.parsed.y !== null) {
                                        label += context.parsed.y;
                                        // Ajoute "min" pour la durée moyenne (index 4)
                                        if (context.dataIndex === 4) label += ' min';
                                        // Ajoute "jours" pour le délai moyen (index 6)
                                        if (context.dataIndex === 6) label += ' jours';
                                    }
                                    return label;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: { display: true, text: 'Nombre / Durée' }
                        },
                        x: {
                            title: { display: true, text: 'Catégories' }
                        }
                    }
                }
            };

            // Création du graphique
            new Chart(ctx, config);

            // =============================================
            // GESTION DE LA MODALE DE CONNEXION
            // =============================================
            const modal = document.getElementById('loginModal');
            if (modal) {
                window.onclick = function(event) {
                    if (event.target == modal) {
                        modal.style.display = 'none';
                    }
                };
            }
        });
    </script>
</body>
</html><?php
// =============================================
// FICHIER: index.php
// RÔLE: Page principale du tableau de bord pour la gestion des rendez-vous d'un cabinet médical.
// AFFICHE: Statistiques, graphiques, et interface de connexion.
// =============================================

// --- INCLUSION DU FICHIER DE CONNEXION À LA BASE DE DONNÉES ---
// Ce fichier contient la fonction getConnexion() qui retourne un objet PDO pour interagir avec la base de données.
require_once './database/connexion_database.php';

// =============================================
// RÉCUPÉRATION DES STATISTIQUES DEPUIS LA BASE DE DONNÉES
// =============================================
try {
    // --- CONNEXION À LA BASE DE DONNÉES ---
    // Appel de la fonction getConnexion() pour obtenir une instance PDO.
    $pdo = getConnexion();

    // --- REQUÊTE SQL POUR COMPTER LE NOMBRE TOTAL DE RENDEZ-VOUS ---
    // Exécute une requête SQL pour compter le nombre total de rendez-vous dans la table "rendez_vous".
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM rendez_vous");

    // Récupère le résultat sous forme de tableau associatif et extrait la valeur 'total'.
    $totalRdv = $stmt->fetch()['total'];

    // =============================================
    // DONNÉES STATIQUES (À REMPLACER PAR DES REQUÊTES SQL DYNAMIQUES)
    // =============================================
    // Ces valeurs sont actuellement codées en dur, mais devraient idéalement provenir de requêtes SQL.
    $patientsConsultes = 150;    // Nombre de patients consultés ce mois
    $rdvAnnules = 12;           // Nombre de rendez-vous annulés
    $rdvRetard = 25;            // Nombre de rendez-vous en retard
    $dureeMoyenne = 22;         // Durée moyenne des consultations (en minutes)
    $noteMoyenne = 4.2;        // Note moyenne des avis patients (sur 5)
    $delaiMoyen = 3;            // Délai moyen pour obtenir un rendez-vous (en jours)

} catch (PDOException $e) {
    // --- GESTION DES ERREURS DE CONNEXION À LA BASE DE DONNÉES ---
    // En cas d'erreur avec la base de données, on affiche un message et on arrête l'exécution du script.
    die("Erreur : " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <!-- =============================================
         MÉTADONNÉES DE LA PAGE
         ============================================= -->
    <meta charset="UTF-8"/> <!-- Définit l'encodage des caractères en UTF-8 -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/> <!-- Rend la page responsive pour les appareils mobiles -->

    <title>Gestion de Rendez-vous - Cabinet Médical</title> <!-- Titre de la page affiché dans l'onglet du navigateur -->

    <!-- =============================================
         INTÉGRATION DE LA BIBLIOTHÈQUE CHART.JS
         ============================================= -->
    <!-- Chart.js est une bibliothèque JavaScript pour créer des graphiques interactifs. -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- =============================================
         STYLES CSS
         ============================================= -->
    <style>
        /* =============================================
           STYLES GLOBAUX
           ============================================= */
        /* Applique une police moderne et une couleur de fond claire à toute la page. */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; /* Police par défaut */
            margin: 0; /* Supprime les marges par défaut */
            padding: 0; /* Supprime les espacements internes par défaut */
            background-color: #f8f9fa; /* Couleur de fond claire */
            color: #333; /* Couleur de texte gris foncé */
        }

        /* =============================================
           CONTAINER PRINCIPAL
           ============================================= */
        /* Conteneur principal qui limite la largeur du contenu et le centre horizontalement. */
        .container {
            width: 95%; /* Largeur relative */
            max-width: 1200px; /* Largeur maximale */
            margin: 0 auto; /* Centre horizontalement */
            padding: 20px; /* Espacement interne */
        }

        /* =============================================
           EN-TÊTE
           ============================================= */
        /* Style de l'en-tête avec un fond gris clair et un texte centré. */
        header {
            text-align: center; /* Centre le texte */
            margin-bottom: 30px; /* Espacement en bas */
            padding: 15px 0; /* Espacement interne vertical */
            background-color: #e9ecef; /* Fond gris clair */
            border-radius: 8px; /* Coins arrondis */
        }

        /* Style du titre principal. */
        h1 {
            color: #2c3e50; /* Couleur bleu foncé */
            margin: 0; /* Supprime les marges par défaut */
        }

        /* =============================================
           GRILLE DES STATISTIQUES
           ============================================= */
        /* Affiche les cartes de statistiques en grille responsive. */
        .stats-grid {
            display: grid; /* Utilise CSS Grid pour la mise en page */
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); /* Grille responsive avec des colonnes de 280px minimum */
            gap: 20px; /* Espacement entre les éléments de la grille */
            margin-bottom: 30px; /* Espacement en bas */
        }

        /* =============================================
           CARTES DE STATISTIQUES
           ============================================= */
        /* Style des cartes individuelles pour chaque statistique. */
        .stat-card {
            background-color: white; /* Fond blanc */
            border-radius: 8px; /* Coins arrondis */
            padding: 20px; /* Espacement interne */
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); /* Ombre légère pour un effet 3D */
            text-align: center; /* Centre le texte */
        }

        /* Style du titre des cartes. */
        .stat-card h3 {
            margin-top: 0; /* Supprime la marge supérieure */
            color: #3498db; /* Couleur bleu clair */
            font-size: 16px; /* Taille de la police */
        }

        /* Style de la valeur de la statistique. */
        .stat-value {
            font-size: 24px; /* Taille de la police */
            font-weight: bold; /* Texte en gras */
            color: #2c3e50; /* Couleur bleu foncé */
            margin: 10px 0; /* Espacement vertical */
        }

        /* =============================================
           SECTION DES GRAPHIQUES
           ============================================= */
        /* Affiche les graphiques en grille responsive. */
        .charts {
            display: grid; /* Utilise CSS Grid */
            grid-template-columns: repeat(auto-fit, minmax(500px, 1fr)); /* Grille responsive avec des colonnes de 500px minimum */
            gap: 20px; /* Espacement entre les éléments */
            margin-bottom: 30px; /* Espacement en bas */
        }

        /* Style du conteneur des graphiques. */
        .chart-container {
            background-color: white; /* Fond blanc */
            border-radius: 8px; /* Coins arrondis */
            padding: 20px; /* Espacement interne */
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); /* Ombre légère */
        }

        /* =============================================
           SECTION DES COMMENTAIRES
           ============================================= */
        /* Style de la section des commentaires. */
        .comments {
            background-color: white; /* Fond blanc */
            border-radius: 8px; /* Coins arrondis */
            padding: 20px; /* Espacement interne */
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); /* Ombre légère */
            margin-bottom: 30px; /* Espacement en bas */
        }

        /* Style du titre de la section des commentaires. */
        .comments h3 {
            color: #3498db; /* Couleur bleu clair */
            margin-top: 0; /* Supprime la marge supérieure */
        }

        /* =============================================
           MOTS-CLÉS DES COMMENTAIRES
           ============================================= */
        /* Affiche les mots-clés en flexbox pour un enroulement automatique. */
        .comment-keywords {
            display: flex; /* Utilise Flexbox */
            flex-wrap: wrap; /* Permet l'enroulement sur plusieurs lignes */
            gap: 10px; /* Espacement entre les éléments */
            margin-top: 15px; /* Espacement en haut */
        }

        /* Style des mots-clés. */
        .keyword {
            background-color: #e9f7fe; /* Fond bleu très clair */
            color: #3498db; /* Texte bleu clair */
            padding: 5px 10px; /* Espacement interne */
            border-radius: 20px; /* Coins très arrondis */
            font-size: 14px; /* Taille de la police */
            display: flex; /* Utilise Flexbox pour aligner le texte et le compteur */
            align-items: center; /* Centre verticalement les éléments */
            gap: 5px; /* Espacement entre le texte et le compteur */
        }

        /* Style du compteur des mots-clés. */
        .keyword-count {
            background-color: #3498db; /* Fond bleu clair */
            color: white; /* Texte blanc */
            border-radius: 10px; /* Coins arrondis */
            padding: 2px 6px; /* Espacement interne */
            font-size: 12px; /* Taille de la police */
        }

        /* =============================================
           BOUTON DE CONNEXION
           ============================================= */
        /* Bouton de connexion fixé en haut à droite de la page. */
        .login-btn {
            position: fixed; /* Position fixe par rapport à la fenêtre */
            top: 20px; /* Distance par rapport au haut */
            right: 20px; /* Distance par rapport à la droite */
            padding: 10px 20px; /* Espacement interne */
            background-color: #3498db; /* Fond bleu clair */
            color: white; /* Texte blanc */
            border: none; /* Supprime la bordure */
            border-radius: 5px; /* Coins arrondis */
            cursor: pointer; /* Curseur en forme de main au survol */
            font-size: 16px; /* Taille de la police */
            z-index: 1000; /* Assure que le bouton est au-dessus des autres éléments */
        }

        /* Effet de survol pour le bouton de connexion. */
        .login-btn:hover {
            background-color: #2980b9; /* Fond bleu plus foncé au survol */
        }

        /* =============================================
           MODALE DE CONNEXION
           ============================================= */
        /* Fenêtre modale pour le formulaire de connexion. */
        .login-modal {
            display: none; /* Masquée par défaut */
            position: fixed; /* Position fixe par rapport à la fenêtre */
            top: 50%; /* Positionnée à 50% du haut */
            left: 50%; /* Positionnée à 50% de la gauche */
            transform: translate(-50%, -50%); /* Centre la modale */
            background-color: white; /* Fond blanc */
            padding: 30px; /* Espacement interne */
            border-radius: 8px; /* Coins arrondis */
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2); /* Ombre plus marquée */
            z-index: 1001; /* Assure que la modale est au-dessus du bouton */
            width: 350px; /* Largeur fixe */
        }

        /* Style des champs de formulaire dans la modale. */
        .login-modal input,
        .login-modal select,
        .login-modal button {
            display: block; /* Chaque élément prend toute la largeur */
            width: 100%; /* Largeur à 100% */
            margin-bottom: 15px; /* Espacement en bas */
            padding: 10px; /* Espacement interne */
            border: 1px solid #ddd; /* Bordure grise claire */
            border-radius: 5px; /* Coins arrondis */
        }

        /* Style du bouton de soumission du formulaire. */
        .login-modal button {
            background-color: #2ecc71; /* Fond vert */
            color: white; /* Texte blanc */
            border: none; /* Supprime la bordure */
            cursor: pointer; /* Curseur en forme de main au survol */
            padding: 12px; /* Espacement interne */
        }

        /* Effet de survol pour le bouton de soumission. */
        .login-modal button:hover {
            background-color: #27ae60; /* Fond vert plus foncé au survol */
        }
    </style>
</head>

<body>
    <!-- =============================================
         CONTAINER PRINCIPAL
         ============================================= -->
    <!-- Conteneur principal qui contient tout le contenu de la page. -->
    <div class="container">
        <!-- =============================================
             EN-TÊTE
             ============================================= -->
        <!-- Titre principal de la page. -->
        <header>
            <h1>Gestion des Rendez-vous - Cabinet Médical</h1>
        </header>

        <!-- =============================================
             SECTION DES STATISTIQUES
             ============================================= -->
        <!-- Grille de cartes affichant les statistiques clés. -->
        <div class="stats-grid">
            <!-- Carte pour le nombre total de rendez-vous. -->
            <div class="stat-card">
                <h3>Rendez-vous du mois</h3>
                <div class="stat-value"><?= $totalRdv ?></div> <!-- Affiche la valeur dynamique depuis PHP -->
                <p>Total des rendez-vous pris</p>
            </div>

            <!-- Carte pour le nombre de patients consultés. -->
            <div class="stat-card">
                <h3>Patients consultés</h3>
                <div class="stat-value"><?= $patientsConsultes ?></div>
                <p>Nombre de consultations réalisées</p>
            </div>

            <!-- Carte pour le nombre de rendez-vous annulés. -->
            <div class="stat-card">
                <h3>Rendez-vous annulés</h3>
                <div class="stat-value"><?= $rdvAnnules ?></div>
                <p>Nombre d'annulations ce mois</p>
            </div>

            <!-- Carte pour le nombre de rendez-vous en retard. -->
            <div class="stat-card">
                <h3>Rendez-vous en retard</h3>
                <div class="stat-value"><?= $rdvRetard ?></div>
                <p>Patients arrivés en retard</p>
            </div>

            <!-- Carte pour la durée moyenne des consultations. -->
            <div class="stat-card">
                <h3>Durée moyenne des consultations</h3>
                <div class="stat-value"><?= $dureeMoyenne ?> min</div>
                <p>Temps moyen par consultation</p>
            </div>

            <!-- Carte pour la note moyenne des avis. -->
            <div class="stat-card">
                <h3>Note moyenne des avis</h3>
                <div class="stat-value"><?= $noteMoyenne ?>/5</div>
                <p>Satisfaction des patients</p>
            </div>

            <!-- Carte pour le délai moyen de prise de rendez-vous. -->
            <div class="stat-card">
                <h3>Délai moyen de prise de RDV</h3>
                <div class="stat-value"><?= $delaiMoyen ?> jours</div>
                <p>Temps entre demande et consultation</p>
            </div>
        </div>

        <!-- =============================================
             SECTION DES GRAPHIQUES
             ============================================= -->
        <!-- Conteneur pour le graphique des statistiques. -->
        <div class="charts">
            <div class="chart-container">
                <h3>Statistiques des rendez-vous (mois en cours)</h3>
                <!-- Canvas pour le graphique Chart.js. -->
                <canvas id="rdvStatsChart"></canvas>
            </div>
        </div>

        <!-- =============================================
             SECTION DES COMMENTAIRES DES PATIENTS
             ============================================= -->
        <!-- Conteneur pour les mots-clés des retours patients. -->
        <div class="comments">
            <h3>Mots-clés des retours patients (ce mois)</h3>
            <div class="comment-keywords">
                <!-- Liste des mots-clés avec leurs compteurs. -->
                <span class="keyword">Rendez-vous rapide et efficace <span class="keyword-count">42</span></span>
                <span class="keyword">Médecin à l'écoute <span class="keyword-count">38</span></span>
                <span class="keyword">Attente un peu longue <span class="keyword-count">25</span></span>
                <span class="keyword">Accueil chaleureux <span class="keyword-count">30</span></span>
                <span class="keyword">Rendez-vous lent <span class="keyword-count">12</span></span>
                <span class="keyword">Manque de ponctualité <span class="keyword-count">18</span></span>
                <span class="keyword">Explications claires <span class="keyword-count">28</span></span>
            </div>
        </div>

        <!-- =============================================
             BOUTON DE CONNEXION
             ============================================= -->
        <!-- Bouton pour ouvrir la modale de connexion. -->
        <button class="login-btn" onclick="document.getElementById('loginModal').style.display='block'">
            Se connecter
        </button>

        <!-- =============================================
             MODALE DE CONNEXION
             ============================================= -->
        <!-- Fenêtre modale contenant le formulaire de connexion. -->
        <div id="loginModal" class="login-modal">
            <h2>Connexion</h2>
            <!-- Formulaire de connexion envoyé à login.php avec la méthode POST. -->
            <form action="login.php" method="post">
                <!-- Sélection du rôle de l'utilisateur. -->
                <select name="role" required>
                    <option value="">Sélectionnez votre rôle</option>
                    <option value="secretaire">Secrétaire</option>
                    <option value="medecin">Médecin</option>
                    <option value="administrateur">Administrateur</option>
                </select>

                <!-- Champ pour le nom d'utilisateur. -->
                <input name="username" type="text" placeholder="Nom d'utilisateur" required>

                <!-- Champ pour le mot de passe. -->
                <input name="password" type="password" placeholder="Mot de passe" required>

                <!-- Bouton de soumission du formulaire. -->
                <button type="submit">Se connecter</button>
            </form>
        </div>
    </div>

    <!-- =============================================
         SCRIPT POUR LE GRAPHIQUE ET LA MODALE
         ============================================= -->
    <script>
        // =============================================
        // INITIALISATION DU GRAPHIQUE CHART.JS
        // =============================================

        // Récupère le contexte du canvas pour le graphique.
        const ctx = document.getElementById('rdvStatsChart').getContext('2d');

        // =============================================
        // DONNÉES DU GRAPHIQUE
        // =============================================
        // Définit les données à afficher dans le graphique.
        const data = {
            // Étiquettes pour l'axe X (catégories).
            labels: [
                'Rendez-vous',
                'Patients consultés',
                'Annulations',
                'Retards',
                'Durée moyenne',
                'Note moyenne',
                'Délai moyen'
            ],
            // Jeu de données pour le graphique.
            datasets: [{
                label: 'Statistiques', // Légende du jeu de données.
                // Données dynamiques injectées depuis PHP.
                data: [
                    <?= $totalRdv ?>,          // Rendez-vous du mois
                    <?= $patientsConsultes ?>, // Patients consultés
                    <?= $rdvAnnules ?>,        // Rendez-vous annulés
                    <?= $rdvRetard ?>,         // Rendez-vous en retard
                    <?= $dureeMoyenne ?>,      // Durée moyenne
                    <?= $noteMoyenne ?>,       // Note moyenne
                    <?= $delaiMoyen ?>         // Délai moyen
                ],
                // Couleurs de fond pour chaque barre du graphique.
                backgroundColor: [
                    'rgba(255, 99, 132, 1)',   // Rouge
                    'rgba(54, 162, 235, 1)',   // Bleu
                    'rgba(255, 206, 86, 1)',  // Jaune
                    'rgba(75, 192, 192, 1)',  // Vert
                    'rgba(153, 102, 255, 1)', // Violet
                    'rgba(255, 159, 64, 1)',  // Orange
                    'rgba(201, 203, 207, 1)'  // Gris
                ],
                borderWidth: 1 // Épaisseur de la bordure des barres.
            }]
        };

        // =============================================
        // CONFIGURATION DU GRAPHIQUE
        // =============================================
        // Définit les options de configuration du graphique.
        const config = {
            type: 'bar', // Type de graphique : barres.
            data: data, // Données à afficher.
            options: {
                responsive: true, // Le graphique s'adapte à la taille de l'écran.
                plugins: {
                    legend: { display: false }, // Masque la légende.
                    title: {
                        display: true, // Affiche le titre.
                        text: 'Statistiques détaillées des rendez-vous', // Texte du titre.
                        font: { size: 16 } // Taille de la police du titre.
                    },
                    tooltip: {
                        // Personnalisation des infobulles (tooltips) affichées au survol.
                        callbacks: {
                            label: function(context) {
                                let label = context.label || '';
                                if (label) label += ': ';
                                if (context.parsed.y !== null) {
                                    label += context.parsed.y;
                                    // Ajoute "min" pour la durée moyenne (index 4).
                                    if (context.dataIndex === 4) label += ' min';
                                }
                                return label;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true, // L'axe Y commence à 0.
                        title: { display: true, text: 'Nombre / Durée' } // Titre de l'axe Y.
                    },
                    x: {
                        title: { display: true, text: 'Catégories' } // Titre de l'axe X.
                    }
                }
            }
        };

        // Crée une nouvelle instance de Chart avec le contexte, les données et la configuration.
        new Chart(ctx, config);

        // =============================================
        // GESTION DE LA MODALE DE CONNEXION
        // =============================================
        // Ferme la modale si l'utilisateur clique en dehors de celle-ci.
        window.onclick = function(event) {
            const modal = document.getElementById('loginModal');
            if (event.target == modal) {
                modal.style.display = 'none'; // Masque la modale.
            }
        };
    </script>
</body>
</html>
