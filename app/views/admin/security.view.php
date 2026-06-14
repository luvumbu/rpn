<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RPN — Sécurité</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <?= Theme::css() ?>
    <style>
        * { box-sizing:border-box; margin:0; padding:0; }
        body {
            font-family:'Poppins',sans-serif; min-height:100vh; color:var(--text); padding:40px 20px;
            background: radial-gradient(circle at 90% 0%, var(--glow1), transparent 45%), var(--bg-base);
        }
        body::before { content:""; position:fixed; top:0; left:0; right:0; height:6px; background: var(--bar); }
        .wrap { max-width:760px; margin:0 auto; }
        .top { display:flex; justify-content:space-between; align-items:center; margin-bottom:26px; flex-wrap:wrap; gap:12px; }
        h1 { font-size:22px; } h1 span { color:var(--accent); }
        .nav a { color:var(--text); text-decoration:none; font-size:14px; padding:8px 16px; border-radius:10px; border:1px solid var(--card-border); }
        .nav a:hover { border-color:var(--accent); color:var(--accent); }
        .card { background:var(--card-bg); border:1px solid var(--card-border); border-radius:16px; overflow:hidden; box-shadow:var(--card-shadow); }
        table { width:100%; border-collapse:collapse; }
        th,td { text-align:left; padding:13px 16px; font-size:14px; border-bottom:1px solid var(--card-border); }
        th { color:var(--accent); background:rgba(127,127,127,.08); }
        button { border:none; cursor:pointer; padding:7px 14px; border-radius:8px; font-family:inherit; font-size:13px; font-weight:600; color:#fff; }
        .b-unblock { background:var(--vert); }
        .b-all { background:var(--accent); color:var(--accent-ink); padding:11px 20px; border-radius:10px; font-weight:700; margin-top:18px; }
        button:hover { filter:brightness(1.08); }
        .empty { text-align:center; padding:40px; color:var(--muted); }
        .count { color:var(--muted); font-size:14px; margin-bottom:16px; }
    </style>
</head>
<body>
    <div class="wrap">
        <?php view('admin/_nav', ['active' => 'security']); ?>
        <div class="top">
            <h1>🛡️ <span>Sécurité</span> — IP bloquées</h1>
        </div>

        <?php if (empty($blocked)): ?>
            <div class="card"><p class="empty">✅ Aucune IP bloquée actuellement.</p></div>
        <?php else: ?>
            <p class="count"><?= count($blocked) ?> IP bloquée(s)</p>
            <div class="card">
                <table>
                    <tr><th>Adresse IP</th><th>Temps restant</th><th></th></tr>
                    <?php foreach ($blocked as $ip => $left): ?>
                        <tr>
                            <td><?= htmlspecialchars($ip) ?></td>
                            <td><?= LoginGuard::humanRemaining($left) ?></td>
                            <td>
                                <form method="post" action="<?= url('admin/ip_unblock') ?>">
                                    <input type="hidden" name="ip" value="<?= htmlspecialchars($ip) ?>">
                                    <button class="b-unblock" type="submit">Débloquer</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
            <form method="post" action="<?= url('admin/ip_unblock_all') ?>"
                  onsubmit="return confirm('Débloquer toutes les IP ?');">
                <button class="b-all" type="submit">Tout débloquer</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
