<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RPN — Connexion admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <?= Theme::css() ?>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Poppins', sans-serif; min-height: 100vh; color: var(--text);
            display: flex; align-items: center; justify-content: center; padding: 20px;
            background:
                radial-gradient(circle at 15% 15%, var(--glow1), transparent 40%),
                radial-gradient(circle at 85% 80%, var(--glow2), transparent 42%),
                var(--bg-base);
        }
        body::before { content:""; position:fixed; top:0; left:0; right:0; height:6px; background: var(--bar); }
        .card {
            background: var(--card-bg); backdrop-filter: blur(14px);
            border: 1px solid var(--card-border); padding: 44px 40px; border-radius: 24px;
            box-shadow: var(--card-shadow); width: 100%; max-width: 400px;
        }
        .logo {
            width: 70px; height: 70px; margin: 0 auto 18px; border-radius: 18px;
            display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 24px;
            color: var(--accent-ink); background: var(--accent);
        }
        h1 { text-align: center; font-size: 22px; margin-bottom: 4px; color: var(--text); }
        .sub { text-align: center; color: var(--muted); font-size: 13px; margin-bottom: 26px; }
        label { display: block; font-size: 13px; margin: 0 0 6px 2px; color: var(--muted); }
        input {
            width: 100%; padding: 12px 14px; margin-bottom: 16px; border-radius: 10px;
            border: 1px solid var(--card-border); background: rgba(127,127,127,.08); color: var(--text);
            font-family: inherit; font-size: 14px;
        }
        input:focus { outline: none; border-color: var(--accent); }
        .btn {
            width: 100%; padding: 13px; border: none; border-radius: 10px; cursor: pointer;
            font-family: inherit; font-weight: 700; font-size: 15px; color: var(--accent-ink); background: var(--accent);
        }
        .btn:hover { filter: brightness(1.05); }
        input:disabled { opacity: .5; cursor: not-allowed; }
        .btn:disabled { opacity: .45; cursor: not-allowed; filter: grayscale(.4); }
        .error {
            background: rgba(230,57,70,.15); border: 1px solid rgba(230,57,70,.4);
            color: #e0566a; padding: 10px 14px; border-radius: 10px; font-size: 13px;
            margin-bottom: 18px; text-align: center;
        }
        .back { display: block; text-align: center; margin-top: 20px; color: var(--muted);
            font-size: 13px; text-decoration: none; }
        .back:hover { color: var(--accent); }
    </style>
</head>
<body>
    <?php $blocked = $blocked ?? false; ?>
    <form class="card" method="post" action="<?= url('admin/login') ?>">
        <div class="logo">RPN</div>
        <h1>Espace administrateur</h1>
        <p class="sub">Connexion avec la base de données</p>

        <?php if (!empty($error)): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <label for="login">Nom de la base (dbname)</label>
        <input type="text" id="login" name="login" placeholder="en ligne: u123..._rpm — en local: root" autofocus <?= $blocked ? 'disabled' : '' ?>>

        <label for="password">Mot de passe</label>
        <input type="password" id="password" name="password" placeholder="mot de passe de la base" <?= $blocked ? 'disabled' : '' ?>>

        <button class="btn" type="submit" <?= $blocked ? 'disabled' : '' ?>>
            <?= $blocked ? 'Accès bloqué' : 'Se connecter' ?>
        </button>
        <a class="back" href="<?= url('') ?>">← Retour à l'accueil</a>
    </form>
</body>
</html>
