<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RPN — Articles (admin)</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <?= Theme::css() ?>
    <style>
        * { box-sizing:border-box; margin:0; padding:0; }
        body {
            font-family:'Poppins',sans-serif; min-height:100vh; color:var(--text); padding:40px 20px;
            background: radial-gradient(circle at 90% 0%, var(--glow2), transparent 45%), var(--bg-base);
        }
        body::before { content:""; position:fixed; top:0; left:0; right:0; height:6px; background:var(--bar); }
        .wrap { max-width:1000px; margin:0 auto; }
        .top { display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; flex-wrap:wrap; gap:12px; }
        h1 { font-size:22px; } h1 span { color:var(--accent); }
        .nav { display:flex; gap:10px; flex-wrap:wrap; }
        .nav a { color:var(--text); text-decoration:none; font-size:14px; padding:8px 16px; border-radius:10px; border:1px solid var(--card-border); }
        .nav a:hover { border-color:var(--accent); color:var(--accent); }
        .nav a.add { background:var(--accent); color:var(--accent-ink); border-color:var(--accent); font-weight:700; }
        .saved { background:rgba(42,157,74,.15); border:1px solid rgba(42,157,74,.45); color:#2a9d4a;
            padding:11px 14px; border-radius:10px; margin-bottom:20px; font-size:14px; }
        .table { width:100%; border-collapse:collapse; background:var(--card-bg); border-radius:14px; overflow:hidden; box-shadow:var(--card-shadow); }
        th,td { padding:13px 16px; text-align:left; font-size:14px; border-bottom:1px solid var(--card-border); vertical-align:middle; }
        th { color:var(--accent); background:rgba(127,127,127,.08); }
        .thumb { width:64px; height:42px; object-fit:cover; border-radius:8px; background:rgba(127,127,127,.15); display:block; }
        .thumb.empty { display:flex; align-items:center; justify-content:center; font-size:18px; color:var(--muted); }
        .actions { display:flex; gap:8px; flex-wrap:wrap; }
        .actions a, .actions button { border:none; cursor:pointer; padding:7px 12px; border-radius:8px; font-family:inherit;
            font-size:12px; font-weight:600; color:#fff; text-decoration:none; }
        .subtag { font-size:11px; color:var(--muted); }
        .badge { font-size:12px; font-weight:600; padding:4px 10px; border-radius:999px; white-space:nowrap; }
        .badge.pub { background:rgba(42,157,74,.18); color:#2a9d4a; border:1px solid rgba(42,157,74,.45); }
        .badge.draft { background:rgba(127,127,127,.15); color:var(--muted); border:1px solid var(--card-border); }
        .b-view { background:#0a9bdc; }
        .b-edit { background:#6d28d9; }
        .b-del  { background:var(--rouge); }
        .b-prot { background:#9a7b15; }
        .b-prot.on { background:var(--or, #f4c14b); color:#14110f; }
        .badge.prot { background:rgba(244,193,75,.20); color:#9a7b15; border:1px solid rgba(244,193,75,.5); }
        .b-ann { background:#0a7d4b; }
        .b-ann.on { background:#e63946; }
        .badge.annonce { background:rgba(10,125,75,.18); color:#0a7d4b; border:1px solid rgba(10,125,75,.45); }
        .actions a:hover, .actions button:hover { filter:brightness(1.1); }
        .empty { text-align:center; padding:40px; color:var(--muted); }
        .badge.flagged { background:rgba(230,57,70,.18); color:#e0566a; border:1px solid rgba(230,57,70,.5); }
        .badge.hiddenf { background:var(--rouge,#e63946); color:#fff; border:1px solid var(--rouge,#e63946); }
        .b-flag { background:#9a7b15; }
        /* Panneau « Articles signalés » */
        .report-panel { background:var(--card-bg); border:1px solid var(--rouge,#e63946); border-left:6px solid var(--rouge,#e63946);
            border-radius:14px; padding:18px 20px; margin-bottom:20px; box-shadow:var(--card-shadow); }
        .report-panel h2 { font-size:16px; color:var(--rouge,#e63946); margin-bottom:14px; }
        .rp-list { display:flex; flex-direction:column; gap:10px; }
        .rp-item { display:flex; align-items:center; gap:12px; flex-wrap:wrap; padding:10px 12px; border-radius:11px;
            border:1px solid var(--card-border); background:rgba(127,127,127,.04); }
        .rp-item.hidden { background:rgba(230,57,70,.08); border-color:rgba(230,57,70,.4); }
        .rp-count { flex:0 0 auto; min-width:34px; height:34px; border-radius:9px; display:flex; align-items:center;
            justify-content:center; font-weight:800; background:var(--rouge,#e63946); color:#fff; }
        .rp-title { flex:1; min-width:140px; font-weight:600; color:var(--text); text-decoration:none; }
        .rp-title:hover { color:var(--accent); }
        .rp-state { font-size:12.5px; color:var(--muted); }
        .rp-item form { margin:0; }
        .rp-item button { border:none; cursor:pointer; padding:8px 13px; border-radius:9px; font-family:inherit;
            font-size:12.5px; font-weight:700; color:var(--accent-ink); background:var(--accent); }
        .rp-item button:hover { filter:brightness(1.08); }

        /* Barre export / import */
        .flash { padding:11px 14px; border-radius:10px; margin-bottom:18px; font-size:14px; }
        .flash.ok  { background:rgba(42,157,74,.15); border:1px solid rgba(42,157,74,.45); color:#2a9d4a; }
        .flash.err { background:rgba(230,57,70,.15); border:1px solid rgba(230,57,70,.4); color:#e0566a; }
        .io-bar { display:flex; gap:12px; flex-wrap:wrap; align-items:center; margin-bottom:20px;
            padding:14px 16px; background:var(--card-bg); border:1px solid var(--card-border);
            border-radius:14px; box-shadow:var(--card-shadow); }
        .io-bar .io-title { font-size:13px; font-weight:700; color:var(--accent); margin-right:4px; }
        .io-btn { border:none; cursor:pointer; padding:9px 16px; border-radius:9px; font-family:inherit;
            font-size:13px; font-weight:600; text-decoration:none; }
        .io-btn.exp { background:#0a9bdc; color:#fff; }
        .io-btn.imp { background:var(--accent); color:var(--accent-ink); }
        .io-btn:hover { filter:brightness(1.08); }
        .io-form { display:flex; gap:8px; align-items:center; flex-wrap:wrap; }
        .io-form input[type=file] { font-size:13px; color:var(--muted); max-width:280px;
            background:rgba(127,127,127,.06); border:1px solid var(--card-border); border-radius:9px; padding:5px 8px; }
        /* Bouton natif « Choisir un fichier » : assorti au thème */
        .io-form input[type=file]::file-selector-button {
            font-family:inherit; font-size:13px; font-weight:600; cursor:pointer; margin-right:10px;
            border:1px solid var(--card-border); background:var(--card-bg); color:var(--text);
            border-radius:8px; padding:8px 14px; transition:border-color .15s, color .15s; }
        .io-form input[type=file]::file-selector-button:hover { border-color:var(--accent); color:var(--accent); }
        .io-form input[type=file]::-webkit-file-upload-button {
            font-family:inherit; font-size:13px; font-weight:600; cursor:pointer; margin-right:10px;
            border:1px solid var(--card-border); background:var(--card-bg); color:var(--text);
            border-radius:8px; padding:8px 14px; }
        .io-hint { font-size:11px; color:var(--muted); flex-basis:100%; margin-top:2px; }

        /* Réattribution d'auteur */
        .author-cell { min-width:210px; }
        .author-now { font-size:12px; color:var(--muted); margin-bottom:6px; }
        .author-now b { color:var(--text); font-weight:600; }
        .assign-form { display:flex; gap:6px; align-items:center; flex-wrap:wrap; }
        .assign-form select { padding:7px 9px; border-radius:8px; border:1px solid var(--card-border);
            background:rgba(127,127,127,.08); color:var(--text); font-family:inherit; font-size:12px; max-width:140px; }
        .assign-form select option { background:var(--bg-base); color:var(--text); }
        .assign-form button { border:none; cursor:pointer; padding:7px 11px; border-radius:8px; font-family:inherit;
            font-size:12px; font-weight:600; color:#fff; background:#6d28d9; }
        .assign-form button:hover { filter:brightness(1.1); }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="top">
            <h1>📰 <span>Articles</span> (<?= count($articles) ?>)</h1>
            <div class="nav">
                <a href="<?= url('admin/dashboard') ?>">← Tableau de bord</a>
                <a href="<?= url('admin/articles/style') ?>">🎨 Style des articles</a>
                <a class="add" href="<?= url('articles/new') ?>">＋ Nouvel article</a>
            </div>
        </div>

        <?php if (!empty($notice)): ?><div class="flash ok"><?= htmlspecialchars($notice) ?></div><?php endif; ?>
        <?php if (!empty($error)): ?><div class="flash err"><?= htmlspecialchars($error) ?></div><?php endif; ?>

        <?php
            $flagged = array_values(array_filter($articles, fn ($a) => (int) (($flags ?? [])[(int) $a['id']] ?? 0) > 0));
        ?>
        <?php if (!empty($flagged)): ?>
            <div class="report-panel">
                <h2>🚩 Articles signalés (<?= count($flagged) ?>)</h2>
                <div class="rp-list">
                    <?php foreach ($flagged as $a):
                        $cnt    = (int) ($flags[(int) $a['id']] ?? 0);
                        $exempt = (int) ($a['protected'] ?? 0) === 1 || (int) ($a['announcement'] ?? 0) === 1;
                        $hidden = !$exempt && $cnt >= (int) $flagLimit;
                    ?>
                        <div class="rp-item <?= $hidden ? 'hidden' : '' ?>">
                            <span class="rp-count"><?= $cnt ?></span>
                            <a class="rp-title" href="<?= url('article') ?>?id=<?= (int) $a['id'] ?>"><?= htmlspecialchars($a['title']) ?></a>
                            <span class="rp-state">
                                <?php if ($hidden): ?>🙈 <b>masqué au public</b>
                                <?php elseif ($exempt): ?>protégé/annonce : reste visible
                                <?php else: ?>visible (<?= $cnt ?>/<?= (int) $flagLimit ?> avant masquage)
                                <?php endif; ?>
                            </span>
                            <form method="post" action="<?= url('admin/articles/clear_flags') ?>">
                                <input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
                                <button type="submit">♻️ Réinitialiser</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="io-bar">
            <span class="io-title">📦 Sauvegarde</span>
            <a class="io-btn exp" href="<?= url('admin/articles/export') ?>">⬇️ Exporter tout (.zip)</a>
            <form class="io-form" method="post" action="<?= url('admin/articles/import') ?>" enctype="multipart/form-data">
                <input type="file" name="archive" accept=".zip,application/zip" required>
                <button class="io-btn imp" type="submit">⬆️ Importer</button>
            </form>
            <p class="io-hint">L'export inclut <b>tout le projet</b> : articles (texte, couverture, galerie, pièces jointes) <b>et</b> les questionnaires (questions, réponses, images) avec leurs associations aux articles.
                À l'import, les articles arrivent en <b>brouillon</b> et te sont <b>attribués</b> : réattribue-les ensuite à un utilisateur via la colonne « Auteur ».</p>
        </div>

        <table class="table">
            <tr><th>Image</th><th>Titre</th><th>Date</th><th>Statut</th><th>Auteur</th><th>Actions</th></tr>
            <?php if (empty($articles)): ?>
                <tr><td colspan="6" class="empty">Aucun article. Cliquez sur « Nouvel article » pour commencer.</td></tr>
            <?php endif; ?>
            <?php foreach ($articles as $a): ?>
                <tr>
                    <td>
                        <?php if (!empty($a['image'])): ?>
                            <img class="thumb" src="<?= url('uploads/articles/' . rawurlencode($a['image'])) ?>" alt="">
                        <?php else: ?>
                            <div class="thumb empty">📄</div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (!empty($a['parent_id'])): ?><span class="subtag">↳ sous-article</span><br><?php endif; ?>
                        <?= htmlspecialchars($a['title']) ?>
                    </td>
                    <td><?= date('d/m/Y', strtotime($a['created_at'])) ?></td>
                    <td>
                        <?php if ((int) $a['active'] === 1): ?>
                            <span class="badge pub">● Publié</span>
                        <?php else: ?>
                            <span class="badge draft">● Brouillon</span>
                        <?php endif; ?>
                        <?php if ((int) ($a['protected'] ?? 0) === 1): ?>
                            <br><span class="badge prot">🔒 Protégé</span>
                        <?php endif; ?>
                        <?php if ((int) ($a['announcement'] ?? 0) === 1): ?>
                            <br><span class="badge annonce">📣 Annonce</span>
                        <?php endif; ?>
                        <?php
                            $fcnt   = (int) (($flags ?? [])[(int) $a['id']] ?? 0);
                            $fexempt = (int) ($a['protected'] ?? 0) === 1 || (int) ($a['announcement'] ?? 0) === 1;
                            $fhidden = !$fexempt && $fcnt >= (int) $flagLimit;
                        ?>
                        <?php if ($fcnt > 0): ?>
                            <br><span class="badge <?= $fhidden ? 'hiddenf' : 'flagged' ?>">🚩 <?= $fcnt ?> signalement<?= $fcnt > 1 ? 's' : '' ?><?= $fhidden ? ' · masqué' : '' ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="author-cell">
                        <div class="author-now">👤 <b><?= htmlspecialchars($a['author_name'] !== '' ? $a['author_name'] : 'Non attribué') ?></b></div>
                        <form class="assign-form" method="post" action="<?= url('admin/articles/assign') ?>">
                            <input type="hidden" name="article_id" value="<?= (int) $a['id'] ?>">
                            <select name="user_id" required>
                                <option value="">— Choisir —</option>
                                <?php foreach ($members as $mb): ?>
                                    <option value="<?= (int) $mb['id'] ?>" <?= (int) $a['author_id'] === (int) $mb['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars(($mb['name'] ?: $mb['email']) ?: ('Membre #' . $mb['id'])) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" title="Attribuer cet article à l'utilisateur choisi">Attribuer</button>
                        </form>
                    </td>
                    <td>
                        <div class="actions">
                            <a class="b-view" href="<?= url('article') ?>?id=<?= (int) $a['id'] ?>">Voir</a>
                            <a class="b-edit" href="<?= url('articles/edit') ?>?id=<?= (int) $a['id'] ?>">Modifier</a>
                            <?php $isProt = (int) ($a['protected'] ?? 0) === 1; ?>
                            <form method="post" action="<?= url('admin/articles/protect') ?>">
                                <input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
                                <button class="b-prot<?= $isProt ? ' on' : '' ?>" type="submit" title="<?= $isProt ? 'Retirer la protection' : 'Protéger : conservé même en cas de tout effacer' ?>"><?= $isProt ? '🔓 Déprotéger' : '🔒 Protéger' ?></button>
                            </form>
                            <?php $isAnn = (int) ($a['announcement'] ?? 0) === 1; ?>
                            <form method="post" action="<?= url('admin/articles/announce') ?>">
                                <input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
                                <button class="b-ann<?= $isAnn ? ' on' : '' ?>" type="submit" title="<?= $isAnn ? 'Retirer de l\'accueil' : 'Mettre en avant sur la page d\'accueil de tous' ?>"><?= $isAnn ? '📣 Retirer annonce' : '📣 Annonce' ?></button>
                            </form>
                            <?php if ((int) (($flags ?? [])[(int) $a['id']] ?? 0) > 0): ?>
                                <form method="post" action="<?= url('admin/articles/clear_flags') ?>">
                                    <input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
                                    <button class="b-flag" type="submit" title="Réinitialiser les signalements (rétablit l'article)">♻️ Signalements</button>
                                </form>
                            <?php endif; ?>
                            <form method="post" action="<?= url('articles/delete') ?>"
                                  onsubmit="return confirm('Supprimer définitivement cet article ?');">
                                <input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
                                <button class="b-del" type="submit">Supprimer</button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
</body>
</html>
