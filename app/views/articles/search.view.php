<?php
/**
 * Recherche d'articles : barre de recherche + filtres par tag + résultats.
 * Variables : $user, $q, $tag, $results, $allTags, $reviews, $views.
 */
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(Settings::get('main_title', 'RPN')) ?> — Recherche d'articles</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <?= Theme::css() ?>
    <style>
        * { box-sizing:border-box; margin:0; padding:0; }
        body { font-family:'Poppins',sans-serif; min-height:100vh; color:var(--text); padding:40px 20px 80px;
            background: radial-gradient(circle at 10% 0%, var(--glow1), transparent 42%), var(--bg-base); }
        body::before { content:""; position:fixed; top:0; left:0; right:0; height:6px; background:var(--bar); }
        .wrap { max-width:1000px; margin:0 auto; }
        .top { display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; flex-wrap:wrap; gap:12px; }
        h1 { font-size:24px; } h1 span { color:var(--accent); }
        .back { color:var(--text); text-decoration:none; font-size:14px; padding:8px 16px; border-radius:10px; border:1px solid var(--card-border); }
        .back:hover { border-color:var(--accent); color:var(--accent); }
        .searchbar { display:flex; gap:10px; flex-wrap:wrap; margin-bottom:16px; }
        .searchbar input { flex:1; min-width:200px; padding:13px 16px; border-radius:12px; border:1px solid var(--card-border);
            background:rgba(127,127,127,.08); color:var(--text); font-family:inherit; font-size:15px; }
        .searchbar input:focus { outline:none; border-color:var(--accent); }
        .searchbar button { padding:13px 24px; border:none; border-radius:12px; cursor:pointer; font-family:inherit;
            font-weight:700; font-size:15px; background:var(--accent); color:var(--accent-ink); }
        .tags { display:flex; flex-wrap:wrap; gap:8px; margin-bottom:24px; }
        .tag-chip { font-size:13px; font-weight:600; text-decoration:none; color:var(--text); padding:7px 13px; border-radius:999px;
            border:1px solid var(--card-border); background:rgba(127,127,127,.05); transition:border-color .15s, color .15s; }
        .tag-chip:hover { border-color:var(--accent); color:var(--accent); }
        .tag-chip.active { background:var(--accent); color:var(--accent-ink); border-color:var(--accent); }
        .count { font-size:13px; color:var(--muted); margin-bottom:14px; }
        .grid { display:grid; gap:18px; grid-template-columns:repeat(auto-fill, minmax(240px,1fr)); }
        .card { display:flex; flex-direction:column; text-decoration:none; color:var(--text); background:var(--card-bg);
            border:1px solid var(--card-border); border-radius:18px; overflow:hidden; box-shadow:var(--card-shadow);
            transition:border-color .15s, transform .15s; }
        .card:hover { border-color:var(--accent); transform:translateY(-3px); }
        .cover { width:100%; height:140px; object-fit:cover; border-bottom:1px solid var(--card-border); }
        .cover.ph { display:flex; align-items:center; justify-content:center; font-size:40px; color:var(--accent); background:rgba(127,127,127,.10); }
        .body { padding:14px 16px; display:flex; flex-direction:column; gap:6px; flex:1; }
        .title { font-weight:700; font-size:15.5px; line-height:1.3; }
        .ex { font-size:13px; color:var(--muted); line-height:1.45;
            display:-webkit-box; -webkit-line-clamp:3; -webkit-box-orient:vertical; overflow:hidden; }
        .meta { margin-top:auto; font-size:11.5px; color:var(--muted); display:flex; align-items:center; gap:8px; flex-wrap:wrap; }
        .ctags { display:flex; flex-wrap:wrap; gap:5px; }
        .ct { font-size:11px; color:var(--accent); background:rgba(127,127,127,.10); border:1px solid var(--card-border); border-radius:999px; padding:1px 8px; }
        .empty { text-align:center; color:var(--muted); padding:40px; }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="top">
            <h1>🔎 <span>Recherche</span> d'articles</h1>
            <a class="back" href="<?= url('articles') ?>">← Tous les articles</a>
        </div>

        <form class="searchbar" method="get" action="<?= url('articles/search') ?>">
            <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Mot-clé, titre, contenu…" autofocus>
            <?php if ($tag !== ''): ?><input type="hidden" name="tag" value="<?= htmlspecialchars($tag) ?>"><?php endif; ?>
            <button type="submit">Rechercher</button>
        </form>

        <?php if (!empty($allTags)): ?>
            <div class="tags">
                <a class="tag-chip <?= $tag === '' ? 'active' : '' ?>" href="<?= url('articles/search') ?><?= $q !== '' ? '?q=' . rawurlencode($q) : '' ?>">Tous</a>
                <?php foreach ($allTags as $t): ?>
                    <a class="tag-chip <?= mb_strtolower($t) === mb_strtolower($tag) ? 'active' : '' ?>"
                       href="<?= url('articles/search') ?>?tag=<?= rawurlencode($t) ?><?= $q !== '' ? '&q=' . rawurlencode($q) : '' ?>">#<?= htmlspecialchars($t) ?></a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($q === '' && $tag === ''): ?>
            <p class="empty">Saisis un mot-clé ou choisis un tag pour lancer la recherche.</p>
        <?php elseif (empty($results)): ?>
            <p class="empty">Aucun article trouvé<?= $q !== '' ? ' pour « ' . htmlspecialchars($q) . ' »' : '' ?><?= $tag !== '' ? ' avec le tag #' . htmlspecialchars($tag) : '' ?>.</p>
        <?php else: ?>
            <p class="count"><?= count($results) ?> article<?= count($results) > 1 ? 's' : '' ?> trouvé<?= count($results) > 1 ? 's' : '' ?>.</p>
            <div class="grid">
                <?php foreach ($results as $a): ?>
                    <?php $excerpt = trim(mb_strimwidth(strip_tags((string) ($a['content'] ?? '')), 0, 140, '…')); ?>
                    <a class="card" href="<?= url('article') ?>?id=<?= (int) $a['id'] ?>">
                        <?php if (!empty($a['image'])): ?>
                            <img class="cover" src="<?= url('uploads/articles/' . rawurlencode($a['image'])) ?>" alt="">
                        <?php else: ?>
                            <span class="cover ph">📰</span>
                        <?php endif; ?>
                        <span class="body">
                            <span class="title"><?= htmlspecialchars($a['title']) ?></span>
                            <?php if ($excerpt !== ''): ?><span class="ex"><?= htmlspecialchars($excerpt) ?></span><?php endif; ?>
                            <?php $cts = Article::tagsToList($a['tags'] ?? ''); ?>
                            <?php if ($cts): ?>
                                <span class="ctags"><?php foreach (array_slice($cts, 0, 4) as $t): ?><span class="ct">#<?= htmlspecialchars($t) ?></span><?php endforeach; ?></span>
                            <?php endif; ?>
                            <span class="meta">
                                <?php if (!empty($a['author_name'])): ?><span>✍️ <?= htmlspecialchars($a['author_name']) ?></span><?php endif; ?>
                                <span><?= date('d/m/Y', strtotime($a['created_at'])) ?></span>
                            </span>
                        </span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
