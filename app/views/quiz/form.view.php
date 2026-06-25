<?php
/**
 * FORMULAIRE DE CRÉATION / ÉDITION D'UN QUESTIONNAIRE — builder dynamique.
 * Variables : $user, $quiz (ou null), $questions (existantes), $error, $action, $active.
 *
 * Le membre ajoute autant de questions qu'il veut. Chaque question :
 *   - un énoncé ;
 *   - un mode : « Une seule bonne réponse » (single) ou « Plusieurs bonnes réponses » (multiple) ;
 *   - plusieurs réponses possibles, dont on coche la/les bonne(s).
 *
 * Les champs sont nommés q[IDX][body], q[IDX][type], q[IDX][opt][OPTIDX][label],
 * q[IDX][opt][OPTIDX][correct] — PHP les reçoit en tableaux imbriqués (cf. QuizController).
 */
$isEdit = !empty($quiz);
$qid    = $isEdit ? (int) $quiz['id'] : 0;

// Repli après une erreur de validation : on réaffiche la saisie (Session quiz_old).
$old = Session::get('quiz_old');
Session::remove('quiz_old');

$title = $isEdit ? (string) $quiz['title'] : '';
$desc  = $isEdit ? (string) $quiz['description'] : '';
if ($old) {
    $title = (string) ($old['title'] ?? $title);
    $desc  = (string) ($old['description'] ?? $desc);
    $active = !empty($old['active']) ? 1 : 0;
}

/**
 * Construit la liste des questions à pré-afficher :
 *  - en repli d'erreur : depuis $old['q'] (saisie brute) ;
 *  - en édition : depuis $questions (base) ;
 *  - en création : une question vide par défaut.
 */
$initial = [];
if ($old && !empty($old['q']) && is_array($old['q'])) {
    foreach ($old['q'] as $q) {
        $opts = [];
        foreach ((array) ($q['opt'] ?? []) as $o) {
            $opts[] = ['label' => (string) ($o['label'] ?? ''), 'correct' => !empty($o['correct']), 'pair' => (string) ($o['pair'] ?? '')];
        }
        $initial[] = [
            'body'        => (string) ($q['body'] ?? ''),
            'type'        => Quiz::normalizeType((string) ($q['type'] ?? 'single')),
            'image'       => (string) ($q['existing_image'] ?? ''),
            'explanation' => (string) ($q['explanation'] ?? ''),
            'answer'      => (string) ($q['answer'] ?? ''),
            'tolerance'   => (string) ($q['tolerance'] ?? ''),
            'options'     => $opts,
        ];
    }
} elseif ($isEdit) {
    foreach ($questions as $q) {
        $opts = [];
        foreach ($q['options'] as $o) {
            $opts[] = ['label' => (string) $o['label'], 'correct' => (int) $o['is_correct'] === 1, 'pair' => (string) ($o['pair'] ?? '')];
        }
        $initial[] = [
            'body'        => (string) $q['body'],
            'type'        => Quiz::normalizeType((string) $q['type']),
            'image'       => (string) ($q['image'] ?? ''),
            'explanation' => (string) ($q['explanation'] ?? ''),
            'answer'      => (string) ($q['answer'] ?? ''),
            'tolerance'   => ($q['tolerance'] ?? '') !== '' && (float) $q['tolerance'] != 0 ? (string) (float) $q['tolerance'] : '',
            'options'     => $opts,
        ];
    }
}
// URL de base des images de questions (pour l'aperçu côté JS).
$qImgBase = url('uploads/quizzes/');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(Settings::get('main_title', 'RPN')) ?> — <?= $isEdit ? 'Modifier' : 'Créer' ?> un questionnaire</title>
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
        .top { display:flex; justify-content:space-between; align-items:center; margin-bottom:22px; flex-wrap:wrap; gap:12px; }
        h1 { font-size:22px; } h1 span { color:var(--accent); }
        .back { color:var(--text); text-decoration:none; font-size:14px; padding:8px 16px; border-radius:10px; border:1px solid var(--card-border); }
        .back:hover { border-color:var(--accent); color:var(--accent); }

        .error { background:rgba(230,57,70,.12); border:1px solid var(--rouge,#e63946); color:var(--text);
            padding:12px 16px; border-radius:12px; margin-bottom:18px; font-size:14px; }

        .card { background:var(--card-bg); border:1px solid var(--card-border); border-radius:18px;
            box-shadow:var(--card-shadow); padding:22px 24px; margin-bottom:20px; }
        label.lbl { display:block; font-size:13px; font-weight:600; color:var(--muted); margin-bottom:7px; }
        input[type=text], textarea {
            width:100%; padding:12px 14px; border-radius:11px; border:1px solid var(--card-border);
            background:rgba(127,127,127,.08); color:var(--text); font-family:inherit; font-size:15px; }
        input[type=text]:focus, textarea:focus { outline:none; border-color:var(--accent); }
        textarea { resize:vertical; min-height:64px; }

        .question { border:1px solid var(--card-border); border-radius:16px; padding:18px 18px 16px; margin-bottom:16px;
            background:rgba(127,127,127,.04); position:relative; }
        .question .qtop { display:flex; align-items:center; gap:10px; margin-bottom:12px; }
        .question .qnum { font-weight:800; color:var(--accent); font-size:15px; flex:0 0 auto; }
        .question .qbody { flex:1; }
        .qmode { display:flex; gap:8px; flex-wrap:wrap; margin:6px 0 14px; }
        .qmode label { cursor:pointer; font-size:13px; font-weight:600; color:var(--text);
            border:1px solid var(--card-border); border-radius:10px; padding:8px 12px; display:flex; align-items:center; gap:7px; }
        .qmode label:hover { border-color:var(--accent); }
        .qmode input { accent-color:var(--accent); }
        /* Image par question */
        .qimg-row { display:flex; align-items:center; gap:14px; flex-wrap:wrap; margin:4px 0 12px; }
        .qimg-pick { display:inline-flex; align-items:center; gap:7px; cursor:pointer; font-size:13px; font-weight:600;
            color:var(--accent); border:1px solid var(--accent); border-radius:10px; padding:8px 14px; }
        .qimg-pick:hover { background:var(--accent); color:var(--accent-ink); }
        .qimg-pick input[type=file] { display:none; }
        .qimg-current { display:flex; align-items:center; gap:10px; }
        .qimg-thumb { width:90px; height:56px; object-fit:cover; border-radius:9px; border:1px solid var(--card-border); }
        .qimg-rm { display:flex; align-items:center; gap:6px; font-size:12.5px; color:var(--muted); cursor:pointer; }
        .hint { font-size:12.5px; color:var(--muted); margin-bottom:10px; }

        .opt { display:flex; align-items:center; gap:10px; margin-bottom:9px; }
        .opt .mark { width:30px; height:30px; flex:0 0 30px; display:flex; align-items:center; justify-content:center; }
        .opt .mark input { width:18px; height:18px; accent-color:var(--vert,#2a9d4a); cursor:pointer; }
        .opt input[type=text] { flex:1; }
        .opt .rm { background:none; border:1px solid var(--card-border); color:var(--muted); cursor:pointer;
            border-radius:9px; width:36px; height:36px; flex:0 0 36px; font-size:16px; }
        .opt .rm:hover { border-color:var(--rouge,#e63946); color:var(--rouge,#e63946); }

        .btn-line { display:flex; gap:10px; flex-wrap:wrap; margin-top:8px; }
        .mini { font:inherit; font-size:13px; font-weight:600; cursor:pointer; border:1px dashed var(--card-border);
            background:transparent; color:var(--accent); border-radius:10px; padding:8px 14px; }
        .mini:hover { border-color:var(--accent); }
        .rm-q { position:absolute; top:14px; right:14px; background:none; border:1px solid var(--card-border);
            color:var(--muted); cursor:pointer; border-radius:9px; padding:5px 9px; font-size:13px; }
        .rm-q:hover { border-color:var(--rouge,#e63946); color:var(--rouge,#e63946); }

        .add-q { width:100%; font:inherit; font-size:15px; font-weight:700; cursor:pointer;
            border:2px dashed var(--card-border); background:transparent; color:var(--accent); border-radius:14px; padding:16px; }
        .add-q:hover { border-color:var(--accent); }

        /* Sélecteur de type + blocs conditionnels */
        .qtype-sel { width:100%; padding:11px 13px; border-radius:11px; border:1px solid var(--card-border);
            background:rgba(127,127,127,.08); color:var(--text); font-family:inherit; font-size:14.5px; font-weight:600; margin-bottom:12px; }
        .qtype-sel:focus { outline:none; border-color:var(--accent); }
        .blk-input { margin:2px 0 12px; }
        .blk-tol { margin-top:10px; }
        .opt .opair { flex:1; }
        .opt .ord-handle { cursor:grab; user-select:none; font-size:18px; color:var(--muted); flex:0 0 auto; padding:0 2px; }
        .opt.drag-over { border-top:2px solid var(--accent); }
        .opt[draggable=true] { background:rgba(127,127,127,.06); }

        .pubrow { display:flex; align-items:center; gap:10px; margin:4px 0 0; }
        .pubrow input { width:18px; height:18px; accent-color:var(--accent); }
        .pubrow label { font-size:14px; color:var(--text); }

        .actions { display:flex; gap:12px; flex-wrap:wrap; margin-top:6px; }
        .save { font:inherit; font-size:15px; font-weight:700; cursor:pointer; border:none; border-radius:12px;
            padding:14px 26px; background:var(--accent); color:var(--accent-ink); }
        .cancel { font:inherit; font-size:15px; font-weight:600; text-decoration:none; border:1px solid var(--card-border);
            color:var(--text); border-radius:12px; padding:14px 22px; display:inline-flex; align-items:center; }
        .cancel:hover { border-color:var(--rouge,#e63946); color:var(--rouge,#e63946); }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="top">
            <h1><?= $isEdit ? '✏️ <span>Modifier</span> le questionnaire' : '➕ <span>Créer</span> un questionnaire' ?></h1>
            <a class="back" href="<?= $isEdit ? url('quiz/show') . '?id=' . $qid : url('quiz') ?>">← Retour</a>
        </div>

        <?php if (!empty($error)): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="post" action="<?= htmlspecialchars($action) ?>" id="quizForm" enctype="multipart/form-data">
            <?php if ($isEdit): ?><input type="hidden" name="id" value="<?= $qid ?>"><?php endif; ?>

            <div class="card">
                <label class="lbl" for="title">Titre du questionnaire</label>
                <input type="text" id="title" name="title" value="<?= htmlspecialchars($title) ?>" placeholder="Ex : Quiz culture générale" required>
                <label class="lbl" for="description" style="margin-top:16px;">Description (facultatif)</label>
                <textarea id="description" name="description" placeholder="Quelques mots pour présenter le questionnaire…"><?= htmlspecialchars($desc) ?></textarea>

                <label class="lbl" for="quizImage" style="margin-top:16px;">Image de couverture (facultatif)</label>
                <?php $curImg = $isEdit ? (string) ($quiz['image'] ?? '') : ''; ?>
                <?php if ($curImg !== ''): ?>
                    <div style="margin-bottom:10px;display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
                        <img src="<?= url('uploads/quizzes/' . rawurlencode($curImg)) ?>" alt="" style="width:120px;height:68px;object-fit:cover;border-radius:10px;border:1px solid var(--card-border);">
                        <label style="font-size:13px;display:flex;align-items:center;gap:7px;cursor:pointer;color:var(--muted);">
                            <input type="checkbox" name="remove_image" value="1"> Retirer l'image actuelle
                        </label>
                    </div>
                <?php endif; ?>
                <input type="file" id="quizImage" name="image" class="js-autoresize" accept="image/jpeg,image/png,image/gif,image/webp" style="font-size:13px;color:var(--muted);">
                <div id="quizImgNew" hidden style="margin-top:10px;display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
                    <img id="quizImgPreview" alt="" style="width:120px;height:68px;object-fit:cover;border-radius:10px;border:2px solid var(--accent);">
                    <span style="font-size:13px;color:var(--accent);font-weight:700;">✅ Nouvelle image sélectionnée — <b>enregistre</b> pour l'appliquer.</span>
                </div>
                <p class="hint" style="font-size:12px;color:var(--muted);margin-top:6px;">JPG, PNG, GIF ou WEBP — redimensionnée automatiquement. <?= $isEdit ? 'Pour <b>changer</b> la couverture : choisis un nouveau fichier ci-dessus, puis enregistre.' : 'Elle s\'affiche sur la carte du quiz et en tête.' ?></p>
                <script>
                (function(){
                    var inp=document.getElementById('quizImage'), box=document.getElementById('quizImgNew'), prev=document.getElementById('quizImgPreview');
                    if(inp){ inp.addEventListener('change', function(){
                        if(this.files && this.files[0]){ prev.src=URL.createObjectURL(this.files[0]); box.hidden=false; }
                        else { box.hidden=true; }
                    }); }
                })();
                </script>
            </div>

            <div id="questions"></div>

            <button type="button" class="add-q" id="addQuestion">➕ Ajouter une question</button>

            <div class="card" style="margin-top:20px; display:flex; flex-direction:column; gap:14px;">
                <div class="pubrow">
                    <input type="checkbox" id="active" name="active" value="1" <?= ((int) ($active ?? 1)) === 1 ? 'checked' : '' ?>>
                    <label for="active">Publier le questionnaire (visible par tous les membres). Décoche pour garder un brouillon.</label>
                </div>
                <div class="pubrow">
                    <input type="checkbox" id="urgent" name="urgent" value="1" <?= !empty($isUrgent) ? 'checked' : '' ?>>
                    <label for="urgent">🟥 <b>Afficher dans le tableau de bord de tout le monde</b> — une alerte rouge « URGENT » apparaît chez chaque membre.</label>
                </div>

                <div class="pubrow" style="align-items:flex-start;">
                    <span style="font-size:18px;line-height:1.3;">🔁</span>
                    <label style="flex:1;">Nombre de tentatives autorisées
                        <input type="number" name="max_attempts" min="0" max="50" value="<?= (int) ($maxAttempts ?? 0) ?>"
                               style="width:80px;margin-left:8px;padding:6px 8px;border-radius:8px;border:1px solid var(--card-border);background:var(--card-bg);color:var(--text);font-family:inherit;">
                        <small style="display:block;color:var(--muted);margin-top:4px;"><b>0 = illimité.</b> Sinon, le membre ne pourra refaire le quiz qu'un nombre limité de fois. (Ignoré si le questionnaire est obligatoire.)</small>
                    </label>
                </div>

                <div style="border-top:1px solid var(--card-border); padding-top:14px; display:flex; flex-direction:column; gap:12px;">
                    <div style="font-size:13px;font-weight:700;color:var(--accent);">🎬 Déroulé & affichage</div>
                    <div class="pubrow">
                        <input type="checkbox" id="one_by_one" name="one_by_one" value="1" <?= !empty($oneByOne) ? 'checked' : '' ?>>
                        <label for="one_by_one">1️⃣ <b>Une question à la fois</b> — le membre valide, puis passe à la question suivante (pas tout affiché d'un coup).</label>
                    </div>
                    <div class="pubrow">
                        <input type="checkbox" id="instant_feedback" name="instant_feedback" value="1" <?= !empty($instantFeedback) ? 'checked' : '' ?>>
                        <label for="instant_feedback">✅ <b>Retour immédiat</b> — dire tout de suite si la réponse est bonne ou fausse après chaque question (active « une question à la fois »).</label>
                    </div>
                    <div class="pubrow">
                        <input type="checkbox" id="effects" name="effects" value="1" <?= !empty($effects) ? 'checked' : '' ?>>
                        <label for="effects">🎉 <b>Effets visuels</b> — petite animation festive si c'est juste, secousse si c'est faux (nécessite le retour immédiat).</label>
                    </div>
                    <div class="pubrow">
                        <input type="checkbox" id="shuffle" name="shuffle" value="1" <?= !empty($shuffle) ? 'checked' : '' ?>>
                        <label for="shuffle">🔀 <b>Ordre aléatoire</b> — mélange les questions et les réponses à chaque tentative.</label>
                    </div>
                    <div class="pubrow" style="align-items:flex-start;">
                        <span style="font-size:18px;line-height:1.3;">⏱️</span>
                        <label style="flex:1;">Temps limité (minutes)
                            <input type="number" name="time_limit" min="0" max="180" value="<?= (int) ($timeLimit ?? 0) ?>"
                                   style="width:80px;margin-left:8px;padding:6px 8px;border-radius:8px;border:1px solid var(--card-border);background:var(--card-bg);color:var(--text);font-family:inherit;">
                            <small style="display:block;color:var(--muted);margin-top:4px;"><b>0 = pas de chrono.</b> Sinon, un compte à rebours s'affiche ; à la fin du temps, les réponses sont validées automatiquement.</small>
                        </label>
                    </div>
                </div>

                <div style="border-top:1px solid var(--card-border); padding-top:14px; display:flex; flex-direction:column; gap:12px;">
                    <div style="font-size:13px;font-weight:700;color:var(--accent);">🎯 Réussite</div>
                    <div class="pubrow" style="align-items:flex-start;">
                        <span style="font-size:18px;line-height:1.3;">🎯</span>
                        <label style="flex:1;">Seuil de réussite (%)
                            <input type="number" name="pass_threshold" min="0" max="100" value="<?= (int) ($passThreshold ?? 0) ?>"
                                   style="width:80px;margin-left:8px;padding:6px 8px;border-radius:8px;border:1px solid var(--card-border);background:var(--card-bg);color:var(--text);font-family:inherit;">
                            <small style="display:block;color:var(--muted);margin-top:4px;"><b>0 = aucun seuil.</b> Sinon, le membre « réussit » à partir de ce pourcentage de bonnes réponses.</small>
                        </label>
                    </div>
                    <label class="lbl" for="msg_pass">Message si réussi (facultatif)</label>
                    <input type="text" id="msg_pass" name="msg_pass" maxlength="255" value="<?= htmlspecialchars($msgPass ?? '') ?>" placeholder="Ex : Bravo, c'est validé ! 🎉">
                    <label class="lbl" for="msg_fail">Message si non atteint (facultatif)</label>
                    <input type="text" id="msg_fail" name="msg_fail" maxlength="255" value="<?= htmlspecialchars($msgFail ?? '') ?>" placeholder="Ex : Presque ! Retente ta chance 💪">
                </div>

                <?php if (!empty($isAdmin)): ?>
                <div style="border-top:1px solid var(--card-border); padding-top:14px; display:flex; flex-direction:column; gap:12px;">
                    <div class="pubrow">
                        <input type="checkbox" id="required" name="required" value="1" <?= !empty($isRequired) ? 'checked' : '' ?>>
                        <label for="required">⛔ <b>Rendre OBLIGATOIRE</b> — l'application est bloquée pour chaque membre tant qu'il n'a pas répondu (les admins ne sont jamais bloqués).</label>
                    </div>
                    <div class="pubrow" style="padding-left:28px;">
                        <input type="checkbox" id="pass_required" name="pass_required" value="1" <?= !empty($isPassRequired) ? 'checked' : '' ?>>
                        <label for="pass_required">🎯 <b>Il faut réussir pour continuer</b> — le membre doit avoir <b>toutes les bonnes réponses</b> (sinon il doit recommencer). Sans cette case, répondre suffit.</label>
                    </div>
                    <p class="hint" style="margin:0;">⚠️ « Obligatoire » concerne tout le site : à n'utiliser que pour un questionnaire que chaque membre doit vraiment remplir (charte, consigne…).</p>
                </div>
                <?php endif; ?>
            </div>

            <div class="actions">
                <button type="submit" class="save"><?= $isEdit ? '💾 Enregistrer les modifications' : '✅ Créer le questionnaire' ?></button>
                <a class="cancel" href="<?= $isEdit ? url('quiz/show') . '?id=' . $qid : url('quiz') ?>">Annuler</a>
            </div>
        </form>
    </div>

    <!-- Gabarits clonés par le JS -->
    <template id="tpl-question">
        <div class="question">
            <button type="button" class="rm-q" title="Supprimer cette question">🗑️ Question</button>
            <div class="qtop">
                <span class="qnum">Q1</span>
                <input type="text" class="qbody" placeholder="Énoncé de la question…">
            </div>

            <label class="lbl">Type de question</label>
            <select class="qtype-sel">
                <option value="single">🔘 QCM — une seule bonne réponse</option>
                <option value="multiple">☑️ QCM — plusieurs bonnes réponses</option>
                <option value="numeric">🔢 Réponse chiffrée à saisir (avec tolérance)</option>
                <option value="text">⌨️ Réponse texte à saisir</option>
                <option value="fill">✍️ Texte à trous</option>
                <option value="order">🔀 Remettre dans l'ordre</option>
                <option value="match">🔗 Associer par paires</option>
            </select>

            <div class="qimg-row">
                <label class="qimg-pick">🖼️ <span class="qimg-label">Ajouter une image</span>
                    <input type="file" class="qimg js-autoresize" accept="image/jpeg,image/png,image/gif,image/webp">
                </label>
                <span class="qimg-current" hidden>
                    <img class="qimg-thumb" alt="">
                    <label class="qimg-rm"><input type="checkbox" class="qimg-remove"> Retirer</label>
                </span>
                <input type="hidden" class="qimg-existing">
            </div>

            <!-- Bloc « réponse à saisir » : numeric / text / fill -->
            <div class="blk-input" hidden>
                <p class="hint blk-fill-hint" hidden>✍️ Dans l'énoncé ci-dessus, marque chaque trou entre crochets, ex : <b>« La diagonale mesure [5] cm »</b>. Indique ci-dessous les réponses des trous, séparées par <b>|</b>, dans l'ordre.</p>
                <label class="lbl blk-ans-lbl">Réponse attendue</label>
                <input type="text" class="qanswer" placeholder="Ex : 5">
                <div class="blk-tol" hidden>
                    <label class="lbl">Tolérance acceptée (±)</label>
                    <input type="text" class="qtol" placeholder="0 (réponse exacte)">
                </div>
            </div>

            <!-- Bloc « options » : single / multiple / order / match -->
            <div class="blk-options">
                <p class="hint blk-opt-hint">Coche la (ou les) bonne(s) réponse(s) à gauche de chaque proposition.</p>
                <div class="opts"></div>
                <div class="btn-line">
                    <button type="button" class="mini add-opt">➕ Ajouter une réponse</button>
                </div>
            </div>

            <input type="text" class="qexplain" placeholder="💡 Explication (facultatif) — montrée après la réponse / dans le corrigé" style="margin-top:12px;">
        </div>
    </template>

    <template id="tpl-option">
        <div class="opt">
            <span class="mark"><input type="checkbox" class="correct" title="Bonne réponse"></span>
            <span class="ord-handle" title="Glisser pour réordonner" hidden>≡</span>
            <input type="text" class="olabel" placeholder="Une réponse possible…">
            <input type="text" class="opair" placeholder="à associer à…" hidden>
            <button type="button" class="rm" title="Supprimer cette réponse">✕</button>
        </div>
    </template>

    <script>
    (function () {
        var box       = document.getElementById('questions');
        var tplQ      = document.getElementById('tpl-question');
        var tplO      = document.getElementById('tpl-option');
        var qCounter  = 0; // index unique pour les noms de champs (q[IDX])

        // Données initiales (édition ou repli après erreur).
        var INITIAL = <?= json_encode($initial, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
        var QIMG_BASE = <?= json_encode($qImgBase, JSON_UNESCAPED_SLASHES) ?>;

        var INPUT_TYPES = ['numeric', 'text', 'fill'];

        function renumber() {
            var qs = box.querySelectorAll('.question');
            qs.forEach(function (q, i) { q.querySelector('.qnum').textContent = 'Q' + (i + 1); });
        }

        // Affiche/masque les sous-champs d'UNE option selon le type de question.
        function applyOptType(opt, type) {
            var mark    = opt.querySelector('.mark');
            var correct = opt.querySelector('.correct');
            var handle  = opt.querySelector('.ord-handle');
            var pair    = opt.querySelector('.opair');
            var isQcm   = (type === 'single' || type === 'multiple');
            mark.style.display = isQcm ? '' : 'none';
            correct.disabled   = !isQcm;
            handle.hidden       = (type !== 'order');
            opt.setAttribute('draggable', type === 'order' ? 'true' : 'false');
            pair.hidden         = (type !== 'match');
            pair.disabled       = (type !== 'match');
            opt.querySelector('.olabel').disabled = false;
            correct.dataset.mode = (type === 'multiple') ? 'multiple' : 'single';
        }

        // Applique le type à toute la question : blocs visibles, champs actifs, libellés.
        function applyType(qEl) {
            var type = qEl.querySelector('.qtype-sel').value;
            qEl.dataset.type = type;
            var isInput = INPUT_TYPES.indexOf(type) !== -1;

            var blkInput = qEl.querySelector('.blk-input');
            var blkOpts  = qEl.querySelector('.blk-options');
            blkInput.hidden = !isInput;
            blkOpts.hidden  = isInput;

            var qa = qEl.querySelector('.qanswer'), qt = qEl.querySelector('.qtol');
            qa.disabled = !isInput;
            qt.disabled = (type !== 'numeric');
            qEl.querySelector('.blk-tol').hidden       = (type !== 'numeric');
            qEl.querySelector('.blk-fill-hint').hidden = (type !== 'fill');

            var lbl = qEl.querySelector('.blk-ans-lbl');
            if (type === 'numeric') { lbl.textContent = 'Réponse attendue (un nombre)'; qa.placeholder = 'Ex : 13'; }
            else if (type === 'text') { lbl.textContent = 'Réponse(s) acceptée(s) — variantes séparées par |'; qa.placeholder = "Ex : hypoténuse|l'hypoténuse"; }
            else if (type === 'fill') { lbl.textContent = "Réponses des trous — séparées par | (dans l'ordre)"; qa.placeholder = 'Ex : 5|13'; }

            var optHint = qEl.querySelector('.blk-opt-hint');
            if (type === 'order') { optHint.innerHTML = '🔀 Saisis les éléments <b>dans le bon ordre</b> (de haut en bas). Ils seront mélangés pour le membre. Glisse ≡ pour réordonner.'; }
            else if (type === 'match') { optHint.innerHTML = "🔗 Pour chaque élément de gauche, indique l'élément de droite à associer."; }
            else { optHint.textContent = 'Coche la (ou les) bonne(s) réponse(s) à gauche de chaque proposition.'; }

            if (isInput) {
                // Bloc options inactif : on désactive ses champs pour qu'ils ne soient pas envoyés.
                blkOpts.querySelectorAll('input').forEach(function (i) { i.disabled = true; });
            } else {
                qEl.querySelectorAll('.opt').forEach(function (o) { applyOptType(o, type); });
            }
        }

        function addOption(qEl, data) {
            var qIndex = qEl.dataset.index;
            var node   = tplO.content.firstElementChild.cloneNode(true);
            var optIndex = (qEl.dataset.optCounter = (parseInt(qEl.dataset.optCounter || '0', 10) + 1));

            var label = node.querySelector('.olabel');
            var cb    = node.querySelector('.correct');
            var pair  = node.querySelector('.opair');
            label.name = 'q[' + qIndex + '][opt][' + optIndex + '][label]';
            cb.name    = 'q[' + qIndex + '][opt][' + optIndex + '][correct]';
            cb.value   = '1';
            pair.name  = 'q[' + qIndex + '][opt][' + optIndex + '][pair]';

            if (data) {
                label.value = data.label || '';
                cb.checked  = !!data.correct;
                pair.value  = data.pair || '';
            }

            // En mode « single », cocher une bonne réponse décoche les autres.
            cb.addEventListener('change', function () {
                if (cb.dataset.mode === 'single' && cb.checked) {
                    qEl.querySelectorAll('.correct').forEach(function (other) {
                        if (other !== cb) { other.checked = false; }
                    });
                }
            });

            node.querySelector('.rm').addEventListener('click', function () {
                if (qEl.querySelectorAll('.opt').length <= 2) {
                    alert('Une question doit garder au moins 2 réponses.');
                    return;
                }
                node.remove();
            });

            qEl.querySelector('.opts').appendChild(node);
            applyOptType(node, qEl.dataset.type || 'single');
        }

        // Glisser-déposer pour réordonner les options (type « order »).
        function setupDrag(qEl) {
            var opts = qEl.querySelector('.opts');
            var dragged = null;
            opts.addEventListener('dragstart', function (e) {
                var t = e.target.closest('.opt');
                if (!t || qEl.dataset.type !== 'order') { return; }
                dragged = t; t.style.opacity = '.5';
            });
            opts.addEventListener('dragend', function () {
                if (dragged) { dragged.style.opacity = ''; }
                dragged = null;
                opts.querySelectorAll('.opt').forEach(function (o) { o.classList.remove('drag-over'); });
            });
            opts.addEventListener('dragover', function (e) {
                if (!dragged) { return; }
                e.preventDefault();
                var t = e.target.closest('.opt');
                opts.querySelectorAll('.opt').forEach(function (o) { o.classList.remove('drag-over'); });
                if (t && t !== dragged) { t.classList.add('drag-over'); }
            });
            opts.addEventListener('drop', function (e) {
                if (!dragged) { return; }
                e.preventDefault();
                var t = e.target.closest('.opt');
                if (t && t !== dragged) { opts.insertBefore(dragged, t); }
            });
        }

        function addQuestion(data) {
            var node = tplQ.content.firstElementChild.cloneNode(true);
            var index = (qCounter++);
            node.dataset.index = index;
            node.dataset.optCounter = 0;
            node.dataset.type = (data && data.type) || 'single';

            var body = node.querySelector('.qbody');
            body.name = 'q[' + index + '][body]';
            if (data && data.body) { body.value = data.body; }

            var explain = node.querySelector('.qexplain');
            explain.name = 'q[' + index + '][explanation]';
            if (data && data.explanation) { explain.value = data.explanation; }

            // Sélecteur de type.
            var sel = node.querySelector('.qtype-sel');
            sel.name = 'q[' + index + '][type]';
            if (data && data.type) { sel.value = data.type; }
            sel.addEventListener('change', function () { applyType(node); });

            // Réponse à saisir (numeric/text/fill) + tolérance (numeric).
            var qa = node.querySelector('.qanswer'), qt = node.querySelector('.qtol');
            qa.name = 'q[' + index + '][answer]';
            qt.name = 'q[' + index + '][tolerance]';
            if (data && data.answer) { qa.value = data.answer; }
            if (data && data.tolerance) { qt.value = data.tolerance; }

            // Image de la question : champ fichier + conservation/retrait de l'existante.
            var fileInp  = node.querySelector('.qimg');
            var existing = node.querySelector('.qimg-existing');
            var curBox   = node.querySelector('.qimg-current');
            var thumb    = node.querySelector('.qimg-thumb');
            var rmCb     = node.querySelector('.qimg-remove');
            var lbl      = node.querySelector('.qimg-label');
            fileInp.name  = 'qimg[' + index + ']';
            existing.name = 'q[' + index + '][existing_image]';
            rmCb.name     = 'q[' + index + '][remove_image]';
            rmCb.value    = '1';
            if (data && data.image) {
                existing.value = data.image;
                thumb.src = QIMG_BASE + encodeURIComponent(data.image);
                curBox.hidden = false;
                if (lbl) { lbl.textContent = "Changer l'image"; }
            }
            fileInp.addEventListener('change', function () {
                if (fileInp.files && fileInp.files[0]) {
                    thumb.src = URL.createObjectURL(fileInp.files[0]);
                    curBox.hidden = false;
                    if (rmCb) { rmCb.checked = false; }
                    if (lbl) { lbl.textContent = "Changer l'image"; }
                }
            });

            node.querySelector('.add-opt').addEventListener('click', function () { addOption(node); });
            node.querySelector('.rm-q').addEventListener('click', function () {
                node.remove();
                renumber();
            });

            box.appendChild(node);
            setupDrag(node);

            // Options : celles fournies, sinon 2 vides par défaut.
            if (data && data.options && data.options.length) {
                data.options.forEach(function (o) { addOption(node, o); });
            } else {
                addOption(node); addOption(node);
            }
            applyType(node);
            renumber();
        }

        document.getElementById('addQuestion').addEventListener('click', function () { addQuestion(); });

        // Pré-remplissage initial : questions existantes, sinon une question vierge.
        if (INITIAL && INITIAL.length) {
            INITIAL.forEach(function (q) { addQuestion(q); });
        } else {
            addQuestion();
        }

        // Une question est-elle complète, selon son type ? (garde-fou avant envoi)
        function questionOk(q) {
            var type = q.dataset.type || 'single';
            var hasBody = q.querySelector('.qbody').value.trim() !== '';
            if (!hasBody) { return false; }
            if (type === 'numeric') { var v = q.querySelector('.qanswer').value.trim().replace(',', '.'); return v !== '' && !isNaN(parseFloat(v)); }
            if (type === 'text')    { return q.querySelector('.qanswer').value.trim() !== ''; }
            if (type === 'fill')    { return /\[[^\]]*\]/.test(q.querySelector('.qbody').value) && q.querySelector('.qanswer').value.trim() !== ''; }
            var labels = Array.prototype.filter.call(q.querySelectorAll('.olabel'), function (i) { return i.value.trim() !== ''; });
            if (labels.length < 2) { return false; }
            if (type === 'order') { return true; }
            if (type === 'match') { return Array.prototype.every.call(q.querySelectorAll('.opt'), function (o) {
                return o.querySelector('.olabel').value.trim() !== '' && o.querySelector('.opair').value.trim() !== ''; }); }
            // single / multiple : au moins une bonne réponse cochée
            return Array.prototype.some.call(q.querySelectorAll('.correct'), function (c) { return c.checked; });
        }

        document.getElementById('quizForm').addEventListener('submit', function (e) {
            var ok = Array.prototype.some.call(box.querySelectorAll('.question'), questionOk);
            if (!ok) {
                e.preventDefault();
                alert('Ajoute au moins une question complète selon son type (énoncé + réponse(s) attendue(s)).');
            }
        });
    })();
    </script>
    <?= image_resize_js() ?>
</body>
</html>
