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
            $opts[] = ['label' => (string) ($o['label'] ?? ''), 'correct' => !empty($o['correct'])];
        }
        $initial[] = [
            'body'    => (string) ($q['body'] ?? ''),
            'type'    => ($q['type'] ?? 'single') === 'multiple' ? 'multiple' : 'single',
            'options' => $opts,
        ];
    }
} elseif ($isEdit) {
    foreach ($questions as $q) {
        $opts = [];
        foreach ($q['options'] as $o) {
            $opts[] = ['label' => (string) $o['label'], 'correct' => (int) $o['is_correct'] === 1];
        }
        $initial[] = ['body' => (string) $q['body'], 'type' => (string) $q['type'], 'options' => $opts];
    }
}
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
            <div class="qmode">
                <label><input type="radio" class="qtype" value="single" checked> 🔘 Une seule bonne réponse</label>
                <label><input type="radio" class="qtype" value="multiple"> ☑️ Plusieurs bonnes réponses</label>
            </div>
            <p class="hint">Coche la (ou les) bonne(s) réponse(s) à gauche de chaque proposition.</p>
            <div class="opts"></div>
            <div class="btn-line">
                <button type="button" class="mini add-opt">➕ Ajouter une réponse</button>
            </div>
        </div>
    </template>

    <template id="tpl-option">
        <div class="opt">
            <span class="mark"><input type="checkbox" class="correct" title="Bonne réponse"></span>
            <input type="text" class="olabel" placeholder="Une réponse possible…">
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

        function renumber() {
            var qs = box.querySelectorAll('.question');
            qs.forEach(function (q, i) { q.querySelector('.qnum').textContent = 'Q' + (i + 1); });
        }

        // Applique le mode (single/multiple) : cases à cocher qui se comportent
        // comme des radios en mode « une seule bonne réponse ».
        function applyMode(qEl) {
            var multiple = qEl.querySelector('.qtype[value="multiple"]').checked;
            var marks = qEl.querySelectorAll('.correct');
            marks.forEach(function (cb) { cb.dataset.mode = multiple ? 'multiple' : 'single'; });
        }

        function addOption(qEl, data) {
            var qIndex = qEl.dataset.index;
            var node   = tplO.content.firstElementChild.cloneNode(true);
            var optIndex = (qEl.dataset.optCounter = (parseInt(qEl.dataset.optCounter || '0', 10) + 1));

            var label = node.querySelector('.olabel');
            var cb    = node.querySelector('.correct');
            label.name = 'q[' + qIndex + '][opt][' + optIndex + '][label]';
            cb.name    = 'q[' + qIndex + '][opt][' + optIndex + '][correct]';
            cb.value   = '1';

            if (data) {
                label.value = data.label || '';
                cb.checked  = !!data.correct;
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
            applyMode(qEl);
        }

        function addQuestion(data) {
            var node = tplQ.content.firstElementChild.cloneNode(true);
            var index = (qCounter++);
            node.dataset.index = index;
            node.dataset.optCounter = 0;

            var body = node.querySelector('.qbody');
            body.name = 'q[' + index + '][body]';
            if (data && data.body) { body.value = data.body; }

            var types = node.querySelectorAll('.qtype');
            types.forEach(function (r) {
                r.name = 'q[' + index + '][type]';
                if (data && data.type === r.value) { r.checked = true; }
                r.addEventListener('change', function () { applyMode(node); });
            });

            node.querySelector('.add-opt').addEventListener('click', function () { addOption(node); });
            node.querySelector('.rm-q').addEventListener('click', function () {
                node.remove();
                renumber();
            });

            box.appendChild(node);

            // Options : celles fournies, sinon 2 vides par défaut.
            if (data && data.options && data.options.length) {
                data.options.forEach(function (o) { addOption(node, o); });
            } else {
                addOption(node); addOption(node);
            }
            applyMode(node);
            renumber();
        }

        document.getElementById('addQuestion').addEventListener('click', function () { addQuestion(); });

        // Pré-remplissage initial : questions existantes, sinon une question vierge.
        if (INITIAL && INITIAL.length) {
            INITIAL.forEach(function (q) { addQuestion(q); });
        } else {
            addQuestion();
        }

        // Garde-fou avant envoi : au moins une question avec une bonne réponse.
        document.getElementById('quizForm').addEventListener('submit', function (e) {
            var ok = false;
            box.querySelectorAll('.question').forEach(function (q) {
                var hasBody = q.querySelector('.qbody').value.trim() !== '';
                var hasCorrect = Array.prototype.some.call(q.querySelectorAll('.correct'), function (c) { return c.checked; });
                var enough = q.querySelectorAll('.olabel').length >= 2;
                if (hasBody && hasCorrect && enough) { ok = true; }
            });
            if (!ok) {
                e.preventDefault();
                alert('Ajoute au moins une question complète : un énoncé, 2 réponses et au moins une bonne réponse cochée.');
            }
        });
    })();
    </script>
    <?= image_resize_js() ?>
</body>
</html>
