<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(Settings::get('main_title', 'RPN')) ?> — Nouveau mot de passe</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <?= Theme::css() ?>
    <style>
        * { box-sizing:border-box; margin:0; padding:0; }
        body { font-family:'Poppins',sans-serif; min-height:100vh; display:flex; align-items:center; justify-content:center;
            padding:40px 20px; color:var(--text);
            background:radial-gradient(circle at 15% 12%, var(--glow1), transparent 42%), var(--bg-base); }
        body::before { content:""; position:fixed; top:0; left:0; right:0; height:6px; background:var(--bar); }
        .card { width:100%; max-width:420px; background:var(--card-bg); border:1px solid var(--card-border);
            border-radius:22px; padding:36px 30px; box-shadow:var(--card-shadow); }
        h1 { font-size:22px; color:var(--accent); margin-bottom:6px; }
        p.sub { font-size:13.5px; color:var(--muted); margin-bottom:20px; line-height:1.5; }
        label { display:block; font-size:13px; color:var(--muted); margin-bottom:6px; }
        input { width:100%; padding:13px 15px; border-radius:12px; border:1px solid var(--card-border);
            background:rgba(127,127,127,.08); color:var(--text); font-family:inherit; font-size:14px; margin-bottom:14px; }
        input:focus { outline:none; border-color:var(--accent); }
        button { width:100%; padding:14px; border:none; border-radius:12px; cursor:pointer; font-family:inherit;
            font-weight:700; font-size:15px; background:var(--accent); color:var(--accent-ink); }
        .msg { padding:11px 14px; border-radius:10px; font-size:13px; margin-bottom:16px; }
        .msg.err { background:rgba(230,57,70,.12); border:1px solid var(--rouge,#e63946); }
        .back { display:block; text-align:center; margin-top:16px; font-size:13px; color:var(--muted); text-decoration:none; }
    </style>
</head>
<body>
    <div class="card">
        <h1>🔒 Nouveau mot de passe</h1>

        <?php if (!empty($error)): ?><div class="msg err"><?= htmlspecialchars($error) ?></div><?php endif; ?>

        <?php if (empty($valid)): ?>
            <p class="sub">Ce lien est <b>invalide ou expiré</b>. Les liens sont valables 1 heure et à usage unique.</p>
            <a class="back" href="<?= url('forgot') ?>">↻ Refaire une demande</a>
        <?php else: ?>
            <p class="sub">Choisis un nouveau mot de passe (au moins 6 caractères).</p>
            <form method="post" action="<?= url('reset') ?>">
                <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                <label for="password">Nouveau mot de passe</label>
                <input type="password" id="password" name="password" required autofocus autocomplete="new-password" minlength="6">
                <label for="password2">Confirme le mot de passe</label>
                <input type="password" id="password2" name="password2" required autocomplete="new-password" minlength="6">
                <button type="submit">Enregistrer le nouveau mot de passe</button>
            </form>
        <?php endif; ?>
        <a class="back" href="<?= url('') ?>">← Retour à la connexion</a>
    </div>
</body>
</html>
