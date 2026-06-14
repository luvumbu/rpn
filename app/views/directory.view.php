<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trouver un professeur — <?= htmlspecialchars(Settings::get('main_title', 'RPN')) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <?= Theme::css() ?>
    <style>
        * { box-sizing:border-box; margin:0; padding:0; }
        body { font-family:'Poppins',sans-serif; min-height:100vh; color:var(--text); padding:32px 20px 60px;
            background:radial-gradient(circle at 12% 0%, var(--glow1), transparent 42%),
                radial-gradient(circle at 88% 100%, var(--glow2), transparent 44%), var(--bg-base); }
        body::before { content:""; position:fixed; top:0; left:0; right:0; height:6px; background:var(--bar); }
        .wrap { max-width:960px; margin:0 auto; }
        .top { display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; margin-bottom:18px; }
        h1 { font-size:24px; } h1 span { color:var(--accent); }
        .back { font-size:14px; color:var(--muted); text-decoration:none; border:1px solid var(--card-border); padding:8px 14px; border-radius:10px; }
        .search { background:var(--card-bg); border:1px solid var(--card-border); border-radius:16px; padding:18px 20px; margin-bottom:14px; }
        .search form { display:flex; gap:10px; flex-wrap:wrap; }
        .search input[type=text] { flex:1; min-width:200px; padding:12px 14px; border-radius:11px; border:1px solid var(--card-border);
            background:rgba(127,127,127,.06); color:var(--text); font:inherit; }
        .search button { font:inherit; font-weight:700; border:none; border-radius:11px; padding:12px 20px; cursor:pointer;
            background:var(--accent); color:var(--accent-ink); }
        .chips { display:flex; flex-wrap:wrap; gap:8px; margin-top:12px; }
        .chip { font-size:12.5px; text-decoration:none; color:var(--text); background:rgba(127,127,127,.08);
            border:1px solid var(--card-border); border-radius:20px; padding:5px 12px; }
        .chip:hover, .chip.on { border-color:var(--accent); color:var(--accent); }
        .chip .cnt { display:inline-block; min-width:18px; text-align:center; font-size:11px; font-weight:800; background:var(--accent); color:var(--accent-ink); border-radius:20px; padding:0 6px; margin-left:4px; }
        .t-dist { color:var(--accent); font-weight:600; }
        .loc-hint { font-size:12.5px; color:var(--muted); margin-top:10px; }
        .loc-hint a { color:var(--accent); }
        .count { font-size:13px; color:var(--muted); margin:14px 4px; }
        .grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(280px,1fr)); gap:16px; }
        .teacher { background:var(--card-bg); border:1px solid var(--card-border); border-radius:16px; padding:18px 20px; }
        .t-head { display:flex; align-items:center; gap:12px; }
        .t-head img, .t-ph { width:54px; height:54px; border-radius:50%; object-fit:cover; flex:0 0 auto; }
        .t-name { font-weight:800; font-size:16px; }
        .t-lvl { font-size:12px; color:var(--accent); font-weight:700; }
        .t-dom { display:flex; flex-wrap:wrap; gap:6px; margin:12px 0; }
        .t-dom span { font-size:12px; background:rgba(127,127,127,.08); border:1px solid var(--card-border); border-radius:8px; padding:3px 9px; }
        .t-city { font-size:12.5px; color:var(--muted); margin-bottom:12px; }
        .t-actions { display:flex; gap:8px; flex-wrap:wrap; }
        .t-btn { flex:1; text-align:center; text-decoration:none; font-weight:700; font-size:13.5px; border-radius:10px; padding:10px; }
        .t-btn.msg { background:var(--accent); color:var(--accent-ink); }
        .t-btn.agenda { background:var(--card-bg); color:var(--text); border:1px solid var(--card-border); }
        .empty { text-align:center; color:var(--muted); padding:40px 20px; }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="top">
            <h1>🔍 Rechercher un <span>membre / professeur</span></h1>
            <a class="back" href="<?= url('dashboard') ?>">← Tableau de bord</a>
        </div>

        <?= meet_link_widget() ?>

        <div class="search">
            <form method="get" action="<?= url('professeurs') ?>">
                <span class="member-wrap" style="flex:1;min-width:200px;">
                    <input type="text" name="q" class="js-member" autocomplete="off" value="<?= htmlspecialchars($q) ?>" placeholder="Nom, matière, ville… (ex. Audrey, Lingala, Paris)" autofocus style="width:100%;">
                </span>
                <button type="submit">Rechercher</button>
            </form>
            <?= member_autocomplete_js() ?>
            <?php if (!empty($allDomains)): ?>
                <div class="chips">
                    <a class="chip <?= $q === '' ? 'on' : '' ?>" href="<?= url('professeurs') ?>">Tous</a>
                    <?php foreach ($allDomains as $d => $cnt): ?>
                        <a class="chip <?= strcasecmp($q, (string) $d) === 0 ? 'on' : '' ?>" href="<?= url('professeurs') ?>?q=<?= urlencode((string) $d) ?>"><?= htmlspecialchars((string) $d) ?> <span class="cnt"><?= (int) $cnt ?></span></a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <p class="count"><?= count($teachers) ?> professeur<?= count($teachers) > 1 ? 's' : '' ?><?= $q !== '' ? ' pour « ' . htmlspecialchars($q) . ' »' : '' ?></p>

        <?php if (empty($teachers)): ?>
            <div class="empty">
                <p style="font-size:40px">🧑‍🏫</p>
                <p>Aucun professeur trouvé<?= $q !== '' ? ' pour cette matière' : '' ?>.</p>
                <p style="font-size:13px;margin-top:8px">Les membres apparaissent ici s'ils ont activé « <b>être trouvable</b> » et renseigné leurs <b>matières</b> dans leur profil.</p>
            </div>
        <?php else: ?>
            <div class="grid">
                <?php foreach ($teachers as $t): ?>
                    <div class="teacher">
                        <div class="t-head">
                            <?php if (!empty($t['picture'])): ?>
                                <img src="<?= htmlspecialchars(avatar_url($t['picture'], $t['name'])) ?>" alt="" referrerpolicy="no-referrer">
                            <?php else: ?>
                                <img class="t-ph" src="<?= htmlspecialchars(avatar_url(null, $t['name'])) ?>" alt="">
                            <?php endif; ?>
                            <div>
                                <div class="t-name"><?= htmlspecialchars($t['name']) ?></div>
                                <div class="t-lvl"><?= $t['level']['emoji'] ?> <?= htmlspecialchars($t['level']['name']) ?></div>
                            </div>
                        </div>
                        <?php if (!empty($t['domains']) || !empty($t['countries'])): ?>
                            <div class="t-dom">
                                <?php foreach ($t['domains'] as $d): ?><span>🎓 <?= htmlspecialchars($d) ?></span><?php endforeach; ?>
                                <?php foreach (($t['countries'] ?? []) as $c): ?><span><?= country_flag_img($c, 18, 13) ?> <?= htmlspecialchars($c) ?></span><?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($t['city']) || $t['distance'] !== null): ?>
                            <div class="t-city">
                                <?php if (!empty($t['city'])): ?>📍 <?= htmlspecialchars($t['city']) ?><?php endif; ?>
                                <?php if ($t['distance'] !== null): ?><span class="t-dist">· 📏 à <?= (int) $t['distance'] ?> km de toi</span><?php endif; ?>
                            </div>
                        <?php endif; ?>
                        <div class="t-actions">
                            <a class="t-btn msg" href="<?= url('messages/thread') ?>?with=<?= (int) $t['id'] ?>">✉️ Message</a>
                            <a class="t-btn agenda" href="<?= url('agenda/global') ?>">📅 Agenda</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
