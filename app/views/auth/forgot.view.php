<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(Settings::get('main_title', 'RPN')) ?> — Mot de passe oublié</title>
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
        .msg.ok { background:rgba(42,157,74,.14); border:1px solid var(--vert,#2a9d4a); }
        .msg.err { background:rgba(230,57,70,.12); border:1px solid var(--rouge,#e63946); }
        .back { display:block; text-align:center; margin-top:16px; font-size:13px; color:var(--muted); text-decoration:none; }
    </style>
</head>
<body>
    <div class="card">
        <h1>🔑 Mot de passe oublié</h1>
        <p class="sub">Saisis l'e-mail de ton compte. Si un compte existe, tu recevras un lien pour choisir un nouveau mot de passe (valable 1 heure).</p>

        <?php if (!empty($notice)): ?><div class="msg ok"><?= htmlspecialchars($notice) ?></div><?php endif; ?>
        <?php if (!empty($error)): ?><div class="msg err"><?= htmlspecialchars($error) ?></div><?php endif; ?>

        <form method="post" action="<?= url('forgot') ?>">
            <label for="email">Adresse e-mail</label>
            <input type="email" id="email" name="email" placeholder="ton@email.com" required autofocus autocomplete="email">
            <button type="submit">Envoyer le lien</button>
        </form>
        <a class="back" href="<?= url('') ?>">← Retour à la connexion</a>
    </div>
</body>
</html>
