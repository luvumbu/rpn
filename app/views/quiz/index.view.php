<?php
/**
 * LISTE DES QUESTIONNAIRES — /rpn/quiz
 * Variables : $user, $quizzes, $meta (par id : questions, participants, myResponse),
 *             $isAdmin, $notice.
 */
$uid = (int) ($user['id'] ?? 0);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(Settings::get('main_title', 'RPN')) ?> — Questionnaires</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <?= Theme::css() ?>
    <style>
        * { box-sizing:border-box; margin:0; padding:0; }
        body { font-family:'Poppins',sans-serif; min-height:100vh; color:var(--text); padding:40px 20px;
            background:
                radial-gradient(circle at 10% 0%, var(--glow1), transparent 40%),
                radial-gradient(circle at 90% 100%, var(--glow2), transparent 42%),
                var(--bg-base); }
        body::before { content:""; position:fixed; top:0; left:0; right:0; height:6px; background:var(--bar); }
        .wrap { max-width:1000px; margin:0 auto; }
        .top { display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; flex-wrap:wrap; gap:12px; }
        h1 { font-size:24px; } h1 span { color:var(--accent); }
        .nav { display:flex; gap:10px; flex-wrap:wrap; }
        .nav a { color:var(--text); text-decoration:none; font-size:14px; padding:8px 16px; border-radius:10px; border:1px solid var(--card-border); }
        .nav a:hover { border-color:var(--accent); color:var(--accent); }
        .nav a.cta { background:var(--accent); color:var(--accent-ink); border-color:var(--accent); font-weight:700; }

        .notice { background:rgba(42,157,74,.12); border:1px solid var(--vert,#2a9d4a); color:var(--text);
            padding:12px 16px; border-radius:12px; margin-bottom:20px; font-size:14px; }

        .grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(280px, 1fr)); gap:22px; }
        .card { background:var(--card-bg); border:1px solid var(--card-border); border-radius:18px; overflow:hidden;
            box-shadow:var(--card-shadow); display:flex; flex-direction:column; transition:transform .15s; text-decoration:none; color:inherit; }
        .card:hover { transform:translateY(-4px); }
        .qcover { width:100%; aspect-ratio:16/9; object-fit:cover; display:block; background:rgba(127,127,127,.12); }
        .qhead { padding:22px 22px 0; display:flex; align-items:center; gap:12px; }
        .qicon { width:48px; height:48px; flex:0 0 48px; border-radius:14px; display:flex; align-items:center; justify-content:center;
            font-size:24px; background:rgba(127,127,127,.12); color:var(--accent); }
        .body { padding:14px 22px 22px; display:flex; flex-direction:column; gap:8px; }
        .body h2 { font-size:18px; color:var(--accent); line-height:1.3; }
        .date { font-size:12px; color:var(--muted); }
        .draft-tag { display:inline-block; margin-left:6px; padding:2px 8px; border-radius:999px; font-weight:600;
            background:rgba(127,127,127,.18); color:var(--muted); border:1px solid var(--card-border); }
        .excerpt { font-size:14px; color:var(--muted); line-height:1.55; }
        .meta { display:flex; gap:14px; flex-wrap:wrap; font-size:12.5px; color:var(--muted); margin-top:2px; }
        .meta b { color:var(--text); }
        .done-tag { display:inline-block; padding:3px 10px; border-radius:999px; font-weight:700; font-size:12px;
            background:rgba(42,157,74,.15); color:var(--vert,#2a9d4a); border:1px solid var(--vert,#2a9d4a); }
        .empty-state { text-align:center; padding:60px 20px; color:var(--muted); background:var(--card-bg);
            border:1px solid var(--card-border); border-radius:18px; }
        .empty-state .big { font-size:46px; margin-bottom:10px; }
        .empty-state a { color:var(--accent); font-weight:700; text-decoration:none; }
    </style>
    <?= math_assets() ?>
</head>
<body>
    <div class="wrap">
        <div class="top">
            <h1>❓ <span>Questionnaires</span></h1>
            <div class="nav">
                <a href="<?= url('dashboard') ?>">← Tableau de bord</a>
                <a class="cta" href="<?= url('quiz/new') ?>">➕ Créer un questionnaire</a>
            </div>
        </div>

        <?php if (!empty($notice)): ?>
            <div class="notice"><?= htmlspecialchars($notice) ?></div>
        <?php endif; ?>

        <?php if (empty($quizzes)): ?>
            <div class="empty-state">
                <div class="big">📝</div>
                <p>Aucun questionnaire pour l'instant.</p>
                <p style="margin-top:10px;"><a href="<?= url('quiz/new') ?>">Crée le premier →</a></p>
            </div>
        <?php else: ?>
            <div class="grid">
                <?php foreach ($quizzes as $q): ?>
                    <?php
                        $qid  = (int) $q['id'];
                        $m    = $meta[$qid] ?? ['questions' => 0, 'participants' => 0, 'myResponse' => null];
                        $resp = $m['myResponse'];
                    ?>
                    <a class="card" href="<?= url('quiz/show') ?>?id=<?= $qid ?>">
                        <?php if (!empty($q['image'])): ?>
                            <img class="qcover" src="<?= url('uploads/quizzes/' . rawurlencode($q['image'])) ?>" alt="">
                        <?php endif; ?>
                        <div class="qhead">
                            <div class="qicon">❓</div>
                            <div style="min-width:0;">
                                <span class="date">
                                    <?= date('d/m/Y', strtotime($q['created_at'])) ?>
                                    <?php if ((int) $q['active'] !== 1): ?><span class="draft-tag">Brouillon</span><?php endif; ?>
                                </span>
                            </div>
                        </div>
                        <div class="body">
                            <h2><?= htmlspecialchars($q['title']) ?></h2>
                            <?php if (trim((string) $q['description']) !== ''): ?>
                                <p class="excerpt"><?= htmlspecialchars(mb_strimwidth($q['description'], 0, 120, '…')) ?></p>
                            <?php endif; ?>
                            <div class="meta">
                                <span>📋 <b><?= (int) $m['questions'] ?></b> question<?= $m['questions'] > 1 ? 's' : '' ?></span>
                                <span>👥 <b><?= (int) $m['participants'] ?></b> participant<?= $m['participants'] > 1 ? 's' : '' ?></span>
                            </div>
                            <?php if ($resp): ?>
                                <span class="done-tag">✅ Ton score : <?= (int) $resp['score'] ?>/<?= (int) $resp['total'] ?> (<?= (int) $resp['total'] > 0 ? round((int) $resp['score'] / (int) $resp['total'] * 100) : 0 ?> %)</span>
                            <?php endif; ?>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
