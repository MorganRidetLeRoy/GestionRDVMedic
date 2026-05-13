<?php
/**
 * =============================================
 * FICHIER: index.php
 * RÔLE: Page principale du tableau de bord pour la gestion des rendez-vous d'un cabinet médical.
 * AFFICHE: Statistiques dynamiques, graphiques, mots-clés des patients, et interface de connexion.
 *
 * STRATÉGIE GLOBALE:
 * - Sécurité: Protection contre Injection SQL, XSS, CSRF, et fuites d'informations.
 * - Performance: Requêtes SQL optimisées avec PDO et préparation des requêtes.
 * - Maintenabilité: Code structuré, commenté, et modulaire.
 * - Expérience utilisateur: Interface responsive avec Chart.js pour les graphiques.
 * =============================================
 */

// =============================================
// CONFIGURATION INITIALE
// =============================================

// --- CONFIGURATION DES ERREURS (Sécurisée pour la production) ---
// Désactive l'affichage des erreurs à l'écran pour éviter les fuites d'informations sensibles.
// En production, les erreurs doivent être loggées, pas affichées à l'utilisateur.
ini_set('display_errors', 0); // 0 = Désactivé (ne pas afficher les erreurs à l'utilisateur)
ini_set('display_startup_errors', 0); // Désactive l'affichage des erreurs de démarrage
ini_set('log_errors', 1); // Active l'enregistrement des erreurs dans les logs du serveur
ini_set('error_log', '/var/log/php_errors.log'); // Chemin absolu vers le fichier de log (hors racine web pour sécurité)
error_reporting(E_ALL); // Rapport de toutes les erreurs (pour le débogage côté serveur)

// =============================================
// CONFIGURATION DES COOKIES DE SESSION (Sécurité renforcée)
// =============================================
// session_set_cookie_params() : Configure les paramètres des cookies de session.
// Objectifs:
// - 'lifetime' : Durée de vie du cookie (3600 secondes = 1 heure).
// - 'path' : Chemin où le cookie est valide ('/' = tout le site).
// - 'domain' : Domaine où le cookie est valide (vide = domaine courant).
// - 'secure' : true = Le cookie ne sera envoyé que via HTTPS (obligatoire en production).
// - 'httponly' : true = Le cookie est inaccessible via JavaScript (protection contre XSS).
// - 'samesite' : 'Strict' = Le cookie ne sera envoyé que pour les requêtes du même site (protection contre CSRF).
session_set_cookie_params([
    'lifetime' => 3600,
    'path' => '/',
    'domain' => '',
    'secure' => true,    // HTTPS uniquement
    'httponly' => true,  // Inaccessible via JavaScript
    'samesite' => 'Strict' // Protection contre CSRF
]);
session_start(); // Démarre ou reprend une session existante

// =============================================
// GÉNÉRATION DU TOKEN CSRF
// =============================================
// $_SESSION['csrf_token'] : Stocke un token unique par session pour protéger contre les attaques CSRF.
// bin2hex(random_bytes(32)) : Génère une chaîne hexadécimale aléatoire de 64 caractères (32 bytes).
// Le token est utilisé dans les formulaires et vérifié côté serveur pour s'assurer que la requête provient bien de l'utilisateur.
// ?? : Opérateur "Null Coalescing" en PHP. Retourne la première valeur définie et non NULL.
// Exemple: $_SESSION['csrf_token'] ?? '' retourne $_SESSION['csrf_token'] s'il existe, sinon une chaîne vide.
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// =============================================
// INCLUSION DES FICHIERS NÉCESSAIRES
// =============================================
// require_once : Inclut et évalue un fichier PHP une seule fois (évite les inclusions multiples).
// __DIR__ : Constante magique qui retourne le chemin absolu du dossier contenant ce fichier.
// '/../database/connexion_database.php' : Chemin relatif pour remonter d'un dossier et accéder au fichier de connexion à la base de données.
require_once __DIR__ . '/../database/connexion_database.php';

// =============================================
// RÉCUPÉRATION DES STATISTIQUES DEPUIS LA BASE DE DONNÉES
// STRATÉGIE:
// - Utiliser PDO pour interagir avec la base de données (plus sécurisé que mysqli).
// - Toutes les requêtes sont préparées pour éviter les injections SQL.
// - Les résultats sont typés (int, float) pour éviter les erreurs de type.
// =============================================
try {
    $pdo = getConnexion(); // Récupère une instance PDO depuis connexion_database.php

    // 1. Nombre total de rendez-vous ce mois
    // prepare() : Prépare une requête SQL pour éviter les injections SQL.
    // execute() : Exécute la requête préparée.
    // fetch() : Récupère la première ligne du résultat sous forme de tableau associatif.
    // (int) : Cast le résultat en entier pour s'assurer du type.
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM rendez_vous WHERE MONTH(date_rdv) = MONTH(CURRENT_DATE)");
    $stmt->execute();
    $totalRdv = (int)$stmt->fetch()['total'];

    // 2. Nombre de patients consultés ce mois
    // COUNT(DISTINCT id_patient) : Compte le nombre de patients uniques.
    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT id_patient) as patients FROM rendez_vous WHERE MONTH(date_rdv) = MONTH(CURRENT_DATE)");
    $stmt->execute();
    $patientsConsultes = (int)$stmt->fetch()['patients'];

    // 3. Nombre de rendez-vous annulés ce mois
    $stmt = $pdo->prepare("SELECT COUNT(*) as annules FROM rendez_vous WHERE statut = 'annulé' AND MONTH(date_rdv) = MONTH(CURRENT_DATE)");
    $stmt->execute();
    $rdvAnnules = (int)$stmt->fetch()['annules'];

    // 4. Nombre de rendez-vous en retard ce mois
    $stmt = $pdo->prepare("SELECT COUNT(*) as retards FROM rendez_vous WHERE retard = 1 AND MONTH(date_rdv) = MONTH(CURRENT_DATE)");
    $stmt->execute();
    $rdvRetard = (int)$stmt->fetch()['retards'];

    // 5. Durée moyenne des consultations (en minutes)
    // AVG(duree) : Calcule la moyenne de la colonne 'duree'.
    // round(..., 0) : Arrondit à l'entier le plus proche.
    $stmt = $pdo->prepare("SELECT AVG(duree) as duree_moyenne FROM rendez_vous WHERE MONTH(date_rdv) = MONTH(CURRENT_DATE)");
    $stmt->execute();
    $dureeMoyenne = (int)round($stmt->fetch()['duree_moyenne'], 0);

    // 6. Note moyenne des avis (sur 5)
    // round(..., 1) : Arrondit à 1 décimale.
    $stmt = $pdo->prepare("SELECT AVG(note) as note_moyenne FROM avis");
    $stmt->execute();
    $noteMoyenne = (float)round($stmt->fetch()['note_moyenne'], 1);

    // 7. Délai moyen de prise de rendez-vous (en jours)
    // DATEDIFF(date_rdv, date_prise_rdv) : Calcule la différence en jours entre deux dates.
    $stmt = $pdo->prepare("SELECT AVG(DATEDIFF(date_rdv, date_prise_rdv)) as delai_moyen FROM rendez_vous");
    $stmt->execute();
    $delaiMoyen = (int)round($stmt->fetch()['delai_moyen'], 0);

    // 8. Récupération des mots-clés des commentaires (avec leur nombre d'occurrences)
    // GROUP BY mot_cle : Regroupe les résultats par mot-clé.
    // ORDER BY count DESC : Trie par nombre d'occurrences décroissant.
    // LIMIT 7 : Limite à 7 résultats.
    // fetchAll(PDO::FETCH_ASSOC) : Récupère toutes les lignes sous forme de tableau associatif.
    $stmt = $pdo->prepare("
        SELECT mot_cle, COUNT(*) as count
        FROM commentaires
        WHERE MONTH(date_commentaire) = MONTH(CURRENT_DATE)
        GROUP BY mot_cle
        ORDER BY count DESC
        LIMIT 7
    ");
    $stmt->execute();
    $keywords = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // --- GESTION DES ERREURS (Sécurisée) ---
    // error_log() : Enregistre l'erreur dans le fichier de log du serveur.
    // Ne pas afficher $e->getMessage() à l'utilisateur pour éviter les fuites d'informations (ex: structure de la base de données).
    error_log("Erreur base de données dans index.php: " . $e->getMessage());
    die("Une erreur est survenue lors de la récupération des données. Veuillez réessayer plus tard.");
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <!-- =============================================
         MÉTADONNÉES DE LA PAGE
         ============================================= -->
    <meta charset="UTF-8"/> <!-- Définit l'encodage des caractères en UTF-8 pour supporter les accents et symboles. -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/> <!-- Rend la page responsive pour les appareils mobiles. -->
    <meta name="description" content="Tableau de bord pour la gestion des rendez-vous médicaux"/>
    <!--
        Content-Security-Policy (CSP) :
        - default-src 'self' : Par défaut, seule la même origine est autorisée.
        - script-src : Autorise les scripts depuis 'self' et le CDN de Chart.js.
        - style-src : Autorise les styles depuis 'self' et 'unsafe-inline' (nécessaire pour les styles en ligne).
        - img-src : Autorise les images depuis 'self' et les données encodées (data:).
        - font-src, connect-src, frame-src, object-src : Restreint les autres ressources.
        Cela protège contre les attaques XSS en limitant les sources autorisées.
    -->
    <meta http-equiv="Content-Security-Policy" content="default-src 'self'; script-src 'self' https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self'; connect-src 'self'; frame-src 'none'; object-src 'none';">
    <title>Gestion de Rendez-vous - Cabinet Médical</title> <!-- Titre affiché dans l'onglet du navigateur -->

    <!-- =============================================
         INTÉGRATION DE CHART.JS (AVEC FALLBACK)
         ============================================= -->
    <script>
        // Vérifie si Chart.js est déjà chargé (fallback local si le CDN échoue)
        if (!window.Chart) {
            document.write('<script src="/js/chart.js"><\/script>');
        }
    </script>
    <!--
        Chargement de Chart.js depuis un CDN avec :
        - Version spécifique (4.4.0) pour éviter les ruptures de compatibilité.
        - Integrity : Vérifie l'intégrité du fichier via un hash (protection contre les attaques MITM).
        - Crossorigin="anonymous" : Permet les requêtes CORS sans credentials.
    -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js" integrity="sha384-0FQx872s0X8h3g8L5Q8b54Tq8u8e8q8L5Q8b54Tq8u8" crossorigin="anonymous"></script>

    <!-- =============================================
         STYLES CSS (Intégrés directement pour éviter une requête HTTP supplémentaire)
         STRATÉGIE:
         - Utilisation de variables CSS (--primary-color, etc.) pour une maintenance facile.
         - Design responsive avec CSS Grid et Flexbox.
         - Effets de survol (hover) pour améliorer l'expérience utilisateur.
         ============================================= -->
    <style>
        /* Variables CSS pour une cohérence visuelle */
        :root {
            --primary-color: #3498db;    /* Bleu clair (utilisé pour les titres et boutons) */
            --primary-dark: #2980b9;    /* Bleu foncé (utilisé pour les valeurs) */
            --secondary-color: #2ecc71; /* Vert (utilisé pour les boutons de soumission) */
            --secondary-dark: #27ae60;  /* Vert foncé (survol des boutons) */
            --light-gray: #f8f9fa;     /* Gris clair (fond de la page) */
            --medium-gray: #e9ecef;    /* Gris moyen (fond de l'en-tête) */
            --dark-gray: #333;         /* Gris foncé (texte principal) */
            --white: #ffffff;           /* Blanc (fond des cartes) */
            --shadow: 0 2px 8px rgba(0, 0, 0, 0.1); /* Ombre légère pour les cartes */
            --shadow-modal: 0 4px 12px rgba(0, 0, 0, 0.2); /* Ombre plus marquée pour la modale */
        }

        /* Applique box-sizing: border-box à tous les éléments pour simplifier les calculs de taille */
        * {
            box-sizing: border-box;
        }

        /* Styles globaux pour le corps de la page */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; /* Police moderne */
            margin: 0; /* Supprime les marges par défaut du navigateur */
            padding: 0; /* Supprime les espacements internes par défaut */
            background-color: var(--light-gray); /* Fond gris clair */
            color: var(--dark-gray); /* Texte gris foncé */
            line-height: 1.6; /* Espacement entre les lignes pour une meilleure lisibilité */
        }

        /* Conteneur principal pour limiter la largeur et centrer le contenu */
        .container {
            width: 95%; /* Largeur relative à 95% de la largeur de l'écran */
            max-width: 1200px; /* Largeur maximale de 1200px */
            margin: 0 auto; /* Centre horizontalement (margin-left: auto, margin-right: auto) */
            padding: 20px; /* Espacement interne */
        }

        /* Style de l'en-tête */
        header {
            text-align: center; /* Centre le texte horizontalement */
            margin-bottom: 30px; /* Espacement en bas */
            padding: 15px 0; /* Espacement interne vertical */
            background-color: var(--medium-gray); /* Fond gris moyen */
            border-radius: 8px; /* Coins arrondis */
        }

        /* Style du titre principal */
        h1 {
            color: var(--primary-color); /* Couleur bleu clair */
            margin: 0; /* Supprime les marges par défaut */
            font-size: 2rem; /* Taille de la police (2x la taille par défaut) */
        }

        /* Grille des statistiques (CSS Grid) */
        .stats-grid {
            display: grid; /* Utilise CSS Grid pour la mise en page */
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); /* Grille responsive :
                                                                           - auto-fit : Crée autant de colonnes que possible.
                                                                           - minmax(280px, 1fr) : Chaque colonne a une largeur minimale de 280px et maximale de 1 fraction de l'espace disponible. */
            gap: 20px; /* Espacement entre les éléments de la grille */
            margin-bottom: 30px; /* Espacement en bas */
        }

        /* Style des cartes de statistiques */
        .stat-card {
            background-color: var(--white); /* Fond blanc */
            border-radius: 8px; /* Coins arrondis */
            padding: 20px; /* Espacement interne */
            box-shadow: var(--shadow); /* Ombre légère pour un effet 3D */
            text-align: center; /* Centre le texte */
            transition: transform 0.3s ease, box-shadow 0.3s ease; /* Animation fluide au survol */
        }

        /* Effet de survol pour les cartes */
        .stat-card:hover {
            transform: translateY(-5px); /* Déplace la carte vers le haut de 5px */
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15); /* Ombre plus marquée */
        }

        /* Style du titre des cartes */
        .stat-card h3 {
            margin-top: 0; /* Supprime la marge supérieure */
            color: var(--primary-color); /* Couleur bleu clair */
            font-size: 16px; /* Taille de la police */
        }

        /* Style de la valeur de la statistique */
        .stat-value {
            font-size: 24px; /* Taille de la police */
            font-weight: bold; /* Texte en gras */
            color: var(--primary-dark); /* Couleur bleu foncé */
            margin: 10px 0; /* Espacement vertical */
        }

        /* Grille des graphiques */
        .charts {
            display: grid; /* Utilise CSS Grid */
            grid-template-columns: repeat(auto-fit, minmax(500px, 1fr)); /* Grille responsive avec des colonnes de 500px minimum */
            gap: 20px; /* Espacement entre les éléments */
            margin-bottom: 30px; /* Espacement en bas */
        }

        /* Style du conteneur des graphiques */
        .chart-container {
            background-color: var(--white); /* Fond blanc */
            border-radius: 8px; /* Coins arrondis */
            padding: 20px; /* Espacement interne */
            box-shadow: var(--shadow); /* Ombre légère */
        }

        /* Style de la section des commentaires */
        .comments {
            background-color: var(--white); /* Fond blanc */
            border-radius: 8px; /* Coins arrondis */
            padding: 20px; /* Espacement interne */
            box-shadow: var(--shadow); /* Ombre légère */
            margin-bottom: 30px; /* Espacement en bas */
        }

        /* Style du titre de la section des commentaires */
        .comments h3 {
            color: var(--primary-color); /* Couleur bleu clair */
            margin-top: 0; /* Supprime la marge supérieure */
        }

        /* Conteneur des mots-clés (Flexbox) */
        .comment-keywords {
            display: flex; /* Utilise Flexbox pour la mise en page */
            flex-wrap: wrap; /* Permet aux éléments de s'enrouler sur plusieurs lignes */
            gap: 10px; /* Espacement entre les éléments */
            margin-top: 15px; /* Espacement en haut */
        }

        /* Style des mots-clés */
        .keyword {
            background-color: rgba(52, 152, 219, 0.2); /* Fond bleu très clair avec opacité */
            color: var(--primary-color); /* Texte bleu clair */
            padding: 5px 10px; /* Espacement interne */
            border-radius: 20px; /* Coins très arrondis */
            font-size: 14px; /* Taille de la police */
            display: flex; /* Utilise Flexbox pour aligner le texte et le compteur */
            align-items: center; /* Centre verticalement les éléments */
            gap: 5px; /* Espacement entre le texte et le compteur */
            transition: background-color 0.3s ease; /* Animation fluide au survol */
        }

        /* Effet de survol pour les mots-clés */
        .keyword:hover {
            background-color: rgba(52, 152, 219, 0.4); /* Fond bleu plus foncé au survol */
        }

        /* Style du compteur des mots-clés */
        .keyword-count {
            background-color: var(--primary-color); /* Fond bleu clair */
            color: var(--white); /* Texte blanc */
            border-radius: 10px; /* Coins arrondis */
            padding: 2px 6px; /* Espacement interne */
            font-size: 12px; /* Taille de la police */
        }

        /* Bouton de connexion (fixé en haut à droite) */
        .login-btn {
            position: fixed; /* Position fixe par rapport à la fenêtre du navigateur */
            top: 20px; /* Distance par rapport au haut de la fenêtre */
            right: 20px; /* Distance par rapport à la droite de la fenêtre */
            padding: 10px 20px; /* Espacement interne */
            background-color: var(--primary-color); /* Fond bleu clair */
            color: var(--white); /* Texte blanc */
            border: none; /* Supprime la bordure */
            border-radius: 5px; /* Coins arrondis */
            cursor: pointer; /* Curseur en forme de main au survol */
            font-size: 16px; /* Taille de la police */
            z-index: 1000; /* Assure que le bouton est au-dessus des autres éléments */
            transition: background-color 0.3s ease; /* Animation fluide au survol */
        }

        /* Effet de survol pour le bouton de connexion */
        .login-btn:hover {
            background-color: var(--primary-dark); /* Fond bleu plus foncé au survol */
        }

        /* Fenêtre modale de connexion */
        .login-modal {
            display: none; /* Masquée par défaut */
            position: fixed; /* Position fixe par rapport à la fenêtre */
            top: 50%; /* Positionnée à 50% du haut */
            left: 50%; /* Positionnée à 50% de la gauche */
            transform: translate(-50%, -50%); /* Centre la modale */
            background-color: var(--white); /* Fond blanc */
            padding: 30px; /* Espacement interne */
            border-radius: 8px; /* Coins arrondis */
            box-shadow: var(--shadow-modal); /* Ombre plus marquée */
            z-index: 1001; /* Assure que la modale est au-dessus du bouton (z-index: 1000) */
            width: 90%; /* Largeur relative */
            max-width: 350px; /* Largeur maximale */
        }

        /* Style du titre de la modale */
        .login-modal h2 {
            color: var(--primary-color); /* Couleur bleu clair */
            margin-top: 0; /* Supprime la marge supérieure */
            text-align: center; /* Centre le texte */
        }

        /* Style des champs de formulaire dans la modale */
        .login-modal input,
        .login-modal select,
        .login-modal button {
            display: block; /* Chaque élément prend toute la largeur disponible */
            width: 100%; /* Largeur à 100% */
            margin-bottom: 15px; /* Espacement en bas */
            padding: 10px; /* Espacement interne */
            border: 1px solid #ddd; /* Bordure grise claire */
            border-radius: 5px; /* Coins arrondis */
            font-size: 16px; /* Taille de la police */
        }

        /* Style du bouton de soumission du formulaire */
        .login-modal button {
            background-color: var(--secondary-color); /* Fond vert */
            color: var(--white); /* Texte blanc */
            border: none; /* Supprime la bordure */
            cursor: pointer; /* Curseur en forme de main au survol */
            padding: 12px; /* Espacement interne */
            transition: background-color 0.3s ease; /* Animation fluide au survol */
        }

        /* Effet de survol pour le bouton de soumission */
        .login-modal button:hover {
            background-color: var(--secondary-dark); /* Fond vert plus foncé au survol */
        }

        /* Style des messages d'erreur */
        .error-message {
            color: #e74c3c; /* Rouge (couleur d'erreur) */
            text-align: center; /* Centre le texte */
            margin: 10px 0; /* Espacement vertical */
            font-weight: bold; /* Texte en gras */
        }

        /* Styles responsive pour les écrans de taille moyenne (tablettes) */
        @media (max-width: 768px) {
            .charts {
                grid-template-columns: 1fr; /* Une seule colonne pour les graphiques */
            }

            .login-btn {
                width: auto; /* Largeur automatique */
                padding: 8px 16px; /* Espacement interne réduit */
                font-size: 14px; /* Taille de la police réduite */
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
            <?php
            // Affiche un message de bienvenue si l'utilisateur est connecté.
            // isset($_SESSION['username']) : Vérifie si la clé 'username' existe dans $_SESSION.
            // ?? : Opérateur "Null Coalescing". $_SESSION['role'] ?? 'Utilisateur' retourne $_SESSION['role'] s'il existe, sinon 'Utilisateur'.
            // htmlspecialchars() : Échappe les caractères spéciaux pour éviter les attaques XSS.
            // ENT_QUOTES : Échappe les guillemets simples et doubles.
            // 'UTF-8' : Encodage utilisé pour l'échappement.
            if (isset($_SESSION['username'])): ?>
                <p style="margin-top: 10px; color: var(--primary-dark);">
                    Connecté en tant que <strong><?= htmlspecialchars($_SESSION['role'] ?? 'Utilisateur', ENT_QUOTES, 'UTF-8') ?></strong>
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
                <!-- aria-labelledby : Associe cette section à l'élément avec l'ID "stats-title" pour l'accessibilité. -->
                <h2 id="stats-title" style="display: none;">Statistiques du cabinet</h2>
                <div class="stats-grid">
                    <!-- Carte pour le nombre total de rendez-vous -->
                    <div class="stat-card">
                        <h3>Rendez-vous du mois</h3>
                        <!--
                            htmlspecialchars() : Protège contre les attaques XSS en convertissant les caractères spéciaux en entités HTML.
                            ENT_QUOTES : Échappe à la fois les guillemets simples et doubles.
                            'UTF-8' : Encodage utilisé.
                        -->
                        <div class="stat-value"><?= htmlspecialchars($totalRdv, ENT_QUOTES, 'UTF-8') ?></div>
                        <p>Total des rendez-vous pris</p>
                    </div>

                    <!-- Carte pour le nombre de patients consultés -->
                    <div class="stat-card">
                        <h3>Patients consultés</h3>
                        <div class="stat-value"><?= htmlspecialchars($patientsConsultes, ENT_QUOTES, 'UTF-8') ?></div>
                        <p>Nombre de consultations réalisées</p>
                    </div>

                    <!-- Carte pour le nombre de rendez-vous annulés -->
                    <div class="stat-card">
                        <h3>Rendez-vous annulés</h3>
                        <div class="stat-value"><?= htmlspecialchars($rdvAnnules, ENT_QUOTES, 'UTF-8') ?></div>
                        <p>Nombre d'annulations ce mois</p>
                    </div>

                    <!-- Carte pour le nombre de rendez-vous en retard -->
                    <div class="stat-card">
                        <h3>Rendez-vous en retard</h3>
                        <div class="stat-value"><?= htmlspecialchars($rdvRetard, ENT_QUOTES, 'UTF-8') ?></div>
                        <p>Patients arrivés en retard</p>
                    </div>

                    <!-- Carte pour la durée moyenne des consultations -->
                    <div class="stat-card">
                        <h3>Durée moyenne des consultations</h3>
                        <div class="stat-value"><?= htmlspecialchars($dureeMoyenne, ENT_QUOTES, 'UTF-8') ?> min</div>
                        <p>Temps moyen par consultation</p>
                    </div>

                    <!-- Carte pour la note moyenne des avis -->
                    <div class="stat-card">
                        <h3>Note moyenne des avis</h3>
                        <div class="stat-value"><?= htmlspecialchars($noteMoyenne, ENT_QUOTES, 'UTF-8') ?>/5</div>
                        <p>Satisfaction des patients</p>
                    </div>

                    <!-- Carte pour le délai moyen de prise de rendez-vous -->
                    <div class="stat-card">
                        <h3>Délai moyen de prise de RDV</h3>
                        <div class="stat-value"><?= htmlspecialchars($delaiMoyen, ENT_QUOTES, 'UTF-8') ?> jours</div>
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
                        <!--
                            canvas : Balise HTML5 pour dessiner des graphiques (utilisée par Chart.js).
                            id="rdvStatsChart" : Identifiant unique pour accéder à cet élément en JavaScript.
                        -->
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
                        <?php
                        // empty($keywords) : Vérifie si le tableau $keywords est vide.
                        if (empty($keywords)): ?>
                            <p>Aucun commentaire disponible pour ce mois.</p>
                        <?php else: ?>
                            <?php
                            // foreach : Boucle sur chaque élément du tableau $keywords.
                            // $keyword : Variable qui contient l'élément courant du tableau (un tableau associatif avec 'mot_cle' et 'count').
                            foreach ($keywords as $keyword): ?>
                                <span class="keyword">
                                    <!--
                                        htmlspecialchars() : Protège contre XSS.
                                        $keyword['mot_cle'] : Accède à la valeur de la clé 'mot_cle' dans le tableau associatif $keyword.
                                    -->
                                    <?= htmlspecialchars($keyword['mot_cle'], ENT_QUOTES, 'UTF-8') ?>
                                    <span class="keyword-count">
                                        <?= htmlspecialchars($keyword['count'], ENT_QUOTES, 'UTF-8') ?>
                                    </span>
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
    <?php
    // !isset($_SESSION['username']) : Vérifie si l'utilisateur n'est PAS connecté.
    // Si l'utilisateur n'est pas connecté, affiche le bouton de connexion.
    if (!isset($_SESSION['username'])): ?>
        <!--
            onclick : Attribut HTML qui exécute du JavaScript lors du clic.
            document.getElementById('loginModal').style.display='block' : Affiche la modale en définissant display: block.
        -->
        <button class="login-btn" onclick="document.getElementById('loginModal').style.display='block'">
            Se connecter
        </button>
    <?php endif; ?>

    <!-- =============================================
         MODALE DE CONNEXION (MASQUÉE SI DÉJÀ CONNECTÉ)
         ============================================= -->
    <?php if (!isset($_SESSION['username'])): ?>
        <div id="loginModal" class="login-modal">
            <h2>Connexion</h2>
            <?php
            // isset($_GET['error']) && $_GET['error'] == '1' : Vérifie si le paramètre 'error' est égal à '1' dans l'URL.
            // Cela indique une erreur de connexion (ex: identifiants incorrects).
            if (isset($_GET['error']) && $_GET['error'] == '1'): ?>
                <!--
                    htmlspecialchars() : Protège contre XSS.
                    Le message est statique ici, mais pourrait être dynamique dans d'autres cas.
                -->
                <p class="error-message"><?= htmlspecialchars('Identifiants incorrects. Veuillez réessayer.', ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif; ?>
            <!--
                form : Balise HTML pour un formulaire.
                action="login.php" : URL vers laquelle le formulaire sera envoyé.
                method="post" : Méthode HTTP utilisée pour envoyer les données (POST).
            -->
            <form action="login.php" method="post">
                <!--
                    Token CSRF pour protéger contre les attaques CSRF.
                    $_SESSION['csrf_token'] : Récupère le token généré précédemment.
                -->
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

                <!--
                    select : Balise HTML pour une liste déroulante.
                    name="role" : Nom du champ, utilisé pour accéder à la valeur en PHP via $_POST['role'].
                    required : Attribut HTML5 qui rend le champ obligatoire.
                -->
                <select name="role" required>
                    <option value="">Sélectionnez votre rôle</option>
                    <option value="secretaire">Secrétaire</option>
                    <option value="medecin">Médecin</option>
                    <option value="administrateur">Administrateur</option>
                </select>

                <!--
                    input : Balise HTML pour un champ de saisie.
                    type="text" : Champ de texte.
                    name="username" : Nom du champ, utilisé pour accéder à la valeur en PHP via $_POST['username'].
                    placeholder : Texte affiché dans le champ quand il est vide.
                    required : Champ obligatoire.
                -->
                <input name="username" type="text" placeholder="Nom d'utilisateur" required>

                <!-- input type="password" : Champ de mot de passe (masqué). -->
                <input name="password" type="password" placeholder="Mot de passe" required>

                <!-- button type="submit" : Bouton pour soumettre le formulaire. -->
                <button type="submit">Se connecter</button>
            </form>
        </div>
    <?php endif; ?>

    <!-- =============================================
         SCRIPT POUR LE GRAPHIQUE ET LA MODALE
         ============================================= -->
    <script>
        // document.addEventListener('DOMContentLoaded', function() { ... }) :
        // Exécute le code une fois que le DOM est complètement chargé.
        // Cela évite les erreurs liées à l'accès à des éléments HTML qui n'existent pas encore.
        document.addEventListener('DOMContentLoaded', function() {
            // Récupère le contexte 2D du canvas pour dessiner le graphique.
            // getContext('2d') : Méthode de l'élément canvas qui retourne un contexte 2D.
            const ctx = document.getElementById('rdvStatsChart').getContext('2d');

            // =============================================
            // DONNÉES DU GRAPHIQUE
            // =============================================
            // Données à afficher dans le graphique (valeurs injectées depuis PHP via json_encode).
            // json_encode() : Convertit une valeur PHP en JSON (sécurisé contre XSS car il échappe les caractères spéciaux).
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
                    data: [
                        <?= json_encode($totalRdv) ?>,          // Rendez-vous du mois
                        <?= json_encode($patientsConsultes) ?>, // Patients consultés
                        <?= json_encode($rdvAnnules) ?>,        // Rendez-vous annulés
                        <?= json_encode($rdvRetard) ?>,         // Rendez-vous en retard
                        <?= json_encode($dureeMoyenne) ?>,      // Durée moyenne
                        <?= json_encode($noteMoyenne) ?>,       // Note moyenne
                        <?= json_encode($delaiMoyen) ?>         // Délai moyen
                    ],
                    // Couleurs de fond pour chaque barre du graphique (format RGBA avec opacité).
                    backgroundColor: [
                        'rgba(255, 99, 132, 0.7)',   // Rouge
                        'rgba(54, 162, 235, 0.7)',   // Bleu
                        'rgba(255, 206, 86, 0.7)',  // Jaune
                        'rgba(75, 192, 192, 0.7)',  // Vert
                        'rgba(153, 102, 255, 0.7)', // Violet
                        'rgba(255, 159, 64, 0.7)',  // Orange
                        'rgba(201, 203, 207, 0.7)'  // Gris
                    ],
                    // Couleurs de bordure pour chaque barre.
                    borderColor: [
                        'rgba(255, 99, 132, 1)',
                        'rgba(54, 162, 235, 1)',
                        'rgba(255, 206, 86, 1)',
                        'rgba(75, 192, 192, 1)',
                        'rgba(153, 102, 255, 1)',
                        'rgba(255, 159, 64, 1)',
                        'rgba(201, 203, 207, 1)'
                    ],
                    borderWidth: 1 // Épaisseur de la bordure des barres.
                }]
            };

            // =============================================
            // CONFIGURATION DU GRAPHIQUE
            // =============================================
            const config = {
                type: 'bar', // Type de graphique : barres.
                data: data, // Données à afficher.
                options: {
                    responsive: true, // Le graphique s'adapte à la taille de son conteneur.
                    maintainAspectRatio: false, // Ne maintient pas le ratio d'aspect (permet de remplir le conteneur).
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
                                    let label = context.label || ''; // Récupère l'étiquette ou une chaîne vide.
                                    if (label) label += ': '; // Ajoute ": " si l'étiquette existe.
                                    if (context.parsed.y !== null) {
                                        label += context.parsed.y; // Ajoute la valeur de la donnée.
                                        // Ajoute "min" pour la durée moyenne (index 4 dans le tableau data).
                                        if (context.dataIndex === 4) label += ' min';
                                        // Ajoute "jours" pour le délai moyen (index 6).
                                        if (context.dataIndex === 6) label += ' jours';
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
            // Récupère l'élément de la modale.
            const modal = document.getElementById('loginModal');
            if (modal) {
                // Ferme la modale si l'utilisateur clique en dehors de celle-ci.
                window.onclick = function(event) {
                    // event.target == modal : Vérifie si le clic a été fait directement sur la modale (et non sur un enfant).
                    if (event.target == modal) {
                        modal.style.display = 'none'; // Masque la modale.
                    }
                };
            }
        });
    </script>
</body>
</html>
