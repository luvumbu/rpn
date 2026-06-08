<?php
/**
 * Vitrine des drapeaux des 54 pays d'Afrique. Style hérité du thème actif
 * (Theme::css()) — donc cohérent avec l'apparence courante du site.
 * Variables : $pays (code ISO => nom), $user (peut être null).
 */
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(Settings::get('main_title', 'RPN')) ?> — Pays d'Afrique</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <?= Theme::css() ?>
    <style>
        * { box-sizing:border-box; margin:0; padding:0; }
        body {
            font-family:'Poppins',sans-serif; min-height:100vh; color:var(--text); padding:40px 20px 60px;
            background:
                radial-gradient(circle at 10% 0%, var(--glow1), transparent 40%),
                radial-gradient(circle at 90% 100%, var(--glow2), transparent 42%),
                var(--bg-base);
        }
        body::before { content:""; position:fixed; top:0; left:0; right:0; height:6px; background:var(--bar); }
        .wrap { max-width:1100px; margin:0 auto; }
        .top { display:flex; justify-content:space-between; align-items:center; gap:14px; flex-wrap:wrap; margin-bottom:8px; }
        h1 { font-size:26px; } h1 span { color:var(--accent); }
        .sub { color:var(--muted); font-size:14px; margin-bottom:22px; }
        .nav a { color:var(--text); text-decoration:none; font-size:14px; padding:9px 16px; border-radius:10px; border:1px solid var(--card-border); }
        .nav a:hover { border-color:var(--accent); color:var(--accent); }

        .search { margin-bottom:22px; }
        .search input { width:100%; padding:13px 16px; border-radius:12px; border:1px solid var(--card-border);
            background:var(--card-bg); color:var(--text); font-family:inherit; font-size:15px; }
        .search input:focus { outline:none; border-color:var(--accent); }

        .grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(160px, 1fr)); gap:16px; }
        .flag { background:var(--card-bg); border:1px solid var(--card-border); border-radius:16px; overflow:hidden;
            box-shadow:var(--card-shadow); transition:transform .15s, border-color .15s; }
        .flag:hover { transform:translateY(-4px); border-color:var(--accent); }
        .flag img { width:100%; aspect-ratio:3/2; object-fit:cover; display:block; background:rgba(127,127,127,.12);
            border-bottom:1px solid var(--card-border); }
        .flag .name { padding:11px 12px; font-size:13.5px; font-weight:600; text-align:center; line-height:1.35; }
        .none { display:none; text-align:center; color:var(--muted); padding:40px; background:var(--card-bg);
            border:1px solid var(--card-border); border-radius:16px; }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="top">
            <h1>🌍 Pays d'<span>Afrique</span></h1>
            <div class="nav">
                <?php if (!empty($user)): ?>
                    <a href="<?= url('dashboard') ?>">← Tableau de bord</a>
                <?php else: ?>
                    <a href="<?= url('') ?>">← Accueil</a>
                <?php endif; ?>
            </div>
        </div>
        <p class="sub"><?= count($pays) ?> pays et leurs drapeaux.</p>

        <div class="search">
            <input type="search" id="flagSearch" placeholder="🔎 Rechercher un pays…" autocomplete="off">
        </div>

        <div class="grid" id="flagGrid">
            <?php foreach ($pays as $code => $nom): ?>
                <div class="flag" data-name="<?= htmlspecialchars(mb_strtolower($nom)) ?>">
                    <img src="https://flagcdn.com/w320/<?= $code ?>.png"
                         srcset="https://flagcdn.com/w640/<?= $code ?>.png 2x"
                         alt="Drapeau : <?= htmlspecialchars($nom) ?>" loading="lazy" width="320" height="213">
                    <div class="name"><?= htmlspecialchars($nom) ?></div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="none" id="flagNone">Aucun pays ne correspond à ta recherche.</div>
    </div>

    <script>
    (function () {
        var input = document.getElementById('flagSearch');
        var grid  = document.getElementById('flagGrid');
        var none  = document.getElementById('flagNone');
        if (!input || !grid) { return; }
        input.addEventListener('input', function () {
            var q = input.value.trim().toLowerCase();
            var shown = 0;
            grid.querySelectorAll('.flag').forEach(function (c) {
                var ok = q === '' || (c.getAttribute('data-name') || '').indexOf(q) !== -1;
                c.style.display = ok ? '' : 'none';
                if (ok) { shown++; }
            });
            none.style.display = shown === 0 ? 'block' : 'none';
        });
    })();
    </script>
</body>
</html>
