<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assistant Sankofa — <?= htmlspecialchars(Settings::get('main_title', 'RPN')) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800&display=swap" rel="stylesheet">
    <?= Theme::css() ?>
    <style>
        * { box-sizing:border-box; margin:0; padding:0; }
        body { font-family:'Poppins',sans-serif; min-height:100vh; color:var(--text); padding:30px 18px 60px;
            background:radial-gradient(circle at 12% 0%, var(--glow1), transparent 42%),
                radial-gradient(circle at 88% 100%, var(--glow2), transparent 44%), var(--bg-base); }
        body::before { content:""; position:fixed; top:0; left:0; right:0; height:6px; background:var(--bar); }
        .wrap { max-width:680px; margin:0 auto; }
        .top { display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; margin-bottom:6px; }
        h1 { font-size:23px; } h1 span { color:var(--accent); }
        .back { font-size:14px; color:var(--muted); text-decoration:none; border:1px solid var(--card-border); padding:8px 14px; border-radius:10px; }
        .sub { font-size:12.5px; color:var(--muted); margin-bottom:18px; }
        .chat { display:flex; flex-direction:column; gap:14px; margin-bottom:18px; }
        .msg { display:flex; gap:10px; align-items:flex-start; }
        .msg .av { width:38px; height:38px; border-radius:50%; flex:0 0 auto; display:flex; align-items:center; justify-content:center; font-size:20px; }
        .msg.me { flex-direction:row-reverse; }
        .msg.me .bub { background:var(--accent); color:var(--accent-ink); border-bottom-right-radius:5px; }
        .msg.bot .av { background:var(--noir,#14110f); color:var(--or,#f4c14b); }
        .msg.me .av { background:rgba(127,127,127,.18); }
        .bub { background:var(--card-bg); border:1px solid var(--card-border); border-radius:16px; padding:12px 16px; font-size:14.5px; line-height:1.5; max-width:80%; border-bottom-left-radius:5px; }
        .links { display:flex; flex-direction:column; gap:8px; margin-top:10px; }
        .reslink { display:flex; align-items:center; gap:10px; text-decoration:none; color:inherit; background:rgba(127,127,127,.06);
            border:1px solid var(--card-border); border-radius:11px; padding:10px 12px; font-size:14px; font-weight:600; }
        .reslink:hover { border-color:var(--accent); color:var(--accent); }
        .ask { display:flex; gap:10px; position:sticky; bottom:10px; background:var(--card-bg); border:1px solid var(--card-border); border-radius:14px; padding:10px; }
        .ask input { flex:1; border:none; background:transparent; color:var(--text); font:inherit; padding:9px; outline:none; }
        .ask button { font:inherit; font-weight:700; border:none; border-radius:11px; padding:0 20px; cursor:pointer; background:var(--accent); color:var(--accent-ink); }
        .hint { display:flex; flex-wrap:wrap; gap:8px; margin-bottom:16px; }
        .hint a { font-size:12.5px; text-decoration:none; color:var(--text); background:rgba(127,127,127,.08); border:1px solid var(--card-border); border-radius:20px; padding:5px 12px; }
        .hint a:hover { border-color:var(--accent); color:var(--accent); }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="top">
            <h1>🤖 Assistant <span>Sankofa</span></h1>
            <a class="back" href="<?= url('dashboard') ?>">← Tableau de bord</a>
        </div>
        <p class="sub">⚙️ Ce n'est pas une intelligence artificielle : c'est un <strong>algorithme</strong> qui cherche dans les ressources du site et t'oriente.</p>

        <div class="hint">
            <a href="<?= url('assistant') ?>?q=lingala">lingala</a>
            <a href="<?= url('assistant') ?>?q=distributivit%C3%A9">distributivité</a>
            <a href="<?= url('assistant') ?>?q=trouver+un+prof+de+maths">prof de maths</a>
            <a href="<?= url('assistant') ?>?q=r%C3%A9server+un+cours">réserver un cours</a>
            <a href="<?= url('assistant') ?>?q=quiz">quiz</a>
        </div>

        <div class="chat">
            <?php if ($q !== ''): ?>
                <div class="msg me">
                    <span class="av">🧑</span>
                    <div class="bub"><?= htmlspecialchars($q) ?></div>
                </div>
            <?php endif; ?>
            <div class="msg bot">
                <span class="av">🤖</span>
                <div class="bub">
                    <?= htmlspecialchars($reply['text']) ?>
                    <?php if (!empty($reply['links'])): ?>
                        <div class="links">
                            <?php foreach ($reply['links'] as $l): ?>
                                <a class="reslink" href="<?= htmlspecialchars($l['url']) ?>"><span><?= $l['icon'] ?></span> <span><?= htmlspecialchars($l['label']) ?></span></a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <form class="ask" method="get" action="<?= url('assistant') ?>">
            <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Pose ta question…" autofocus>
            <button type="submit">Demander</button>
        </form>
    </div>
</body>
</html>
