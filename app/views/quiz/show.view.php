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
        /* Bloc rapide : photo de couverture (auteur/admin) */
        .cover-edit { margin-top:10px; border:1px solid var(--card-border); border-radius:12px; background:var(--card-bg); }
        .cover-edit summary { cursor:pointer; padding:11px 14px; font-size:13.5px; font-weight:700; color:var(--accent); list-style:none; }
        .cover-edit summary::-webkit-details-marker { display:none; }
        .cover-edit[open] summary { border-bottom:1px solid var(--card-border); }
        .cover-form { display:flex; flex-direction:column; gap:10px; padding:14px; }
        .cover-form input[type=file] { font-size:13px; color:var(--muted); }
        .cover-form button { align-self:flex-start; font:inherit; font-size:13px; font-weight:700; cursor:pointer;
            padding:9px 16px; border:none; border-radius:10px; background:var(--accent); color:var(--accent-ink); }
        .cover-rm { display:flex; align-items:center; gap:8px; font-size:13px; color:var(--muted); cursor:pointer; }
        .cover-hint { font-size:12px; color:var(--muted); margin:0; }

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
        .q .q-image { display:block; max-width:100%; max-height:320px; border-radius:12px; border:1px solid var(--card-border); margin:0 0 14px; object-fit:contain; }
        .opt { display:flex; align-items:center; gap:12px; padding:11px 14px; border-radius:11px; margin-bottom:9px;
            border:1px solid var(--card-border); background:rgba(127,127,127,.05); cursor:pointer; }
        .opt:hover { border-color:var(--accent); }
        .opt input { width:18px; height:18px; flex:0 0 18px; accent-color:var(--accent); }
        .opt .txt { flex:1; font-size:15px; }
        .opt.locked { cursor:default; pointer-events:none; }
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

        .submit { font:inherit; font-size:16px; font-weight:800; letter-spacing:.3px; cursor:pointer; border:none;
            display:inline-flex; align-items:center; justify-content:center; gap:10px;
            padding:16px 34px; border-radius:14px; color:var(--accent-ink);
            background:linear-gradient(135deg, var(--accent), color-mix(in srgb, var(--accent) 70%, #000));
            box-shadow:0 12px 30px rgba(0,0,0,.28), inset 0 1px 0 rgba(255,255,255,.28);
            transition:transform .15s, box-shadow .2s, filter .15s; }
        .submit:hover { transform:translateY(-2px); box-shadow:0 18px 42px rgba(0,0,0,.34), inset 0 1px 0 rgba(255,255,255,.32); filter:brightness(1.06); }
        .submit:active { transform:translateY(0); box-shadow:0 8px 20px rgba(0,0,0,.25); }
        .submit:disabled { opacity:.7; cursor:default; transform:none; filter:none; }
        .redo { display:inline-flex; align-items:center; font:inherit; font-size:14px; font-weight:600; text-decoration:none;
            border:1px solid var(--card-border); color:var(--text); border-radius:12px; padding:13px 22px; }
        .redo:hover { border-color:var(--accent); color:var(--accent); }
        .actions { display:flex; gap:12px; flex-wrap:wrap; margin-top:6px; }
        .empty { text-align:center; color:var(--muted); padding:30px; }

        /* ===== Mode « une question à la fois » ===== */
        .step-nav { display:flex; align-items:center; gap:14px; flex-wrap:wrap; margin:4px 0 18px; }
        .step-prog { flex:1; min-width:120px; height:8px; border-radius:999px; background:rgba(127,127,127,.18); overflow:hidden; }
        .step-prog span { display:block; height:100%; width:0; border-radius:999px; background:var(--accent); transition:width .35s ease; }
        .step-count { font-size:13px; font-weight:700; color:var(--muted); white-space:nowrap; }
        .step-btns { display:flex; gap:10px; }
        .step-prev, .step-next { font:inherit; font-size:14px; font-weight:700; cursor:pointer; border-radius:11px; padding:11px 20px;
            border:1px solid var(--card-border); background:var(--card-bg); color:var(--text); transition:border-color .15s, color .15s, transform .1s; }
        .step-prev:hover, .step-next:hover { border-color:var(--accent); color:var(--accent); }
        .step-prev:disabled { opacity:.45; cursor:default; }
        .step-next.verify { background:var(--accent); color:var(--accent-ink); border-color:var(--accent); }
        .step-next:active { transform:translateY(1px); }
        /* Secousse (réponse fausse / réponse manquante) */
        .shake { animation:q-shake .5s cubic-bezier(.36,.07,.19,.97); }
        @keyframes q-shake { 10%,90%{transform:translateX(-1px);} 20%,80%{transform:translateX(2px);}
            30%,50%,70%{transform:translateX(-6px);} 40%,60%{transform:translateX(6px);} }
        /* Confettis (bonne réponse) */
        .confetti-piece { position:fixed; top:-12px; width:10px; height:14px; z-index:9999; pointer-events:none;
            border-radius:2px; animation:confetti-fall linear forwards; }
        @keyframes confetti-fall { to { transform:translateY(105vh) rotate(540deg); opacity:.9; } }

        /* Chrono */
        .quiz-timer { position:sticky; top:8px; z-index:5; display:inline-flex; align-items:center; gap:8px;
            font-size:15px; font-weight:800; color:var(--accent-ink); background:var(--accent);
            padding:9px 16px; border-radius:999px; margin-bottom:16px; box-shadow:0 8px 22px rgba(0,0,0,.25); }
        .quiz-timer.low { background:var(--rouge,#e63946); color:#fff; animation:timer-pulse 1s infinite; }
        @keyframes timer-pulse { 0%,100%{transform:scale(1);} 50%{transform:scale(1.05);} }
        /* Explication d'une question */
        .q-explain { margin-top:12px; padding:11px 14px; font-size:14px; line-height:1.5; border-radius:10px;
            background:rgba(127,127,127,.08); border:1px solid var(--card-border); border-left:3px solid var(--accent); color:var(--text); }

        /* ===== Types interactifs : saisie / trous / ordre / association ===== */
        .ans-input { width:100%; padding:12px 14px; border-radius:11px; border:1px solid var(--card-border);
            background:rgba(127,127,127,.08); color:var(--text); font-family:inherit; font-size:16px; }
        .ans-input:focus { outline:none; border-color:var(--accent); }
        .ans-num { max-width:220px; }
        .fill-line { font-size:16px; line-height:2.1; }
        .fill-blank { display:inline-block; width:90px; padding:5px 8px; margin:0 3px; border-radius:8px;
            border:1px solid var(--card-border); background:rgba(127,127,127,.08); color:var(--text); font-family:inherit; font-size:15px; text-align:center; }
        .fill-blank:focus { outline:none; border-color:var(--accent); }
        .fill-blank.correct { border-color:var(--vert,#2a9d4a); background:rgba(42,157,74,.14); }
        .fill-blank.wrong { border-color:var(--rouge,#e63946); background:rgba(230,57,70,.12); }
        /* Liste à remettre dans l'ordre */
        .order-list { list-style:none; padding:0; margin:0; }
        .order-item { display:flex; align-items:center; gap:12px; padding:12px 14px; margin-bottom:9px; border-radius:11px;
            border:1px solid var(--card-border); background:rgba(127,127,127,.06); cursor:grab; }
        .order-item .grip { font-size:18px; color:var(--muted); flex:0 0 auto; }
        .order-item .num { font-weight:800; color:var(--accent); flex:0 0 auto; width:22px; }
        .order-item.drag-over { border-top:2px solid var(--accent); }
        .order-item.correct { border-color:var(--vert,#2a9d4a); background:rgba(42,157,74,.12); }
        .order-item.wrong { border-color:var(--rouge,#e63946); background:rgba(230,57,70,.12); }
        /* Association par paires */
        .match-row { display:flex; align-items:center; gap:12px; padding:9px 0; flex-wrap:wrap; }
        .match-left { flex:1; min-width:140px; font-size:15px; font-weight:600;
            padding:11px 14px; border-radius:11px; border:1px solid var(--card-border); background:rgba(127,127,127,.06); }
        .match-arrow { color:var(--muted); font-size:18px; }
        .match-sel { flex:1; min-width:140px; padding:11px 12px; border-radius:11px; border:1px solid var(--card-border);
            background:rgba(127,127,127,.08); color:var(--text); font-family:inherit; font-size:15px; }
        .match-sel:focus { outline:none; border-color:var(--accent); }
        .match-sel.correct { border-color:var(--vert,#2a9d4a); }
        .match-sel.wrong { border-color:var(--rouge,#e63946); }
        .ans-recap { margin-top:8px; font-size:14px; }
        .ans-recap .ok-txt { color:var(--vert,#2a9d4a); font-weight:700; }
        .ans-recap .ko-txt { color:var(--rouge,#e63946); font-weight:700; }
        .ans-recap .exp { color:var(--muted); }
        /* Exercice interactif (manipulable) */
        .interactive-host { background:#fff; border-radius:10px; padding:10px; display:flex; justify-content:center; }
        .interactive-host canvas { max-width:100%; touch-action:none; cursor:grab; }
        .inter-note { font-size:13px; color:var(--muted); margin-top:8px; }
        .inter-ctrl { display:flex; gap:12px; align-items:center; justify-content:center; margin-top:10px; flex-wrap:wrap; font-size:15px; }
        .inter-ctrl button { font:inherit; font-weight:800; cursor:pointer; border:1px solid var(--card-border); background:var(--card-bg); color:var(--text); border-radius:9px; width:38px; height:38px; }
        .inter-ctrl button:hover { border-color:var(--accent); color:var(--accent); }
        .inter-val { color:var(--accent); font-weight:800; font-variant-numeric:tabular-nums; }
        /* Bandeau réussite / échec */
        .pass-banner { display:flex; align-items:flex-start; gap:14px; padding:16px 18px; border-radius:14px; margin-bottom:18px; }
        .pass-banner .pass-ico { font-size:26px; line-height:1; }
        .pass-banner b { font-size:16px; display:block; }
        .pass-banner .pass-sub { font-size:13px; color:var(--muted); }
        .pass-banner .pass-msg { margin-top:6px; font-size:14px; }
        .pass-banner.pass { background:rgba(42,157,74,.14); border:1px solid var(--vert,#2a9d4a); }
        .pass-banner.fail { background:rgba(230,57,70,.12); border:1px solid var(--rouge,#e63946); }
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
            <details class="cover-edit">
                <summary>📷 <?= !empty($quiz['image']) ? 'Changer' : 'Ajouter' ?> la photo de couverture</summary>
                <form method="post" action="<?= url('quiz/image') ?>" enctype="multipart/form-data" class="cover-form">
                    <input type="hidden" name="id" value="<?= $qid ?>">
                    <input type="file" name="image" class="js-autoresize" accept="image/jpeg,image/png,image/gif,image/webp">
                    <?php if (!empty($quiz['image'])): ?>
                        <label class="cover-rm"><input type="checkbox" name="remove_image" value="1"> Retirer la photo actuelle</label>
                    <?php endif; ?>
                    <button type="submit">💾 Enregistrer la photo</button>
                    <p class="cover-hint">JPG, PNG, GIF ou WEBP — redimensionnée automatiquement. Elle s'affiche sur la carte du quiz et en tête.</p>
                </form>
            </details>
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
            <?php
                $qFeedback = (int) ($quiz['instant_feedback'] ?? 0) === 1; // dire bon/faux tout de suite
                $qStep     = $qFeedback || (int) ($quiz['one_by_one'] ?? 0) === 1; // une question à la fois
                $qEffects  = (int) ($quiz['effects'] ?? 0) === 1;            // confettis / secousse
            ?>
            <?php $qTime = (int) ($quiz['time_limit'] ?? 0); ?>
            <?php if ($qTime > 0): ?>
                <div class="quiz-timer" id="quizTimer" data-seconds="<?= $qTime ?>">⏱️ Temps restant : <span id="quizTimerText">--:--</span></div>
            <?php endif; ?>
            <form method="post" action="<?= url('quiz/submit') ?>" id="answerForm"
                  class="<?= $qStep ? 'step-mode' : '' ?>"
                  data-step="<?= $qStep ? 1 : 0 ?>" data-feedback="<?= $qFeedback ? 1 : 0 ?>" data-fx="<?= $qEffects ? 1 : 0 ?>">
                <input type="hidden" name="id" value="<?= $qid ?>">
                <?php
                    // Libellés courts pour l'en-tête de chaque question.
                    $typeLabel = [
                        'single' => '🔘 Une seule réponse', 'multiple' => '☑️ Plusieurs réponses possibles',
                        'numeric' => '🔢 Réponse chiffrée à saisir', 'text' => '⌨️ Réponse à saisir',
                        'fill' => '✍️ Complète les trous', 'order' => '🔀 Remets dans le bon ordre',
                        'match' => '🔗 Associe chaque élément', 'interactive' => '🧪 Exercice à manipuler',
                    ];
                ?>
                <?php foreach ($questions as $i => $q): $type = Quiz::normalizeType((string) $q['type']); $qidN = (int) $q['id']; ?>
                    <div class="q" data-qi="<?= $i ?>" data-type="<?= $type ?>" data-explain="<?= htmlspecialchars((string) ($q['explanation'] ?? '')) ?>">
                        <?php if ($type !== 'fill'): ?>
                            <div class="qbody"><?= ($i + 1) ?>. <?= htmlspecialchars($q['body']) ?></div>
                        <?php endif; ?>
                        <div class="qtype"><?= $typeLabel[$type] ?? '🔘 Question' ?></div>
                        <?php if (!empty($q['image'])): ?>
                            <img class="q-image" src="<?= url('uploads/quizzes/' . rawurlencode($q['image'])) ?>" alt="">
                        <?php endif; ?>

                        <?php if ($type === 'single' || $type === 'multiple'): ?>
                            <?php foreach ($q['options'] as $o): ?>
                                <label class="opt">
                                    <?php $fb = $qFeedback ? ' data-correct="' . ((int) $o['is_correct'] === 1 ? '1' : '0') . '"' : ''; ?>
                                    <?php if ($type === 'multiple'): ?>
                                        <input type="checkbox" name="answer[<?= $qidN ?>][]" value="<?= (int) $o['id'] ?>"<?= $fb ?>>
                                    <?php else: ?>
                                        <input type="radio" name="answer[<?= $qidN ?>]" value="<?= (int) $o['id'] ?>"<?= $fb ?>>
                                    <?php endif; ?>
                                    <span class="txt"><?= htmlspecialchars($o['label']) ?></span>
                                </label>
                            <?php endforeach; ?>

                        <?php elseif ($type === 'numeric'): ?>
                            <input class="ans-input ans-num" type="text" inputmode="decimal" autocomplete="off"
                                   name="answer_text[<?= $qidN ?>]" placeholder="Ta réponse (un nombre)…">

                        <?php elseif ($type === 'text'): ?>
                            <input class="ans-input" type="text" autocomplete="off"
                                   name="answer_text[<?= $qidN ?>]" placeholder="Ta réponse…">

                        <?php elseif ($type === 'fill'): ?>
                            <?php
                                // Découpe l'énoncé sur les [trous] et insère un champ par trou.
                                $parts = preg_split('/(\[[^\]]*\])/', $q['body'], -1, PREG_SPLIT_DELIM_CAPTURE);
                            ?>
                            <div class="qbody"><?= ($i + 1) ?>.</div>
                            <div class="fill-line">
                                <?php foreach ($parts as $p): ?>
                                    <?php if (preg_match('/^\[[^\]]*\]$/', $p)): ?>
                                        <input class="fill-blank" type="text" autocomplete="off" name="answer_fill[<?= $qidN ?>][]">
                                    <?php else: ?>
                                        <?= htmlspecialchars($p) ?>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>

                        <?php elseif ($type === 'order'): ?>
                            <?php $shuf = $q['options']; shuffle($shuf); ?>
                            <input type="hidden" name="answer_order[<?= $qidN ?>]" class="order-input">
                            <ul class="order-list" data-qid="<?= $qidN ?>">
                                <?php foreach ($shuf as $o): ?>
                                    <li class="order-item" draggable="true" data-oid="<?= (int) $o['id'] ?>">
                                        <span class="grip">≡</span><span class="num"></span>
                                        <span class="txt"><?= htmlspecialchars($o['label']) ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>

                        <?php elseif ($type === 'match'): ?>
                            <?php
                                $targets = array_map(fn ($o) => (string) $o['pair'], $q['options']);
                                shuffle($targets);
                            ?>
                            <input type="hidden" name="answer_match[<?= $qidN ?>]" class="match-input">
                            <div class="match-box" data-qid="<?= $qidN ?>">
                                <?php foreach ($q['options'] as $o): ?>
                                    <div class="match-row">
                                        <span class="match-left"><?= htmlspecialchars($o['label']) ?></span>
                                        <span class="match-arrow">→</span>
                                        <select class="match-sel" data-oid="<?= (int) $o['id'] ?>">
                                            <option value="">— choisir —</option>
                                            <?php foreach ($targets as $t): ?>
                                                <option value="<?= htmlspecialchars($t) ?>"><?= htmlspecialchars($t) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                        <?php elseif ($type === 'interactive'): ?>
                            <div class="interactive-host" data-widget="<?= htmlspecialchars((string) $q['answer']) ?>"></div>
                            <div class="inter-note">🧪 Exercice d'exploration — manipule la figure ci-dessus. <b>Non noté</b> : il ne compte pas dans le score.</div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
                <div class="actions" id="quizActions">
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

            <?php
                $threshold = (int) ($quiz['pass_threshold'] ?? 0);
                if ($threshold > 0):
                    $passed = $pct >= $threshold;
                    $msg    = trim((string) ($passed ? ($quiz['msg_pass'] ?? '') : ($quiz['msg_fail'] ?? '')));
            ?>
                <div class="pass-banner <?= $passed ? 'pass' : 'fail' ?>">
                    <span class="pass-ico"><?= $passed ? '🎉' : '💪' ?></span>
                    <div>
                        <b><?= $passed ? 'Réussi !' : 'Pas encore atteint' ?></b>
                        <span class="pass-sub">Seuil de réussite : <?= $threshold ?> % — ton score : <?= $pct ?> %</span>
                        <?php if ($msg !== ''): ?><p class="pass-msg"><?= htmlspecialchars($msg) ?></p><?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php
                $typeLabelR = [
                    'single' => '🔘 Une seule réponse', 'multiple' => '☑️ Plusieurs réponses',
                    'numeric' => '🔢 Réponse chiffrée', 'text' => '⌨️ Réponse saisie',
                    'fill' => '✍️ Texte à trous', 'order' => '🔀 Remise en ordre', 'match' => '🔗 Association',
                ];
            ?>
            <?php foreach ($questions as $i => $q): ?>
                <?php
                    $type = Quiz::normalizeType((string) $q['type']);
                    $mine = $myAnswers[(int) $q['id']] ?? [];        // option_id (ordre choisi pour 'order')
                    $myText = $myTexts[(int) $q['id']] ?? '';        // texte saisi
                    // Exercice interactif : non noté → pas de marque juste/faux.
                    $qok = $type === 'interactive' ? null : Quiz::gradeQuestion($q, $mine, $myText);
                ?>
                <div class="q <?= $qok === null ? '' : ($qok ? 'qok' : 'qko') ?>">
                    <?php if ($type !== 'fill'): ?>
                        <div class="qbody"><?= ($i + 1) ?>. <?= htmlspecialchars($q['body']) ?></div>
                    <?php else: ?>
                        <div class="qbody"><?= ($i + 1) ?>. <?= htmlspecialchars(preg_replace('/\[([^\]]*)\]/', '____', $q['body'])) ?></div>
                    <?php endif; ?>
                    <div class="qtype"><?= $typeLabelR[$type] ?? '🔘 Question' ?></div>
                    <?php if (!empty($q['image'])): ?>
                        <img class="q-image" src="<?= url('uploads/quizzes/' . rawurlencode($q['image'])) ?>" alt="">
                    <?php endif; ?>

                    <?php if ($type === 'single' || $type === 'multiple'): ?>
                        <?php foreach ($q['options'] as $o): ?>
                            <?php
                                $oid = (int) $o['id']; $isCorrect = (int) $o['is_correct'] === 1; $chosen = in_array($oid, $mine, true);
                                $cls = ($chosen && $isCorrect) ? 'correct' : ($chosen ? 'wrong' : ($isCorrect ? 'correct' : ''));
                            ?>
                            <div class="opt locked <?= $cls ?>">
                                <input type="<?= $type === 'multiple' ? 'checkbox' : 'radio' ?>" <?= $chosen ? 'checked' : '' ?> disabled>
                                <span class="txt"><?= htmlspecialchars($o['label']) ?></span>
                                <?php if ($chosen && $isCorrect): ?><span class="tag ok">Bonne réponse</span>
                                <?php elseif ($chosen && !$isCorrect): ?><span class="tag ko">Ton choix · faux</span>
                                <?php elseif (!$chosen && $isCorrect): ?><span class="tag miss">À cocher</span><?php endif; ?>
                            </div>
                        <?php endforeach; ?>

                    <?php elseif ($type === 'numeric' || $type === 'text'): ?>
                        <?php
                            $expected = $type === 'text'
                                ? implode(' / ', array_map('trim', explode('|', (string) $q['answer'])))
                                : (string) $q['answer'];
                        ?>
                        <div class="opt locked <?= $qok ? 'correct' : 'wrong' ?>">
                            <span class="txt"><b>Ta réponse :</b> <?= $myText !== '' ? htmlspecialchars($myText) : '<i>(vide)</i>' ?></span>
                            <span class="tag <?= $qok ? 'ok' : 'ko' ?>"><?= $qok ? 'Juste' : 'Faux' ?></span>
                        </div>
                        <?php if (!$qok): ?>
                            <div class="ans-recap"><span class="exp">Réponse attendue : </span><span class="ok-txt"><?= htmlspecialchars($expected) ?></span></div>
                        <?php endif; ?>

                    <?php elseif ($type === 'fill'): ?>
                        <?php
                            $exp   = array_map('trim', explode('|', (string) $q['answer']));
                            $given = array_map('trim', explode('|', (string) $myText));
                            $parts = preg_split('/(\[[^\]]*\])/', $q['body'], -1, PREG_SPLIT_DELIM_CAPTURE);
                            $bi = 0;
                        ?>
                        <div class="fill-line">
                            <?php foreach ($parts as $p): ?>
                                <?php if (preg_match('/^\[[^\]]*\]$/', $p)): ?>
                                    <?php
                                        $g = $given[$bi] ?? ''; $e = $exp[$bi] ?? '';
                                        $bok = Quiz::normText($g) === Quiz::normText($e) && $e !== '';
                                        $bi++;
                                    ?>
                                    <input class="fill-blank <?= $bok ? 'correct' : 'wrong' ?>" type="text" value="<?= htmlspecialchars($g) ?>" disabled title="<?= $bok ? '' : 'Attendu : ' . htmlspecialchars($e) ?>">
                                <?php else: ?>
                                    <?= htmlspecialchars($p) ?>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                        <?php if (!$qok): ?>
                            <div class="ans-recap"><span class="exp">Attendu : </span><span class="ok-txt"><?= htmlspecialchars(implode(' · ', $exp)) ?></span></div>
                        <?php endif; ?>

                    <?php elseif ($type === 'order'): ?>
                        <?php
                            $correctOrder = array_map(fn ($o) => (int) $o['id'], $q['options']); // positions = bon ordre
                            $labels = []; foreach ($q['options'] as $o) { $labels[(int) $o['id']] = (string) $o['label']; }
                            $seq = !empty($mine) ? $mine : $correctOrder;
                        ?>
                        <ul class="order-list">
                            <?php foreach ($seq as $pos => $oid): ?>
                                <?php $good = isset($correctOrder[$pos]) && $correctOrder[$pos] === (int) $oid; ?>
                                <li class="order-item <?= $good ? 'correct' : 'wrong' ?>">
                                    <span class="num"><?= $pos + 1 ?></span>
                                    <span class="txt"><?= htmlspecialchars($labels[(int) $oid] ?? '?') ?></span>
                                    <?php if (!$good): ?><span class="tag miss" style="margin-left:auto;">à la place <?= array_search((int) $oid, $correctOrder, true) + 1 ?></span><?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>

                    <?php elseif ($type === 'match'): ?>
                        <?php
                            $picked = [];
                            foreach (explode(',', (string) $myText) as $couple) {
                                $pp = explode(':', $couple, 2);
                                if (count($pp) === 2) { $picked[(int) $pp[0]] = $pp[1]; }
                            }
                        ?>
                        <div class="match-box">
                            <?php foreach ($q['options'] as $o): ?>
                                <?php
                                    $oid = (int) $o['id']; $mineT = $picked[$oid] ?? '';
                                    $good = Quiz::normText($mineT) === Quiz::normText((string) $o['pair']) && $o['pair'] !== '';
                                ?>
                                <div class="match-row">
                                    <span class="match-left"><?= htmlspecialchars($o['label']) ?></span>
                                    <span class="match-arrow">→</span>
                                    <span class="match-sel <?= $good ? 'correct' : 'wrong' ?>" style="display:flex;align-items:center;gap:8px;">
                                        <?= $mineT !== '' ? htmlspecialchars($mineT) : '<i>(vide)</i>' ?>
                                        <?php if ($good): ?><span class="tag ok">✓</span><?php else: ?><span class="tag ko">≠ <?= htmlspecialchars($o['pair']) ?></span><?php endif; ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php elseif ($type === 'interactive'): ?>
                        <div class="interactive-host" data-widget="<?= htmlspecialchars((string) $q['answer']) ?>"></div>
                        <div class="inter-note">🧪 Exercice d'exploration (non noté).</div>
                    <?php endif; ?>

                    <?php if (trim((string) ($q['explanation'] ?? '')) !== ''): ?>
                        <div class="q-explain">💡 <?= htmlspecialchars($q['explanation']) ?></div>
                    <?php endif; ?>
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
    <?= image_resize_js() ?>
    <script>
    // Quiz interactif : une question à la fois + retour immédiat + effets.
    (function () {
        var form = document.getElementById('answerForm');
        if (!form || form.getAttribute('data-step') !== '1') { return; }
        var feedback = form.getAttribute('data-feedback') === '1';
        var fx       = form.getAttribute('data-fx') === '1';
        var qs       = Array.prototype.slice.call(form.querySelectorAll('.q'));
        var actions  = document.getElementById('quizActions');
        var submitBtn = actions ? actions.querySelector('.submit') : null;
        var n = qs.length;
        if (!n) { return; }
        var cur = 0;
        var done = qs.map(function () { return false; }); // question vérifiée (mode retour immédiat)

        // Barre de progression + navigation.
        var nav = document.createElement('div');
        nav.className = 'step-nav';
        nav.innerHTML = '<div class="step-prog"><span></span></div>'
            + '<div class="step-count"></div>'
            + '<div class="step-btns">'
            + '<button type="button" class="step-prev">‹ Précédent</button>'
            + '<button type="button" class="step-next"></button>'
            + '</div>';
        form.insertBefore(nav, actions);
        var prog = nav.querySelector('.step-prog span');
        var count = nav.querySelector('.step-count');
        var prevBtn = nav.querySelector('.step-prev');
        var nextBtn = nav.querySelector('.step-next');

        function selected(q) {
            return Array.prototype.slice.call(q.querySelectorAll('input')).filter(function (i) { return i.checked; });
        }
        function tag(opt, cls, txt) {
            if (opt.querySelector('.tag')) { return; }
            var s = document.createElement('span'); s.className = 'tag ' + cls; s.textContent = txt; opt.appendChild(s);
        }
        function reveal(q) {
            var ok = true;
            Array.prototype.slice.call(q.querySelectorAll('.opt')).forEach(function (opt) {
                var inp = opt.querySelector('input');
                var isCorrect = inp.getAttribute('data-correct') === '1';
                var chosen = inp.checked;
                opt.classList.add('locked');
                if (chosen && isCorrect)      { opt.classList.add('correct'); tag(opt, 'ok', 'Bonne réponse'); }
                else if (chosen && !isCorrect){ opt.classList.add('wrong');   tag(opt, 'ko', 'Faux'); ok = false; }
                else if (!chosen && isCorrect){ opt.classList.add('correct'); tag(opt, 'miss', 'À cocher'); ok = false; }
            });
            q.classList.add(ok ? 'qok' : 'qko');
            // Explication (si fournie) montrée juste après la vérification.
            var expl = q.getAttribute('data-explain');
            if (expl && !q.querySelector('.q-explain')) {
                var ex = document.createElement('div');
                ex.className = 'q-explain'; ex.textContent = '💡 ' + expl;
                q.appendChild(ex);
            }
            if (fx) { ok ? confetti() : shake(q); }
            return ok;
        }
        function shake(el) { el.classList.add('shake'); setTimeout(function () { el.classList.remove('shake'); }, 520); }
        function confetti() {
            var colors = ['#f4c14b', '#e63946', '#2a9d4a', '#38bdf8', '#a855f7', '#ff8a3d'];
            for (var i = 0; i < 80; i++) {
                (function (k) {
                    var p = document.createElement('div'); p.className = 'confetti-piece';
                    p.style.left = (5 + (k * 1.15) % 90) + 'vw';
                    p.style.background = colors[k % colors.length];
                    var dur = 2.2 + ((k % 10) / 10) * 1.6;
                    p.style.animationDuration = dur + 's';
                    p.style.animationDelay = ((k % 8) / 28) + 's';
                    p.style.transform = 'rotate(' + (k * 37 % 360) + 'deg)';
                    document.body.appendChild(p);
                    setTimeout(function () { p.remove(); }, (dur + 0.4) * 1000);
                })(i);
            }
        }

        function isQcm(q) { var t = q.getAttribute('data-type'); return t === 'single' || t === 'multiple'; }
        function updateNext() {
            var last = cur === n - 1;
            if (feedback && isQcm(qs[cur]) && !done[cur]) {
                nextBtn.textContent = '✅ Vérifier'; nextBtn.classList.add('verify');
                nextBtn.style.display = ''; if (submitBtn) { submitBtn.style.display = 'none'; }
            } else if (last) {
                nextBtn.style.display = 'none'; if (submitBtn) { submitBtn.style.display = ''; }
            } else {
                nextBtn.textContent = 'Suivant ›'; nextBtn.classList.remove('verify');
                nextBtn.style.display = ''; if (submitBtn) { submitBtn.style.display = 'none'; }
            }
        }
        function show(i) {
            cur = Math.max(0, Math.min(n - 1, i));
            qs.forEach(function (q, idx) { q.style.display = idx === cur ? '' : 'none'; });
            prog.style.width = ((cur + 1) / n * 100) + '%';
            count.textContent = 'Question ' + (cur + 1) + ' / ' + n;
            prevBtn.disabled = cur === 0;
            updateNext();
        }
        nextBtn.addEventListener('click', function () {
            var q = qs[cur];
            // Retour immédiat : seulement pour les QCM (les autres types sont notés à l'envoi).
            if (feedback && isQcm(q) && !done[cur]) {
                if (!selected(q).length) { shake(q); return; }  // on exige une réponse avant de vérifier
                reveal(q); done[cur] = true; updateNext(); return;
            }
            show(cur + 1);
        });
        prevBtn.addEventListener('click', function () { show(cur - 1); });

        show(0);
    })();
    </script>
    <script>
    // Chrono : compte à rebours ; à 0, on valide automatiquement le formulaire.
    (function () {
        var box = document.getElementById('quizTimer');
        var form = document.getElementById('answerForm');
        if (!box || !form) { return; }
        var left = parseInt(box.getAttribute('data-seconds'), 10) || 0;
        var txt = document.getElementById('quizTimerText');
        var submitted = false, timer = null;
        function fmt(s) {
            var m = Math.floor(s / 60), r = s % 60;
            return m + ':' + (r < 10 ? '0' : '') + r;
        }
        function tick() {
            if (txt) { txt.textContent = fmt(left); }
            box.classList.toggle('low', left <= 15);
            if (left <= 0) {
                clearInterval(timer);
                if (!submitted) { submitted = true; if (form.requestSubmit) { form.requestSubmit(); } else { form.submit(); } }
                return;
            }
            left--;
        }
        tick();
        timer = setInterval(tick, 1000);
    })();
    </script>
    <script>
    // Types « remettre dans l'ordre » et « associer » (mode réponse).
    (function () {
        // --- Glisser-déposer pour ordonner ---
        document.querySelectorAll('.order-list[data-qid]').forEach(function (list) {
            var hidden = list.closest('.q').querySelector('.order-input');
            function sync() {
                var ids = [];
                list.querySelectorAll('.order-item').forEach(function (it, idx) {
                    ids.push(it.dataset.oid);
                    var num = it.querySelector('.num'); if (num) { num.textContent = (idx + 1); }
                });
                if (hidden) { hidden.value = ids.join(','); }
            }
            var dragged = null;
            list.addEventListener('dragstart', function (e) {
                var t = e.target.closest('.order-item'); if (!t) { return; }
                dragged = t; t.style.opacity = '.5';
            });
            list.addEventListener('dragend', function () {
                if (dragged) { dragged.style.opacity = ''; }
                dragged = null;
                list.querySelectorAll('.order-item').forEach(function (o) { o.classList.remove('drag-over'); });
                sync();
            });
            list.addEventListener('dragover', function (e) {
                if (!dragged) { return; }
                e.preventDefault();
                var t = e.target.closest('.order-item');
                list.querySelectorAll('.order-item').forEach(function (o) { o.classList.remove('drag-over'); });
                if (t && t !== dragged) { t.classList.add('drag-over'); }
            });
            list.addEventListener('drop', function (e) {
                if (!dragged) { return; }
                e.preventDefault();
                var t = e.target.closest('.order-item');
                if (t && t !== dragged) {
                    var items = Array.prototype.slice.call(list.querySelectorAll('.order-item'));
                    if (items.indexOf(dragged) < items.indexOf(t)) { list.insertBefore(dragged, t.nextSibling); }
                    else { list.insertBefore(dragged, t); }
                }
                sync();
            });
            // Repli tactile / sans souris : cliquer un élément le descend d'un cran.
            list.querySelectorAll('.order-item').forEach(function (it) {
                it.addEventListener('click', function () {
                    if (it.nextElementSibling) { list.insertBefore(it.nextElementSibling, it); sync(); }
                });
            });
            sync();
        });

        // --- Association par paires : synchronise les <select> vers le champ caché ---
        document.querySelectorAll('.match-box[data-qid]').forEach(function (box) {
            var hidden = box.closest('.q').querySelector('.match-input');
            function sync() {
                var pairs = [];
                box.querySelectorAll('.match-sel').forEach(function (sel) {
                    if (sel.value !== '') { pairs.push(sel.dataset.oid + ':' + sel.value); }
                });
                if (hidden) { hidden.value = pairs.join(','); }
            }
            box.querySelectorAll('.match-sel').forEach(function (sel) { sel.addEventListener('change', sync); });
            sync();
        });
    })();
    </script>
    <script>
    /* ====== Catalogue des EXERCICES INTERACTIFS intégrés (manipulables Canvas) ====== */
    (function () {
        function mkCanvas(host, w, h) { var c = document.createElement('canvas'); c.width = w; c.height = h; host.appendChild(c); return c; }
        function ctrl(host, html) { var d = document.createElement('div'); d.className = 'inter-ctrl'; d.innerHTML = html; host.parentNode.insertBefore(d, host.nextSibling); return d; }
        function loopOn(fn) { function fr(t) { fn(t / 1000); requestAnimationFrame(fr); } requestAnimationFrame(fr); }
        function xOf(c, e) { var r = c.getBoundingClientRect(); var t = e.touches ? e.touches[0] : e; return (t.clientX - r.left) * c.width / r.width; }

        var WIDGETS = {
            // 1) Aire sous la courbe : on glisse les bornes a et b, l'aire se recalcule.
            integral_area: function (host) {
                var c = mkCanvas(host, 440, 250), ctx = c.getContext('2d');
                var ox = 50, oy = 205, sc = 40, f = function (x) { return 0.3 * x * x + 0.4; };
                var A = { x: ox + 1.2 * sc }, B = { x: ox + 4.2 * sc }, active = null;
                var info = ctrl(host, 'Aire (∫ₐᵇ f) ≈ <span class="inter-val">0</span>'), val = info.querySelector('.inter-val');
                function clamp(p) { p.x = Math.max(ox + 8, Math.min(ox + 360, p.x)); }
                function pick(x) { active = (Math.abs(x - A.x) <= Math.abs(x - B.x)) ? A : B; }
                c.addEventListener('mousedown', function (e) { pick(xOf(c, e)); });
                window.addEventListener('mousemove', function (e) { if (active) { active.x = xOf(c, e); clamp(active); } });
                window.addEventListener('mouseup', function () { active = null; });
                c.addEventListener('touchstart', function (e) { pick(xOf(c, e)); }, { passive: true });
                c.addEventListener('touchmove', function (e) { if (active) { active.x = xOf(c, e); clamp(active); } }, { passive: true });
                c.addEventListener('touchend', function () { active = null; });
                loopOn(function () {
                    ctx.fillStyle = '#fff'; ctx.fillRect(0, 0, 440, 250);
                    ctx.strokeStyle = '#9aa7b4'; ctx.lineWidth = 2;
                    ctx.beginPath(); ctx.moveTo(ox, oy); ctx.lineTo(ox + 380, oy); ctx.stroke();
                    ctx.beginPath(); ctx.moveTo(ox, oy); ctx.lineTo(ox, 28); ctx.stroke();
                    var a = Math.min(A.x, B.x), b = Math.max(A.x, B.x);
                    ctx.fillStyle = 'rgba(56,211,159,.30)'; ctx.beginPath(); ctx.moveTo(a, oy);
                    for (var x = a; x <= b; x += 3) { ctx.lineTo(x, oy - f((x - ox) / sc) * sc * 0.5); }
                    ctx.lineTo(b, oy); ctx.closePath(); ctx.fill();
                    ctx.strokeStyle = '#7c5cff'; ctx.lineWidth = 3; ctx.beginPath();
                    for (var x2 = ox + 4, fst = true; x2 <= ox + 380; x2 += 3) { var y = oy - f((x2 - ox) / sc) * sc * 0.5; if (fst) { ctx.moveTo(x2, y); fst = false; } else ctx.lineTo(x2, y); } ctx.stroke();
                    [[A, 'a'], [B, 'b']].forEach(function (p) {
                        var u = (p[0].x - ox) / sc; ctx.strokeStyle = '#38d39f'; ctx.lineWidth = 2;
                        ctx.beginPath(); ctx.moveTo(p[0].x, oy); ctx.lineTo(p[0].x, oy - f(u) * sc * 0.5); ctx.stroke();
                        ctx.fillStyle = '#38d39f'; ctx.beginPath(); ctx.arc(p[0].x, oy, 7, 0, 7); ctx.fill();
                        ctx.fillStyle = '#222'; ctx.font = 'bold 14px Segoe UI'; ctx.textAlign = 'center'; ctx.fillText(p[1], p[0].x, oy + 20);
                    });
                    var ua = (a - ox) / sc, ub = (b - ox) / sc, area = 0, N = 240;
                    for (var i = 0; i < N; i++) { var x0 = ua + (ub - ua) * i / N, x1 = ua + (ub - ua) * (i + 1) / N; area += (f(x0) + f(x1)) / 2 * (x1 - x0); }
                    val.textContent = area.toFixed(2);
                });
            },
            // 2) Rectangles de Riemann : on change le nombre n, l'approximation s'affine.
            integral_riemann: function (host) {
                var c = mkCanvas(host, 440, 250), ctx = c.getContext('2d');
                var ox = 50, oy = 205, sc = 40, f = function (x) { return 0.3 * x * x + 0.5; }, a = 0.5, b = 4.5, n = 4;
                var info = ctrl(host, '<button type="button" data-d="-1">−</button> n = <span class="inter-val nval">4</span> rectangles <button type="button" data-d="1">+</button> &nbsp;·&nbsp; somme ≈ <span class="inter-val sval">0</span>');
                var nval = info.querySelector('.nval'), sval = info.querySelector('.sval');
                info.querySelectorAll('button').forEach(function (btn) { btn.addEventListener('click', function () { n = Math.max(1, Math.min(40, n + parseInt(btn.dataset.d, 10))); nval.textContent = n; }); });
                loopOn(function () {
                    ctx.fillStyle = '#fff'; ctx.fillRect(0, 0, 440, 250);
                    ctx.strokeStyle = '#9aa7b4'; ctx.lineWidth = 2;
                    ctx.beginPath(); ctx.moveTo(ox, oy); ctx.lineTo(ox + 380, oy); ctx.stroke();
                    ctx.beginPath(); ctx.moveTo(ox, oy); ctx.lineTo(ox, 28); ctx.stroke();
                    var dx = (b - a) / n, sum = 0;
                    for (var i = 0; i < n; i++) {
                        var xm = a + (i + 0.5) * dx, h = f(xm);
                        sum += h * dx;
                        var px = ox + (a + i * dx) * sc, pw = dx * sc, ph = h * sc * 0.5;
                        ctx.fillStyle = 'rgba(124,92,255,.28)'; ctx.fillRect(px, oy - ph, pw, ph);
                        ctx.strokeStyle = '#7c5cff'; ctx.lineWidth = 1; ctx.strokeRect(px, oy - ph, pw, ph);
                    }
                    ctx.strokeStyle = '#e67e22'; ctx.lineWidth = 3; ctx.beginPath();
                    for (var x2 = ox + 4, fst = true; x2 <= ox + 380; x2 += 3) { var y = oy - f((x2 - ox) / sc) * sc * 0.5; if (fst) { ctx.moveTo(x2, y); fst = false; } else ctx.lineTo(x2, y); } ctx.stroke();
                    sval.textContent = sum.toFixed(2);
                });
            },
            // 3) Pente de la tangente (dérivée) : un point glisse sur la courbe.
            derivative_slope: function (host) {
                var c = mkCanvas(host, 440, 250), ctx = c.getContext('2d');
                var ox = 50, oy = 205, sc = 40, f = function (x) { return 0.25 * x * x + 0.5; }, df = function (x) { return 0.5 * x; };
                var P = { x: ox + 2 * sc }, active = false;
                var info = ctrl(host, 'pente (f ′) au point = <span class="inter-val">0</span>'), val = info.querySelector('.inter-val');
                c.addEventListener('mousedown', function (e) { active = true; P.x = xOf(c, e); });
                window.addEventListener('mousemove', function (e) { if (active) { P.x = Math.max(ox + 10, Math.min(ox + 360, xOf(c, e))); } });
                window.addEventListener('mouseup', function () { active = false; });
                c.addEventListener('touchstart', function (e) { active = true; P.x = xOf(c, e); }, { passive: true });
                c.addEventListener('touchmove', function (e) { if (active) { P.x = Math.max(ox + 10, Math.min(ox + 360, xOf(c, e))); } }, { passive: true });
                c.addEventListener('touchend', function () { active = false; });
                loopOn(function () {
                    ctx.fillStyle = '#fff'; ctx.fillRect(0, 0, 440, 250);
                    ctx.strokeStyle = '#9aa7b4'; ctx.lineWidth = 2;
                    ctx.beginPath(); ctx.moveTo(ox, oy); ctx.lineTo(ox + 380, oy); ctx.stroke();
                    ctx.beginPath(); ctx.moveTo(ox, oy); ctx.lineTo(ox, 28); ctx.stroke();
                    ctx.strokeStyle = '#7c5cff'; ctx.lineWidth = 3; ctx.beginPath();
                    for (var x2 = ox + 4, fst = true; x2 <= ox + 380; x2 += 3) { var y = oy - f((x2 - ox) / sc) * sc * 0.5; if (fst) { ctx.moveTo(x2, y); fst = false; } else ctx.lineTo(x2, y); } ctx.stroke();
                    var u = (P.x - ox) / sc, py = oy - f(u) * sc * 0.5, m = df(u), d = 55;
                    ctx.strokeStyle = '#e67e22'; ctx.lineWidth = 2; ctx.beginPath();
                    ctx.moveTo(P.x - d, py + m * d * 0.5); ctx.lineTo(P.x + d, py - m * d * 0.5); ctx.stroke();
                    ctx.fillStyle = '#7c5cff'; ctx.beginPath(); ctx.arc(P.x, py, 7, 0, 7); ctx.fill();
                    val.textContent = m.toFixed(2);
                });
            }
        };
        document.querySelectorAll('.interactive-host').forEach(function (h) {
            var k = h.getAttribute('data-widget');
            if (WIDGETS[k]) { try { WIDGETS[k](h); } catch (e) { h.innerHTML = '<p style="color:#888;padding:14px;">Exercice indisponible.</p>'; } }
        });
    })();
    </script>
</body>
</html>
