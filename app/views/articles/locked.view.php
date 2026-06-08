<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🔒 <?= htmlspecialchars($article['title']) ?> — <?= htmlspecialchars(Settings::get('main_title', 'RPN')) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <?= Theme::css() ?>
    <style>
        * { box-sizing:border-box; margin:0; padding:0; }
        body { font-family:'Poppins',sans-serif; min-height:100vh; color:var(--text); padding:40px 20px;
            display:flex; align-items:center; justify-content:center;
            background:
                radial-gradient(circle at 15% 12%, var(--glow1), transparent 42%),
                radial-gradient(circle at 85% 85%, var(--glow2), transparent 45%),
                var(--bg-base); }
        body::before { content:""; position:fixed; top:0; left:0; right:0; height:6px; background:var(--bar); }
        .card { width:100%; max-width:420px; background:var(--card-bg); border:1px solid var(--card-border);
            border-radius:22px; box-shadow:var(--card-shadow); padding:38px 32px; text-align:center; }
        .lock { width:74px; height:74px; margin:0 auto 18px; border-radius:20px; display:flex; align-items:center;
            justify-content:center; font-size:34px; background:rgba(127,127,127,.12); color:var(--accent); }
        h1 { font-size:20px; color:var(--accent); line-height:1.3; margin-bottom:6px; }
        .sub { color:var(--muted); font-size:14px; margin-bottom:22px; line-height:1.5; }
        .err { background:rgba(230,57,70,.12); border:1px solid var(--rouge,#e63946); color:var(--text);
            padding:11px 14px; border-radius:10px; font-size:13px; margin-bottom:16px; }
        form { display:flex; flex-direction:column; gap:12px; }
        input { width:100%; padding:13px 15px; border-radius:12px; border:1px solid var(--card-border);
            background:rgba(127,127,127,.08); color:var(--text); font-family:inherit; font-size:15px; text-align:center; }
        input:focus { outline:none; border-color:var(--accent); }
        button { width:100%; padding:14px 20px; border:none; border-radius:12px; cursor:pointer; font-family:inherit;
            font-size:15px; font-weight:700; background:var(--accent); color:var(--accent-ink);
            transition:transform .15s, box-shadow .2s; }
        button:hover { transform:translateY(-2px); box-shadow:0 12px 30px rgba(0,0,0,.25); }
        .back { display:inline-block; margin-top:18px; font-size:13px; color:var(--muted); text-decoration:none;
            border:1px solid var(--card-border); padding:8px 16px; border-radius:10px; }
        .back:hover { border-color:var(--accent); color:var(--accent); }
    </style>
</head>
<body>
    <div class="card">
        <div class="lock">🔒</div>
        <h1><?= htmlspecialchars($article['title']) ?></h1>
        <p class="sub">Cet article est protégé. Saisis le <b>mot de passe</b> pour y accéder.</p>

        <?php if (!empty($error)): ?>
            <div class="err"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="post" action="<?= url('articles/unlock') ?>">
            <input type="hidden" name="id" value="<?= (int) $article['id'] ?>">
            <input type="password" name="access_password" placeholder="Mot de passe" autocomplete="off" autofocus required>
            <button type="submit">🔓 Accéder à l'article</button>
        </form>
        <a class="back" href="<?= url('articles') ?>">← Retour aux articles</a>
    </div>
</body>
</html>
