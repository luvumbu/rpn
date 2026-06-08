<?php
/**
 * HUB ARTICLES — une seule identité « Articles » qui réunit l'écriture et la
 * lecture. On entre ici, PUIS on choisit (onglets) :
 *   🌍 Articles publics (créés par les AUTRES) · 🗂️ Mes articles ·
 *   🔎 Rechercher · ✍️ Écrire.
 * Variables : $user (peut être null), $articles, $reviews, $views.
 */
$uid     = (int) ($user['id'] ?? 0);
$isAdmin = ($user['role'] ?? '') === 'admin';

// Répartition : « Articles publics » = TOUS les articles publiés (y compris les
// miens, pour que je voie ma publication dans le fil public) ; « Mes articles »
// = tout ce que j'ai écrit (brouillons compris). Un article publié par moi
// apparaît donc dans les deux onglets — c'est voulu.
$mine = $others = [];
foreach ($articles as $a) {
    if ($uid && (int) $a['author_id'] === $uid) {
        $mine[] = $a;
    }
    if ((int) $a['active'] === 1) {
        $others[] = $a;
    }
}

// Rendu d'une carte d'article (réutilisé dans chaque onglet).
$renderCard = function ($a) use ($reviews, $views, $children) {
    $rs   = ($reviews ?? [])[(int) $a['id']] ?? null;
    $vc   = (int) (($views ?? [])[(int) $a['id']] ?? 0);
    $kids = ($children ?? [])[(int) $a['id']] ?? [];
    ob_start(); ?>
    <a class="card" href="<?= url('article') ?>?id=<?= (int) $a['id'] ?>"
       data-title="<?= htmlspecialchars(mb_strtolower($a['title'])) ?>">
        <?php if (!empty($a['image'])): ?>
            <img class="thumb" src="<?= url('uploads/articles/' . rawurlencode($a['image'])) ?>" alt="">
        <?php else: ?>
            <div class="thumb empty">📄</div>
        <?php endif; ?>
        <div class="body">
            <span class="date">
                <?= date('d/m/Y', strtotime($a['created_at'])) ?>
                <?php if ((int) $a['active'] !== 1): ?><span class="draft-tag">Brouillon</span><?php endif; ?>
            </span>
            <h2><?= htmlspecialchars($a['title']) ?></h2>
            <div class="card-rating"><?= rating_stars($rs['avg'] ?? 0, $rs['count'] ?? 0) ?></div>
            <span class="card-views">👁️ <?= number_format($vc, 0, ',', ' ') ?> vue<?= $vc > 1 ? 's' : '' ?></span>
            <?php if (!empty($a['access_password'])): ?>
                <p class="excerpt">🔒 <em>Contenu protégé par mot de passe.</em></p>
            <?php else: ?>
                <p class="excerpt"><?= htmlspecialchars(mb_strimwidth(strip_tags($a['content']), 0, 120, '…')) ?></p>
            <?php endif; ?>
            <?php if (!empty($kids)): ?>
                <div class="subs" title="<?= count($kids) ?> sous-article<?= count($kids) > 1 ? 's' : '' ?>">
                    <span class="subs-ico">🗂️ <?= count($kids) ?></span>
                    <?php foreach (array_slice($kids, 0, 8) as $k): ?>
                        <?php $kt = htmlspecialchars($k['title']) . ((int) $k['active'] !== 1 ? ' (brouillon)' : ''); ?>
                        <?php if (!empty($k['image'])): ?>
                            <img class="sub-sq<?= (int) $k['active'] !== 1 ? ' draft' : '' ?>"
                                 src="<?= url('uploads/articles/' . rawurlencode($k['image'])) ?>" alt="" title="<?= $kt ?>">
                        <?php else: ?>
                            <span class="sub-sq empty<?= (int) $k['active'] !== 1 ? ' draft' : '' ?>" title="<?= $kt ?>">📄</span>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    <?php if (count($kids) > 8): ?><span class="subs-more">+<?= count($kids) - 8 ?></span><?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </a>
    <?php return ob_get_clean();
};
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(Settings::get('main_title', 'RPN')) ?> — Articles</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <?= Theme::css() ?>
    <style>
        * { box-sizing:border-box; margin:0; padding:0; }
        body {
            font-family:'Poppins',sans-serif; min-height:100vh; color:var(--text); padding:40px 20px;
            background:
                radial-gradient(circle at 10% 0%, var(--glow1), transparent 40%),
                radial-gradient(circle at 90% 100%, var(--glow2), transparent 42%),
                var(--bg-base);
        }
        body::before { content:""; position:fixed; top:0; left:0; right:0; height:6px; background:var(--bar); }
        .wrap { max-width:1000px; margin:0 auto; }
        .top { display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; flex-wrap:wrap; gap:12px; }
        h1 { font-size:24px; } h1 span { color:var(--accent); }
        .nav { display:flex; gap:10px; flex-wrap:wrap; }
        .nav a { color:var(--text); text-decoration:none; font-size:14px; padding:8px 16px; border-radius:10px; border:1px solid var(--card-border); }
        .nav a:hover { border-color:var(--accent); color:var(--accent); }

        /* Onglets (la distinction écrire / lire / mes articles / rechercher) */
        .tabs { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:24px; }
        .tabs .tab { cursor:pointer; font-family:inherit; font-size:14px; font-weight:600; color:var(--text);
            background:var(--card-bg); border:1px solid var(--card-border); border-radius:12px; padding:11px 18px;
            transition:border-color .15s, color .15s, background .15s; }
        .tabs .tab:hover { border-color:var(--accent); }
        .tabs .tab.active { background:var(--accent); color:var(--accent-ink); border-color:var(--accent); }
        .tabs .tab .count { opacity:.85; font-weight:700; }
        .tab-panel { display:none; }
        .tab-panel.active { display:block; }

        .grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(280px, 1fr)); gap:22px; }
        .card { background:var(--card-bg); border:1px solid var(--card-border); border-radius:18px; overflow:hidden;
            box-shadow:var(--card-shadow); display:flex; flex-direction:column; transition:transform .15s; text-decoration:none; color:inherit; }
        .card:hover { transform:translateY(-4px); }
        .thumb { width:100%; aspect-ratio:16/9; object-fit:cover; background:rgba(127,127,127,.12); display:block; }
        .thumb.empty { display:flex; align-items:center; justify-content:center; font-size:40px; color:var(--accent); }
        .body { padding:18px 20px 22px; display:flex; flex-direction:column; gap:8px; }
        .body h2 { font-size:18px; color:var(--accent); line-height:1.3; }
        .date { font-size:12px; color:var(--muted); }
        .draft-tag { display:inline-block; margin-left:6px; padding:2px 8px; border-radius:999px; font-weight:600;
            background:rgba(127,127,127,.18); color:var(--muted); border:1px solid var(--card-border); }
        .excerpt { font-size:14px; color:var(--muted); line-height:1.55; }
        .card-rating { font-size:12px; }
        .card-rating .rstars { color:#f4b400; letter-spacing:1px; }
        .card-rating em { color:var(--muted); font-style:normal; }
        .card-rating .rating.none { color:var(--card-border); }
        .card-rating .rating.none em { color:var(--muted); }
        .card-views { font-size:12px; color:var(--muted); }
        /* Sous-articles : petits carrés représentés à l'intérieur de la carte */
        .subs { display:flex; align-items:center; flex-wrap:wrap; gap:5px; margin-top:8px;
            padding-top:8px; border-top:1px dashed var(--card-border); }
        .subs-ico { font-size:12px; font-weight:700; color:var(--accent); margin-right:4px; }
        .sub-sq { width:28px; height:28px; border-radius:7px; object-fit:cover; flex:0 0 auto;
            border:1px solid var(--card-border); display:inline-flex; align-items:center; justify-content:center;
            font-size:14px; background:rgba(127,127,127,.12); color:var(--accent); }
        .sub-sq.draft { opacity:.45; }
        .subs-more { font-size:12px; color:var(--muted); font-weight:700; margin-left:2px; }
        .empty-state { text-align:center; padding:60px 20px; color:var(--muted); background:var(--card-bg);
            border:1px solid var(--card-border); border-radius:18px; }

        /* Recherche */
        .search-bar { margin-bottom:20px; }
        .search-bar input { width:100%; padding:14px 16px; border-radius:12px; border:1px solid var(--card-border);
            background:rgba(127,127,127,.08); color:var(--text); font-family:inherit; font-size:15px; }
        .search-bar input:focus { outline:none; border-color:var(--accent); }
        .search-empty { display:none; }

        /* Onglet « Écrire » : grande invitation à créer */
        .write-cta { text-align:center; padding:50px 24px; background:var(--card-bg); border:1px solid var(--card-border);
            border-radius:18px; box-shadow:var(--card-shadow); }
        .write-cta .big { font-size:46px; margin-bottom:10px; }
        .write-cta h2 { font-size:20px; color:var(--accent); margin-bottom:8px; }
        .write-cta p { color:var(--muted); font-size:14px; margin-bottom:22px; line-height:1.6; }
        .btn-write { display:inline-block; text-decoration:none; font-weight:700; font-size:15px;
            color:var(--accent-ink); background:var(--accent); border-radius:12px; padding:14px 26px; }
        .btn-write:hover { filter:brightness(1.05); }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="top">
            <h1>📰 <span>Articles</span></h1>
            <div class="nav">
                <?php if (!empty($user)): ?>
                    <a href="<?= url('dashboard') ?>">← Tableau de bord</a>
                    <?php if ($isAdmin): ?>
                        <a href="<?= url('admin/articles') ?>">🛡️ Modération</a>
                    <?php endif; ?>
                <?php else: ?>
                    <a href="<?= url('') ?>">← Accueil</a>
                    <a href="<?= url('') ?>">🔑 Se connecter</a>
                <?php endif; ?>
            </div>
        </div>

        <!-- La distinction (lire / mes articles / rechercher / écrire) apparaît ICI,
             une fois entré dans l'identité « Articles ». -->
        <div class="tabs">
            <button type="button" class="tab active" data-tab="public">🌍 Articles publics <span class="count">(<?= count($others) ?>)</span></button>
            <?php if (!empty($user)): ?>
                <button type="button" class="tab" data-tab="mine">🗂️ Mes articles <span class="count">(<?= count($mine) ?>)</span></button>
            <?php endif; ?>
            <button type="button" class="tab" data-tab="search">🔎 Rechercher</button>
            <?php if (!empty($user)): ?>
                <button type="button" class="tab" data-tab="write">✍️ Écrire</button>
            <?php endif; ?>
        </div>

        <!-- PANEL : Articles publics (des autres) -->
        <section class="tab-panel active" id="panel-public">
            <?php if (empty($others)): ?>
                <div class="empty-state">Aucun article public pour le moment.</div>
            <?php else: ?>
                <div class="grid">
                    <?php foreach ($others as $a) { echo $renderCard($a); } ?>
                </div>
            <?php endif; ?>
        </section>

        <?php if (!empty($user)): ?>
        <!-- PANEL : Mes articles -->
        <section class="tab-panel" id="panel-mine">
            <?php if (empty($mine)): ?>
                <div class="empty-state">Tu n'as pas encore écrit d'article. Va dans l'onglet « ✍️ Écrire » pour commencer.</div>
            <?php else: ?>
                <div class="grid">
                    <?php foreach ($mine as $a) { echo $renderCard($a); } ?>
                </div>
            <?php endif; ?>
        </section>
        <?php endif; ?>

        <!-- PANEL : Rechercher -->
        <section class="tab-panel" id="panel-search">
            <div class="search-bar">
                <input type="search" id="articleSearch" placeholder="🔎 Rechercher un article par son titre…" autocomplete="off">
            </div>
            <?php
                // On cherche dans tout ce que l'utilisateur peut voir (publics + les siens).
                $searchable = array_merge($others, $mine);
            ?>
            <?php if (empty($searchable)): ?>
                <div class="empty-state">Aucun article à rechercher.</div>
            <?php else: ?>
                <div class="grid" id="searchGrid">
                    <?php foreach ($searchable as $a) { echo $renderCard($a); } ?>
                </div>
                <div class="empty-state search-empty" id="searchEmpty">Aucun article ne correspond à ta recherche.</div>
            <?php endif; ?>
        </section>

        <?php if (!empty($user)): ?>
        <!-- PANEL : Écrire -->
        <section class="tab-panel" id="panel-write">
            <div class="write-cta">
                <div class="big">✍️</div>
                <h2>Écrire un nouvel article</h2>
                <p>Rédige une publication, ajoute une image de couverture, une galerie et des pièces jointes.<br>
                   Tu pourras l'enregistrer en brouillon avant de la publier.</p>
                <a class="btn-write" href="<?= url('articles/new') ?>">＋ Commencer la rédaction</a>
            </div>
        </section>
        <?php endif; ?>
    </div>

    <script>
    (function () {
        // Onglets : affiche un panneau, masque les autres.
        var tabs = document.querySelectorAll('.tabs .tab');
        tabs.forEach(function (btn) {
            btn.addEventListener('click', function () {
                var id = btn.getAttribute('data-tab');
                tabs.forEach(function (t) { t.classList.remove('active'); });
                document.querySelectorAll('.tab-panel').forEach(function (p) { p.classList.remove('active'); });
                btn.classList.add('active');
                var panel = document.getElementById('panel-' + id);
                if (panel) { panel.classList.add('active'); }
                if (id === 'search') { var s = document.getElementById('articleSearch'); if (s) { s.focus(); } }
            });
        });

        // Recherche : filtre les cartes par titre (insensible à la casse).
        var input = document.getElementById('articleSearch');
        var grid  = document.getElementById('searchGrid');
        var empty = document.getElementById('searchEmpty');
        if (input && grid) {
            input.addEventListener('input', function () {
                var q = input.value.trim().toLowerCase();
                var shown = 0;
                grid.querySelectorAll('.card').forEach(function (c) {
                    var t = c.getAttribute('data-title') || '';
                    var ok = q === '' || t.indexOf(q) !== -1;
                    c.style.display = ok ? '' : 'none';
                    if (ok) { shown++; }
                });
                if (empty) { empty.style.display = shown === 0 ? 'block' : 'none'; }
            });
        }
    })();
    </script>
</body>
</html>
