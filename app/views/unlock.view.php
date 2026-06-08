<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex,nofollow">
    <title>·</title>
    <?= Theme::css() ?>
    <style>
        * { box-sizing:border-box; margin:0; padding:0; }
        body { font-family:'Poppins',sans-serif; min-height:100vh; color:var(--text); display:flex; align-items:center; justify-content:center; padding:20px; background:var(--bg-base); }
        .card { background:var(--card-bg); border:1px solid var(--card-border); border-radius:18px; padding:32px; width:100%; max-width:460px; box-shadow:var(--card-shadow); }
        h1 { color:var(--accent); font-size:20px; margin-bottom:18px; }
        .msg { background:rgba(42,157,74,.15); border:1px solid rgba(42,157,74,.45); color:#2a9d4a; padding:10px 14px; border-radius:10px; margin-bottom:18px; font-size:14px; }
        table { width:100%; border-collapse:collapse; margin-bottom:18px; }
        th,td { text-align:left; padding:10px; font-size:14px; border-bottom:1px solid var(--card-border); }
        th { color:var(--accent); }
        a.btn { display:inline-block; padding:6px 12px; border-radius:8px; text-decoration:none; font-size:13px; font-weight:600; color:#fff; background:var(--vert); }
        a.all { display:inline-block; margin-top:6px; padding:11px 18px; border-radius:10px; text-decoration:none; font-weight:700; color:var(--accent-ink); background:var(--accent); }
        .empty { color:var(--muted); font-size:14px; padding:14px 0; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Gestion des blocages</h1>

        <?php if ($msg): ?><div class="msg"><?= $msg ?></div><?php endif; ?>

        <?php if (empty($blocked)): ?>
            <p class="empty">Aucune IP bloquée actuellement.</p>
        <?php else: ?>
            <table>
                <tr><th>IP bloquée</th><th>Temps restant</th><th></th></tr>
                <?php foreach ($blocked as $ip => $left): ?>
                    <tr>
                        <td><?= htmlspecialchars($ip) ?></td>
                        <td><?= LoginGuard::humanRemaining($left) ?></td>
                        <td>
                            <a class="btn" href="<?= url('unlock') ?>?key=<?= urlencode($key) ?>&ip=<?= urlencode($ip) ?>">Débloquer</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
            <a class="all" href="<?= url('unlock') ?>?key=<?= urlencode($key) ?>&all=1">Tout débloquer</a>
        <?php endif; ?>
    </div>
</body>
</html>
