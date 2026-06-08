<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes niveaux — <?= htmlspecialchars(Settings::get('main_title', 'RPN')) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800&display=swap" rel="stylesheet">
    <?= Theme::css() ?>
    <style>
        * { box-sizing:border-box; margin:0; padding:0; }
        body { font-family:'Poppins',sans-serif; min-height:100vh; color:var(--text); padding:30px 18px 60px;
            background:radial-gradient(circle at 12% 0%, var(--glow1), transparent 42%),
                radial-gradient(circle at 88% 100%, var(--glow2), transparent 44%), var(--bg-base); }
        body::before { content:""; position:fixed; top:0; left:0; right:0; height:6px; background:var(--bar); }
        .wrap { max-width:760px; margin:0 auto; }
        .top { display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; margin-bottom:16px; }
        h1 { font-size:24px; } h1 span { color:var(--accent); }
        .back { font-size:14px; color:var(--muted); text-decoration:none; border:1px solid var(--card-border); padding:8px 14px; border-radius:10px; }
        .card { background:var(--card-bg); border:1px solid var(--card-border); border-radius:18px; padding:22px 24px; margin-bottom:18px; }
        h2 { font-size:16px; color:var(--accent); margin-bottom:12px; }
        /* Statut actuel */
        .now { display:flex; align-items:center; gap:16px; }
        .now .em { font-size:48px; }
        .now .nm { font-size:20px; font-weight:800; }
        .now .pts { font-size:13px; color:var(--accent); font-weight:700; }
        .bar { height:14px; border-radius:20px; background:rgba(127,127,127,.18); overflow:hidden; margin:16px 0 8px; }
        .bar span { display:block; height:100%; background:linear-gradient(90deg,var(--accent),var(--or,#f4c14b)); }
        .next { font-size:13.5px; color:var(--muted); }
        /* Échelle des niveaux */
        .ladder { display:flex; flex-direction:column; gap:10px; }
        .step { display:flex; align-items:center; gap:14px; border:1px solid var(--card-border); border-radius:14px; padding:12px 16px; opacity:.6; }
        .step.reached { opacity:1; }
        .step.current { border-color:var(--accent); box-shadow:0 0 0 2px var(--accent) inset; opacity:1; }
        .step .e { font-size:30px; flex:0 0 auto; }
        .step .info { flex:1; }
        .step .lname { font-weight:800; }
        .step .lreq { font-size:12.5px; color:var(--muted); }
        .step .tag { font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.5px; padding:3px 9px; border-radius:20px; }
        .tag.cur { background:var(--accent); color:var(--accent-ink); }
        .tag.nxt { background:rgba(244,193,75,.18); color:var(--accent); }
        /* Barème */
        .scale { display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:10px; }
        .scale .row { display:flex; align-items:center; justify-content:space-between; gap:10px; background:rgba(127,127,127,.06);
            border:1px solid var(--card-border); border-radius:11px; padding:10px 14px; font-size:14px; }
        .scale .row b { color:var(--accent); }
        .mine { display:flex; flex-wrap:wrap; gap:8px 14px; margin-top:12px; font-size:13px; color:var(--muted); }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="top">
            <h1>🏅 Mes <span>niveaux</span></h1>
            <a class="back" href="<?= url('dashboard') ?>">← Tableau de bord</a>
        </div>

        <!-- Statut actuel -->
        <section class="card">
            <div class="now">
                <span class="em"><?= $info['emoji'] ?></span>
                <div>
                    <div class="nm">Niveau <?= (int) $info['level'] ?> — <?= htmlspecialchars($info['name']) ?></div>
                    <div class="pts"><?= (int) $info['points'] ?> points</div>
                </div>
            </div>
            <div class="bar"><span style="width:<?= (int) $info['progress'] ?>%"></span></div>
            <div class="next">
                <?php if ($info['next']): ?>
                    Encore <b><?= (int) $info['toNext'] ?> points</b> pour atteindre <?= $info['nextEmoji'] ?> <b><?= htmlspecialchars($info['next']) ?></b> (<?= (int) $info['progress'] ?> %).
                <?php else: ?>
                    🏆 Tu as atteint le niveau maximum : <b>Sage</b> !
                <?php endif; ?>
            </div>
            <?php if (!empty($detail)): ?>
            <div class="mine">
                <span>📰 <?= (int) $detail['article'] ?> article·s</span>
                <span>❓ <?= (int) $detail['quiz'] ?> QCM</span>
                <span>📅 <?= (int) $detail['booking'] ?> réservations</span>
                <span>⭐ <?= (int) $detail['review'] ?> avis</span>
                <span>💬 <?= (int) $detail['comment'] ?> messages</span>
                <span>⏳ <?= (int) $detail['weeks'] ?> semaines</span>
            </div>
            <?php endif; ?>
        </section>

        <!-- Tous les niveaux -->
        <section class="card">
            <h2>📈 Les 5 niveaux</h2>
            <div class="ladder">
                <?php foreach ($levels as $n => $d): ?>
                    <?php
                        $reached = (int) $info['level'] >= $n;
                        $current = (int) $info['level'] === $n;
                        $isNext  = (int) $info['level'] + 1 === $n;
                    ?>
                    <div class="step <?= $reached ? 'reached' : '' ?> <?= $current ? 'current' : '' ?>">
                        <span class="e"><?= $d['emoji'] ?></span>
                        <div class="info">
                            <div class="lname">Niveau <?= $n ?> — <?= htmlspecialchars($d['name']) ?></div>
                            <div class="lreq"><?= (int) $d['min'] ?> points<?= $n < count($levels) ? '' : ' et +' ?></div>
                        </div>
                        <?php if ($current): ?><span class="tag cur">Toi</span>
                        <?php elseif ($isNext): ?><span class="tag nxt">Suivant</span><?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- Comment gagner des points -->
        <section class="card">
            <h2>⚡ Comment gagner des points</h2>
            <div class="scale">
                <?php foreach ($scale as $s): ?>
                    <div class="row"><span><?= $s['icon'] ?> <?= htmlspecialchars($s['label']) ?></span> <b>+<?= (int) $s['pts'] ?></b></div>
                <?php endforeach; ?>
            </div>
        </section>
    </div>
</body>
</html>
