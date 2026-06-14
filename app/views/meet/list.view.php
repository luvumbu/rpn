<?php
/**
 * « Mes salons » — liste des liens visio enregistrés par le membre.
 * Variables : $user, $links, $notice.
 */
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(Settings::get('main_title', 'RPN')) ?> — Mes salons</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <?= Theme::css() ?>
    <style>
        * { box-sizing:border-box; margin:0; padding:0; }
        body { font-family:'Poppins',sans-serif; min-height:100vh; color:var(--text); padding:32px 20px 70px;
            background:radial-gradient(circle at 12% 0%, var(--glow1), transparent 42%), var(--bg-base); }
        body::before { content:""; position:fixed; top:0; left:0; right:0; height:6px; background:var(--bar); }
        .wrap { max-width:760px; margin:0 auto; }
        .top { display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; margin-bottom:10px; }
        h1 { font-size:24px; } h1 span { color:var(--accent); }
        .back { font-size:14px; color:var(--muted); text-decoration:none; border:1px solid var(--card-border); padding:8px 14px; border-radius:10px; }
        .subtitle { color:var(--muted); font-size:13px; margin-bottom:20px; }
        .notice { background:rgba(42,157,74,.14); border:1px solid var(--vert,#2a9d4a); border-radius:11px; padding:11px 14px; font-size:14px; margin-bottom:16px; }
        .row { display:flex; align-items:center; gap:12px; background:var(--card-bg); border:1px solid var(--card-border);
            border-radius:14px; padding:13px 16px; margin-bottom:10px; flex-wrap:wrap; }
        .row .info { flex:1; min-width:160px; }
        .row .rename { display:flex; gap:6px; align-items:center; margin-bottom:3px; }
        .row .rename input { flex:1; min-width:120px; font:inherit; font-weight:700; font-size:15px; color:var(--text);
            background:transparent; border:1px solid transparent; border-radius:8px; padding:4px 8px; }
        .row .rename input:hover, .row .rename input:focus { border-color:var(--card-border); background:rgba(127,127,127,.06); outline:none; }
        .row .rename button { font-size:14px; cursor:pointer; border:1px solid var(--card-border); background:var(--card-bg);
            color:var(--text); border-radius:8px; padding:5px 9px; }
        .row .rename button:hover { border-color:var(--accent); }
        .row .url { font-size:12.5px; color:var(--muted); font-family:Consolas,Monaco,monospace; word-break:break-all; }
        .row .date { font-size:11.5px; color:var(--muted); margin-top:2px; }
        .row a.open, .row button { font:inherit; font-size:13px; font-weight:700; cursor:pointer; text-decoration:none;
            border-radius:10px; padding:9px 13px; white-space:nowrap; border:1px solid var(--card-border); background:var(--card-bg); color:var(--text); }
        .row a.open { background:var(--accent); color:var(--accent-ink); border-color:var(--accent); }
        .row .del { background:rgba(230,57,70,.12); border-color:rgba(230,57,70,.4); color:var(--rouge,#e63946); }
        .empty { text-align:center; color:var(--muted); padding:48px 20px; }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="top">
            <h1>🎥 <span>Mes salons</span></h1>
            <a class="back" href="<?= url('messages') ?>">← Messages</a>
        </div>
        <p class="subtitle">Les liens de salon visio que tu as enregistrés. Tu peux les rouvrir, les copier ou les supprimer.</p>

        <?php if (!empty($notice)): ?><div class="notice"><?= htmlspecialchars($notice) ?></div><?php endif; ?>

        <?php if (empty($links)): ?>
            <div class="empty">
                <p style="font-size:42px">🎥</p>
                <p>Aucun salon enregistré pour l'instant.</p>
                <p style="font-size:13px;margin-top:8px">Crée un lien depuis n'importe quelle page Communauté, puis clique sur <b>💾 Enregistrer</b>.</p>
            </div>
        <?php else: ?>
            <?php foreach ($links as $l): ?>
                <div class="row">
                    <div class="info">
                        <form method="post" action="<?= url('meet/rename') ?>" class="rename">
                            <input type="hidden" name="id" value="<?= (int) $l['id'] ?>">
                            <input type="text" name="label" value="<?= htmlspecialchars($l['label'] ?? '') ?>" placeholder="Nom du salon…" maxlength="120">
                            <button type="submit" title="Renommer">✏️</button>
                        </form>
                        <div class="url"><?= htmlspecialchars($l['url']) ?></div>
                        <div class="date"><?= date('d/m/Y à H\hi', strtotime($l['created_at'])) ?></div>
                    </div>
                    <a class="open" href="<?= htmlspecialchars($l['url']) ?>" target="_blank" rel="noopener">Ouvrir →</a>
                    <button type="button" onclick="rpmCopyUrl(this, '<?= htmlspecialchars(addslashes($l['url'])) ?>')">Copier</button>
                    <form method="post" action="<?= url('meet/delete') ?>" style="margin:0;" onsubmit="return confirm('Retirer ce salon de la liste ?');">
                        <input type="hidden" name="id" value="<?= (int) $l['id'] ?>">
                        <button type="submit" class="del">🗑️</button>
                    </form>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <script>
    function rpmCopyUrl(btn, url) {
        var done = function () { var t = btn.textContent; btn.textContent = '✓'; setTimeout(function () { btn.textContent = t; }, 1200); };
        if (navigator.clipboard) { navigator.clipboard.writeText(url).then(done, done); } else { done(); }
    }
    </script>
</body>
</html>
