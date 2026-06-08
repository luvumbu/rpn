<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(Settings::get('main_title', 'RPN')) ?> — La communauté</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <?= Theme::css() ?>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Poppins', -apple-system, Segoe UI, Roboto, sans-serif;
            min-height: 100vh; display: flex; align-items: center; justify-content: center;
            padding: 48px 20px; color: var(--text); position: relative; overflow-x: hidden;
            background:
                radial-gradient(circle at 15% 12%, var(--glow1), transparent 42%),
                radial-gradient(circle at 85% 85%, var(--glow2), transparent 45%),
                var(--bg-base);
        }
        body::before { content: ""; position: fixed; top: 0; left: 0; right: 0; height: 6px; background: var(--bar); }

        /* Mise en page : connexion + annonces côte à côte (large), empilées (mobile) */
        .shell { display: flex; flex-wrap: wrap; align-items: stretch; justify-content: center;
            gap: 26px; width: 100%; max-width: 920px; }
        .card, .announce-board { flex: 1 1 380px; max-width: 440px; }

        /* ---- Carte de connexion ---- */
        .card {
            position: relative; background: var(--card-bg);
            backdrop-filter: blur(14px); -webkit-backdrop-filter: blur(14px);
            border: 1px solid var(--card-border); padding: 42px 36px; border-radius: 24px;
            box-shadow: var(--card-shadow); text-align: center; align-self: center;
        }
        .logo { width: 80px; height: 80px; margin: 0 auto 20px; border-radius: 22px;
            display: flex; align-items: center; justify-content: center;
            font-weight: 800; font-size: 28px; letter-spacing: 1px;
            color: var(--accent-ink); background: var(--accent); box-shadow: 0 12px 30px rgba(0,0,0,.25); }
        h1 { font-size: 27px; font-weight: 800; margin-bottom: 6px; color: var(--accent); }
        .sub { color: var(--muted); font-size: 14px; margin-bottom: 26px; line-height: 1.55; }
        .google-btn { display: inline-flex; align-items: center; justify-content: center; gap: 12px;
            width: 100%; background: #fff; border: none; border-radius: 12px; padding: 14px 20px;
            font-family: inherit; font-size: 15px; color: #3c4043; text-decoration: none; font-weight: 600;
            transition: transform .15s, box-shadow .2s; }
        .google-btn:hover { transform: translateY(-2px); box-shadow: 0 12px 30px rgba(0,0,0,.25); }
        .google-btn img { width: 20px; height: 20px; }

        .divider { display: flex; align-items: center; gap: 12px; margin: 22px 0 18px; color: var(--muted); font-size: 12px; }
        .divider::before, .divider::after { content: ""; flex: 1; height: 1px; background: var(--card-border); }
        .auth-form { display: flex; flex-direction: column; gap: 10px; text-align: left; }
        .auth-form input { width: 100%; padding: 13px 15px; border-radius: 12px; border: 1px solid var(--card-border);
            background: rgba(127,127,127,.08); color: var(--text); font-family: inherit; font-size: 14px; }
        .auth-form input:focus { outline: none; border-color: var(--accent); }
        .auth-form button { width: 100%; padding: 14px 20px; border: none; border-radius: 12px; cursor: pointer;
            font-family: inherit; font-size: 15px; font-weight: 700; background: var(--accent); color: var(--accent-ink);
            transition: transform .15s, box-shadow .2s; }
        .auth-form button:hover { transform: translateY(-2px); box-shadow: 0 12px 30px rgba(0,0,0,.25); }
        .auth-error { background: rgba(230,57,70,.12); border: 1px solid var(--rouge,#e63946); color: var(--text);
            padding: 11px 14px; border-radius: 10px; font-size: 13px; margin-bottom: 16px; text-align: left; }
        .register-link { display: flex; align-items: center; justify-content: center; gap: 8px; margin-top: 14px;
            padding: 13px 18px; font-size: 14.5px; font-weight: 700; text-decoration: none; color: #fff;
            background: linear-gradient(135deg, var(--vert,#2a9d4a), #1f7a39); border-radius: 12px;
            box-shadow: 0 8px 22px rgba(42,157,74,.35); transition: transform .15s, box-shadow .2s; }
        .register-link:hover { transform: translateY(-2px); box-shadow: 0 12px 30px rgba(42,157,74,.45); }
        .register-link b { color: #fff; }

        .foot { margin-top: 24px; font-size: 12px; color: var(--muted);
            display: flex; align-items: center; justify-content: center; gap: 8px; }
        .dot { width: 8px; height: 8px; border-radius: 50%; }
        .dot.r { background: var(--rouge); } .dot.n { background: #888; } .dot.v { background: var(--vert); }
        .links { display: flex; flex-wrap: wrap; gap: 8px; justify-content: center; margin-top: 16px; }
        .links a { font-size: 13px; color: var(--muted); text-decoration: none; border: 1px solid var(--card-border);
            padding: 8px 14px; border-radius: 10px; transition: border-color .2s, color .2s; }
        .links a:hover { color: var(--accent); border-color: var(--accent); }

        /* ---- Tableau des annonces ---- */
        .announce-board { background: var(--card-bg); border: 1px solid var(--card-border);
            border-radius: 24px; box-shadow: var(--card-shadow); padding: 24px 22px; text-align: left;
            display: flex; flex-direction: column; }
        .ab-head { display: flex; align-items: center; gap: 9px; font-size: 13px; font-weight: 800;
            color: var(--accent); margin-bottom: 16px; text-transform: uppercase; letter-spacing: .6px; }
        .ab-head .pulse { width: 9px; height: 9px; border-radius: 50%; background: var(--rouge,#e63946);
            box-shadow: 0 0 0 0 rgba(230,57,70,.6); animation: abpulse 2s infinite; }
        @keyframes abpulse { 0%{box-shadow:0 0 0 0 rgba(230,57,70,.55);} 70%{box-shadow:0 0 0 8px rgba(230,57,70,0);} 100%{box-shadow:0 0 0 0 rgba(230,57,70,0);} }
        .ab-list { display: flex; flex-direction: column; gap: 12px; }
        .ab-item { display: flex; align-items: center; gap: 13px; text-decoration: none; color: var(--text);
            padding: 12px; border-radius: 14px; border: 1px solid var(--card-border); background: rgba(127,127,127,.04);
            transition: border-color .15s, transform .15s, background .15s; }
        .ab-item:hover { border-color: var(--accent); transform: translateY(-2px); background: rgba(127,127,127,.08); }
        .ab-thumb { width: 56px; height: 56px; flex: 0 0 56px; border-radius: 12px; object-fit: cover;
            border: 1px solid var(--card-border); display: flex; align-items: center; justify-content: center;
            font-size: 24px; color: var(--accent); background: rgba(127,127,127,.12); }
        .ab-body { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 3px; }
        .ab-title { font-weight: 700; font-size: 14.5px; line-height: 1.3; }
        .ab-ex { font-size: 12.5px; color: var(--muted); line-height: 1.4;
            display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .ab-date { font-size: 11px; color: var(--muted); margin-top: 2px; }
        .ab-go { color: var(--accent); font-size: 18px; flex: 0 0 auto; }
        .ab-empty { color: var(--muted); font-size: 13px; }

        /* Sur grand écran : les annonces à gauche, la connexion à droite. */
        @media (min-width: 880px) { .announce-board { order: -1; } }
    </style>
</head>
<body>
    <main class="shell">
        <section class="card">
            <div class="logo"><?= htmlspecialchars(mb_substr(Settings::get('main_title', 'RPN'), 0, 3)) ?></div>
            <h1><?= htmlspecialchars(Settings::get('main_title', 'RPN')) ?></h1>
            <p class="sub"><?= htmlspecialchars(Settings::get('main_message', 'Bienvenue. Connecte-toi pour rejoindre la communauté.')) ?></p>

            <?php if (!empty($error)): ?>
                <div class="auth-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <a class="google-btn" href="<?= htmlspecialchars($googleLoginUrl) ?>">
                <img src="https://www.gstatic.com/firebasejs/ui/2.0.0/images/auth/google.svg" alt="">
                Se connecter avec Google
            </a>

            <div class="divider">ou avec un email</div>
            <form class="auth-form" method="post" action="<?= url('login') ?>">
                <input type="email" name="email" placeholder="Adresse email" value="<?= htmlspecialchars($old['email'] ?? '') ?>" required autocomplete="email">
                <input type="password" name="password" placeholder="Mot de passe" required autocomplete="current-password">
                <button type="submit">Se connecter</button>
            </form>
            <a class="register-link" href="<?= url('register') ?>">Pas encore de compte ? <b>S'inscrire →</b></a>

            <div class="foot">
                <span class="dot r"></span><span class="dot n"></span><span class="dot v"></span>
                <?= htmlspecialchars(Settings::get('main_footer', 'Ensemble, plus forts')) ?>
            </div>
            <div class="links">
                <a href="<?= url('articles') ?>">📰 Articles</a>
                <a href="<?= url('afrique') ?>">🌍 Pays d'Afrique</a>
                <a href="<?= url('admin/login') ?>">🔐 Administrateur</a>
            </div>
        </section>

        <?php if (!empty($announcements)): ?>
            <aside class="announce-board">
                <div class="ab-head"><span class="pulse"></span> 📣 Annonces</div>
                <div class="ab-list">
                    <?php foreach ($announcements as $an): ?>
                        <?php $excerpt = trim(mb_strimwidth(strip_tags((string) ($an['content'] ?? '')), 0, 90, '…')); ?>
                        <a class="ab-item" href="<?= url('article') ?>?id=<?= (int) $an['id'] ?>">
                            <?php if (!empty($an['image'])): ?>
                                <img class="ab-thumb" src="<?= url('uploads/articles/' . rawurlencode($an['image'])) ?>" alt="">
                            <?php else: ?>
                                <span class="ab-thumb">📰</span>
                            <?php endif; ?>
                            <span class="ab-body">
                                <span class="ab-title"><?= htmlspecialchars($an['title']) ?></span>
                                <?php if ($excerpt !== ''): ?><span class="ab-ex"><?= htmlspecialchars($excerpt) ?></span><?php endif; ?>
                                <span class="ab-date"><?= date('d/m/Y', strtotime($an['created_at'])) ?></span>
                            </span>
                            <span class="ab-go">→</span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </aside>
        <?php endif; ?>
    </main>
</body>
</html>
