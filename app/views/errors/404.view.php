<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 — Page introuvable</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;800&display=swap" rel="stylesheet">
    <?= Theme::css() ?>
    <style>
        * { box-sizing:border-box; margin:0; padding:0; }
        body {
            font-family:'Poppins',sans-serif; min-height:100vh; color:var(--text);
            display:flex; align-items:center; justify-content:center; padding:20px;
            background:
                radial-gradient(circle at 15% 15%, var(--glow1), transparent 40%),
                radial-gradient(circle at 85% 80%, var(--glow2), transparent 42%),
                var(--bg-base);
        }
        body::before { content:""; position:fixed; top:0; left:0; right:0; height:6px; background:var(--bar); }
        .card { background:var(--card-bg); border:1px solid var(--card-border); border-radius:24px;
            padding:48px 40px; box-shadow:var(--card-shadow); text-align:center; max-width:420px; width:100%; }
        .code { font-size:64px; font-weight:800; color:var(--accent); line-height:1; }
        h1 { font-size:20px; margin:10px 0 8px; }
        p { color:var(--muted); font-size:14px; margin-bottom:26px; }
        a { display:inline-block; background:var(--accent); color:var(--accent-ink);
            padding:12px 24px; border-radius:12px; text-decoration:none; font-weight:700; font-size:14px; }
    </style>
</head>
<body>
    <div class="card">
        <div class="code">404</div>
        <h1>Page introuvable</h1>
        <p>La page demandée n'existe pas ou a été déplacée.</p>
        <a href="<?= url('') ?>">← Retour à l'accueil</a>
    </div>
</body>
</html>
