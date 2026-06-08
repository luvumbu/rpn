<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RPN — Paramètres</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <?= Theme::css() ?>
    <style>
        * { box-sizing:border-box; margin:0; padding:0; }
        body {
            font-family:'Poppins',sans-serif; min-height:100vh; color:var(--text); padding:40px 20px 90px;
            background: radial-gradient(circle at 10% 0%, var(--glow1), transparent 42%), var(--bg-base);
        }
        body::before { content:""; position:fixed; top:0; left:0; right:0; height:6px; background: var(--bar); z-index:5; }
        .wrap { max-width:940px; margin:0 auto; }
        .top { display:flex; justify-content:space-between; align-items:center; margin-bottom:8px; flex-wrap:wrap; gap:12px; }
        h1 { font-size:24px; } h1 span { color:var(--accent); }
        .subtitle { color:var(--muted); font-size:13px; margin-bottom:24px; }
        .nav a { color:var(--text); text-decoration:none; font-size:14px; padding:8px 16px; border-radius:10px; border:1px solid var(--card-border); }
        .nav a:hover { border-color:var(--accent); color:var(--accent); }
        .saved { background:rgba(42,157,74,.15); border:1px solid rgba(42,157,74,.45); color:#2a9d4a;
            padding:12px 16px; border-radius:12px; margin-bottom:22px; font-size:14px; }

        .layout { display:flex; gap:24px; align-items:flex-start; }
        .tabs { flex:0 0 230px; display:flex; flex-direction:column; gap:6px; position:sticky; top:24px; }
        .tab { display:flex; align-items:center; gap:11px; text-align:left; width:100%;
            padding:12px 14px; border-radius:11px; border:1px solid transparent; background:transparent;
            color:var(--text); cursor:pointer; font-family:inherit; font-size:14px; transition:background .15s, border-color .15s; }
        .tab:hover { background:rgba(127,127,127,.10); }
        .tab.active { background:var(--card-bg); border-color:var(--accent); color:var(--accent); font-weight:600; }
        .tab .ico { font-size:18px; line-height:1; }

        .content { flex:1; min-width:0; }
        .panel { display:none; animation:fade .2s ease; }
        .panel.active { display:block; }
        @keyframes fade { from { opacity:0; transform:translateY(6px); } to { opacity:1; transform:none; } }
        .card { background:var(--card-bg); border:1px solid var(--card-border); border-radius:18px; padding:28px; box-shadow:var(--card-shadow); }
        .card h2 { font-size:17px; color:var(--accent); margin-bottom:4px; }
        .card .lead { font-size:12px; color:var(--muted); margin-bottom:20px; }
        label { display:block; font-size:13px; margin:0 0 6px 2px; color:var(--muted); }
        input, select, textarea { width:100%; padding:11px 13px; margin-bottom:16px; border-radius:9px;
            border:1px solid var(--card-border); background:rgba(127,127,127,.08); color:var(--text); font-size:14px; font-family:inherit; }
        input:focus, select:focus, textarea:focus { outline:none; border-color:var(--accent); }
        select option { background:var(--bg-base); color:var(--text); }
        textarea { resize:vertical; min-height:80px; }
        .row { display:flex; gap:14px; }
        .row > div { flex:1; }
        .hint { font-size:11px; color:var(--muted); margin:-10px 0 14px 2px; }

        .savebar { position:sticky; bottom:0; margin-top:22px; padding:14px 0;
            display:flex; align-items:center; justify-content:flex-end; gap:14px;
            background:linear-gradient(to top, var(--bg-base) 60%, transparent); }
        .savebar .note { margin-right:auto; font-size:12px; color:var(--muted); }
        .btn { padding:13px 30px; border:none; border-radius:11px; cursor:pointer; font-family:inherit;
            font-weight:700; font-size:15px; color:var(--accent-ink); background:var(--accent); }
        .btn:hover { filter:brightness(1.05); }

        @media (max-width:720px) {
            .layout { flex-direction:column; }
            .tabs { flex:none; width:100%; flex-direction:row; overflow-x:auto; position:static; padding-bottom:4px; }
            .tab { white-space:nowrap; }
        }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="top">
            <h1>⚙️ <span>Paramètres</span></h1>
            <div class="nav"><a href="<?= url('admin/dashboard') ?>">← Tableau de bord</a></div>
        </div>
        <p class="subtitle">Configure l'apparence, les articles, le contenu, la connexion Google et la sécurité du site.</p>

        <?php if (!empty($saved)): ?>
            <div class="saved">✅ Paramètres enregistrés.</div>
        <?php endif; ?>

        <form method="post" action="<?= url('admin/settings') ?>">
            <div class="layout">

                <nav class="tabs" id="tabs">
                    <button type="button" class="tab active" data-target="p-apparence"><span class="ico">🎨</span> Apparence</button>
                    <button type="button" class="tab" data-target="p-articles"><span class="ico">📰</span> Articles</button>
                    <button type="button" class="tab" data-target="p-contenu"><span class="ico">✏️</span> Contenu</button>
                    <button type="button" class="tab" data-target="p-google"><span class="ico">🔑</span> Connexion Google</button>
                    <button type="button" class="tab" data-target="p-securite"><span class="ico">🔒</span> Sécurité</button>
                    <button type="button" class="tab" data-target="p-api"><span class="ico">🔌</span> Clé API</button>
                    <button type="button" class="tab" data-target="p-sauvegarde"><span class="ico">💾</span> Sauvegarde</button>
                </nav>

                <div class="content">

                    <section class="panel active" id="p-apparence">
                        <div class="card">
                            <h2>🎨 Apparence</h2>
                            <p class="lead">Le thème s'applique à tout le site, articles compris. Aperçu en direct dès la sélection.</p>
                            <label>Thème de la page</label>
                            <select name="theme">
                                <?php foreach (Theme::byFamily() as $famille => $group): ?>
                                    <optgroup label="<?= htmlspecialchars($famille) ?>">
                                        <?php foreach ($group as $key => $t): ?>
                                            <option value="<?= htmlspecialchars($key) ?>" <?= $key === $theme ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($t['label']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                <?php endforeach; ?>
                            </select>
                            <p class="hint">Accueil, tableau de bord, articles et espace admin. Choisis <b>🎨 Personnalisé</b> pour définir tes propres couleurs.</p>

                            <div id="customColors" class="custom-colors"<?= $theme === 'custom' ? '' : ' hidden' ?>>
                                <p class="hint">Choisis chaque couleur. Les nuances (cartes, bordures, halos) s'adaptent automatiquement.</p>
                                <div class="color-grid">
                                    <label>Fond de page <input type="color" name="tc_bg" value="<?= htmlspecialchars($custom['bg']) ?>" data-var="--bg-base"></label>
                                    <label>Texte <input type="color" name="tc_text" value="<?= htmlspecialchars($custom['text']) ?>" data-var="--text"></label>
                                    <label>Couleur principale <input type="color" name="tc_accent" value="<?= htmlspecialchars($custom['accent']) ?>" data-var="--accent"></label>
                                    <label>Texte sur principale <input type="color" name="tc_ink" value="<?= htmlspecialchars($custom['ink']) ?>" data-var="--accent-ink"></label>
                                    <label>Rouge (déco) <input type="color" name="tc_rouge" value="<?= htmlspecialchars($custom['rouge']) ?>" data-var="--rouge"></label>
                                    <label>Vert (déco) <input type="color" name="tc_vert" value="<?= htmlspecialchars($custom['vert']) ?>" data-var="--vert"></label>
                                    <label>Or (déco) <input type="color" name="tc_or" value="<?= htmlspecialchars($custom['or']) ?>" data-var="--or"></label>
                                </div>
                            </div>
                            <style>
                                .custom-colors { margin-top:16px; }
                                .color-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(190px,1fr)); gap:12px; margin-top:8px; }
                                .color-grid label { display:flex; align-items:center; justify-content:space-between; gap:10px;
                                    font-size:13px; background:rgba(127,127,127,.06); border:1px solid var(--card-border); border-radius:10px; padding:8px 12px; }
                                .color-grid input[type=color] { width:46px; height:30px; border:none; background:none; cursor:pointer; padding:0; }
                            </style>
                        </div>
                    </section>

                    <section class="panel" id="p-articles">
                        <div class="card">
                            <h2>📰 Articles</h2>
                            <p class="lead">Réglages par défaut appliqués aux nouveaux articles.</p>
                            <label>Mise en page par défaut</label>
                            <select name="default_template">
                                <?php foreach ($articleTemplates as $key => $label): ?>
                                    <option value="<?= htmlspecialchars($key) ?>" <?= $key === $defaultTemplate ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($label) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="hint">Modèle pré-sélectionné à la création (le membre peut en choisir un autre). Le style global des articles se règle dans <b>Articles → 🎨 Style</b>.</p>
                        </div>
                    </section>

                    <section class="panel" id="p-contenu">
                        <div class="card">
                            <h2>✏️ Message principal</h2>
                            <p class="lead">Textes affichés sur la page d'accueil.</p>
                            <label>Titre</label>
                            <input type="text" name="main_title" value="<?= htmlspecialchars($mainTitle) ?>">
                            <label>Message d'accueil</label>
                            <textarea name="main_message"><?= htmlspecialchars($mainMessage) ?></textarea>
                            <label>Bas de page (petit texte)</label>
                            <input type="text" name="main_footer" value="<?= htmlspecialchars($mainFooter) ?>">
                        </div>
                    </section>

                    <section class="panel" id="p-google">
                        <div class="card">
                            <h2>🔑 Clés API Google</h2>
                            <p class="lead">Pour la connexion « Se connecter avec Google ».</p>
                            <label>Client ID</label>
                            <input type="text" name="client_id" value="<?= htmlspecialchars($clientId) ?>">
                            <label>Client Secret</label>
                            <input type="text" name="client_secret" value="<?= htmlspecialchars($clientSecret) ?>">
                            <p class="hint">Récupérables sur Google Cloud Console → Identifiants.</p>
                        </div>
                    </section>

                    <section class="panel" id="p-securite">
                        <div class="card">
                            <h2>🔒 Sécurité — connexions admin</h2>
                            <p class="lead">Protection anti-bruteforce de la connexion administrateur.</p>
                            <div class="row">
                                <div>
                                    <label>Essais avant blocage</label>
                                    <input type="number" name="max_attempts" min="1" value="<?= (int) $maxAttempts ?>">
                                </div>
                                <div>
                                    <label>Durée du blocage (heures)</label>
                                    <input type="number" name="block_hours" min="1" value="<?= (int) $blockHours ?>">
                                </div>
                            </div>
                            <p class="hint">Ex : 3 essais, 24 h de blocage.</p>
                        </div>
                    </section>

                    <section class="panel" id="p-api">
                        <div class="card">
                            <h2>🔌 Clé API</h2>
                            <p class="lead">Permet d'écrire des articles à distance (ex. via un assistant) en appelant <code><?= htmlspecialchars(url('api/article')) ?></code>.</p>
                            <label>Clé API actuelle</label>
                            <div class="apikey-row">
                                <input type="text" id="apiKeyField" value="<?= htmlspecialchars($apiKey) ?>" readonly onclick="this.select()">
                                <button type="button" class="btn-copy" id="copyApiKey">Copier</button>
                            </div>
                            <p class="hint">À envoyer dans l'en-tête <code>X-API-Key</code> (ou le paramètre <code>key</code>). Garde-la secrète.</p>
                            <label class="regen-line">
                                <input type="checkbox" name="regen_api_key" value="1">
                                <span>🔄 Régénérer une nouvelle clé à l'enregistrement <small>(l'ancienne cessera de fonctionner)</small></span>
                            </label>
                            <style>
                                .apikey-row { display:flex; gap:10px; align-items:center; }
                                .apikey-row input { flex:1; font-family:Consolas,Monaco,monospace; }
                                .btn-copy { cursor:pointer; font-family:inherit; font-weight:700; border:1px solid var(--card-border);
                                    background:var(--card-bg); color:var(--text); border-radius:9px; padding:9px 14px; white-space:nowrap; }
                                .regen-line { display:flex; align-items:flex-start; gap:10px; margin-top:16px; font-size:14px; cursor:pointer; }
                                .regen-line input { width:18px; height:18px; margin-top:2px; flex:0 0 auto; accent-color:var(--accent); }
                                .regen-line small { color:var(--muted); }
                            </style>
                        </div>
                    </section>

                    <section class="panel" id="p-sauvegarde">
                        <div class="card">
                            <h2>💾 Sauvegarde — Export / Import</h2>
                            <p class="lead">Télécharge une copie complète du site (articles + sous-articles + questionnaires + questions + associations), ou restaure depuis un fichier <code>.zip</code>.</p>
                            <div class="backup-actions">
                                <a class="bk-btn export" href="<?= url('admin/articles/export') ?>" download>⬇️ Exporter TOUT le site (.zip)</a>
                                <a class="bk-btn import" href="<?= url('profile/import') ?>">⬆️ Importer un fichier (.zip)</a>
                            </div>
                            <p class="hint">L'import recrée le contenu <b>en brouillon</b>, <b>sans doublon</b> (les éléments déjà présents sont ignorés). Tu peux importer <b>un seul projet</b> ou <b>tout</b> — c'est le même format.</p>
                            <style>
                                .backup-actions { display:flex; gap:12px; flex-wrap:wrap; margin:6px 0 4px; }
                                .bk-btn { display:inline-flex; align-items:center; gap:8px; text-decoration:none; font-weight:700; font-size:14px; border-radius:11px; padding:12px 18px; }
                                .bk-btn.export { background:var(--accent); color:var(--accent-ink); }
                                .bk-btn.import { background:var(--card-bg); color:var(--text); border:1px solid var(--card-border); }
                                .bk-btn:hover { filter:brightness(1.05); }
                            </style>
                        </div>
                    </section>

                    <div class="savebar">
                        <span class="note">Les modifications s'appliquent à tout le site après enregistrement.</span>
                        <button class="btn" type="submit">Enregistrer</button>
                    </div>

                </div>
            </div>
        </form>
    </div>

    <script>
        // Navigation par onglets
        (function () {
            var tabs = document.querySelectorAll('.tab');
            tabs.forEach(function (tab) {
                tab.addEventListener('click', function () {
                    document.querySelectorAll('.tab').forEach(function (t) { t.classList.remove('active'); });
                    document.querySelectorAll('.panel').forEach(function (p) { p.classList.remove('active'); });
                    tab.classList.add('active');
                    var target = document.getElementById(tab.getAttribute('data-target'));
                    if (target) { target.classList.add('active'); }
                });
            });
        })();

        // Aperçu en direct du thème (applique les couleurs sans recharger)
        (function () {
            var THEMES = <?= json_encode(array_map(static fn ($t) => $t['vars'], $themes), JSON_UNESCAPED_SLASHES) ?>;
            var root = document.documentElement;
            var select = document.querySelector('select[name="theme"]');
            var customBox = document.getElementById('customColors');

            function applyTheme(key) {
                var vars = THEMES[key] || {};
                for (var name in vars) { root.style.setProperty(name, vars[name]); }
            }
            if (select) {
                select.addEventListener('change', function () {
                    applyTheme(this.value);
                    if (customBox) { customBox.hidden = (this.value !== 'custom'); }
                });
            }
            // Sélecteurs de couleur (thème personnalisé) : aperçu en direct.
            document.querySelectorAll('#customColors input[type=color]').forEach(function (inp) {
                inp.addEventListener('input', function () {
                    root.style.setProperty(inp.getAttribute('data-var'), inp.value);
                });
            });
        })();

        // Bouton « Copier » de la clé API
        (function () {
            var btn = document.getElementById('copyApiKey');
            var fld = document.getElementById('apiKeyField');
            if (!btn || !fld) { return; }
            btn.addEventListener('click', function () {
                fld.select();
                var done = function () { var t = btn.textContent; btn.textContent = '✓ Copié'; setTimeout(function () { btn.textContent = t; }, 1500); };
                if (navigator.clipboard) { navigator.clipboard.writeText(fld.value).then(done, function () { document.execCommand('copy'); done(); }); }
                else { document.execCommand('copy'); done(); }
            });
        })();
    </script>
</body>
</html>
