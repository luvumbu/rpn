<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RPN — Statistiques</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <?= Theme::css() ?>
    <style>
        * { box-sizing:border-box; margin:0; padding:0; }
        body { font-family:'Poppins',sans-serif; min-height:100vh; color:var(--text); padding:40px 20px 80px;
            background: radial-gradient(circle at 10% 0%, var(--glow1), transparent 42%), var(--bg-base); }
        body::before { content:""; position:fixed; top:0; left:0; right:0; height:6px; background:var(--bar); z-index:5; }
        .wrap { max-width:980px; margin:0 auto; }
        h1 { font-size:24px; margin-bottom:4px; } h1 span { color:var(--accent); }
        .subtitle { color:var(--muted); font-size:13px; margin-bottom:22px; }
        .grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(150px,1fr)); gap:16px; margin-bottom:8px; }
        .stat { background:var(--card-bg); border:1px solid var(--card-border); border-radius:16px; padding:20px; box-shadow:var(--card-shadow); }
        .stat .num { font-size:32px; font-weight:800; color:var(--accent); line-height:1.1; }
        .stat .lbl { color:var(--muted); font-size:13px; margin-top:6px; }
        .section-title { font-size:12px; text-transform:uppercase; letter-spacing:1.4px; color:var(--muted); margin:30px 4px 12px; font-weight:700; }
        .card { background:var(--card-bg); border:1px solid var(--card-border); border-radius:16px; box-shadow:var(--card-shadow); overflow:hidden; }
        table { width:100%; border-collapse:collapse; }
        th, td { text-align:left; padding:12px 16px; font-size:14px; border-bottom:1px solid var(--card-border); }
        th { font-size:12px; text-transform:uppercase; letter-spacing:.5px; color:var(--muted); font-weight:700; }
        tr:last-child td { border-bottom:none; }
        td a { color:var(--text); text-decoration:none; }
        td a:hover { color:var(--accent); }
        td.n { text-align:right; font-weight:700; color:var(--accent); white-space:nowrap; }
        .empty { padding:18px 16px; color:var(--muted); font-size:14px; }
    </style>
</head>
<body>
    <div class="wrap">
        <?php view('admin/_nav', ['active' => 'analytics']); ?>
        <h1>📊 <span>Statistiques</span></h1>
        <p class="subtitle">Vue d'ensemble de l'activité du site (sur les 30 derniers jours pour « actifs / nouveaux »).</p>

        <p class="section-title">👥 Membres</p>
        <div class="grid">
            <div class="stat"><div class="num"><?= (int) $members ?></div><div class="lbl">Membres au total</div></div>
            <div class="stat"><div class="num"><?= (int) $activeMembers ?></div><div class="lbl">Actifs (30 j)</div></div>
            <div class="stat"><div class="num"><?= (int) $newMembers ?></div><div class="lbl">Nouveaux (30 j)</div></div>
        </div>

        <p class="section-title">📰 Articles</p>
        <div class="grid">
            <div class="stat"><div class="num"><?= (int) $articles ?></div><div class="lbl">Articles (dont <?= (int) $articlesActive ?> publiés)</div></div>
            <div class="stat"><div class="num"><?= (int) $totalViews ?></div><div class="lbl">Vues cumulées</div></div>
        </div>
        <p class="section-title" style="margin-top:16px;">Articles les plus vus</p>
        <div class="card">
            <table>
                <tr><th>Article</th><th style="text-align:right;">Vues</th></tr>
                <?php if (empty($topArticles)): ?>
                    <tr><td colspan="2" class="empty">Aucun article pour l'instant.</td></tr>
                <?php else: foreach ($topArticles as $a): ?>
                    <tr>
                        <td><a href="<?= url('article') ?>?id=<?= (int) $a['id'] ?>"><?= htmlspecialchars($a['title']) ?></a></td>
                        <td class="n"><?= (int) $a['views'] ?></td>
                    </tr>
                <?php endforeach; endif; ?>
            </table>
        </div>

        <p class="section-title">❓ Questionnaires</p>
        <div class="grid">
            <div class="stat"><div class="num"><?= (int) $quizzes ?></div><div class="lbl">Quiz (dont <?= (int) $quizActive ?> publiés)</div></div>
            <div class="stat"><div class="num"><?= (int) $quizResponses ?></div><div class="lbl">Participations</div></div>
            <div class="stat"><div class="num"><?= (int) $quizAvg ?> %</div><div class="lbl">Réussite moyenne</div></div>
        </div>
        <p class="section-title" style="margin-top:16px;">Quiz les plus suivis</p>
        <div class="card">
            <table>
                <tr><th>Questionnaire</th><th style="text-align:right;">Participants</th><th style="text-align:right;">Réussite moy.</th></tr>
                <?php if (empty($topQuizzes)): ?>
                    <tr><td colspan="3" class="empty">Aucun questionnaire pour l'instant.</td></tr>
                <?php else: foreach ($topQuizzes as $q): ?>
                    <tr>
                        <td><a href="<?= url('quiz/show') ?>?id=<?= (int) $q['id'] ?>"><?= htmlspecialchars($q['title']) ?></a></td>
                        <td class="n"><?= (int) $q['participants'] ?></td>
                        <td class="n"><?= $q['avgpct'] !== null ? (int) $q['avgpct'] . ' %' : '—' ?></td>
                    </tr>
                <?php endforeach; endif; ?>
            </table>
        </div>

        <p class="section-title">📅 Agenda</p>
        <div class="grid">
            <div class="stat"><div class="num"><?= (int) $appointments ?></div><div class="lbl">Événements / créneaux</div></div>
            <div class="stat"><div class="num"><?= (int) $bookings ?></div><div class="lbl">Réservations</div></div>
        </div>

        <p class="section-title">💳 Paiements</p>
        <div class="grid">
            <div class="stat"><div class="num"><?= number_format(((int) $payTotal) / 100, 2, ',', ' ') ?> <?= htmlspecialchars($payCurrency) ?></div><div class="lbl">Total encaissé</div></div>
            <div class="stat"><div class="num"><?= (int) $payCount ?></div><div class="lbl">Paiements réussis</div></div>
            <div class="stat"><div class="num"><?= (int) $paySubs ?></div><div class="lbl">dont abonnements</div></div>
        </div>
        <p class="section-title" style="margin-top:16px;">Derniers paiements</p>
        <div class="card">
            <table>
                <tr><th>Date</th><th>Membre</th><th>Type</th><th style="text-align:right;">Montant</th></tr>
                <?php if (empty($recentPays)): ?>
                    <tr><td colspan="4" class="empty">Aucun paiement pour l'instant.</td></tr>
                <?php else: foreach ($recentPays as $p): ?>
                    <tr>
                        <td><?= date('d/m/Y', strtotime($p['created_at'])) ?></td>
                        <td><?= htmlspecialchars(($p['uname'] ?? '') !== '' ? $p['uname'] : ('Membre #' . (int) $p['user_id'])) ?></td>
                        <td><?= $p['type'] === 'subscription' ? '🔄 Abonnement' : '❤️ Ponctuel' ?></td>
                        <td class="n"><?= number_format(((int) $p['amount']) / 100, 2, ',', ' ') ?> <?= strtoupper(htmlspecialchars($p['currency'])) ?></td>
                    </tr>
                <?php endforeach; endif; ?>
            </table>
        </div>
    </div>
</body>
</html>
