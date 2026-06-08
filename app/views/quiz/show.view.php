<?php
/**
 * AFFICHAGE D'UN QUESTIONNAIRE — répondre OU voir ses résultats.
 * Variables : $user, $quiz, $questions (avec options), $canManage,
 *             $participants, $response (null si pas répondu), $myAnswers
 *             ([question_id => [option_id…]]), $error, $notice.
 */
$qid         = (int) $quiz['id'];
$answered    = !empty($response);
$showRedo    = isset($_GET['redo']);               // « Refaire » : réaffiche le formulaire
$mustComplete = !empty($mustComplete);             // questionnaire obligatoire non terminé (membre)
$canRetry    = !empty($canRetry);                  // tentatives restantes (ou illimité)
$asForm      = !$answered || ($showRedo && $canRetry) || $mustComplete; // mode formulaire vs résultats
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(Settings::get('main_title', 'RPN')) ?> — <?= htmlspecialchars($quiz['title']) ?></title>
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
        .wrap { max-width:760px; margin:0 auto; }
        .top { display:flex; justify-content:space-between; align-items:center; margin-bottom:18px; flex-wrap:wrap; gap:12px; }
        .back { color:var(--text); text-decoration:none; font-size:14px; padding:8px 16px; border-radius:10px; border:1px solid var(--card-border); }
        .back:hover { border-color:var(--accent); color:var(--accent); }
        .admin-tools { display:flex; gap:8px; flex-wrap:wrap; }
        .admin-tools a, .admin-tools button { font:inherit; font-size:13px; font-weight:600; cursor:pointer;
            text-decoration:none; padding:8px 14px; border-radius:10px; border:1px solid var(--card-border);
            background:var(--card-bg); color:var(--text); }
        .admin-tools a:hover { border-color:var(--accent); color:var(--accent); }
        .admin-tools .danger:hover { border-color:var(--rouge,#e63946); color:var(--rouge,#e63946); }

        .header { background:var(--card-bg); border:1px solid var(--card-border); border-radius:18px;
            box-shadow:var(--card-shadow); padding:26px 28px; margin-bottom:20px; }
        .quiz-cover { width:100%; max-height:240px; object-fit:cover; border-radius:14px; margin-bottom:16px; display:block; }
        .header h1 { font-size:26px; color:var(--accent); line-height:1.2; margin-bottom:8px; }
        .header .desc { font-size:15px; color:var(--muted); line-height:1.6; margin-bottom:12px; white-space:pre-wrap; }
        .header .meta { display:flex; gap:16px; flex-wrap:wrap; font-size:13px; color:var(--muted); }
        .header .meta b { color:var(--text); }
        .draft-tag { display:inline-block; padding:3px 10px; border-radius:999px; font-weight:600; font-size:12px;
            background:rgba(127,127,127,.18); color:var(--muted); border:1px solid var(--card-border); }

        .notice { background:rgba(42,157,74,.12); border:1px solid var(--vert,#2a9d4a); padding:12px 16px;
            border-radius:12px; margin-bottom:18px; font-size:14px; }
        .error { background:rgba(230,57,70,.12); border:1px solid var(--rouge,#e63946); padding:12px 16px;
            border-radius:12px; margin-bottom:18px; font-size:14px; }
        .gate { display:flex; align-items:flex-start; gap:14px; background:rgba(230,57,70,.12);
            border:1px solid var(--rouge,#e63946); border-left:6px solid var(--rouge,#e63946);
            border-radius:14px; padding:16px 18px; margin-bottom:18px; }
        .gate-ico { font-size:26px; line-height:1; flex:0 0 auto; }
        .gate b { font-size:15px; } .gate p { font-size:13.5px; color:var(--muted); margin-top:4px; line-height:1.5; }

        /* Bandeau de score */
        .score { display:flex; align-items:center; gap:18px; background:var(--card-bg); border:1px solid var(--accent);
            border-radius:18px; padding:22px 26px; margin-bottom:20px; }
        .score .big { font-size:40px; font-weight:800; color:var(--accent); line-height:1; }
        .score.low { border-color:var(--rouge,#e63946); }
        .score .lbl { font-size:14px; color:var(--muted); }
        .score .pct { font-size:15px; font-weight:800; }
        .score .pbar { height:8px; border-radius:99px; background:rgba(127,127,127,.25); overflow:hidden; margin:8px 0 6px; max-width:320px; }
        .score .pbar span { display:block; height:100%; background:var(--accent); border-radius:99px; }
        .score.low .pbar span { background:var(--rouge,#e63946); }
        .score .att { font-size:12.5px; color:var(--muted); }

        .q { background:var(--card-bg); border:1px solid var(--card-border); border-radius:16px; padding:20px 22px; margin-bottom:16px; }
        .q .qbody { font-size:17px; font-weight:700; margin-bottom:6px; }
        .q .qtype { font-size:12.5px; color:var(--muted); margin-bottom:14px; }
        .opt { display:flex; align-items:center; gap:12px; padding:11px 14px; border-radius:11px; margin-bottom:9px;
            border:1px solid var(--card-border); background:rgba(127,127,127,.05); cursor:pointer; }
        .opt:hover { border-color:var(--accent); }
        .opt input { width:18px; height:18px; flex:0 0 18px; accent-color:var(--accent); }
        .opt .txt { flex:1; font-size:15px; }
        .opt.locked { cursor:default; }
        .opt.locked:hover { border-color:var(--card-border); }

        /* Corrigé (résultats) */
        .opt.correct { border-color:var(--vert,#2a9d4a); background:rgba(42,157,74,.12); }
        .opt.wrong   { border-color:var(--rouge,#e63946); background:rgba(230,57,70,.12); }
        .tag { font-size:12px; font-weight:700; padding:2px 9px; border-radius:999px; flex:0 0 auto; }
        .tag.ok  { background:var(--vert,#2a9d4a); color:#fff; }
        .tag.ko  { background:var(--rouge,#e63946); color:#fff; }
        .tag.miss{ background:transparent; color:var(--vert,#2a9d4a); border:1px solid var(--vert,#2a9d4a); }
        .q.qok .qbody::after  { content:" ✅"; }
        .q.qko .qbody::after  { content:" ❌"; }

        .submit { font:inherit; font-size:16px; font-weight:700; cursor:pointer; border:none; border-radius:12px;
            padding:15px 30px; background:var(--accent); color:var(--accent-ink); }
        .redo { display:inline-flex; align-items:center; font:inherit; font-size:14px; font-weight:600; text-decoration:none;
            border:1px solid var(--card-border); color:var(--text); border-radius:12px; padding:13px 22px; }
        .redo:hover { border-color:var(--accent); color:var(--accent); }
        .actions { display:flex; gap:12px; flex-wrap:wrap; margin-top:6px; }
        .empty { text-align:center; color:var(--muted); padding:30px; }
    </style>
    <?= math_assets() ?>
</head>
<body>
    <div class="wrap">
        <div class="top">
            <?php if ($mustComplete): ?>
                <a class="back" href="<?= url('logout') ?>">⏻ Se déconnecter</a>
            <?php else: ?>
                <a class="back" href="<?= url('quiz') ?>">← Tous les questionnaires</a>
            <?php endif; ?>
            <?php if (!empty($canManage)): ?>
            <div class="admin-tools">
                <a href="<?= url('quiz/edit') ?>?id=<?= $qid ?>">✏️ Modifier</a>
                <form method="post" action="<?= url('quiz/toggle') ?>" style="margin:0;">
                    <input type="hidden" name="id" value="<?= $qid ?>">
                    <button type="submit"><?= (int) $quiz['active'] === 1 ? '📝 Repasser en brouillon' : '🚀 Publier' ?></button>
                </form>
                <form method="post" action="<?= url('quiz/delete') ?>" style="margin:0;" onsubmit="return confirm('Supprimer définitivement ce questionnaire et toutes les participations ?');">
                    <input type="hidden" name="id" value="<?= $qid ?>">
                    <button type="submit" class="danger">🗑️ Supprimer</button>
                </form>
            </div>
            <?php endif; ?>
        </div>

        <?php if (!empty($notice)): ?><div class="notice"><?= htmlspecialchars($notice) ?></div><?php endif; ?>
        <?php if (!empty($error)): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

        <?php if ($mustComplete): ?>
            <div class="gate">
                <span class="gate-ico">⛔</span>
                <div>
                    <b>Questionnaire obligatoire</b>
                    <p>Tu dois répondre à ce questionnaire<?= (int) ($quiz['pass_required'] ?? 0) === 1 ? ' <b>et obtenir toutes les bonnes réponses</b>' : '' ?> pour accéder au reste de l'application.</p>
                </div>
            </div>
        <?php endif; ?>

        <div class="header">
            <?php if (!empty($quiz['image'])): ?>
                <img class="quiz-cover" src="<?= url('uploads/quizzes/' . rawurlencode($quiz['image'])) ?>" alt="">
            <?php endif; ?>
            <h1><?= htmlspecialchars($quiz['title']) ?>
                <?php if ((int) $quiz['active'] !== 1): ?><span class="draft-tag">Brouillon</span><?php endif; ?>
            </h1>
            <?php if (trim((string) $quiz['description']) !== ''): ?>
                <div class="desc"><?= htmlspecialchars($quiz['description']) ?></div>
            <?php endif; ?>
            <div class="meta">
                <span>📋 <b><?= count($questions) ?></b> question<?= count($questions) > 1 ? 's' : '' ?></span>
                <span>👥 <b><?= (int) $participants ?></b> participant<?= $participants > 1 ? 's' : '' ?></span>
                <span>✍️ par <b><?= htmlspecialchars($quiz['author_name'] ?: 'Membre') ?></b></span>
            </div>
        </div>

        <?php if (empty($questions)): ?>
            <div class="header"><p class="empty">Ce questionnaire ne contient pas encore de question.</p></div>

        <?php elseif ($asForm): ?>
            <!-- ============ MODE FORMULAIRE : répondre ============ -->
            <form method="post" action="<?= url('quiz/submit') ?>" id="answerForm">
                <input type="hidden" name="id" value="<?= $qid ?>">
                <?php foreach ($questions as $i => $q): $type = $q['type']; ?>
                    <div class="q">
                        <div class="qbody"><?= ($i + 1) ?>. <?= htmlspecialchars($q['body']) ?></div>
                        <div class="qtype"><?= $type === 'multiple' ? '☑️ Plusieurs réponses possibles' : '🔘 Une seule réponse' ?></div>
                        <?php foreach ($q['options'] as $o): ?>
                            <label class="opt">
                                <?php if ($type === 'multiple'): ?>
                                    <input type="checkbox" name="answer[<?= (int) $q['id'] ?>][]" value="<?= (int) $o['id'] ?>">
                                <?php else: ?>
                                    <input type="radio" name="answer[<?= (int) $q['id'] ?>]" value="<?= (int) $o['id'] ?>">
                                <?php endif; ?>
                                <span class="txt"><?= htmlspecialchars($o['label']) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
                <div class="actions">
                    <button type="submit" class="submit">✅ Valider mes réponses</button>
                    <?php if ($answered): ?>
                        <a class="redo" href="<?= url('quiz/show') ?>?id=<?= $qid ?>#resultats">Annuler</a>
                    <?php endif; ?>
                </div>
            </form>

        <?php else: ?>
            <!-- ============ MODE RÉSULTATS : corrigé ============ -->
            <?php
                $score = (int) $response['score'];
                $total = (int) $response['total'];
                $pct   = $total > 0 ? round($score / $total * 100) : 0;
            ?>
            <div class="score <?= $pct >= 50 ? 'ok' : 'low' ?>" id="resultats">
                <div class="big"><?= $score ?>/<?= $total ?></div>
                <div style="flex:1;">
                    <div class="lbl">Ton score</div>
                    <div class="pct"><?= $pct ?> % de réussite</div>
                    <div class="pbar"><span style="width:<?= $pct ?>%"></span></div>
                    <?php if (!empty($maxAttempts)): ?>
                        <div class="att">🔁 Tentative <?= (int) $attemptsUsed ?> / <?= (int) $maxAttempts ?></div>
                    <?php else: ?>
                        <div class="att">🔁 Tentative n°<?= (int) $attemptsUsed ?> · tentatives illimitées</div>
                    <?php endif; ?>
                </div>
            </div>

            <?php foreach ($questions as $i => $q): ?>
                <?php
                    $mine = $myAnswers[(int) $q['id']] ?? [];
                    // La question est-elle réussie ? (mêmes options que les bonnes réponses)
                    $correctIds = [];
                    foreach ($q['options'] as $o) { if ((int) $o['is_correct'] === 1) { $correctIds[] = (int) $o['id']; } }
                    $mineSorted = $mine; sort($mineSorted);
                    $corrSorted = $correctIds; sort($corrSorted);
                    $qok = ($mineSorted === $corrSorted && !empty($corrSorted));
                ?>
                <div class="q <?= $qok ? 'qok' : 'qko' ?>">
                    <div class="qbody"><?= ($i + 1) ?>. <?= htmlspecialchars($q['body']) ?></div>
                    <div class="qtype"><?= $q['type'] === 'multiple' ? '☑️ Plusieurs réponses' : '🔘 Une seule réponse' ?></div>
                    <?php foreach ($q['options'] as $o): ?>
                        <?php
                            $oid       = (int) $o['id'];
                            $isCorrect = (int) $o['is_correct'] === 1;
                            $chosen    = in_array($oid, $mine, true);
                            $cls = '';
                            if ($chosen && $isCorrect)      { $cls = 'correct'; }
                            elseif ($chosen && !$isCorrect) { $cls = 'wrong'; }
                            elseif (!$chosen && $isCorrect) { $cls = 'correct'; }
                        ?>
                        <div class="opt locked <?= $cls ?>">
                            <input type="<?= $q['type'] === 'multiple' ? 'checkbox' : 'radio' ?>" <?= $chosen ? 'checked' : '' ?> disabled>
                            <span class="txt"><?= htmlspecialchars($o['label']) ?></span>
                            <?php if ($chosen && $isCorrect): ?>
                                <span class="tag ok">Bonne réponse</span>
                            <?php elseif ($chosen && !$isCorrect): ?>
                                <span class="tag ko">Ton choix · faux</span>
                            <?php elseif (!$chosen && $isCorrect): ?>
                                <span class="tag miss">À cocher</span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>

            <div class="actions">
                <?php if (!empty($canRetry)): ?>
                    <a class="redo" href="<?= url('quiz/show') ?>?id=<?= $qid ?>&amp;redo=1">🔁 Refaire le questionnaire</a>
                <?php else: ?>
                    <span class="redo" style="opacity:.7;cursor:default;">🔒 Nombre maximum de tentatives atteint (<?= (int) $maxAttempts ?>)</span>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
