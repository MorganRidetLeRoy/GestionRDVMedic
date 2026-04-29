<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord — Secrétariat</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=DM+Serif+Display&display=swap');
 
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
 
        :root {
            --bg:       #f5f4f0;
            --surface:  #ffffff;
            --dark:     #1a1a2e;
            --accent:   #c8783a;
            --accent2:  #2d6a4f;
            --muted:    #6b6b6b;
            --border:   #e0ddd8;
        }
 
        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--bg);
            color: var(--dark);
            min-height: 100vh;
        }
 
        /* ── Navigation ── */
        nav {
            background: var(--dark);
            padding: 0 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 60px;
            position: sticky;
            top: 0;
            z-index: 100;
        }
 
        .nav-brand {
            font-family: 'DM Serif Display', serif;
            color: #fff;
            font-size: 1.1rem;
            letter-spacing: 0.02em;
        }
 
        nav ul {
            list-style: none;
            display: flex;
            gap: 0.25rem;
        }
 
        nav a {
            color: rgba(255,255,255,0.75);
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 500;
            padding: 0.4rem 0.85rem;
            border-radius: 6px;
            transition: background 0.2s, color 0.2s;
            white-space: nowrap;
        }
 
        nav a:hover, nav a.active {
            background: rgba(255,255,255,0.12);
            color: #fff;
        }
 
        nav a.active {
            background: var(--accent);
            color: #fff;
        }
 
        /* ── Header page ── */
        .page-header {
            background: var(--surface);
            border-bottom: 1px solid var(--border);
            padding: 2rem 2.5rem;
        }
 
        .page-header h1 {
            font-family: 'DM Serif Display', serif;
            font-size: 1.75rem;
            font-weight: 400;
            color: var(--dark);
        }
 
        .page-header p {
            color: var(--muted);
            font-size: 0.9rem;
            margin-top: 0.3rem;
        }
 
        /* ── Contenu ── */
        .content {
            padding: 2.5rem;
            max-width: 1100px;
        }
 
        .placeholder-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 3rem 2rem;
            text-align: center;
            color: var(--muted);
        }
 
        .placeholder-card svg {
            opacity: 0.25;
            margin-bottom: 1rem;
        }
 
        .placeholder-card p {
            font-size: 0.95rem;
        }
    </style>
</head>
<body>
 
<nav>
    <span class="nav-brand">Secrétariat</span>
    <ul>
        <li><a href="?action=recherche_medecin">Agendas médecins</a></li>
        <li><a href="vueSecretaireCreationPatients.php">Création patient</a></li>
        <li><a href="vueSecretaireFichePatients.php">Fiches patients</a></li>
        <li><a href="vueSecretaire.php" class="active">Mon profil</a></li>
    </ul>
</nav>
 
<div class="page-header">
    <h1>Bonjour <?= htmlspecialchars($_SESSION['prenom'] ?? 'Secrétaire') ?> 👋</h1>
    <p>Voici votre tableau de bord — semaine du <?= (new DateTime())->format('d/m/Y') ?></p>
</div>
 
<div class="content">
    <div class="placeholder-card">
        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
        </svg>
        <p>Votre agenda de la semaine sera affiché ici.</p>
    </div>
</div>
 
</body>
</html>
