<?php
/**
 * Classement des membres par points (gamification).
 * Variables : $user, $ranking (liste rang/points/niveau), $meId.
 */
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(Settings::get('main_title', 'RPN')) ?> — Classement</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <?= Theme::css() ?>
    <style>
        * { box-sizing:border-box; margin:0; padding:0; }
        body { font-family:'Poppins',sans-serif; min-height:100vh; color:var(--text); padding:40px 20px 80px;
            background: radial-gradient(circle at 10% 0%, var(--glow1), transparent 42%), var(--bg-base); }
        body::before { content:""; position:fixed; top:0; left:0; right:0; height:6px; background:var(--bar); }
        .wrap { max-width:680px; margin:0 auto; }
        .top { display:flex; justify-content:space-between; align-items:center; margin-bottom:8px; flex-wrap:wrap; gap:12px; }
        h1 { font-size:24px; } h1 span { color:var(--accent); }
        .subtitle { color:var(--muted); font-size:13px; margin-bottom:24px; }
        .back { color:var(--text); text-decoration:none; font-size:14px; padding:8px 16px; border-radius:10px; border:1px solid var(--card-border); }
        .back:hover { border-color:var(--accent); color:var(--accent); }
        .lb { display:flex; flex-direction:column; gap:10px; }
        .row { display:flex; align-items:center; gap:14px; padding:13px 16px; border-radius:14px;
            background:var(--card-bg); border:1px solid var(--card-border); box-shadow:var(--card-shadow); }
        .row.me { border-color:var(--accent); }
        .rank { flex:0 0 38px; text-align:center; font-size:18px; font-weight:800; color:var(--muted); }
        .row.top1 .rank, .row.top2 .rank, .row.top3 .rank { font-size:22px; }
        .av { width:46px; height:46px; flex:0 0 46px; border-radius:50%; object-fit:cover; border:1px solid var(--card-border); }
        .who { flex:1; min-width:0; }
        .nm { font-weight:700; font-size:15.5px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .lvl { font-size:12.5px; color:var(--muted); }
        .pts { flex:0 0 auto; text-align:right; }
        .pts b { font-size:18px; color:var(--accent); }
        .pts span { display:block; font-size:11px; color:var(--muted); text-transform:uppercase; letter-spacing:.5px; }
        .empty { text-align:center; color:var(--muted); padding:40px; }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="top">
            <h1>🏆 <span>Classement</span></h1>
            <a class="back" href="<?= url('dashboard') ?>">← Tableau de bord</a>
        </div>
        <p class="subtitle">Les membres les plus actifs de la communauté, classés par points (articles, questionnaires, avis, réservations, ancienneté…).</p>

        <?php if (empty($ranking)): ?>
            <p class="empty">Aucun membre à classer pour l'instant.</p>
        <?php else: ?>
            <div class="lb">
                <?php foreach ($ranking as $r): ?>
                    <?php
                        $medal = $r['rank'] === 1 ? '🥇' : ($r['rank'] === 2 ? '🥈' : ($r['rank'] === 3 ? '🥉' : (string) $r['rank']));
                        $cls   = ($r['rank'] <= 3 ? ' top' . $r['rank'] : '') . ((int) $r['id'] === (int) $meId ? ' me' : '');
                    ?>
                    <div class="row<?= $cls ?>">
                        <div class="rank"><?= $medal ?></div>
                        <img class="av" src="<?= htmlspecialchars(avatar_url($r['picture'] ?? '', $r['name'])) ?>" alt="">
                        <div class="who">
                            <div class="nm"><?= htmlspecialchars($r['name']) ?><?= (int) $r['id'] === (int) $meId ? ' <small style="color:var(--accent)">(toi)</small>' : '' ?></div>
                            <div class="lvl"><?= $r['emoji'] ?> <?= htmlspecialchars($r['levelName']) ?></div>
                        </div>
                        <div class="pts"><b><?= (int) $r['points'] ?></b><span>points</span></div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
