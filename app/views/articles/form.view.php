<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(Settings::get('main_title', 'RPN')) ?> — <?= $article ? 'Modifier' : 'Nouvel' ?> article</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <?= Theme::css() ?>
    <style>
        * { box-sizing:border-box; margin:0; padding:0; }
        body {
            font-family:'Poppins',sans-serif; min-height:100vh; color:var(--text); padding:40px 20px;
            background: radial-gradient(circle at 10% 0%, var(--glow1), transparent 42%), var(--bg-base);
        }
        body::before { content:""; position:fixed; top:0; left:0; right:0; height:6px; background:var(--bar); }
        .wrap { max-width:680px; margin:0 auto; }
        .top { display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; flex-wrap:wrap; gap:12px; }
        h1 { font-size:22px; } h1 span { color:var(--accent); }
        .nav a { color:var(--text); text-decoration:none; font-size:14px; padding:8px 16px; border-radius:10px; border:1px solid var(--card-border); }
        .nav a:hover { border-color:var(--accent); color:var(--accent); }
        .card { background:var(--card-bg); border:1px solid var(--card-border); border-radius:18px; padding:28px; box-shadow:var(--card-shadow); }
        label { display:block; font-size:13px; margin:0 0 6px 2px; color:var(--muted); }
        input[type=text], textarea, input[type=file] { width:100%; padding:12px 14px; margin-bottom:18px; border-radius:10px;
            border:1px solid var(--card-border); background:rgba(127,127,127,.08); color:var(--text); font-size:14px; font-family:inherit; }
        input:focus, textarea:focus { outline:none; border-color:var(--accent); }
        textarea { resize:vertical; min-height:220px; line-height:1.6; }
        .tpl-select { width:100%; padding:12px 14px; margin-bottom:8px; border-radius:10px;
            border:1px solid var(--card-border); background:rgba(127,127,127,.08); color:var(--text); font-size:14px; font-family:inherit; }
        .tpl-select:focus { outline:none; border-color:var(--accent); }
        /* Menu déroulant aux couleurs du thème (sinon options blanches par défaut) */
        .tpl-select option, .rte-select option { background:var(--bg-base); color:var(--text); }
        .tpl-select optgroup { background:var(--bg-base); color:var(--accent); font-weight:700; }
        .pub-check { display:flex; align-items:center; gap:10px; margin:4px 0 20px; cursor:pointer; font-size:14px; color:var(--text); }
        .pub-check input { width:18px; height:18px; accent-color:var(--accent); }
        .pub-check small { color:var(--muted); font-size:12px; }
        /* Interrupteur ANNONCE (rouge quand activé) */
        .ann-box { display:flex; align-items:center; gap:12px; margin:4px 0 16px; }
        .ann-toggle { position:relative; display:inline-block; width:52px; height:28px; flex:0 0 auto; cursor:pointer; }
        .ann-toggle input { opacity:0; width:0; height:0; position:absolute; }
        .ann-slider { position:absolute; inset:0; background:#b9b9c2; border-radius:999px; transition:background .2s; }
        .ann-slider::before { content:""; position:absolute; height:22px; width:22px; left:3px; top:3px;
            background:#fff; border-radius:50%; transition:transform .2s; box-shadow:0 1px 3px rgba(0,0,0,.3); }
        .ann-toggle input:checked + .ann-slider { background:#e63946; }
        .ann-toggle input:checked + .ann-slider::before { transform:translateX(24px); }
        .ann-text { font-size:14px; font-weight:600; color:var(--text); }
        .ann-text small { display:block; color:var(--muted); font-size:12px; font-weight:400; }
        .subnote { font-size:13px; color:var(--accent); background:rgba(127,127,127,.08); border:1px solid var(--card-border);
            border-radius:10px; padding:10px 14px; margin-bottom:18px; }

        /* Éditeur de texte riche */
        .rte-toolbar { display:flex; flex-wrap:wrap; gap:6px; align-items:center; padding:8px;
            border:1px solid var(--card-border); border-bottom:none; border-radius:10px 10px 0 0; background:rgba(127,127,127,.06); }
        .rte-toolbar button { background:rgba(127,127,127,.10); color:var(--text); border:1px solid var(--card-border);
            border-radius:7px; padding:6px 10px; font-size:13px; line-height:1; cursor:pointer; font-family:inherit; }
        .rte-toolbar button:hover { border-color:var(--accent); color:var(--accent); }
        .rte-toolbar .sep { width:1px; height:20px; background:var(--card-border); margin:0 2px; }
        .rte-select { background:rgba(127,127,127,.10); color:var(--text); border:1px solid var(--card-border);
            border-radius:7px; padding:6px 8px; font-size:13px; font-family:inherit; cursor:pointer; }
        .rte-select:focus { outline:none; border-color:var(--accent); }
        .rte-editor { min-height:240px; padding:14px 16px; margin-bottom:18px; border:1px solid var(--card-border);
            border-radius:0 0 10px 10px; background:rgba(127,127,127,.08); color:var(--text); font-size:15px; line-height:1.7; }
        .rte-editor:focus { outline:none; border-color:var(--accent); }
        .rte-editor:empty:before { content:attr(data-ph); color:var(--muted); }
        .rte-editor h2 { font-size:20px; color:var(--accent); margin:14px 0 8px; }
        .rte-editor h3 { font-size:17px; color:var(--accent); margin:12px 0 6px; }
        .rte-editor ul, .rte-editor ol { margin:8px 0 8px 24px; }
        .rte-editor a { color:var(--accent); }
        .rte-editor blockquote { border-left:3px solid var(--accent); margin:10px 0; padding:4px 14px; color:var(--muted); }

        /* Aperçu en temps réel (rendu fidèle via iframe = vrai gabarit) */
        .apv-wrap { margin-top:28px; }
        .apv-head { display:flex; align-items:center; gap:10px; font-size:13px; text-transform:uppercase;
            letter-spacing:1px; color:var(--muted); margin-bottom:12px; }
        .apv-head .spin { width:13px; height:13px; border-radius:50%; border:2px solid var(--card-border);
            border-top-color:var(--accent); animation:apvspin .6s linear infinite; opacity:0; transition:opacity .15s; }
        .apv-head.loading .spin { opacity:1; }
        @keyframes apvspin { to { transform:rotate(360deg); } }
        .apv-frame { width:100%; min-height:420px; border:1px solid var(--card-border); border-radius:18px;
            background:var(--card-bg); box-shadow:var(--card-shadow); display:block; }
        .btn { display:inline-flex; align-items:center; justify-content:center; gap:10px; width:100%;
            padding:16px 28px; border:none; border-radius:14px; cursor:pointer; font-family:inherit;
            font-weight:800; font-size:16px; letter-spacing:.3px; color:var(--accent-ink); background:var(--accent);
            box-shadow:0 12px 30px rgba(0,0,0,.28), inset 0 1px 0 rgba(255,255,255,.25);
            transition:transform .15s, box-shadow .2s, filter .15s; }
        .btn:hover { transform:translateY(-2px); box-shadow:0 18px 42px rgba(0,0,0,.34), inset 0 1px 0 rgba(255,255,255,.3); filter:brightness(1.05); }
        .btn:active { transform:translateY(0); box-shadow:0 8px 20px rgba(0,0,0,.25); }
        .btn .btn-ico { font-size:18px; }
        .error { background:rgba(230,57,70,.15); border:1px solid rgba(230,57,70,.4); color:#e0566a;
            padding:11px 14px; border-radius:10px; margin-bottom:20px; font-size:14px; }
        .hint { font-size:11px; color:var(--muted); margin:-12px 0 16px 2px; }
        .quiz-pick { display:flex; flex-direction:column; gap:6px; max-height:190px; overflow:auto;
            border:1px solid var(--card-border); border-radius:12px; padding:10px 12px; margin-bottom:6px;
            background:rgba(127,127,127,.05); }
        .quiz-opt { display:flex; align-items:center; gap:10px; font-size:14px; cursor:pointer; padding:6px 4px; border-radius:8px; }
        .quiz-opt:hover { background:rgba(127,127,127,.08); }
        .quiz-opt input { width:17px; height:17px; accent-color:var(--accent); flex:0 0 auto; }
        .quiz-opt em { color:var(--muted); font-style:normal; font-size:12px; }
        /* Validation inline élégante (remplace l'alerte) */
        .form-msg { display:none; align-items:center; gap:9px; margin-bottom:14px; font-size:13.5px; font-weight:600;
            color:#e0566a; background:rgba(230,57,70,.1); border:1px solid rgba(230,57,70,.35);
            border-radius:12px; padding:12px 15px; }
        .form-msg.show { display:flex; animation:msgIn .28s ease; }
        @keyframes msgIn { from { opacity:0; transform:translateY(7px); } to { opacity:1; transform:none; } }
        .field-error { border-color:var(--rouge,#e63946) !important;
            box-shadow:0 0 0 3px rgba(230,57,70,.16) !important; }
        .shake { animation:shake .45s cubic-bezier(.36,.07,.19,.97); }
        @keyframes shake { 10%,90%{transform:translateX(-1px);} 20%,80%{transform:translateX(2px);}
            30%,50%,70%{transform:translateX(-5px);} 40%,60%{transform:translateX(5px);} }
        .current { display:flex; align-items:center; gap:14px; margin-bottom:18px; }
        .current img { width:120px; height:78px; object-fit:cover; border-radius:10px; border:1px solid var(--card-border); }
        .current span { font-size:12px; color:var(--muted); }
        .hidden-file { position:absolute; width:1px; height:1px; padding:0; margin:-1px; overflow:hidden; clip:rect(0 0 0 0); border:0; }
        .uploader { display:flex; align-items:center; gap:14px; padding:15px 18px; margin-bottom:12px; cursor:pointer;
            border:2px dashed var(--card-border); border-radius:14px; background:rgba(127,127,127,.05); transition:border-color .15s, background .15s; }
        .uploader:hover { border-color:var(--accent); background:rgba(127,127,127,.10); }
        .uploader:focus-within { border-color:var(--accent); }
        .up-ico { font-size:26px; line-height:1; }
        .up-txt { display:flex; flex-direction:column; gap:2px; }
        .up-txt b { font-size:14px; color:var(--text); font-weight:700; }
        .up-txt small { font-size:12px; color:var(--muted); }
        /* Grille unifiée des photos (avec choix de la principale ⭐) */
        .photo-grid { display:flex; flex-wrap:wrap; gap:14px; margin:8px 0 14px; }
        .photo-grid:empty { display:none; }
        .photo-item { position:relative; width:128px; }
        .photo-item img { width:128px; height:94px; object-fit:cover; border-radius:12px;
            border:1px solid var(--card-border); display:block; }
        .photo-item.is-main img { border:2px solid var(--accent); box-shadow:0 0 0 3px rgba(127,127,127,.12); }
        .photo-item .star { position:absolute; top:6px; left:6px; width:28px; height:28px; border-radius:50%;
            border:none; cursor:pointer; font-size:15px; line-height:1; padding:0; background:rgba(0,0,0,.5); color:#fff;
            display:flex; align-items:center; justify-content:center; transition:background .15s, transform .1s; }
        .photo-item .star:hover { background:rgba(0,0,0,.72); transform:scale(1.08); }
        .photo-item.is-main .star { background:var(--accent); color:var(--accent-ink); }
        .photo-item .rm { position:absolute; top:-8px; right:-8px; width:23px; height:23px; border-radius:50%;
            border:2px solid var(--card-bg); cursor:pointer; background:var(--rouge); color:#fff; font-size:13px; line-height:1;
            display:flex; align-items:center; justify-content:center; padding:0; }
        .photo-item .rm:hover { filter:brightness(1.12); }
        .photo-item .badge { position:absolute; bottom:6px; left:6px; right:6px; text-align:center; font-size:10px;
            font-weight:700; text-transform:uppercase; letter-spacing:.5px; color:var(--accent-ink); background:var(--accent);
            border-radius:7px; padding:2px 0; }
        /* Liste des documents (pièces jointes) dans le formulaire */
        .doc-list { display:flex; flex-direction:column; gap:8px; margin:8px 0 12px; }
        .doc-list:empty { display:none; }
        .doc-item { display:flex; align-items:center; gap:12px; padding:10px 14px; border-radius:10px;
            border:1px solid var(--card-border); background:rgba(127,127,127,.05); }
        .doc-item .d-ico { font-size:22px; line-height:1; flex:0 0 auto; }
        .doc-item .d-info { flex:1; min-width:0; display:flex; flex-direction:column; gap:1px; }
        .doc-item .d-name { font-size:14px; font-weight:600; word-break:break-word; }
        .doc-item .d-meta { font-size:11px; color:var(--muted); text-transform:uppercase; letter-spacing:.5px; }
        .doc-item .d-rm { flex:0 0 auto; width:24px; height:24px; border-radius:50%; border:none; cursor:pointer;
            background:var(--rouge); color:#fff; font-size:14px; line-height:1; display:flex; align-items:center; justify-content:center; padding:0; }
        .doc-item .d-rm:hover { filter:brightness(1.12); }
        .preview-grid { display:flex; flex-wrap:wrap; gap:12px; margin:4px 0 14px; }
        .add-photo { display:inline-flex; align-items:center; gap:8px; margin:0 0 8px; padding:11px 18px; cursor:pointer;
            font-family:inherit; font-weight:600; font-size:14px; color:var(--accent); background:rgba(127,127,127,.06);
            border:2px dashed var(--card-border); border-radius:12px; transition:border-color .15s, background .15s; }
        .add-photo:hover { border-color:var(--accent); background:rgba(127,127,127,.12); }
        .preview-item { position:relative; }
        .preview-item img { width:96px; height:70px; object-fit:cover; border-radius:10px; border:1px solid var(--accent); display:block; }
        .preview-item .rm { position:absolute; top:-7px; right:-7px; width:22px; height:22px; border-radius:50%;
            border:2px solid var(--card-bg); cursor:pointer; background:var(--rouge); color:#fff; font-size:13px; line-height:1;
            display:flex; align-items:center; justify-content:center; padding:0; }
        .preview-item .rm:hover { filter:brightness(1.12); }
        .gallery-existing { display:flex; flex-wrap:wrap; gap:12px; margin:6px 0 14px; }
        .gthumb { display:flex; flex-direction:column; gap:5px; font-size:11px; color:var(--muted); cursor:pointer; }
        .gthumb img { width:92px; height:66px; object-fit:cover; border-radius:8px; border:1px solid var(--card-border); }
        .gthumb .del { display:flex; align-items:center; gap:5px; }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="top">
            <h1><?= $article ? '✏️ <span>Modifier</span> l\'article' : '＋ <span>Nouvel</span> article' ?></h1>
            <div class="nav"><a href="<?= htmlspecialchars($back) ?>">← Retour</a></div>
        </div>

        <?php if (!empty($error)): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form class="card" id="articleForm" method="post" action="<?= htmlspecialchars($action) ?>" enctype="multipart/form-data">
            <?php if ($article): ?>
                <input type="hidden" name="id" value="<?= (int) $article['id'] ?>">
            <?php endif; ?>
            <?php if (!empty($parents)): ?>
                <label for="parent_id">Article parent <small style="color:var(--muted);font-weight:400">— en faire un sous-article (facultatif)</small></label>
                <select id="parent_id" name="parent_id">
                    <option value="0">— Aucun (article principal) —</option>
                    <?php foreach ($parents as $p): ?>
                        <option value="<?= (int) $p['id'] ?>" <?= (int) $p['id'] === (int) ($parentId ?? 0) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($p['title']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="hint">Un sous-article n'apparaît pas dans la liste principale : il s'ouvre depuis son article parent.</p>
            <?php elseif (!empty($parentId)): ?>
                <input type="hidden" name="parent_id" value="<?= (int) $parentId ?>">
                <div class="subnote">↳ Sous-article de <b><?= htmlspecialchars($parentTitle) ?></b></div>
            <?php endif; ?>

            <?php if (!empty($quizzes)): ?>
                <?php $sel = array_map('intval', $selectedQuizzes ?? []); ?>
                <label>📝 Questionnaires associés <small style="color:var(--muted);font-weight:400">— proposés à la fin de la lecture (facultatif)</small></label>
                <div class="quiz-pick">
                    <?php foreach ($quizzes as $qz): ?>
                        <label class="quiz-opt">
                            <input type="checkbox" name="quizzes[]" value="<?= (int) $qz['id'] ?>" <?= in_array((int) $qz['id'], $sel, true) ? 'checked' : '' ?>>
                            <span>❓ <?= htmlspecialchars($qz['title']) ?><?php if ((int) $qz['active'] !== 1): ?> <em>(brouillon)</em><?php endif; ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
                <p class="hint">À la fin de l'article, le lecteur pourra <b>commencer à répondre</b> au(x) questionnaire(s) coché(s).</p>
            <?php endif; ?>

            <label for="title">Titre</label>
            <input type="text" id="title" name="title" value="<?= htmlspecialchars($article['title'] ?? ($prefill['title'] ?? '')) ?>"
                   placeholder="Titre de l'article" autofocus>

            <label for="template">Mise en page de l'article</label>
            <select id="template" name="template" class="tpl-select">
                <?php foreach (ArticleTemplate::groups() as $groupLabel => $items): ?>
                    <optgroup label="<?= htmlspecialchars($groupLabel) ?>">
                        <?php foreach ($items as $key => $label): ?>
                            <option value="<?= htmlspecialchars($key) ?>" <?= $key === ($currentTemplate ?? 'standard') ? 'selected' : '' ?>>
                                <?= htmlspecialchars($label) ?>
                            </option>
                        <?php endforeach; ?>
                    </optgroup>
                <?php endforeach; ?>
            </select>
            <p class="hint">Choisit la mise en page de l'article — plusieurs styles classés par catégorie. L'aperçu en bas montre le rendu réel à chaque changement.</p>

            <label class="pub-check">
                <input type="checkbox" name="active" value="1" <?= ($active ?? 1) ? 'checked' : '' ?>>
                <span>Publier l'article <small>(visible par tout le monde, même sans connexion). Décoché = brouillon privé.</small></span>
            </label>

            <label class="pub-check">
                <input type="checkbox" name="urgent" value="1" id="urgChk" <?= !empty($isUrgent) ? 'checked' : '' ?>>
                <span>🟥 <b>Afficher dans le tableau de bord de tout le monde</b> <small>(une alerte rouge « URGENT » apparaît sur le tableau de bord de chaque membre, jusqu'à ce qu'il la ferme).</small></span>
            </label>

            <div style="margin:4px 0 20px; padding:14px 16px; border:1px solid var(--card-border); border-radius:12px; background:rgba(127,127,127,.05);">
                <div style="font-size:14px; margin-bottom:10px;">🔑 <b>Protéger l'accès par mot de passe</b>
                    <small style="display:block;color:var(--muted);margin-top:3px;">Facultatif. Si protégé, les lecteurs devront saisir un mot de passe pour ouvrir l'article. L'auteur et les admins y accèdent toujours sans.</small>
                    <?php if (!empty($hasPassword)): ?><small style="display:block;color:var(--accent);margin-top:4px;">🔒 Un mot de passe est actuellement défini.</small><?php endif; ?>
                </div>
                <?php $defMode = !empty($hasPassword) ? 'custom' : 'none'; ?>
                <div style="display:flex;flex-direction:column;gap:9px;">
                    <label style="display:flex;align-items:center;gap:9px;font-size:14px;cursor:pointer;">
                        <input type="radio" name="pwd_mode" value="none" <?= $defMode === 'none' ? 'checked' : '' ?> style="width:17px;height:17px;accent-color:var(--accent);">
                        <span>🔓 <b>Aucun</b> — accès libre</span>
                    </label>
                    <?php if (!empty($memberCode)): ?>
                    <label style="display:flex;align-items:center;gap:9px;font-size:14px;cursor:pointer;">
                        <input type="radio" name="pwd_mode" value="code" style="width:17px;height:17px;accent-color:var(--accent);">
                        <span>🪪 <b>Mon code membre</b> (par défaut) — <code style="background:rgba(127,127,127,.18);padding:1px 7px;border-radius:6px;letter-spacing:1px;"><?= htmlspecialchars($memberCode) ?></code> <small style="color:var(--muted);">à communiquer aux lecteurs</small></span>
                    </label>
                    <?php endif; ?>
                    <label style="display:flex;align-items:center;gap:9px;font-size:14px;cursor:pointer;">
                        <input type="radio" name="pwd_mode" value="custom" id="pwdModeCustom" <?= $defMode === 'custom' ? 'checked' : '' ?> style="width:17px;height:17px;accent-color:var(--accent);">
                        <span>✏️ <b>Mot de passe personnalisé</b></span>
                    </label>
                    <input type="text" name="access_password" id="pwdCustomInput" autocomplete="new-password"
                           placeholder="<?= !empty($hasPassword) ? 'Nouveau mot de passe (vide = inchangé)' : 'Saisis un mot de passe' ?>"
                           style="margin-left:26px;max-width:320px;padding:10px 13px;border-radius:10px;border:1px solid var(--card-border);background:rgba(127,127,127,.08);color:var(--text);font-family:inherit;font-size:14px;">
                </div>
            </div>
            <script>
            (function(){
                var inp=document.getElementById('pwdCustomInput');
                function sync(){ var c=document.getElementById('pwdModeCustom'); if(inp){ inp.disabled=!(c&&c.checked); inp.style.opacity=inp.disabled?'.5':'1'; } }
                document.querySelectorAll('input[name="pwd_mode"]').forEach(function(r){ r.addEventListener('change',sync); });
                // Sélectionner « personnalisé » dès qu'on tape dans le champ.
                if(inp){ inp.addEventListener('focus',function(){ var c=document.getElementById('pwdModeCustom'); if(c){c.checked=true;} sync(); }); }
                sync();
            })();
            </script>

            <?php if (!empty($isAdmin)): ?>
                <div class="ann-box">
                    <label class="ann-toggle">
                        <input type="checkbox" name="announcement" value="1" id="annChk" <?= !empty($isAnnouncement) ? 'checked' : '' ?>>
                        <span class="ann-slider"></span>
                    </label>
                    <span class="ann-text" id="annText"></span>
                </div>
                <label class="pub-check">
                    <input type="checkbox" name="protected" value="1" <?= !empty($isProtected) ? 'checked' : '' ?>>
                    <span>🔒 <b>Protéger</b> cet article <small>(conservé même lors d'un « effacer tous les articles »).</small></span>
                </label>
            <?php endif; ?>

            <label for="content">Contenu</label>
            <div class="rte-toolbar" id="rteToolbar" hidden>
                <button type="button" data-cmd="bold" title="Gras"><b>B</b></button>
                <button type="button" data-cmd="italic" title="Italique"><i>I</i></button>
                <button type="button" data-cmd="underline" title="Souligné"><u>U</u></button>
                <span class="sep"></span>
                <button type="button" data-cmd="formatBlock" data-val="h2" title="Titre">H2</button>
                <button type="button" data-cmd="formatBlock" data-val="h3" title="Sous-titre">H3</button>
                <button type="button" data-cmd="formatBlock" data-val="blockquote" title="Citation">❝</button>
                <span class="sep"></span>
                <button type="button" data-cmd="insertUnorderedList" title="Liste à puces">• Liste</button>
                <button type="button" data-cmd="insertOrderedList" title="Liste numérotée">1. Liste</button>
                <span class="sep"></span>
                <button type="button" data-cmd="createLink" title="Insérer un lien">🔗 Lien</button>
                <button type="button" data-cmd="unlink" title="Retirer le lien">⛓ Retirer</button>
                <button type="button" data-cmd="removeFormat" title="Effacer la mise en forme">⨯ Nettoyer</button>
                <span class="sep"></span>
                <select class="rte-select" data-cmd="fontName" title="Police d'écriture">
                    <option value="Poppins, sans-serif">Police : Moderne</option>
                    <option value="Georgia, 'Times New Roman', serif">Police : Classique</option>
                    <option value="'Courier New', monospace">Police : Machine à écrire</option>
                </select>
                <select class="rte-select" data-cmd="fontSize" title="Taille du texte">
                    <option value="3">Taille : Normale</option>
                    <option value="2">Taille : Petite</option>
                    <option value="5">Taille : Grande</option>
                    <option value="6">Taille : Très grande</option>
                </select>
                <span class="sep"></span>
                <button type="button" data-cmd="justifyLeft" title="Aligner à gauche">⯇ Gauche</button>
                <button type="button" data-cmd="justifyCenter" title="Centrer">≡ Centré</button>
                <button type="button" data-cmd="justifyRight" title="Aligner à droite">Droite ⯈</button>
            </div>
            <div class="rte-editor" id="rteEditor" contenteditable="true" data-ph="Écrivez votre article ici…" hidden></div>
            <textarea id="content" name="content" placeholder="Écrivez votre article ici…"><?= htmlspecialchars($article['content'] ?? ($prefill['content'] ?? '')) ?></textarea>

            <label>Photos de l'article <small style="color:var(--muted);font-weight:400">— l'étoile ⭐ désigne la photo principale (couverture)</small></label>
            <input type="file" id="photos" name="photos[]" accept="image/jpeg,image/png,image/gif,image/webp" multiple class="hidden-file">
            <input type="hidden" name="principal" id="principalField" value="">
            <input type="hidden" name="remove_cover" id="removeCoverField" value="0">
            <div id="photoGrid" class="photo-grid"></div>
            <label for="photos" class="uploader">
                <span class="up-ico">🖼️</span>
                <span class="up-txt">
                    <b>Ajouter une ou plusieurs photos</b>
                    <small>JPG, PNG, GIF ou WEBP — redimensionnées automatiquement. Les ajouts se cumulent.</small>
                </span>
            </label>
            <p class="hint">Clique sur ⭐ pour choisir la photo principale (elle sert de couverture / visuel en tête). Les autres forment la galerie. Le × retire une photo.</p>

            <label>Documents <small style="color:var(--muted);font-weight:400">— pièces jointes téléchargeables (PDF, Word, Excel…)</small></label>
            <input type="file" id="docs" name="docs[]"
                   accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.csv,.zip,.odt,.ods,.odp" multiple class="hidden-file">
            <div id="docList" class="doc-list"></div>
            <label for="docs" class="uploader">
                <span class="up-ico">📎</span>
                <span class="up-txt">
                    <b>Ajouter un ou plusieurs documents</b>
                    <small>PDF, Word, Excel, PowerPoint, txt, zip… — max 25 Mo chacun. Les ajouts se cumulent.</small>
                </span>
            </label>
            <p class="hint">Les documents s'affichent en pièces jointes sous l'article (ouverture / téléchargement).</p>

            <div class="form-msg" id="formMsg"></div>
            <button class="btn" id="submitBtn" type="submit">
                <span class="btn-ico"><?= $article ? '💾' : '🚀' ?></span>
                <?= $article ? 'Enregistrer les modifications' : 'Publier l\'article' ?>
            </button>
        </form>

        <div class="apv-wrap">
            <div class="apv-head" id="apvHead">👁️ Aperçu en temps réel — fidèle au rendu réel <span class="spin"></span></div>
            <iframe class="apv-frame" id="apvFrame" title="Aperçu de l'article"></iframe>
        </div>
    </div>

    <script>
    // Éditeur de texte riche (amélioration progressive).
    // Sans JavaScript, le <textarea> reste utilisable tel quel.
    (function () {
        var ta = document.getElementById('content');
        var ed = document.getElementById('rteEditor');
        var tb = document.getElementById('rteToolbar');
        if (!ta || !ed || !tb || !document.execCommand) { return; }

        // Charge le contenu existant dans l'éditeur, masque le champ texte.
        ed.innerHTML = ta.value || '';
        ta.style.display = 'none';
        ed.hidden = false;
        tb.hidden = false;

        function sync() { ta.value = ed.innerHTML; }

        // Convertit les <font size>/<font face> produits par le navigateur
        // en <span style> (compatibles avec le nettoyage côté serveur).
        function normalize() {
            var sizes = {'1':'13px','2':'14px','3':'16px','4':'18px','5':'22px','6':'28px','7':'40px'};
            ed.querySelectorAll('font[size]').forEach(function (fn) {
                var sp = document.createElement('span');
                sp.style.fontSize = sizes[fn.getAttribute('size')] || '16px';
                while (fn.firstChild) { sp.appendChild(fn.firstChild); }
                fn.parentNode.replaceChild(sp, fn);
            });
            ed.querySelectorAll('font[face]').forEach(function (fn) {
                var sp = document.createElement('span');
                sp.style.fontFamily = fn.getAttribute('face');
                while (fn.firstChild) { sp.appendChild(fn.firstChild); }
                fn.parentNode.replaceChild(sp, fn);
            });
        }

        // Sauvegarde / restauration de la sélection (les menus déroulants
        // font perdre le focus de l'éditeur, on rétablit la sélection avant d'agir).
        var savedRange = null;
        function saveSel() {
            var s = window.getSelection();
            if (s && s.rangeCount && ed.contains(s.anchorNode)) { savedRange = s.getRangeAt(0); }
        }
        function restoreSel() {
            if (!savedRange) { return; }
            var s = window.getSelection();
            s.removeAllRanges();
            s.addRange(savedRange);
        }
        ed.addEventListener('keyup', saveSel);
        ed.addEventListener('mouseup', saveSel);
        ed.addEventListener('blur', saveSel);
        ed.addEventListener('input', function () { saveSel(); sync(); });

        var form = ed.closest('form');
        if (form) { form.addEventListener('submit', function () { normalize(); sync(); }); }

        function run(cmd, val) {
            try { document.execCommand('styleWithCSS', false, true); } catch (e) {}
            if (cmd === 'createLink') {
                var url = prompt('Adresse du lien (https://…)');
                if (!url) { return; }
                document.execCommand('createLink', false, url);
            } else {
                document.execCommand(cmd, false, val || null);
            }
            normalize();
            ed.focus();
            saveSel();
            sync();
        }

        // Garde la sélection quand on clique un BOUTON (pas un menu déroulant).
        tb.addEventListener('mousedown', function (e) {
            if (e.target.closest('button')) { e.preventDefault(); }
        });

        tb.addEventListener('click', function (e) {
            var btn = e.target.closest('button[data-cmd]');
            if (!btn) { return; }
            e.preventDefault();
            run(btn.getAttribute('data-cmd'), btn.getAttribute('data-val'));
        });

        // Menus déroulants (police, taille) : on rétablit la sélection d'abord.
        tb.querySelectorAll('select.rte-select').forEach(function (sel) {
            sel.addEventListener('change', function () {
                restoreSel();
                run(sel.getAttribute('data-cmd'), sel.value);
            });
        });
    })();
    </script>

    <script>
    // Gestion UNIFIÉE des photos de l'article :
    //  - une seule liste : couverture existante + galerie existante + nouvelles ;
    //  - une ÉTOILE ⭐ par photo pour désigner la principale (= couverture) ;
    //  - un × pour retirer ;
    //  - les ajouts se CUMULENT (plusieurs sélections s'additionnent).
    // L'état part au serveur via :  principal (jeton), remove_cover, delete_photos[].
    // Et il est exposé au module d'aperçu via window.__apvImages.getPreview().
    (function () {
        var galInput     = document.getElementById('photos');
        var grid         = document.getElementById('photoGrid');
        var fPrincipal   = document.getElementById('principalField');
        var fRemoveCover = document.getElementById('removeCoverField');
        var form         = document.getElementById('articleForm');

        // Données des images DÉJÀ enregistrées (édition).
        var EXISTING_COVER   = <?= json_encode(!empty($article['image']) ? url('uploads/articles/' . rawurlencode($article['image'])) : '') ?>;
        var EXISTING_GALLERY = <?= json_encode(array_map(function ($i) {
            return ['id' => (int) $i['id'], 'url' => url('uploads/articles/' . rawurlencode($i['filename']))];
        }, $images ?? [])) ?>;

        var galDT       = (typeof DataTransfer !== 'undefined') ? new DataTransfer() : null;
        var newURLs     = [];      // data URLs des nouvelles photos (parallèle à galDT.files → jeton "n<i>")
        var coverRemoved= false;   // la couverture existante a-t-elle été retirée ?
        var deletedIds  = {};      // ids de galerie existante retirés
        var principal   = '';      // jeton de la photo principale

        function readURL(file) {
            return new Promise(function (resolve) {
                var r = new FileReader();
                r.onload = function (e) { resolve(e.target.result); };
                r.readAsDataURL(file);
            });
        }
        function fkey(f) { return f.name + '|' + f.size + '|' + f.lastModified; }

        // Liste ordonnée des photos actuelles, avec leur jeton et leur source.
        function photos() {
            var list = [];
            if (EXISTING_COVER && !coverRemoved) { list.push({ token: 'cover', src: EXISTING_COVER }); }
            EXISTING_GALLERY.forEach(function (g) {
                if (!deletedIds[g.id]) { list.push({ token: 'g' + g.id, src: g.url }); }
            });
            if (galDT) {
                Array.prototype.forEach.call(galDT.files, function (f, i) {
                    list.push({ token: 'n' + i, src: newURLs[i] });
                });
            }
            return list;
        }

        // Garantit qu'une principale valide est choisie (défaut : couverture, sinon 1re).
        function ensurePrincipal() {
            var tokens = photos().map(function (p) { return p.token; });
            if (tokens.indexOf(principal) === -1) {
                principal = (tokens.indexOf('cover') !== -1) ? 'cover' : (tokens[0] || '');
            }
            if (fPrincipal)   { fPrincipal.value = principal; }
            if (fRemoveCover) { fRemoveCover.value = coverRemoved ? '1' : '0'; }
        }

        function render() {
            ensurePrincipal();
            if (!grid) { return; }
            grid.innerHTML = '';
            photos().forEach(function (p) {
                var main = (p.token === principal);
                var item = document.createElement('div');
                item.className = 'photo-item' + (main ? ' is-main' : '');
                item.innerHTML =
                    '<img alt="">' +
                    '<button type="button" class="star" title="Définir comme photo principale">' + (main ? '⭐' : '☆') + '</button>' +
                    '<button type="button" class="rm" title="Retirer cette photo">×</button>' +
                    (main ? '<span class="badge">Principale</span>' : '');
                item.querySelector('img').src = p.src;
                item.querySelector('.star').addEventListener('click', function () {
                    principal = p.token; render(); state.onChange();
                });
                item.querySelector('.rm').addEventListener('click', function () { removePhoto(p.token); });
                grid.appendChild(item);
            });
        }

        function syncInput() { if (galDT && galInput) { galInput.files = galDT.files; } }

        function removePhoto(token) {
            if (token === 'cover') {
                coverRemoved = true;
            } else if (token.charAt(0) === 'g') {
                var id = token.slice(1);
                deletedIds[id] = true;
                if (form) { // signale la suppression de cette photo existante au serveur
                    var h = document.createElement('input');
                    h.type = 'hidden'; h.name = 'delete_photos[]'; h.value = id;
                    form.appendChild(h);
                }
            } else if (token.charAt(0) === 'n' && galDT) {
                var idx = parseInt(token.slice(1), 10);
                var keep = new DataTransfer(); var urls = [];
                Array.prototype.forEach.call(galDT.files, function (f, j) {
                    if (j !== idx) { keep.items.add(f); urls.push(newURLs[j]); }
                });
                galDT = keep; newURLs = urls; syncInput();
                // Réaligne la principale si elle pointait une nouvelle photo déplacée.
                if (principal.charAt(0) === 'n') {
                    var pk = parseInt(principal.slice(1), 10);
                    if (pk === idx) { principal = ''; }
                    else if (pk > idx) { principal = 'n' + (pk - 1); }
                }
            }
            render(); state.onChange();
        }

        // Ajout cumulatif de nouvelles photos.
        if (galInput && galDT) {
            galInput.addEventListener('change', function () {
                var have = Array.prototype.map.call(galDT.files, fkey);
                var toRead = [];
                Array.prototype.slice.call(galInput.files).forEach(function (f) {
                    if (!/^image\//.test(f.type)) { return; }
                    if (have.indexOf(fkey(f)) !== -1) { return; } // doublon ignoré
                    galDT.items.add(f); toRead.push(f);
                });
                syncInput(); // l'input contient désormais TOUTES les photos cumulées
                Promise.all(toRead.map(readURL)).then(function (urls) {
                    newURLs = newURLs.concat(urls);
                    render(); state.onChange();
                });
            });
        }

        // Exposition pour l'aperçu : principale = couverture, le reste = galerie.
        var state = window.__apvImages = {
            onChange: function () {},
            getPreview: function () {
                var cover = '', gallery = [];
                photos().forEach(function (p) {
                    if (p.token === principal) { cover = p.src; }
                    else { gallery.push(p.src); }
                });
                return { coverSrc: cover, gallerySrcs: gallery };
            }
        };

        render();
    })();
    </script>

    <script>
    // Pièces jointes (documents) : cumul des sélections + suppression, et liste
    // déjà enregistrée (édition). Exposé à l'aperçu via window.__apvDocs.getList().
    (function () {
        var input = document.getElementById('docs');
        var list  = document.getElementById('docList');
        var form  = document.getElementById('articleForm');

        var EXISTING_DOCS = <?= json_encode(array_map(function ($f) {
            return [
                'id'   => (int) $f['id'],
                'name' => ($f['original'] ?? '') !== '' ? $f['original'] : $f['filename'],
                'ext'  => strtolower(pathinfo($f['filename'], PATHINFO_EXTENSION)),
                'size' => (int) ($f['size'] ?? 0),
                'url'  => url('uploads/articles/files/' . rawurlencode($f['filename'])),
            ];
        }, $files ?? [])) ?>;

        var ICONS = { pdf:'📕', doc:'📝', docx:'📝', odt:'📝', xls:'📊', xlsx:'📊', ods:'📊', csv:'📊',
                      ppt:'📑', pptx:'📑', odp:'📑', zip:'🗜️', txt:'📃' };
        function icon(e) { return ICONS[e] || '📎'; }
        function extOf(name) { var i = name.lastIndexOf('.'); return i >= 0 ? name.slice(i + 1).toLowerCase() : ''; }
        function humanSize(b) {
            var u = ['o','Ko','Mo','Go'], i = 0, n = b || 0;
            while (n >= 1024 && i < 3) { n /= 1024; i++; }
            return (i === 0 ? Math.round(n) : n.toFixed(1).replace('.', ',')) + ' ' + u[i];
        }

        var DT      = (typeof DataTransfer !== 'undefined') ? new DataTransfer() : null;
        var newDocs = [];   // parallèle à DT.files : {name,ext,size}
        var deleted = {};   // ids existants retirés

        function sync()  { if (DT && input) { input.files = DT.files; } }
        function fkey(f) { return f.name + '|' + f.size + '|' + f.lastModified; }

        // Liste unifiée (existants non supprimés + nouveaux) avec repères de retrait.
        function items() {
            var out = [];
            EXISTING_DOCS.forEach(function (d) {
                if (!deleted[d.id]) { out.push({ kind: 'old', id: d.id, name: d.name, ext: d.ext, size: d.size }); }
            });
            newDocs.forEach(function (d, i) { out.push({ kind: 'new', idx: i, name: d.name, ext: d.ext, size: d.size }); });
            return out;
        }

        function render() {
            if (!list) { return; }
            list.innerHTML = '';
            items().forEach(function (d) {
                var it = document.createElement('div');
                it.className = 'doc-item';
                it.innerHTML =
                    '<span class="d-ico">' + icon(d.ext) + '</span>' +
                    '<span class="d-info"><span class="d-name"></span><span class="d-meta"></span></span>' +
                    '<button type="button" class="d-rm" title="Retirer ce document">×</button>';
                it.querySelector('.d-name').textContent = d.name;
                it.querySelector('.d-meta').textContent = (d.ext ? d.ext.toUpperCase() : '') + (d.size ? ' · ' + humanSize(d.size) : '');
                it.querySelector('.d-rm').addEventListener('click', function () { remove(d); });
                list.appendChild(it);
            });
        }

        function remove(d) {
            if (d.kind === 'old') {
                deleted[d.id] = true;
                if (form) {
                    var h = document.createElement('input');
                    h.type = 'hidden'; h.name = 'delete_files[]'; h.value = d.id;
                    form.appendChild(h);
                }
            } else if (DT) {
                var keep = new DataTransfer(), nd = [];
                Array.prototype.forEach.call(DT.files, function (f, j) {
                    if (j !== d.idx) { keep.items.add(f); nd.push(newDocs[j]); }
                });
                DT = keep; newDocs = nd; sync();
            }
            render(); state.onChange();
        }

        if (input && DT) {
            input.addEventListener('change', function () {
                var have = Array.prototype.map.call(DT.files, fkey);
                Array.prototype.slice.call(input.files).forEach(function (f) {
                    if (have.indexOf(fkey(f)) !== -1) { return; } // doublon ignoré
                    DT.items.add(f);
                    newDocs.push({ name: f.name, ext: extOf(f.name), size: f.size });
                });
                sync(); render(); state.onChange();
            });
        }

        // Exposé à l'aperçu : liste des documents (existants gardés + nouveaux).
        var state = window.__apvDocs = {
            onChange: function () {},
            getList: function () {
                var out = [];
                EXISTING_DOCS.forEach(function (d) {
                    if (!deleted[d.id]) { out.push({ name: d.name, ext: d.ext, size: d.size, url: d.url }); }
                });
                newDocs.forEach(function (d) { out.push({ name: d.name, ext: d.ext, size: d.size, url: '#' }); });
                return out;
            }
        };

        render();
    })();
    </script>

    <script>
    // Aperçu en temps réel FIDÈLE : on demande au serveur de rendre l'article
    // avec le vrai gabarit (mêmes styles + Html::clean), puis on l'affiche dans
    // un <iframe>. Les images choisies mais pas encore envoyées sont injectées
    // côté client après chargement (la structure, elle, vient du serveur).
    (function () {
        var frame = document.getElementById('apvFrame');
        if (!frame) { return; }

        var PREVIEW_URL = <?= json_encode(url('articles/preview')) ?>;
        var ARTICLE_ID  = <?= (int) ($article['id'] ?? 0) ?>;

        var title  = document.getElementById('title');
        var tpl    = document.getElementById('template');
        var editor = document.getElementById('rteEditor');
        var ta     = document.getElementById('content');
        var head   = document.getElementById('apvHead');
        var imgs   = window.__apvImages || { getPreview: function () { return { coverSrc: '', gallerySrcs: [] }; }, onChange: function () {} };

        function contentHTML() {
            if (editor && !editor.hidden) { return editor.innerHTML; }
            return ta ? ta.value : '';
        }
        // La couverture de l'aperçu = la photo principale ⭐ ; le reste = galerie.
        function coverSrc()    { return imgs.getPreview().coverSrc; }
        function gallerySrcs() { return imgs.getPreview().gallerySrcs; }

        // Injecte les vraies images dans le document de l'iframe après rendu.
        function inject() {
            var doc = frame.contentDocument;
            if (!doc) { return; }
            var cs = coverSrc();
            if (cs) {
                var mag = doc.querySelector('.mag-hero');
                if (mag) { mag.style.backgroundImage = "url('" + cs + "')"; }
                var heroImg = doc.querySelector('.hero, .split-img img');
                if (heroImg) { heroImg.src = cs; }
            }
            var gsrc = gallerySrcs();
            // La galerie est un carrousel : le jeu d'images est rendu en DOUBLE
            // dans la piste. On remplit tous les <a> via modulo => les doublons
            // reçoivent la même image que l'original (boucle cohérente).
            if (gsrc.length) {
                doc.querySelectorAll('.gallery-marquee a').forEach(function (a, i) {
                    var src = gsrc[i % gsrc.length];
                    a.setAttribute('href', src);
                    var im = a.querySelector('img');
                    if (im) { im.src = src; }
                });
            }
            // Modes carrousel/diaporama : les diapositives = couverture + galerie, dans l'ordre.
            var slidesSrc = [];
            if (cs) { slidesSrc.push(cs); }
            gsrc.forEach(function (s) { slidesSrc.push(s); });
            doc.querySelectorAll('.carousel .cslide img').forEach(function (im, i) {
                if (slidesSrc[i]) { im.src = slidesSrc[i]; }
            });
            resize();
            // Réajuste la hauteur une fois les images chargées (layout tardif).
            setTimeout(resize, 250);
        }
        function resize() {
            try {
                var doc = frame.contentDocument;
                var h = doc && doc.body ? doc.body.scrollHeight : 0;
                frame.style.height = Math.max(420, h) + 'px';
            } catch (e) {}
        }

        function render() {
            var data = new URLSearchParams();
            data.set('id', ARTICLE_ID);
            data.set('title', (title && title.value) || '');
            data.set('content', contentHTML());
            data.set('template', tpl ? tpl.value : 'standard');
            data.set('has_cover', coverSrc() ? '1' : '0');
            data.set('gallery_count', String(gallerySrcs().length));
            data.set('docs', JSON.stringify(window.__apvDocs ? window.__apvDocs.getList() : []));

            if (head) { head.classList.add('loading'); }
            fetch(PREVIEW_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
                body: data.toString(),
                credentials: 'same-origin'
            })
            .then(function (r) { return r.text(); })
            .then(function (html) {
                frame.onload = function () { inject(); if (head) { head.classList.remove('loading'); } };
                frame.srcdoc = html;
            })
            .catch(function () { if (head) { head.classList.remove('loading'); } });
        }

        // Anti-rebond : on attend une courte pause après la dernière frappe.
        var timer = null;
        function schedule() { clearTimeout(timer); timer = setTimeout(render, 300); }

        if (title)  { title.addEventListener('input', schedule); }
        if (tpl)    { tpl.addEventListener('change', schedule); }
        if (editor) { editor.addEventListener('input', schedule); editor.addEventListener('keyup', schedule); }
        if (ta)     { ta.addEventListener('input', schedule); }
        imgs.onChange = schedule; // photos / principale modifiées → rafraîchit l'aperçu
        if (window.__apvDocs) { window.__apvDocs.onChange = schedule; } // documents modifiés

        render(); // premier rendu
    })();
    </script>
    <script>
    // Libellé dynamique de l'interrupteur « Annonce »
    (function () {
        var chk = document.getElementById('annChk'), txt = document.getElementById('annText');
        if (!chk || !txt) { return; }
        function sync() {
            txt.innerHTML = chk.checked
                ? '📣 <b>Annonce activée</b> <small>Cet article est mis en avant sur la page d\'accueil de tout le monde.</small>'
                : '📰 <b>Article normal</b> <small>Active pour le mettre en avant sur l\'accueil (annonce).</small>';
        }
        chk.addEventListener('change', sync);
        sync();
    })();
    </script>

    <script>
    // Bouton toujours visible. Validation INLINE élégante au clic : on surligne le
    // champ manquant (qui tremble), on affiche un message en fondu et on y défile —
    // pas d'alerte système, et l'erreur disparaît dès qu'on corrige.
    (function () {
        var form   = document.getElementById('articleForm');
        var title  = document.getElementById('title');
        var editor = document.getElementById('rteEditor');
        var ta      = document.getElementById('content');
        var msg    = document.getElementById('formMsg');
        if (!form || !title) { return; }

        function contentEl() { return (editor && !editor.hidden) ? editor : ta; }
        function contentText() {
            if (editor && !editor.hidden) { return (editor.textContent || '').trim(); }
            var tmp = document.createElement('div');
            tmp.innerHTML = (ta && ta.value) ? ta.value : '';
            return (tmp.textContent || '').trim();
        }
        function clearErr(el) { if (el) { el.classList.remove('field-error'); } }
        function hideMsg() { if (msg) { msg.classList.remove('show'); } }

        // L'erreur d'un champ disparaît dès qu'on le corrige.
        title.addEventListener('input', function () { clearErr(title); if (title.value.trim() && contentText()) { hideMsg(); } });
        [editor, ta].forEach(function (el) {
            if (!el) { return; }
            ['input', 'keyup'].forEach(function (ev) {
                el.addEventListener(ev, function () { clearErr(contentEl()); if (title.value.trim() && contentText()) { hideMsg(); } });
            });
        });

        form.addEventListener('submit', function (e) {
            var missing = [];
            clearErr(title); clearErr(contentEl());
            if (title.value.trim() === '') { title.classList.add('field-error'); missing.push('le titre'); }
            if (contentText() === '')      { contentEl().classList.add('field-error'); missing.push('le contenu'); }
            if (!missing.length) { return; }

            e.preventDefault();
            if (msg) {
                msg.innerHTML = '<span style="font-size:16px">✍️</span> Il manque ' + missing.join(' et ') + ' pour ' +
                    (<?= $article ? 'true' : 'false' ?> ? 'enregistrer' : 'publier') + ' ton article.';
                msg.classList.add('show');
            }
            var first = form.querySelector('.field-error');
            if (first) {
                first.scrollIntoView({ behavior: 'smooth', block: 'center' });
                first.classList.add('shake');
                setTimeout(function () { first.classList.remove('shake'); }, 500);
                try { first.focus({ preventScroll: true }); } catch (e2) { first.focus(); }
            }
        });
    })();
    </script>
</body>
</html>
