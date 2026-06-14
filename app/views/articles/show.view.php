<?php
$tpl    = ArticleTemplate::key($article['template'] ?? 'standard');
$imgUrl = !empty($article['image']) ? url('uploads/articles/' . rawurlencode($article['image'])) : '';
// Métadonnées (date + auteur), réutilisées par tous les modèles.
ob_start(); ?>Publié le <?= date('d/m/Y', strtotime($article['created_at'])) ?><?php if (!empty($article['author_name'])): ?> · par <?= htmlspecialchars($article['author_name']) ?><?php endif; ?><?php $metaHtml = trim(ob_get_clean()); ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($article['title']) ?> — <?= htmlspecialchars(Settings::get('main_title', 'RPN')) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <?= Theme::css() ?>
    <?php include __DIR__ . '/_article.css.php'; ?>
    <style>
        .report-form { display:inline; margin:0; }
        .report-btn { font:inherit; font-size:13px; font-weight:600; cursor:pointer; color:var(--muted);
            background:none; border:1px solid var(--card-border); border-radius:10px; padding:7px 13px; }
        .report-btn:hover { border-color:var(--rouge,#e63946); color:var(--rouge,#e63946); }
        .reported { font-size:13px; font-weight:600; color:var(--rouge,#e63946);
            border:1px solid var(--rouge,#e63946); border-radius:10px; padding:7px 13px; }
        .flag-banner { display:flex; align-items:center; gap:12px; flex-wrap:wrap; margin:0 0 16px;
            padding:12px 16px; border-radius:12px; font-size:14px;
            background:rgba(244,193,75,.12); border:1px solid var(--accent,#f4c14b); }
        .flag-banner.hidden-now { background:rgba(230,57,70,.12); border-color:var(--rouge,#e63946); }
        .flag-banner form { margin:0; }
        .flag-banner button { font:inherit; font-size:13px; font-weight:700; cursor:pointer; border:none;
            border-radius:9px; padding:7px 14px; background:var(--accent,#f4c14b); color:var(--accent-ink,#14110f); }
        /* Questionnaires associés (fin de lecture) */
        .article-quiz { margin:28px 0; padding:24px 26px; border-radius:18px;
            border:1px solid var(--accent,#f4c14b); background:rgba(244,193,75,.07); box-shadow:var(--card-shadow); }
        .aq-title { font-size:19px; color:var(--accent,#f4c14b); margin-bottom:4px; }
        .aq-sub { font-size:13.5px; color:var(--muted); margin-bottom:16px; }
        .aq-list { display:flex; flex-direction:column; gap:12px; }
        .aq-item { display:flex; align-items:center; gap:14px; text-decoration:none; color:inherit;
            padding:14px 16px; border-radius:14px; border:1px solid var(--card-border); background:var(--card-bg);
            transition:border-color .15s, transform .15s; }
        .aq-item:hover { border-color:var(--accent,#f4c14b); transform:translateY(-2px); }
        .aq-ico { width:48px; height:48px; flex:0 0 48px; border-radius:12px; display:flex; align-items:center;
            justify-content:center; font-size:20px; background:rgba(127,127,127,.12); color:var(--accent,#f4c14b);
            object-fit:cover; overflow:hidden; }
        img.aq-ico { object-fit:cover; }
        .aq-body { flex:1; min-width:0; display:flex; flex-direction:column; gap:3px; }
        .aq-name { font-weight:700; font-size:15.5px; }
        .aq-meta { font-size:12.5px; color:var(--muted); }
        .aq-cta { flex:0 0 auto; font-weight:700; font-size:13.5px; color:var(--accent-ink,#14110f);
            background:var(--accent,#f4c14b); border-radius:10px; padding:9px 14px; white-space:nowrap; }
        @media (max-width:560px) { .aq-item { flex-wrap:wrap; } .aq-cta { width:100%; text-align:center; } }
        /* Photo de l'auteur (rond, en haut à droite) */
        .art-author { display:inline-flex; align-items:center; gap:9px; margin-left:auto; }
        .art-author img { width:40px; height:40px; border-radius:50%; object-fit:cover;
            border:2px solid var(--accent); background:rgba(127,127,127,.15); }
        .art-author .aa-name { font-size:13px; font-weight:600; color:var(--muted); }
        @media (max-width:560px) { .art-author .aa-name { display:none; } }
        /* Liste des IP visiteuses (auteur/admin) */
        .viewer-iptitle { font-size:14px; font-weight:700; color:var(--accent); margin:16px 0 8px; }
        .ip-list { list-style:none; padding:0; margin:0 0 12px; display:flex; flex-wrap:wrap; gap:8px; }
        .ip-item { display:flex; align-items:center; gap:8px; background:rgba(127,127,127,.08);
            border:1px solid var(--card-border); border-radius:10px; padding:7px 12px; }
        .ip-addr { font-family:Consolas,Monaco,monospace; font-size:13px; color:var(--text); }
        .ip-date { font-size:11.5px; color:var(--muted); }
        /* Bouton « PDF » */
        .pdf-btn { cursor:pointer; font-family:inherit; font-size:13px; font-weight:700; color:var(--accent-ink);
            background:var(--accent); border:none; border-radius:10px; padding:8px 14px; }
        .pdf-btn:hover { filter:brightness(1.05); }
        /* Impression / « Enregistrer en PDF » : on ne garde que l'article (titre, méta, contenu, photos) */
        @media print {
            @page { margin:16mm 14mm; }
            body::before, .nav, .crumb, .subarticles, .article-quiz, .reviews, .discussion,
            .viewers, .flag-banner, .pdf-btn, .report-form, .reported, .manage { display:none !important; }
            body { background:#fff !important; color:#000 !important; padding:0 !important; }
            .wrap { max-width:none !important; }
            .art, .card { box-shadow:none !important; border:none !important; }
            a { color:#000 !important; text-decoration:none !important; }
            img { max-width:100% !important; }
        }
    </style>
    <?= math_assets() ?>
</head>
<body class="tpl-<?= $tpl ?>">
    <div class="wrap">
        <?php if (!empty($notice)): ?>
            <div style="background:rgba(42,157,74,.15); border:1px solid var(--vert,#2a9d4a); color:var(--text);
                        padding:12px 16px; border-radius:12px; margin-bottom:16px; font-size:14px;">
                <?= htmlspecialchars($notice) ?>
            </div>
        <?php endif; ?>
        <div class="nav">
            <a href="<?= url('articles') ?>">← Tous les articles</a>
            <span class="art-author" title="Créé par <?= htmlspecialchars($authorName ?? 'Auteur') ?>">
                <img src="<?= htmlspecialchars($authorAvatar ?? '') ?>" alt="" referrerpolicy="no-referrer">
                <span class="aa-name"><?= htmlspecialchars($authorName ?? 'Auteur') ?></span>
            </span>
            <span class="views" title="Nombre de visiteurs uniques (comptés par adresse IP)">👁️ <?= number_format((int) ($views ?? 0), 0, ',', ' ') ?> vue<?= (int) ($views ?? 0) > 1 ? 's' : '' ?></span>
            <button type="button" class="pdf-btn" onclick="window.print()" title="Télécharger cet article en PDF">📄 PDF</button>
            <?php if (!empty($canManage)): ?>
                <span class="manage">
                    <span class="status <?= (int) $article['active'] === 1 ? 'live' : 'draft' ?>">
                        <?= (int) $article['active'] === 1 ? '● Visible par tous' : '○ Brouillon (privé)' ?>
                    </span>
                    <form method="post" action="<?= url('articles/toggle') ?>">
                        <input type="hidden" name="id" value="<?= (int) $article['id'] ?>">
                        <?php if ((int) $article['active'] === 1): ?>
                            <button class="pub off" type="submit" title="Masquer : repasser en brouillon privé">🙈 Mettre en brouillon</button>
                        <?php else: ?>
                            <button class="pub on" type="submit" title="Publier : rendre visible par tout le monde">👁️ Publier</button>
                        <?php endif; ?>
                    </form>
                    <a class="edit" href="<?= url('articles/edit') ?>?id=<?= (int) $article['id'] ?>">✏️ Modifier</a>
                    <form method="post" action="<?= url('articles/delete') ?>"
                          onsubmit="return confirm('Supprimer définitivement cet article ?');">
                        <input type="hidden" name="id" value="<?= (int) $article['id'] ?>">
                        <button class="del" type="submit">🗑️ Supprimer</button>
                    </form>
                </span>
            <?php elseif (!empty($user)): ?>
                <?php if (!empty($iFlagged)): ?>
                    <span class="reported" title="Tu as déjà signalé cet article">🚩 Signalé</span>
                <?php else: ?>
                    <form class="report-form" method="post" action="<?= url('articles/report') ?>"
                          onsubmit="return confirm('Signaler cet article comme inapproprié ?');">
                        <input type="hidden" name="id" value="<?= (int) $article['id'] ?>">
                        <button class="report-btn" type="submit" title="Signaler cet article">🚩 Signaler</button>
                    </form>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <?php if (!empty($canManage) && !empty($flagCount)): ?>
            <div class="flag-banner <?= !empty($flagHidden) ? 'hidden-now' : '' ?>">
                <span>🚩 Cet article a reçu <b><?= (int) $flagCount ?></b> signalement<?= (int) $flagCount > 1 ? 's' : '' ?>.
                    <?php if (!empty($flagHidden)): ?>
                        Il est <b>masqué au public</b> (visible uniquement par toi et les admins).
                    <?php elseif ((int) $flagCount >= (int) $flagLimit): ?>
                        Protégé ou en annonce : il <b>reste visible</b> malgré les signalements.
                    <?php else: ?>
                        Masqué automatiquement à <?= (int) $flagLimit ?> signalements.
                    <?php endif; ?>
                </span>
                <?php if (($user['role'] ?? '') === 'admin'): ?>
                    <form method="post" action="<?= url('articles/clear_flags') ?>">
                        <input type="hidden" name="id" value="<?= (int) $article['id'] ?>">
                        <button type="submit">♻️ Réinitialiser</button>
                    </form>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($parent)): ?>
            <div class="crumb">↳ Sous-article de <a href="<?= url('article') ?>?id=<?= (int) $parent['id'] ?>"><?= htmlspecialchars($parent['title']) ?></a></div>
        <?php endif; ?>

        <?php include __DIR__ . '/_card.php'; ?>

        <?php if (!empty($children) || !empty($canManage)): ?>
            <section class="subarticles">
                <div class="sub-head">
                    <h2 class="sub-title">📂 Sous-articles<?= !empty($children) ? ' (' . count($children) . ')' : '' ?></h2>
                    <?php if (!empty($canManage)): ?>
                        <a class="sub-add" href="<?= url('articles/new') ?>?parent=<?= (int) $article['id'] ?>">＋ Ajouter un sous-article</a>
                    <?php endif; ?>
                </div>
                <?php if (!empty($children)): ?>
                    <div class="sub-list">
                        <?php foreach ($children as $c): ?>
                            <a class="sub-item" href="<?= url('article') ?>?id=<?= (int) $c['id'] ?>">
                                <?php if (!empty($c['image'])): ?>
                                    <img src="<?= url('uploads/articles/' . rawurlencode($c['image'])) ?>" alt="">
                                <?php else: ?>
                                    <span class="sub-ico">📄</span>
                                <?php endif; ?>
                                <span class="sub-info">
                                    <span class="sub-name"><?= htmlspecialchars($c['title']) ?><?php if ((int) $c['active'] !== 1): ?> <em>(brouillon)</em><?php endif; ?></span>
                                    <span class="sub-date"><?= date('d/m/Y', strtotime($c['created_at'])) ?></span>
                                </span>
                                <span class="sub-arrow">→</span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="sub-empty">Aucun sous-article pour l'instant.</p>
                <?php endif; ?>
            </section>
        <?php endif; ?>

        <?php if (!in_array($tpl, ArticleTemplate::carouselKeys(), true)): // les modes carrousel intègrent déjà les photos ?>
            <?php $galleryStyle = Article::galleryStyleKey($article['gallery_style'] ?? 'auto'); ?>
            <?php include __DIR__ . '/_gallery.php'; ?>
        <?php endif; ?>

        <?php
            // Pièces jointes (documents) — normalisées pour le partial partagé.
            $files = array_map(function ($f) {
                return [
                    'url'  => url('uploads/articles/files/' . rawurlencode($f['filename'])),
                    'name' => ($f['original'] ?? '') !== '' ? $f['original'] : $f['filename'],
                    'ext'  => strtolower(pathinfo($f['filename'], PATHINFO_EXTENSION)),
                    'size' => (int) ($f['size'] ?? 0),
                ];
            }, $files ?? []);
            include __DIR__ . '/_files.php';
        ?>

        <?php $artTags = Article::tagsToList($article['tags'] ?? ''); ?>
        <?php if ($artTags): ?>
            <div class="article-tags">
                <?php foreach ($artTags as $t): ?>
                    <a class="art-tag" href="<?= url('articles/search') ?>?tag=<?= rawurlencode($t) ?>">#<?= htmlspecialchars($t) ?></a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($articleQuizzes)): ?>
        <section class="article-quiz" id="quiz">
            <h2 class="aq-title">📝 Teste tes connaissances</h2>
            <p class="aq-sub">Tu as terminé la lecture ? Réponds <?= count($articleQuizzes) > 1 ? 'aux questionnaires' : 'au questionnaire' ?> ci-dessous.</p>
            <div class="aq-list">
                <?php foreach ($articleQuizzes as $qz): $resp = $qz['myResponse'] ?? null; ?>
                    <a class="aq-item" href="<?= url('quiz/show') ?>?id=<?= (int) $qz['id'] ?>">
                        <?php if (!empty($qz['image'])): ?>
                            <img class="aq-ico" src="<?= url('uploads/quizzes/' . rawurlencode($qz['image'])) ?>" alt="">
                        <?php else: ?>
                            <span class="aq-ico">❓</span>
                        <?php endif; ?>
                        <span class="aq-body">
                            <span class="aq-name"><?= htmlspecialchars($qz['title']) ?><?php if ((int) ($qz['active'] ?? 1) !== 1): ?> <em style="color:var(--rouge,#e63946);font-style:normal;">(brouillon — à publier)</em><?php endif; ?></span>
                            <span class="aq-meta">
                                <?= (int) $qz['questionCount'] ?> question<?= (int) $qz['questionCount'] > 1 ? 's' : '' ?>
                                <?php if ($resp): ?> · ✅ ton score : <b><?= (int) $resp['score'] ?>/<?= (int) $resp['total'] ?></b> (<?= (int) $resp['total'] > 0 ? round((int) $resp['score'] / (int) $resp['total'] * 100) : 0 ?> %)<?php endif; ?>
                            </span>
                        </span>
                        <span class="aq-cta"><?= $resp ? 'Revoir →' : 'Commencer à répondre →' ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <section class="reviews" id="avis">
            <div class="reviews-head">
                <h2 class="reviews-title">⭐ Avis des membres</h2>
                <?= rating_stars($reviewSummary['avg'], $reviewSummary['count']) ?>
            </div>

            <?php if (!empty($reviewError)): ?>
                <div class="review-err"><?= htmlspecialchars($reviewError) ?></div>
            <?php endif; ?>

            <?php if (!empty($user)): ?>
                <?php if ((int) ($article['author_id'] ?? 0) === (int) ($user['id'] ?? 0)): ?>
                    <p class="review-login">C'est ton article — tu ne peux pas le noter.</p>
                <?php else: ?>
                    <form class="review-form" method="post" action="<?= url('articles/review') ?>">
                        <input type="hidden" name="id" value="<?= (int) $article['id'] ?>">
                        <div class="rf-label"><?= $myReview ? 'Modifier ton avis' : 'Donne ton avis' ?></div>
                        <div class="stars-input">
                            <?php for ($s = 5; $s >= 1; $s--): ?>
                                <input type="radio" id="rv_<?= $s ?>" name="stars" value="<?= $s ?>" <?= (int) ($myReview['stars'] ?? 0) === $s ? 'checked' : '' ?>>
                                <label for="rv_<?= $s ?>" title="<?= $s ?>/5">★</label>
                            <?php endfor; ?>
                        </div>
                        <textarea name="comment" placeholder="Ton commentaire (facultatif)…"><?= htmlspecialchars($myReview['comment'] ?? '') ?></textarea>
                        <button type="submit"><?= $myReview ? 'Mettre à jour mon avis' : 'Publier mon avis' ?></button>
                    </form>
                <?php endif; ?>
            <?php else: ?>
                <p class="review-login"><a href="<?= url('') ?>">Connecte-toi</a> pour noter et commenter cet article.</p>
            <?php endif; ?>

            <?php if (!empty($reviews)): ?>
                <div class="review-list">
                    <?php foreach ($reviews as $rv): ?>
                        <article class="review">
                            <div class="review-top">
                                <span class="review-author">👤 <?= htmlspecialchars($rv['user_name'] ?: 'Membre') ?></span>
                                <span class="review-stars" title="<?= (int) $rv['stars'] ?>/5"><?= str_repeat('★', (int) $rv['stars']) . str_repeat('☆', 5 - (int) $rv['stars']) ?></span>
                            </div>
                            <?php if (trim((string) $rv['comment']) !== ''): ?>
                                <div class="review-text"><?= htmlspecialchars($rv['comment']) ?></div>
                            <?php endif; ?>
                            <div class="review-date"><?= date('d/m/Y', strtotime($rv['created_at'])) ?><?= !empty($rv['updated_at']) ? ' (modifié)' : '' ?></div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="review-login">Aucun avis pour l'instant. Sois le premier à donner ton avis !</p>
            <?php endif; ?>
        </section>

        <section class="discussion" id="discussion">
            <h2 class="reviews-title" style="margin-bottom:14px;">💬 Discussion (<?= count($comments) ?>)</h2>

            <?php if (!empty($comments)): ?>
                <div class="comment-list">
                    <?php foreach ($comments as $c): ?>
                        <?php
                            $canMod = Session::isAdmin()
                                || (!empty($user) && ((int) $c['user_id'] === (int) ($user['id'] ?? 0)
                                                     || (int) ($article['author_id'] ?? 0) === (int) ($user['id'] ?? 0)));
                        ?>
                        <article class="comment<?= ($canMod && (int) $c['flags'] > 0) ? ' flagged' : '' ?>">
                            <div class="comment-top">
                                <span class="comment-author">👤 <?= htmlspecialchars($c['user_name'] ?: 'Membre') ?></span>
                                <span class="comment-date"><?= date('d/m/Y H:i', strtotime($c['created_at'])) ?></span>
                            </div>
                            <div class="comment-body"><?= htmlspecialchars($c['body']) ?></div>
                            <div class="comment-actions">
                                <?php if ($canMod && (int) $c['flags'] > 0): ?>
                                    <span class="comment-flag-badge">⚠ Signalé (<?= (int) $c['flags'] ?>)</span>
                                <?php endif; ?>
                                <?php if (!empty($user) && (int) $c['user_id'] !== (int) ($user['id'] ?? 0)): ?>
                                    <?php if (!empty($c['flagged_by_me'])): ?>
                                        <span class="comment-flag done">✓ Signalé</span>
                                    <?php else: ?>
                                        <form method="post" action="<?= url('articles/comment_report') ?>">
                                            <input type="hidden" name="comment_id" value="<?= (int) $c['id'] ?>">
                                            <button class="comment-flag" type="submit">⚐ Signaler</button>
                                        </form>
                                    <?php endif; ?>
                                <?php endif; ?>
                                <?php if ($canMod): ?>
                                    <form method="post" action="<?= url('articles/comment_delete') ?>" onsubmit="return confirm('Supprimer ce message ?');">
                                        <input type="hidden" name="comment_id" value="<?= (int) $c['id'] ?>">
                                        <button class="comment-del" type="submit">Supprimer</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="review-login">Aucun message pour l'instant. Lance la discussion !</p>
            <?php endif; ?>

            <?php if (!empty($user)): ?>
                <form class="comment-form" method="post" action="<?= url('articles/comment') ?>">
                    <input type="hidden" name="id" value="<?= (int) $article['id'] ?>">
                    <textarea name="body" placeholder="Écris un message…" required></textarea>
                    <button type="submit">Envoyer</button>
                </form>
            <?php else: ?>
                <p class="review-login"><a href="<?= url('') ?>">Connecte-toi</a> pour participer à la discussion.</p>
            <?php endif; ?>
        </section>

        <?php if (!empty($canManage)): ?>
            <section class="viewers" id="vues">
                <details<?= !empty($viewers) ? ' open' : '' ?>>
                    <summary>👁️ Membres ayant vu l'article (<?= count($viewers ?? []) ?>)</summary>
                    <?php if (!empty($viewers)): ?>
                        <ul class="viewer-list">
                            <?php foreach ($viewers as $v): ?>
                                <li class="viewer">
                                    <?php if (!empty($v['picture'])): ?>
                                        <img class="viewer-pic" src="<?= htmlspecialchars(avatar_url($v['picture'], $v['name'] ?? '')) ?>" alt="" referrerpolicy="no-referrer">
                                    <?php else: ?>
                                        <span class="viewer-pic ph">👤</span>
                                    <?php endif; ?>
                                    <span class="viewer-info">
                                        <span class="viewer-name">
                                            <?= htmlspecialchars($v['name'] ?: ($v['email'] ?? 'Membre')) ?>
                                            <?php if (($v['role'] ?? '') === 'admin'): ?><em class="viewer-badge">admin</em><?php endif; ?>
                                        </span>
                                        <span class="viewer-date">Vu le <?= date('d/m/Y à H:i', strtotime($v['last_view'] ?: $v['first_view'])) ?></span>
                                    </span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="viewer-empty">Aucun membre inscrit n'a encore consulté cet article.</p>
                    <?php endif; ?>

                    <?php if (!empty($viewerIps)): ?>
                        <p class="viewer-iptitle">🌐 Visiteurs par adresse IP (<?= count($viewerIps) ?>) — utile pour les <b>non-membres</b></p>
                        <ul class="ip-list">
                            <?php foreach ($viewerIps as $row): ?>
                                <li class="ip-item">
                                    <code class="ip-addr"><?= htmlspecialchars($row['ip'] ?: '—') ?></code>
                                    <span class="ip-date"><?= date('d/m/Y à H:i', strtotime($row['created_at'])) ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>

                    <p class="viewer-note">Seul·e toi (auteur) et les administrateurs voyez ces listes. Le total <strong><?= number_format((int) ($views ?? 0), 0, ',', ' ') ?></strong> en haut compte tous les visiteurs par adresse IP, connectés ou non. Les visiteurs <b>non connectés</b> n'apparaissent que par leur <b>adresse IP</b> ci-dessus.</p>
                </details>
            </section>
        <?php endif; ?>
    </div>
</body>
</html>
