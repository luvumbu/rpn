<?php
/**
 * Page des notifications du membre connecté.
 * Variables : $items (notifications, plus récentes d'abord), $user.
 */
// Date lisible « il y a … » simplifiée (sinon date courte).
$ago = function ($ts) {
    $d = time() - strtotime($ts);
    if ($d < 60)    { return "à l'instant"; }
    if ($d < 3600)  { return 'il y a ' . floor($d / 60) . ' min'; }
    if ($d < 86400) { return 'il y a ' . floor($d / 3600) . ' h'; }
    if ($d < 172800){ return 'hier'; }
    return 'le ' . date('d/m/Y à H:i', strtotime($ts));
};
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(Settings::get('main_title', 'RPN')) ?> — Notifications</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <?= Theme::css() ?>
    <style>
        * { box-sizing:border-box; margin:0; padding:0; }
        body {
            font-family:'Poppins',sans-serif; min-height:100vh; color:var(--text); padding:42px 20px 60px;
            background:
                radial-gradient(circle at 12% 0%, var(--glow1), transparent 42%),
                radial-gradient(circle at 88% 100%, var(--glow2), transparent 44%),
                var(--bg-base);
        }
        body::before { content:""; position:fixed; top:0; left:0; right:0; height:6px; background:var(--bar); }
        .wrap { max-width:680px; margin:0 auto; }
        .topbar { display:flex; justify-content:space-between; align-items:center; gap:16px; flex-wrap:wrap; margin-bottom:24px; }
        h1 { font-size:24px; font-weight:800; } h1 span { color:var(--accent); }
        .nav { display:flex; gap:10px; flex-wrap:wrap; }
        .nav a, .nav button { color:var(--text); text-decoration:none; font-size:14px; padding:9px 16px; border-radius:10px;
            border:1px solid var(--card-border); background:transparent; cursor:pointer; font-family:inherit; }
        .nav a:hover, .nav button:hover { border-color:var(--accent); color:var(--accent); }

        .list { display:flex; flex-direction:column; gap:10px; }
        .note { display:flex; align-items:flex-start; gap:14px; padding:15px 18px; background:var(--card-bg);
            border:1px solid var(--card-border); border-radius:14px; box-shadow:var(--card-shadow); text-decoration:none; color:inherit; }
        .note:hover { border-color:var(--accent); }
        .note.unread { border-left:4px solid var(--accent); }
        .note .ic { font-size:22px; line-height:1.2; flex:0 0 auto; }
        .note .bd { flex:1; min-width:0; }
        .note .msg { font-size:14px; line-height:1.5; }
        .note .time { font-size:12px; color:var(--muted); margin-top:4px; }
        .note .dot { width:9px; height:9px; border-radius:50%; background:var(--accent); flex:0 0 auto; margin-top:6px; }
        .empty { text-align:center; padding:60px 20px; color:var(--muted); background:var(--card-bg);
            border:1px solid var(--card-border); border-radius:18px; }
    </style>
</head>
<body>
    <div class="wrap">
        <header class="topbar">
            <h1>🔔 <span>Notifications</span></h1>
            <div class="nav">
                <a href="<?= url('dashboard') ?>">← Tableau de bord</a>
                <?php if (!empty($items)): ?>
                    <form method="post" action="<?= url('notifications/clear') ?>"
                          onsubmit="return confirm('Effacer toutes les notifications ?');">
                        <button type="submit">🗑️ Tout effacer</button>
                    </form>
                <?php endif; ?>
            </div>
        </header>

        <?php if (empty($items)): ?>
            <div class="empty">Aucune notification pour l'instant.</div>
        <?php else: ?>
            <div class="list">
                <?php foreach ($items as $n): ?>
                    <?php
                        $unread = (int) $n['is_read'] === 0;
                        $href   = trim((string) $n['link']) !== '' ? url($n['link']) : null;
                        $tag    = $href ? 'a' : 'div';
                    ?>
                    <<?= $tag ?> class="note<?= $unread ? ' unread' : '' ?>"<?= $href ? ' href="' . htmlspecialchars($href) . '"' : '' ?>>
                        <span class="ic"><?= htmlspecialchars($n['icon'] ?: '🔔') ?></span>
                        <span class="bd">
                            <span class="msg"><?= htmlspecialchars($n['message']) ?></span>
                            <span class="time"><?= htmlspecialchars($ago($n['created_at'])) ?></span>
                        </span>
                        <?php if ($unread): ?><span class="dot" title="Non lue"></span><?php endif; ?>
                    </<?= $tag ?>>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
