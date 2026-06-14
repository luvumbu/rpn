<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RPN — Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <?= Theme::css() ?>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Poppins', sans-serif; min-height: 100vh; color: var(--text); padding: 40px 20px;
            background:
                radial-gradient(circle at 10% 0%, var(--glow1), transparent 40%),
                radial-gradient(circle at 90% 100%, var(--glow2), transparent 42%),
                var(--bg-base);
        }
        body::before { content:""; position:fixed; top:0; left:0; right:0; height:6px; background: var(--bar); }
        .wrap { max-width: 900px; margin: 0 auto; }
        .top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px; flex-wrap: wrap; gap: 12px; }
        h1 { font-size: 24px; }
        h1 span { color: var(--accent); }
        .nav { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 10px; }
        .nav a {
            color: var(--text); text-decoration: none; font-size: 14px; font-weight: 500;
            padding: 8px 16px; border-radius: 10px; border: 1px solid var(--card-border);
        }
        .nav a:hover { border-color: var(--accent); color: var(--accent); }
        .nav .out { background: rgba(230,57,70,.18); border-color: rgba(230,57,70,.4); }
        .cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 18px; }
        .stat {
            display: block; text-decoration: none; color: inherit;
            background: var(--card-bg); border: 1px solid var(--card-border);
            border-radius: 18px; padding: 26px; text-align: center; box-shadow: var(--card-shadow);
            transition: border-color .15s, transform .15s;
        }
        a.stat:hover { border-color: var(--accent); transform: translateY(-3px); }
        .stat .num { font-size: 40px; font-weight: 800; color: var(--accent); }
        .stat .lbl { color: var(--muted); font-size: 14px; margin-top: 4px; }
        .cta { margin-top: 28px; }
        .cta a {
            display: inline-block; background: var(--accent); color: var(--accent-ink);
            padding: 12px 24px; border-radius: 12px; text-decoration: none; font-weight: 700;
        }
        .greet { margin-bottom:24px; color:var(--muted); }
        .greet b { color: var(--accent); }
        .section-title { font-size:12px; text-transform:uppercase; letter-spacing:1.5px; color:var(--muted); margin:32px 4px 14px; }
        .app-links { display:grid; grid-template-columns:repeat(auto-fit, minmax(220px,1fr)); gap:16px; }
        .app-link { display:flex; align-items:center; gap:14px; background:var(--card-bg); border:1px solid var(--card-border);
            border-radius:16px; padding:18px 20px; text-decoration:none; color:inherit; box-shadow:var(--card-shadow);
            transition:transform .15s, border-color .15s; }
        .app-link:hover { transform:translateY(-3px); border-color:var(--accent); }
        .app-link .i { font-size:24px; width:46px; height:46px; flex:0 0 46px; display:flex; align-items:center;
            justify-content:center; border-radius:12px; background:rgba(127,127,127,.12); }
        .app-link .t b { font-size:15px; font-weight:700; display:block; }
        .app-link .t span { font-size:12.5px; color:var(--muted); }
        .admin-notice { background:rgba(42,157,74,.14); border:1px solid var(--vert); color:var(--text);
            padding:12px 16px; border-radius:12px; margin-bottom:20px; font-size:14px; }
        .danger { margin-top:30px; border:1px solid rgba(230,57,70,.55); border-radius:18px; padding:22px 24px;
            background:rgba(230,57,70,.07); }
        .danger h2 { font-size:16px; color:var(--rouge); margin-bottom:6px; }
        .danger p { font-size:13px; color:var(--muted); margin-bottom:14px; }
        .danger .row { display:flex; flex-wrap:wrap; gap:16px; }
        .danger form { display:flex; gap:8px; align-items:center; flex-wrap:wrap; }
        .danger input[type=password] { font:inherit; padding:10px 12px; border-radius:9px;
            border:1px solid var(--card-border); background:var(--card-bg); color:var(--text); max-width:170px; }
        .danger button { font:inherit; font-weight:700; cursor:pointer; border:none; border-radius:10px;
            padding:11px 18px; background:var(--rouge); color:#fff; }
        .danger button:active { transform:translateY(1px); }
        .backup { background:var(--card-bg); border:1px solid var(--card-border); border-radius:16px;
            padding:22px 24px; margin:28px 0; }
        .backup h2 { font-size:16px; color:var(--accent); margin-bottom:6px; }
        .backup p { font-size:13px; color:var(--muted); margin-bottom:14px; line-height:1.55; }
        .backup-actions { display:flex; gap:12px; flex-wrap:wrap; }
        .bk-btn { display:inline-flex; align-items:center; gap:8px; text-decoration:none; font-weight:700;
            font-size:14px; border-radius:11px; padding:12px 18px; }
        .bk-btn.export { background:var(--accent); color:var(--accent-ink); }
        .bk-btn.import { background:var(--card-bg); color:var(--text); border:1px solid var(--card-border); }
        .bk-btn:hover { filter:brightness(1.06); }
    </style>
</head>
<body>
    <div class="wrap">
        <?php view('admin/_nav', ['active' => 'dashboard']); ?>
        <div class="top">
            <h1>Tableau de bord <span>Admin</span></h1>
        </div>

        <p class="greet">Bonjour <b><?= htmlspecialchars($user['name'] ?: $user['email']) ?></b> 👋</p>

        <?php if (!empty($notice)): ?>
            <div class="admin-notice"><?= htmlspecialchars($notice) ?></div>
        <?php endif; ?>

        <div class="cards">
            <a class="stat" href="<?= url('admin/members') ?>" title="Gérer les membres"><div class="num"><?= (int) $total ?></div><div class="lbl">Membres au total →</div></a>
            <a class="stat" href="<?= url('admin/members') ?>" title="Gérer les membres"><div class="num"><?= (int) $membres ?></div><div class="lbl">Membres →</div></a>
            <a class="stat" href="<?= url('admin/members') ?>" title="Promouvoir / gérer les administrateurs"><div class="num"><?= (int) $admins ?></div><div class="lbl">Administrateurs →</div></a>
        </div>

        <p class="section-title">🎨 Apparence &amp; style</p>
        <div class="app-links">
            <a class="app-link" href="<?= url('admin/settings') ?>#p-theme">
                <span class="i">🎨</span>
                <span class="t"><b>Thème &amp; couleurs</b><span>Thème du site, palette personnalisée</span></span>
            </a>
            <a class="app-link" href="<?= url('admin/settings') ?>#p-style-global">
                <span class="i">🎛️</span>
                <span class="t"><b>Style global</b><span>Police, arrondi, ombres, animations</span></span>
            </a>
            <a class="app-link" href="<?= url('admin/settings') ?>#p-favicon">
                <span class="i">🖼️</span>
                <span class="t"><b>Favicon</b><span>Icône de l'onglet et de l'appli</span></span>
            </a>
            <a class="app-link" href="<?= url('admin/settings') ?>#p-articles">
                <span class="i">📰</span>
                <span class="t"><b>Style des articles</b><span>Taille du texte, police des articles</span></span>
            </a>
        </div>

        <?php if (!empty($isSuper)): ?>
        <section class="danger">
            <h2>⚠️ Zone de danger — tout effacer</h2>
            <p>Ces actions suppriment <b>définitivement</b> les données (irréversible). Pour confirmer, saisis le <b>mot de passe de la base de données</b>.</p>
            <div class="row">
                <form method="post" action="<?= url('admin/wipe_agenda') ?>"
                      onsubmit="return confirm('Effacer TOUT l\'agenda (créneaux, réservations, historique, notes) ? Action IRRÉVERSIBLE.');">
                    <input type="password" name="db_password" placeholder="Mot de passe BDD (vide si aucun)" autocomplete="off">
                    <button type="submit">🗑️ Effacer tout l'agenda</button>
                </form>
                <form method="post" action="<?= url('admin/wipe_articles') ?>"
                      onsubmit="return confirm('Effacer les articles (+ images, fichiers, avis, discussions) SAUF ceux protégés 🔒 ? Action IRRÉVERSIBLE.');">
                    <input type="password" name="db_password" placeholder="Mot de passe BDD (vide si aucun)" autocomplete="off">
                    <button type="submit">🗑️ Effacer les articles (sauf 🔒)</button>
                </form>
            </div>
        </section>
        <?php endif; ?>
    </div>
</body>
</html>
