<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages — <?= htmlspecialchars(Settings::get('main_title', 'RPN')) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <?= Theme::css() ?>
    <style>
        * { box-sizing:border-box; margin:0; padding:0; }
        body { font-family:'Poppins',sans-serif; min-height:100vh; color:var(--text); padding:32px 20px 60px;
            background:radial-gradient(circle at 12% 0%, var(--glow1), transparent 42%),
                radial-gradient(circle at 88% 100%, var(--glow2), transparent 44%), var(--bg-base); }
        body::before { content:""; position:fixed; top:0; left:0; right:0; height:6px; background:var(--bar); }
        .wrap { max-width:760px; margin:0 auto; }
        .top { display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; margin-bottom:18px; }
        h1 { font-size:24px; } h1 span { color:var(--accent); }
        .back { font-size:14px; color:var(--muted); text-decoration:none; border:1px solid var(--card-border); padding:8px 14px; border-radius:10px; }
        .thread { display:flex; align-items:center; gap:14px; background:var(--card-bg); border:1px solid var(--card-border);
            border-radius:14px; padding:14px 16px; margin-bottom:10px; text-decoration:none; color:inherit; }
        .thread:hover { border-color:var(--accent); }
        .thread img { width:48px; height:48px; border-radius:50%; object-fit:cover; flex:0 0 auto; }
        .t-mid { flex:1; min-width:0; }
        .t-name { font-weight:700; }
        .t-last { font-size:13px; color:var(--muted); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .t-unread { background:var(--rouge,#e63946); color:#fff; font-size:12px; font-weight:700; border-radius:20px; padding:2px 9px; flex:0 0 auto; }
        .empty { text-align:center; color:var(--muted); padding:50px 20px; }
        .empty a { color:var(--accent); font-weight:700; }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="top">
            <h1>✉️ Mes <span>messages</span></h1>
            <a class="back" href="<?= url('dashboard') ?>">← Tableau de bord</a>
        </div>

        <?php if (empty($threads)): ?>
            <div class="empty">
                <p style="font-size:42px">📭</p>
                <p>Aucune conversation pour l'instant.</p>
                <p style="font-size:13px;margin-top:8px">Va sur <a href="<?= url('professeurs') ?>">Trouver un professeur</a> et écris à quelqu'un. ✍️</p>
            </div>
        <?php else: ?>
            <?php foreach ($threads as $t): ?>
                <a class="thread" href="<?= url('messages/thread') ?>?with=<?= (int) $t['other_id'] ?>">
                    <img src="<?= htmlspecialchars(avatar_url($t['picture'], $t['name'])) ?>" alt="" referrerpolicy="no-referrer">
                    <div class="t-mid">
                        <div class="t-name"><?= htmlspecialchars($t['name']) ?></div>
                        <div class="t-last"><?= $t['mine'] ? 'Toi : ' : '' ?><?= htmlspecialchars(mb_substr($t['last'], 0, 60)) ?></div>
                    </div>
                    <?php if ((int) $t['unread'] > 0): ?>
                        <span class="t-unread"><?= (int) $t['unread'] ?></span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>
