<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RPN — Style des articles</title>
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
        .card { background:var(--card-bg); border:1px solid var(--card-border); border-radius:18px; padding:26px; margin-bottom:20px; box-shadow:var(--card-shadow); }
        .card h2 { font-size:15px; color:var(--accent); margin-bottom:6px; }
        .card .desc { font-size:12px; color:var(--muted); margin-bottom:18px; }
        label { display:block; font-size:13px; margin:0 0 6px 2px; color:var(--muted); }
        select { width:100%; padding:11px 13px; border-radius:9px; border:1px solid var(--card-border);
            background:rgba(127,127,127,.08); color:var(--text); font-size:14px; font-family:inherit; }
        select:focus { outline:none; border-color:var(--accent); }
        select:disabled { opacity:.5; }
        .range-row { display:flex; align-items:center; gap:14px; }
        input[type=range] { flex:1; accent-color:var(--accent); }
        .pct { min-width:64px; text-align:center; font-weight:700; color:var(--accent);
            border:1px solid var(--card-border); border-radius:8px; padding:6px 4px; }
        .check { display:flex; align-items:center; gap:10px; margin-bottom:16px; cursor:pointer; font-size:14px; color:var(--text); }
        .check input { width:18px; height:18px; accent-color:var(--accent); }
        /* Aperçu = reproduction fidèle d'une vraie carte d'article (show.view.php).
           Seuls la taille et la police de .article-body changent en direct,
           comme le fait réellement ArticleStyle sur le site. */
        .pv-frame { background:var(--card-bg); border:1px solid var(--card-border); border-radius:16px;
            overflow:hidden; box-shadow:var(--card-shadow); margin-top:6px; }
        .pv-hero { width:100%; height:130px; display:flex; align-items:center; justify-content:center;
            font-size:34px; background:var(--accent);
            background:linear-gradient(135deg, var(--accent), color-mix(in srgb, var(--accent) 45%, #000)); }
        .pv-content { padding:22px 24px 26px; }
        .pv-title { font-size:26px; color:var(--accent); line-height:1.25; margin-bottom:8px; }
        .pv-meta { font-size:13px; color:var(--muted); margin-bottom:18px; }

        /* Mêmes règles que .article-body sur la page publique (rendu identique). */
        #previewBody { font-size:100%; line-height:1.75; color:var(--text); word-wrap:break-word; }
        #previewBody p { margin:0 0 14px; }
        #previewBody h2 { font-size:22px; color:var(--accent); margin:22px 0 10px; }
        #previewBody h3 { font-size:18px; color:var(--accent); margin:18px 0 8px; }
        #previewBody ul, #previewBody ol { margin:0 0 14px 24px; }
        #previewBody li { margin:4px 0; }
        #previewBody a { color:var(--accent); text-decoration:underline; }
        #previewBody strong, #previewBody b { font-weight:700; }
        #previewBody blockquote { border-left:3px solid var(--accent); margin:16px 0; padding:8px 18px;
            color:var(--muted); background:rgba(127,127,127,.06); border-radius:0 10px 10px 0; }
        .btn { padding:13px 28px; border:none; border-radius:10px; cursor:pointer; font-family:inherit;
            font-weight:700; font-size:15px; color:var(--accent-ink); background:var(--accent); }
        .saved { background:rgba(42,157,74,.15); border:1px solid rgba(42,157,74,.45); color:#2a9d4a;
            padding:11px 14px; border-radius:10px; margin-bottom:20px; font-size:14px; }
        .soon { font-size:12px; color:var(--muted); text-align:center; padding:6px 0 4px; }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="top">
            <h1>🎨 <span>Style des articles</span></h1>
            <div class="nav"><a href="<?= url('admin/articles') ?>">← Retour aux articles</a></div>
        </div>

        <?php if (!empty($saved)): ?>
            <div class="saved">✅ Style enregistré. Il s'applique à tous les articles.</div>
        <?php endif; ?>

        <form method="post" action="<?= url('admin/articles/style') ?>">

            <div class="card">
                <h2>🔠 Taille du texte</h2>
                <p class="desc">Agrandit ou réduit le texte des articles, en % par rapport à la normale (100&nbsp;%).</p>
                <div class="range-row">
                    <input type="range" id="scale" name="art_text_scale" min="70" max="200" step="5" value="<?= (int) $scale ?>">
                    <span class="pct" id="scaleVal"><?= (int) $scale ?>%</span>
                </div>
            </div>

            <div class="card">
                <h2>✒️ Police d'écriture</h2>
                <p class="desc">Force une police pour tous les articles. Si décoché, c'est la police du thème qui s'applique.</p>
                <label class="check">
                    <input type="checkbox" id="fontEnabled" name="art_font_enabled" value="1" <?= $fontEnabled ? 'checked' : '' ?>>
                    Activer une police personnalisée
                </label>
                <label for="art_font">Police</label>
                <select id="art_font" name="art_font" <?= $fontEnabled ? '' : 'disabled' ?>>
                    <?php foreach ($fonts as $key => $family): ?>
                        <option value="<?= htmlspecialchars($key) ?>" data-family="<?= htmlspecialchars($family) ?>" <?= $key === $fontKey ? 'selected' : '' ?>>
                            <?= htmlspecialchars(ucfirst($key)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="card">
                <h2>👁️ Aperçu en direct — rendu réel</h2>
                <p class="desc">Voici à quoi ressemblera un article avec ces réglages. Bougez la taille ou changez la police : l'aperçu se met à jour instantanément.</p>
                <div class="pv-frame">
                    <div class="pv-hero">🖼️</div>
                    <div class="pv-content">
                        <h1 class="pv-title">Le titre de votre article</h1>
                        <p class="pv-meta">Publié le <?= date('d/m/Y') ?> · par Un membre</p>
                        <div id="previewBody">
                            <p>Voici un <strong>aperçu fidèle</strong> du texte de vos articles : c'est exactement le même rendu que sur la page publique. Vous pouvez <a href="#" onclick="return false;">insérer des liens</a>, mettre des mots en valeur, et structurer votre propos.</p>
                            <h2>Un sous-titre de section</h2>
                            <p>Le corps du texte s'adapte à la <em>taille</em> et à la <em>police</em> choisies ci-dessus. Les titres, eux, gardent une taille fixe pour rester cohérents.</p>
                            <ul>
                                <li>Une première idée présentée en liste ;</li>
                                <li>une deuxième, tout aussi lisible ;</li>
                                <li>une troisième pour l'exemple.</li>
                            </ul>
                            <blockquote>« Une citation se détache du reste grâce à sa barre colorée et son fond léger. »</blockquote>
                        </div>
                    </div>
                </div>
            </div>

            <button class="btn" type="submit">Enregistrer le style</button>
            <p class="soon">D'autres modules de modification pourront être ajoutés ici plus tard.</p>
        </form>
    </div>

    <script>
    (function () {
        var scale  = document.getElementById('scale');
        var val    = document.getElementById('scaleVal');
        var enabled= document.getElementById('fontEnabled');
        var sel    = document.getElementById('art_font');
        var body   = document.getElementById('previewBody');

        function apply() {
            body.style.fontSize = scale.value + '%';
            var opt = sel.options[sel.selectedIndex];
            body.style.fontFamily = enabled.checked && opt ? opt.getAttribute('data-family') : '';
            val.textContent = scale.value + '%';
            sel.disabled = !enabled.checked;
        }
        scale.addEventListener('input', apply);
        enabled.addEventListener('change', apply);
        sel.addEventListener('change', apply);
        apply();
    })();
    </script>
</body>
</html>
