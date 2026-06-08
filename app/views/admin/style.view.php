<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RPN — Style global du site</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Polices proposées par le panneau (pour l'aperçu en direct) -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&family=Montserrat:wght@400;600;800&family=Roboto:wght@400;500;700&family=Nunito:wght@400;600;800&family=Lora:wght@400;600;700&family=Merriweather:wght@400;700&display=swap" rel="stylesheet">
    <?= Theme::css() ?>
    <style>
        * { box-sizing:border-box; margin:0; padding:0; }
        body {
            font-family:'Poppins',sans-serif; min-height:100vh; color:var(--text); padding:40px 20px;
            background: radial-gradient(circle at 10% 0%, var(--glow1), transparent 42%), var(--bg-base);
        }
        body::before { content:""; position:fixed; top:0; left:0; right:0; height:6px; background:var(--bar); }
        .wrap { max-width:760px; margin:0 auto; }
        .top { display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; flex-wrap:wrap; gap:12px; }
        h1 { font-size:22px; } h1 span { color:var(--accent); }
        .nav a { color:var(--text); text-decoration:none; font-size:14px; padding:8px 16px; border-radius:10px; border:1px solid var(--card-border); }
        .nav a:hover { border-color:var(--accent); color:var(--accent); }
        .card { background:var(--card-bg); border:1px solid var(--card-border); border-radius:18px; padding:24px; margin-bottom:18px; box-shadow:var(--card-shadow); }
        .card h2 { font-size:15px; color:var(--accent); margin-bottom:6px; }
        .card .desc { font-size:12px; color:var(--muted); margin-bottom:16px; }
        label { display:block; font-size:13px; margin:0 0 6px 2px; color:var(--muted); }
        select { width:100%; padding:11px 13px; border-radius:9px; border:1px solid var(--card-border);
            background:rgba(127,127,127,.08); color:var(--text); font-size:14px; font-family:inherit; }
        select:focus { outline:none; border-color:var(--accent); }
        select:disabled { opacity:.5; }
        select option, select optgroup { background:var(--bg-base); color:var(--text); }
        .range-row { display:flex; align-items:center; gap:14px; }
        input[type=range] { flex:1; accent-color:var(--accent); }
        .pct { min-width:64px; text-align:center; font-weight:700; color:var(--accent);
            border:1px solid var(--card-border); border-radius:8px; padding:6px 4px; }
        .check { display:flex; align-items:center; gap:10px; margin-bottom:14px; cursor:pointer; font-size:14px; color:var(--text); }
        .check input { width:18px; height:18px; accent-color:var(--accent); }
        .check small { color:var(--muted); font-size:12px; }
        .seg { display:flex; flex-wrap:wrap; gap:8px; }
        .seg label { flex:1; min-width:120px; margin:0; cursor:pointer; }
        .seg input { position:absolute; opacity:0; width:0; height:0; }
        .seg .opt { display:block; text-align:center; padding:11px 8px; border-radius:10px; font-size:13px; font-weight:600;
            color:var(--muted); background:rgba(127,127,127,.06); border:1px solid var(--card-border); }
        .seg input:checked + .opt { background:var(--accent); color:var(--accent-ink); border-color:var(--accent); }

        /* Aperçu en direct */
        .pv { padding:0; overflow:hidden; }
        .pv-head { padding:14px 20px; border-bottom:1px solid var(--card-border); font-size:13px; text-transform:uppercase; letter-spacing:1px; color:var(--muted); }
        .pv-stage { padding:22px 22px 26px; display:flex; flex-direction:column; gap:16px; }
        .pv-card { background:rgba(127,127,127,.06); border:1px solid var(--card-border); padding:18px 20px; }
        .pv-card h3 { color:var(--accent); font-size:18px; margin-bottom:6px; }
        .pv-card p { font-size:14px; color:var(--muted); line-height:1.6; }
        .pv-row { display:flex; gap:10px; flex-wrap:wrap; align-items:center; }
        .pv-btn { padding:11px 20px; border:none; cursor:pointer; font-family:inherit; font-weight:700; font-size:14px;
            color:var(--accent-ink); background:var(--accent); }
        .pv-btn.ghost { background:transparent; color:var(--text); border:1px solid var(--card-border); }
        .pv-input { padding:11px 13px; border:1px solid var(--card-border); background:rgba(127,127,127,.08);
            color:var(--text); font-size:14px; font-family:inherit; flex:1; min-width:160px; }
        .pv-chip { font-size:12px; padding:5px 12px; border-radius:999px; background:rgba(127,127,127,.14); border:1px solid var(--card-border); }

        .btn { padding:13px 28px; border:none; border-radius:10px; cursor:pointer; font-family:inherit;
            font-weight:700; font-size:15px; color:var(--accent-ink); background:var(--accent); }
        .saved { background:rgba(42,157,74,.15); border:1px solid rgba(42,157,74,.45); color:#2a9d4a;
            padding:11px 14px; border-radius:10px; margin-bottom:20px; font-size:14px; }
        .xref { font-size:12px; color:var(--muted); margin-top:4px; }
        .xref a { color:var(--accent); }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="top">
            <h1>🎛️ <span>Style global</span> du site</h1>
            <div class="nav"><a href="<?= url('admin/dashboard') ?>">← Tableau de bord</a></div>
        </div>

        <?php if (!empty($saved)): ?>
            <div class="saved">✅ Style global enregistré. Il s'applique à TOUTES les pages du site.</div>
        <?php endif; ?>

        <p class="xref">Pour les <strong>couleurs</strong> du site, utilise le thème dans <a href="<?= url('admin/settings') ?>">⚙️ Paramètres</a>. Ce panneau règle la police, l'arrondi, les ombres et les animations de <strong>tous les éléments</strong>.</p>
        <br>

        <form method="post" action="<?= url('admin/style') ?>">

            <div class="card">
                <h2>✒️ Police du site</h2>
                <p class="desc">Force une police pour l'ensemble du site. Si désactivé, la police par défaut (Poppins) s'applique.</p>
                <label class="check">
                    <input type="checkbox" id="fontEnabled" name="gs_font_enabled" value="1" <?= $fontEnabled ? 'checked' : '' ?>>
                    <span>Activer une police personnalisée</span>
                </label>
                <label for="gs_font">Police</label>
                <select id="gs_font" name="gs_font" <?= $fontEnabled ? '' : 'disabled' ?>>
                    <?php foreach ($fonts as $key => $f): ?>
                        <option value="<?= htmlspecialchars($key) ?>" data-family="<?= htmlspecialchars($f['css']) ?>" <?= $key === $fontKey ? 'selected' : '' ?>>
                            <?= htmlspecialchars($f['label']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="card">
                <h2>⬜ Arrondi des éléments</h2>
                <p class="desc">Arrondi des cartes, panneaux et boutons principaux, en pixels (0 = coins droits).</p>
                <div class="range-row">
                    <input type="range" id="radius" name="gs_radius" min="0" max="28" step="1" value="<?= (int) $radius ?>">
                    <span class="pct" id="radiusVal"><?= (int) $radius ?>px</span>
                </div>
            </div>

            <div class="card">
                <h2>🌑 Ombres</h2>
                <p class="desc">Profondeur des ombres portées sous les cartes.</p>
                <div class="seg" id="shadowSeg">
                    <?php foreach ($shadows as $key => $s): ?>
                        <label>
                            <input type="radio" name="gs_shadow" value="<?= htmlspecialchars($key) ?>" <?= $key === $shadowKey ? 'checked' : '' ?>>
                            <span class="opt"><?= htmlspecialchars($s['label']) ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="card">
                <h2>✨ Animations</h2>
                <p class="desc">Active les transitions et animations sur tout le site. Désactivé = interface instantanée (et respecte mieux les préférences d'accessibilité).</p>
                <label class="check">
                    <input type="checkbox" id="anim" name="gs_anim" value="1" <?= $anim ? 'checked' : '' ?>>
                    <span>Activer les animations <small>— transitions, survols, défilement fluide…</small></span>
                </label>
            </div>

            <div class="card pv">
                <div class="pv-head">👁️ Aperçu en direct</div>
                <div class="pv-stage" id="pvStage">
                    <div class="pv-card" id="pvCard">
                        <h3>Exemple de carte</h3>
                        <p>Voici à quoi ressembleront les éléments du site : titres, textes, boutons et champs adoptent la police, l'arrondi et les ombres choisis ci-dessus.</p>
                    </div>
                    <div class="pv-row">
                        <button type="button" class="pv-btn" id="pvBtn">Bouton principal</button>
                        <button type="button" class="pv-btn ghost" id="pvBtnGhost">Bouton secondaire</button>
                        <input type="text" class="pv-input" id="pvInput" placeholder="Champ de saisie…">
                        <span class="pv-chip">Étiquette</span>
                    </div>
                </div>
            </div>

            <button class="btn" type="submit">Enregistrer le style global</button>
        </form>
    </div>

    <script>
    (function () {
        var fontEnabled = document.getElementById('fontEnabled');
        var fontSel     = document.getElementById('gs_font');
        var radius      = document.getElementById('radius');
        var radiusVal   = document.getElementById('radiusVal');
        var anim        = document.getElementById('anim');
        var stage       = document.getElementById('pvStage');

        // Éléments d'aperçu qui reçoivent l'arrondi.
        var rounded = [
            document.getElementById('pvCard'),
            document.getElementById('pvBtn'),
            document.getElementById('pvBtnGhost'),
            document.getElementById('pvInput')
        ];

        function apply() {
            // Police
            var opt = fontSel.options[fontSel.selectedIndex];
            var fam = (fontEnabled.checked && opt) ? opt.getAttribute('data-family') : '';
            stage.style.fontFamily = fam;
            fontSel.disabled = !fontEnabled.checked;

            // Arrondi
            var r = radius.value + 'px';
            radiusVal.textContent = r;
            rounded.forEach(function (el) { if (el) { el.style.borderRadius = r; } });

            // Ombre
            var sh = (document.querySelector('input[name=gs_shadow]:checked') || {}).value;
            var map = { none:'none', soft:'0 8px 26px rgba(0,0,0,.18)', normal:'', strong:'0 36px 90px rgba(0,0,0,.60)' };
            var card = document.getElementById('pvCard');
            if (card) { card.style.boxShadow = (sh && map[sh] !== undefined) ? map[sh] : ''; }

            // Animations
            stage.style.transition = anim.checked ? '' : 'none';
        }

        fontEnabled.addEventListener('change', apply);
        fontSel.addEventListener('change', apply);
        radius.addEventListener('input', apply);
        anim.addEventListener('change', apply);
        document.querySelectorAll('input[name=gs_shadow]').forEach(function (r) { r.addEventListener('change', apply); });
        apply();
    })();
    </script>
</body>
</html>
