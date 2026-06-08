<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(Settings::get('main_title', 'RPN')) ?> — Inscription</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <?= Theme::css() ?>
    <style>
        * { box-sizing:border-box; margin:0; padding:0; }
        body {
            font-family:'Poppins',-apple-system,Segoe UI,Roboto,sans-serif; min-height:100vh;
            display:flex; align-items:center; justify-content:center; padding:40px 20px; color:var(--text);
            background:
                radial-gradient(circle at 15% 15%, var(--glow1), transparent 40%),
                radial-gradient(circle at 85% 80%, var(--glow2), transparent 42%),
                var(--bg-base);
        }
        body::before { content:""; position:fixed; top:0; left:0; right:0; height:6px; background:var(--bar); }
        .card { position:relative; background:var(--card-bg); backdrop-filter:blur(14px); -webkit-backdrop-filter:blur(14px);
            border:1px solid var(--card-border); padding:42px 36px; border-radius:24px; box-shadow:var(--card-shadow);
            text-align:center; width:100%; max-width:420px; }
        .logo { width:78px; height:78px; margin:0 auto 18px; border-radius:20px; display:flex; align-items:center; justify-content:center;
            font-weight:800; font-size:26px; color:var(--accent-ink); background:var(--accent); box-shadow:0 10px 30px rgba(0,0,0,.2); }
        h1 { font-size:24px; font-weight:800; margin-bottom:6px; color:var(--accent); }
        .sub { color:var(--muted); font-size:14px; margin-bottom:26px; line-height:1.5; }
        .auth-error { background:rgba(230,57,70,.12); border:1px solid var(--rouge,#e63946); color:var(--text);
            padding:11px 14px; border-radius:10px; font-size:13px; margin-bottom:16px; text-align:left; }
        .auth-form { display:flex; flex-direction:column; gap:11px; text-align:left; }
        .auth-form label { font-size:12.5px; font-weight:600; color:var(--muted); margin-bottom:-5px; }
        .auth-form input { width:100%; padding:13px 15px; border-radius:12px; border:1px solid var(--card-border);
            background:rgba(127,127,127,.08); color:var(--text); font-family:inherit; font-size:14px; }
        .auth-form input:focus { outline:none; border-color:var(--accent); }
        .auth-form .hint { font-size:11.5px; color:var(--muted); margin-top:-4px; }
        .auth-form button { width:100%; margin-top:6px; padding:14px 20px; border:none; border-radius:12px; cursor:pointer;
            font-family:inherit; font-size:15px; font-weight:700; background:var(--accent); color:var(--accent-ink);
            transition:transform .15s, box-shadow .2s; }
        .auth-form button:hover { transform:translateY(-2px); box-shadow:0 12px 30px rgba(0,0,0,.25); }
        .back-link { display:block; margin-top:18px; font-size:13.5px; color:var(--muted); text-decoration:none; }
        .back-link b { color:var(--accent); }
        .back-link:hover b { text-decoration:underline; }
    </style>
</head>
<body>
    <div class="card">
        <div class="logo"><?= htmlspecialchars(Settings::get('main_title', 'RPN')) ?></div>
        <h1>Créer un compte</h1>
        <p class="sub">Pas de compte Google ? Inscris-toi avec ton email pour rejoindre la communauté.</p>

        <?php if (!empty($error)): ?>
            <div class="auth-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form class="auth-form" method="post" action="<?= url('register') ?>">
            <label for="name">Nom complet</label>
            <input type="text" id="name" name="name" placeholder="Ton nom" value="<?= htmlspecialchars($old['name'] ?? '') ?>" required autocomplete="name">

            <label for="email">Adresse email</label>
            <input type="email" id="email" name="email" placeholder="toi@exemple.com" value="<?= htmlspecialchars($old['email'] ?? '') ?>" required autocomplete="email">

            <label for="password">Mot de passe</label>
            <input type="password" id="password" name="password" placeholder="Au moins 6 caractères" required minlength="6" autocomplete="new-password">

            <label for="password2">Confirme le mot de passe</label>
            <input type="password" id="password2" name="password2" placeholder="Retape le mot de passe" required minlength="6" autocomplete="new-password">

            <button type="submit">✅ Créer mon compte</button>
        </form>

        <a class="back-link" href="<?= url('') ?>">Déjà inscrit ? <b>Se connecter →</b></a>
    </div>
</body>
</html>
