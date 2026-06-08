<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(Settings::get('main_title', 'RPN')) ?> — Importer un projet</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <?= Theme::css() ?>
    <style>
        * { box-sizing:border-box; margin:0; padding:0; }
        body { font-family:'Poppins',sans-serif; min-height:100vh; color:var(--text); padding:40px 20px;
            display:flex; align-items:center; justify-content:center;
            background:radial-gradient(circle at 12% 0%, var(--glow1), transparent 42%),
                radial-gradient(circle at 88% 100%, var(--glow2), transparent 44%), var(--bg-base); }
        body::before { content:""; position:fixed; top:0; left:0; right:0; height:6px; background:var(--bar); }
        .card { width:100%; max-width:520px; background:var(--card-bg); border:1px solid var(--card-border);
            border-radius:20px; box-shadow:var(--card-shadow); padding:30px 30px; }
        h1 { font-size:22px; color:var(--accent); margin-bottom:8px; }
        .sub { font-size:14px; color:var(--muted); line-height:1.55; margin-bottom:20px; }
        .notice { background:rgba(42,157,74,.12); border:1px solid var(--vert,#2a9d4a); padding:12px 15px; border-radius:12px; margin-bottom:16px; font-size:14px; }
        .error { background:rgba(230,57,70,.12); border:1px solid var(--rouge,#e63946); padding:12px 15px; border-radius:12px; margin-bottom:16px; font-size:14px; }
        .drop { border:2px dashed var(--card-border); border-radius:14px; padding:22px; text-align:center; margin-bottom:16px; }
        .drop input[type=file] { width:100%; font-size:14px; color:var(--text); }
        .hint { font-size:12.5px; color:var(--muted); margin-top:10px; }
        .btn { width:100%; padding:15px; border:none; border-radius:12px; cursor:pointer; font-family:inherit;
            font-size:16px; font-weight:800; background:var(--accent); color:var(--accent-ink); }
        .btn:hover { filter:brightness(1.05); }
        .back { display:inline-block; margin-top:18px; font-size:14px; color:var(--muted); text-decoration:none;
            border:1px solid var(--card-border); padding:9px 16px; border-radius:10px; }
        .back:hover { border-color:var(--accent); color:var(--accent); }
        ul { margin:6px 0 0 18px; font-size:13px; color:var(--muted); line-height:1.6; }
    </style>
</head>
<body>
    <div class="card">
        <h1>⬆️ Importer un projet</h1>
        <p class="sub">Choisis un fichier <code>.zip</code> exporté depuis RPN. Le contenu est recréé <b>en brouillon, à ton nom</b>.</p>

        <?php if (!empty($notice)): ?><div class="notice"><?= htmlspecialchars($notice) ?></div><?php endif; ?>
        <?php if (!empty($error)): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

        <form method="post" action="<?= url('profile/import') ?>" enctype="multipart/form-data">
            <div class="drop">
                <input type="file" name="archive" accept=".zip,application/zip" required>
                <p class="hint">Tu peux importer <b>un seul projet</b> (un petit .zip) <b>ou tout</b> (un export complet). Les deux fonctionnent avec ce même bouton.</p>
            </div>
            <button class="btn" type="submit">⬆️ Importer ce fichier</button>
        </form>

        <a class="back" href="<?= url('dashboard') ?>">← Retour au tableau de bord</a>
    </div>
</body>
</html>
