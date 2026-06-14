<?php /** Confidentialité & mes données (RGPD). Variables : $user, $isSuperAdmin, $notice, $error. */ ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(Settings::get('main_title', 'RPN')) ?> — Confidentialité</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <?= Theme::css() ?>
    <style>
        * { box-sizing:border-box; margin:0; padding:0; }
        body { font-family:'Poppins',sans-serif; min-height:100vh; color:var(--text); padding:34px 20px 70px;
            background:radial-gradient(circle at 12% 0%, var(--glow1), transparent 42%), var(--bg-base); }
        body::before { content:""; position:fixed; top:0; left:0; right:0; height:6px; background:var(--bar); }
        .wrap { max-width:680px; margin:0 auto; }
        .top { display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; margin-bottom:8px; }
        h1 { font-size:24px; } h1 span { color:var(--accent); }
        .back { font-size:14px; color:var(--muted); text-decoration:none; border:1px solid var(--card-border); padding:8px 14px; border-radius:10px; }
        .subtitle { color:var(--muted); font-size:13px; margin-bottom:22px; }
        .card { background:var(--card-bg); border:1px solid var(--card-border); border-radius:16px; padding:20px 22px; margin-bottom:18px; box-shadow:var(--card-shadow); }
        .card h2 { font-size:17px; color:var(--accent); margin-bottom:6px; }
        .card .lead { font-size:13.5px; color:var(--muted); margin-bottom:14px; line-height:1.5; }
        .btn { display:inline-block; text-decoration:none; font:inherit; font-weight:700; font-size:14px; cursor:pointer; border-radius:11px; padding:11px 18px; }
        .btn.ok { background:var(--accent); color:var(--accent-ink); border:none; }
        .btn.ghost { border:1px solid var(--card-border); color:var(--text); background:var(--card-bg); }
        .msg { padding:12px 16px; border-radius:12px; font-size:14px; margin-bottom:16px; }
        .msg.ok { background:rgba(42,157,74,.14); border:1px solid var(--vert,#2a9d4a); }
        .msg.err { background:rgba(230,57,70,.12); border:1px solid var(--rouge,#e63946); }
        .danger { border-color:rgba(230,57,70,.5); }
        .danger h2 { color:var(--rouge,#e63946); }
        .danger input[type=text] { width:100%; max-width:260px; padding:11px 13px; border-radius:10px; border:1px solid var(--card-border);
            background:rgba(127,127,127,.08); color:var(--text); font-family:inherit; font-size:14px; margin:10px 0; }
        .btn.del { background:var(--rouge,#e63946); color:#fff; border:none; }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="top">
            <h1>🔏 <span>Confidentialité</span> & mes données</h1>
            <a class="back" href="<?= url('dashboard') ?>">← Tableau de bord</a>
        </div>
        <p class="subtitle">Tes droits sur tes données personnelles (RGPD) : consulter, télécharger, supprimer.</p>

        <?php if (!empty($notice)): ?><div class="msg ok"><?= htmlspecialchars($notice) ?></div><?php endif; ?>
        <?php if (!empty($error)): ?><div class="msg err"><?= htmlspecialchars($error) ?></div><?php endif; ?>

        <?php if (!empty($isSuperAdmin)): ?>
            <div class="card">
                <h2>👑 Compte administrateur technique</h2>
                <p class="lead">Tu es connecté avec le compte administrateur (identifiants de la base). Ce n'est pas un compte membre : il n'a pas de données personnelles à exporter ni à supprimer ici.</p>
            </div>
        <?php else: ?>
            <div class="card">
                <h2>📥 Télécharger mes données</h2>
                <p class="lead">Obtiens une copie de tes données personnelles (profil, contenus, paiements…) au format JSON.</p>
                <a class="btn ok" href="<?= url('profile/data') ?>">⬇️ Télécharger (.json)</a>
            </div>

            <div class="card">
                <h2>📄 Informations légales</h2>
                <p class="lead">Comment tes données sont utilisées et conservées.</p>
                <a class="btn ghost" href="<?= url('legal') ?>">Voir les mentions légales & la confidentialité →</a>
            </div>

            <div class="card danger">
                <h2>🗑️ Supprimer mon compte</h2>
                <p class="lead"><b>Action irréversible.</b> Ton compte et tes données personnelles seront effacés. Tes contenus publiés (articles, quiz) sont conservés mais <b>anonymisés</b>. Les justificatifs de paiement sont conservés (obligation légale).</p>
                <form method="post" action="<?= url('profile/delete') ?>" onsubmit="return confirm('Supprimer définitivement ton compte ? Cette action est IRRÉVERSIBLE.');">
                    <label style="font-size:13px;color:var(--muted);">Pour confirmer, écris <b>SUPPRIMER</b> :</label>
                    <input type="text" name="confirm" placeholder="SUPPRIMER" autocomplete="off" required>
                    <div><button type="submit" class="btn del">Supprimer définitivement mon compte</button></div>
                </form>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
