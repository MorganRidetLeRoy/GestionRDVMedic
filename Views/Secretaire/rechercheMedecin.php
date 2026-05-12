<?php var_dump($resultats); ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recherche médecin — Secrétariat</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=DM+Serif+Display&display=swap');
 
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
 
        :root {
            --bg:      #f5f4f0;
            --surface: #ffffff;
            --dark:    #1a1a2e;
            --accent:  #c8783a;
            --accent2: #2d6a4f;
            --muted:   #6b6b6b;
            --border:  #e0ddd8;
            --danger:  #c0392b;
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
 
        nav ul { list-style: none; display: flex; gap: 0.25rem; }
 
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
 
        nav a:hover { background: rgba(255,255,255,0.12); color: #fff; }
        nav a.active { background: var(--accent); color: #fff; }
 
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
        }
 
        .page-header p { color: var(--muted); font-size: 0.9rem; margin-top: 0.3rem; }
 
        /* ── Contenu ── */
        .content { padding: 2.5rem; max-width: 1100px; }
 
        /* ── Barre de recherche ── */
        .search-bar {
            display: flex;
            gap: 0.75rem;
            margin-bottom: 2rem;
        }
 
        .search-bar input[type="text"] {
            flex: 1;
            max-width: 420px;
            padding: 0.65rem 1rem;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-family: inherit;
            font-size: 0.95rem;
            background: var(--surface);
            color: var(--dark);
            transition: border-color 0.2s, box-shadow 0.2s;
        }
 
        .search-bar input[type="text"]:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(200,120,58,0.15);
        }
 
        .search-bar button {
            padding: 0.65rem 1.4rem;
            background: var(--dark);
            color: #fff;
            border: none;
            border-radius: 8px;
            font-family: inherit;
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.2s;
        }
 
        .search-bar button:hover { background: var(--accent); }
 
        /* ── Résultats ── */
        .result-label {
            font-size: 0.85rem;
            color: var(--muted);
            margin-bottom: 1rem;
        }
 
        .result-label strong { color: var(--dark); }
 
        .result-table-wrap {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 12px;
            overflow: hidden;
        }
 
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
        }
 
        thead th {
            background: #f9f8f5;
            padding: 0.85rem 1.1rem;
            text-align: left;
            font-weight: 600;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: var(--muted);
            border-bottom: 1px solid var(--border);
        }
 
        tbody td {
            padding: 0.9rem 1.1rem;
            border-bottom: 1px solid var(--border);
            vertical-align: middle;
        }
 
        tbody tr:last-child td { border-bottom: none; }
 
        tbody tr:hover { background: #faf9f6; }
 
        .badge {
            display: inline-block;
            padding: 0.2rem 0.6rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
        }
 
        .badge-h { background: #dbeafe; color: #1e40af; }
        .badge-f { background: #fce7f3; color: #9d174d; }
        .badge-n { background: #f3f4f6; color: #6b7280; }
 
        .btn-agenda {
            display: inline-block;
            padding: 0.35rem 0.85rem;
            background: var(--dark);
            color: #fff;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 500;
            text-decoration: none;
            transition: background 0.2s;
        }
 
        .btn-agenda:hover { background: var(--accent); }
 
        /* ── États vides / erreur ── */
        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: var(--muted);
            font-size: 0.95rem;
        }
 
        .alert-error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: var(--danger);
            padding: 0.85rem 1.1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
 
<nav>
    <span class="nav-brand">Secrétariat</span>
    <ul>
        <li><a href="?action=recherche_medecin" class="active">Agendas médecins</a></li>
        <li><a href="vueSecretaireCreationPatients.php">Création patient</a></li>
        <li><a href="vueSecretaireFichePatients.php">Fiches patients</a></li>
        <li><a href="vueSecretaire.php">Mon profil</a></li>
    </ul>
</nav>
 
<div class="page-header">
    <h1>Rechercher un médecin</h1>
    <p>Trouvez un médecin par nom, prénom ou adresse e-mail professionnelle.</p>
</div>
 
<div class="content">
 
    <?php if (!empty($erreur)) : ?>
        <div class="alert-error"><?= htmlspecialchars($erreur) ?></div>
    <?php endif; ?>
 
    <form method="GET" action="../../Routage/Routeur.php" class="search-bar">
        <input type="hidden" name="action" value="recherche_medecin">
        <input
            type="text"
            name="terme"
            placeholder="Nom, prénom, e-mail..."
            value="<?= htmlspecialchars($terme ?? '') ?>"
            required
            autofocus
        >
        <button type="submit">Rechercher</button>
    </form>
 
    <?php if (isset($resultats)) : ?>
 
        <p class="result-label">
            <?= count($resultats) ?> résultat<?= count($resultats) > 1 ? 's' : '' ?>
            pour <strong><?= htmlspecialchars($terme) ?></strong>
        </p>
 
        <?php if (empty($resultats)) : ?>
            <div class="empty-state">Aucun médecin trouvé pour cette recherche.</div>
        <?php else : ?>
            <div class="result-table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th>Prénom</th>
                            <th>Genre</th>
                            <th>E-mail professionnel</th>
                            <th>Agenda</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($resultats as $medecin) : ?>
                        <tr>
                            <td><?= htmlspecialchars($medecin['nom']) ?></td>
                            <td><?= htmlspecialchars($medecin['prenom']) ?></td>
                            <td>
                                <?php
                                    $g = strtolower($medecin['genre'] ?? '');
                                    $classe = match($g) {
                                        'h', 'homme', 'm', 'masculin' => 'badge-h',
                                        'f', 'femme', 'féminin'      => 'badge-f',
                                        default                       => 'badge-n',
                                    };
                                ?>
                                <span class="badge <?= $classe ?>">
                                    <?= htmlspecialchars($medecin['genre'] ?? 'N/A') ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($medecin['email_pro']) ?></td>
                            <td>
                                <a
                                    href="?action=agenda_medecin&id=<?= (int)$medecin['id_medecin'] ?>"
                                    class="btn-agenda"
                                >
                                    Voir l'agenda
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
 
    <?php endif; ?>
 
</div>
 
</body>
</html>
