<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RPN — Paramètres</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Polices proposées par le module « Style global » (pour l'aperçu en direct) -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&family=Montserrat:wght@400;600;800&family=Roboto:wght@400;500;700&family=Nunito:wght@400;600;800&family=Lora:wght@400;600;700&family=Merriweather:wght@400;700&display=swap" rel="stylesheet">
    <?= Theme::css() ?>
    <style>
        * { box-sizing:border-box; margin:0; padding:0; }
        body {
            font-family:'Poppins',sans-serif; min-height:100vh; color:var(--text); padding:40px 20px 90px;
            background: radial-gradient(circle at 10% 0%, var(--glow1), transparent 42%), var(--bg-base);
        }
        body::before { content:""; position:fixed; top:0; left:0; right:0; height:6px; background: var(--bar); z-index:5; }
        .wrap { max-width:980px; margin:0 auto; }
        .top { display:flex; justify-content:space-between; align-items:center; margin-bottom:8px; flex-wrap:wrap; gap:12px; }
        h1 { font-size:24px; } h1 span { color:var(--accent); }
        .subtitle { color:var(--muted); font-size:13px; margin-bottom:24px; }
        .nav a { color:var(--text); text-decoration:none; font-size:14px; padding:8px 16px; border-radius:10px; border:1px solid var(--card-border); }
        .nav a:hover { border-color:var(--accent); color:var(--accent); }
        .saved { background:rgba(42,157,74,.15); border:1px solid rgba(42,157,74,.45); color:#2a9d4a;
            padding:12px 16px; border-radius:12px; margin-bottom:22px; font-size:14px; }
        .err { background:rgba(230,57,70,.12); border:1px solid var(--rouge,#e63946); color:var(--text);
            padding:12px 16px; border-radius:12px; margin-bottom:22px; font-size:14px; }

        .layout { display:flex; gap:24px; align-items:flex-start; }
        .tabs { flex:0 0 240px; display:flex; flex-direction:column; gap:4px; position:sticky; top:24px; }
        .tab-group { font-size:11px; text-transform:uppercase; letter-spacing:1.3px; color:var(--muted);
            margin:16px 12px 6px; font-weight:700; }
        .tab-group:first-child { margin-top:0; }
        .tab { display:flex; align-items:center; gap:11px; text-align:left; width:100%;
            padding:11px 14px; border-radius:11px; border:1px solid transparent; background:transparent;
            color:var(--text); cursor:pointer; font-family:inherit; font-size:14px; transition:background .15s, border-color .15s; }
        .tab:hover { background:rgba(127,127,127,.10); }
        .tab.active { background:var(--card-bg); border-color:var(--accent); color:var(--accent); font-weight:600; }
        .tab .ico { font-size:18px; line-height:1; }

        .content { flex:1; min-width:0; }
        .panel { display:none; animation:fade .2s ease; }
        .panel.active { display:block; }
        @keyframes fade { from { opacity:0; transform:translateY(6px); } to { opacity:1; transform:none; } }
        .card { background:var(--card-bg); border:1px solid var(--card-border); border-radius:18px; padding:28px; box-shadow:var(--card-shadow); margin-bottom:20px; }
        .card:last-child { margin-bottom:0; }
        .card h2 { font-size:17px; color:var(--accent); margin-bottom:4px; }
        .card .lead { font-size:12px; color:var(--muted); margin-bottom:20px; }
        label { display:block; font-size:13px; margin:0 0 6px 2px; color:var(--muted); }
        input, select, textarea { width:100%; padding:11px 13px; margin-bottom:16px; border-radius:9px;
            border:1px solid var(--card-border); background:rgba(127,127,127,.08); color:var(--text); font-size:14px; font-family:inherit; }
        input:focus, select:focus, textarea:focus { outline:none; border-color:var(--accent); }
        select option, select optgroup { background:var(--bg-base); color:var(--text); }
        textarea { resize:vertical; min-height:80px; }
        .row { display:flex; gap:14px; }
        .row > div { flex:1; }
        .hint { font-size:11px; color:var(--muted); margin:-10px 0 14px 2px; }

        .actions { display:flex; align-items:center; justify-content:flex-end; gap:14px; margin-top:6px; }
        .actions .note { margin-right:auto; font-size:12px; color:var(--muted); }
        .btn { padding:13px 30px; border:none; border-radius:11px; cursor:pointer; font-family:inherit;
            font-weight:700; font-size:15px; color:var(--accent-ink); background:var(--accent); }
        .btn:hover { filter:brightness(1.05); }

        /* Thème personnalisé */
        .custom-colors { margin-top:8px; }
        .color-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(190px,1fr)); gap:12px; margin-top:8px; margin-bottom:6px; }
        .color-grid label { display:flex; align-items:center; justify-content:space-between; gap:10px; margin:0;
            font-size:13px; background:rgba(127,127,127,.06); border:1px solid var(--card-border); border-radius:10px; padding:8px 12px; }
        .color-grid input[type=color] { width:46px; height:30px; border:none; background:none; cursor:pointer; padding:0; margin:0; }

        /* Curseurs (taille texte, arrondi) */
        .range-row { display:flex; align-items:center; gap:14px; margin-bottom:16px; }
        input[type=range] { flex:1; accent-color:var(--accent); margin:0; padding:0; }
        .pct { min-width:64px; text-align:center; font-weight:700; color:var(--accent);
            border:1px solid var(--card-border); border-radius:8px; padding:6px 4px; }

        /* Cases à cocher / segments */
        .check { display:flex; align-items:center; gap:10px; margin-bottom:14px; cursor:pointer; font-size:14px; color:var(--text); }
        .check input { width:18px; height:18px; accent-color:var(--accent); margin:0; flex:0 0 auto; }
        .check small { color:var(--muted); font-size:12px; }
        .seg { display:flex; flex-wrap:wrap; gap:8px; margin-bottom:8px; }
        .seg label { flex:1; min-width:120px; margin:0; cursor:pointer; }
        .seg input { position:absolute; opacity:0; width:0; height:0; margin:0; }
        .seg .opt { display:block; text-align:center; padding:11px 8px; border-radius:10px; font-size:13px; font-weight:600;
            color:var(--muted); background:rgba(127,127,127,.06); border:1px solid var(--card-border); }
        .seg input:checked + .opt { background:var(--accent); color:var(--accent-ink); border-color:var(--accent); }

        /* Aperçu style global */
        .pv { padding:0; overflow:hidden; }
        .pv-head { padding:14px 20px; border-bottom:1px solid var(--card-border); font-size:13px; text-transform:uppercase; letter-spacing:1px; color:var(--muted); }
        .pv-stage { padding:22px 22px 26px; display:flex; flex-direction:column; gap:16px; }
        .pv-card { background:rgba(127,127,127,.06); border:1px solid var(--card-border); padding:18px 20px; border-radius:14px; }
        .pv-card h3 { color:var(--accent); font-size:18px; margin-bottom:6px; }
        .pv-card p { font-size:14px; color:var(--muted); line-height:1.6; }
        .pv-row { display:flex; gap:10px; flex-wrap:wrap; align-items:center; }
        .pv-btn { padding:11px 20px; border:none; cursor:pointer; font-family:inherit; font-weight:700; font-size:14px;
            color:var(--accent-ink); background:var(--accent); border-radius:10px; }
        .pv-btn.ghost { background:transparent; color:var(--text); border:1px solid var(--card-border); }
        .pv-input { padding:11px 13px; border:1px solid var(--card-border); background:rgba(127,127,127,.08);
            color:var(--text); font-size:14px; font-family:inherit; flex:1; min-width:160px; margin:0; border-radius:10px; }
        .pv-chip { font-size:12px; padding:5px 12px; border-radius:999px; background:rgba(127,127,127,.14); border:1px solid var(--card-border); }

        /* Aperçu style des articles */
        .pva-frame { background:var(--card-bg); border:1px solid var(--card-border); border-radius:16px; overflow:hidden; box-shadow:var(--card-shadow); margin-top:6px; }
        .pva-hero { width:100%; height:120px; display:flex; align-items:center; justify-content:center; font-size:34px;
            background:linear-gradient(135deg, var(--accent), color-mix(in srgb, var(--accent) 45%, #000)); }
        .pva-content { padding:20px 22px 24px; }
        .pva-title { font-size:24px; color:var(--accent); line-height:1.25; margin-bottom:8px; }
        .pva-meta { font-size:13px; color:var(--muted); margin-bottom:16px; }
        #artPreviewBody { font-size:100%; line-height:1.75; color:var(--text); word-wrap:break-word; }
        #artPreviewBody p { margin:0 0 14px; }
        #artPreviewBody h2 { font-size:22px; color:var(--accent); margin:18px 0 10px; }
        #artPreviewBody ul { margin:0 0 14px 24px; }
        #artPreviewBody li { margin:4px 0; }
        #artPreviewBody a { color:var(--accent); text-decoration:underline; }
        #artPreviewBody blockquote { border-left:3px solid var(--accent); margin:14px 0; padding:8px 18px;
            color:var(--muted); background:rgba(127,127,127,.06); border-radius:0 10px 10px 0; }

        /* Favicon */
        .fav-current { display:flex; align-items:center; gap:18px; }
        .fav-current img { width:64px; height:64px; border-radius:14px; border:1px solid var(--card-border); background:rgba(127,127,127,.1); }
        .fav-modes { display:flex; gap:10px; margin-bottom:18px; flex-wrap:wrap; }
        .fav-modes label { cursor:pointer; font-size:14px; font-weight:600; border:1px solid var(--card-border); margin:0;
            border-radius:12px; padding:10px 16px; display:flex; align-items:center; gap:8px; }
        .fav-modes label:hover { border-color:var(--accent); }
        .fav-modes input { accent-color:var(--accent); width:auto; margin:0; }
        .grid2 { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
        input[type=color] { width:100%; height:46px; border:1px solid var(--card-border); border-radius:11px; background:none; cursor:pointer; padding:4px; }
        input[type=file] { width:100%; font-size:13px; color:var(--muted); background:none; border:none; padding:0; }
        .fav-preview { display:flex; align-items:center; gap:20px; margin:18px 0; flex-wrap:wrap; }
        #favPreview { width:96px; height:96px; border-radius:16px; border:1px solid var(--card-border); }
        .tabsim { display:flex; align-items:center; gap:8px; background:rgba(127,127,127,.12);
            border:1px solid var(--card-border); border-radius:9px 9px 0 0; padding:7px 12px; font-size:13px; }
        .tabsim canvas { width:18px; height:18px; border-radius:4px; }
        .hidden { display:none; }

        /* Clé API */
        .apikey-row { display:flex; gap:10px; align-items:center; }
        .apikey-row input { flex:1; font-family:Consolas,Monaco,monospace; margin:0; }
        .btn-copy { cursor:pointer; font-family:inherit; font-weight:700; border:1px solid var(--card-border);
            background:var(--card-bg); color:var(--text); border-radius:9px; padding:11px 14px; white-space:nowrap; }
        .regen-line { display:flex; align-items:flex-start; gap:10px; margin:16px 0; font-size:14px; cursor:pointer; }
        .regen-line input { width:18px; height:18px; margin-top:2px; flex:0 0 auto; accent-color:var(--accent); }
        .regen-line small { color:var(--muted); }

        /* Sauvegarde */
        .backup-actions { display:flex; gap:12px; flex-wrap:wrap; margin:6px 0 4px; }
        .bk-btn { display:inline-flex; align-items:center; gap:8px; text-decoration:none; font-weight:700; font-size:14px; border-radius:11px; padding:12px 18px; }
        .bk-btn.export { background:var(--accent); color:var(--accent-ink); }
        .bk-btn.import { background:var(--card-bg); color:var(--text); border:1px solid var(--card-border); }
        .bk-btn:hover { filter:brightness(1.05); }

        @media (max-width:760px) {
            .layout { flex-direction:column; }
            .tabs { flex:none; width:100%; flex-direction:row; overflow-x:auto; position:static; padding-bottom:4px; gap:6px; }
            .tab-group { display:none; }
            .tab { white-space:nowrap; }
        }
    </style>
</head>
<body>
    <div class="wrap">
        <?php view('admin/_nav', ['active' => 'settings']); ?>
        <div class="top">
            <h1>⚙️ <span>Paramètres</span></h1>
        </div>
        <p class="subtitle">Tout au même endroit : apparence &amp; style, contenu, et réglages système. Chaque section a son propre bouton d'enregistrement.</p>

        <?php if (!empty($saved)): ?><div class="saved">✅ Paramètres enregistrés.</div><?php endif; ?>
        <?php if (!empty($savedGlobalStyle)): ?><div class="saved">✅ Style global enregistré — il s'applique à tout le site.</div><?php endif; ?>
        <?php if (!empty($savedArticleStyle)): ?><div class="saved">✅ Style des articles enregistré.</div><?php endif; ?>
        <?php if (!empty($genTemplateMsg)): ?><div class="saved"><?= htmlspecialchars($genTemplateMsg) ?></div><?php endif; ?>
        <?php if (!empty($savedFavicon)): ?><div class="saved">✅ Favicon mis à jour. S'il n'apparaît pas tout de suite dans l'onglet, recharge (Ctrl+Maj+R).</div><?php endif; ?>
        <?php if (!empty($faviconError)): ?><div class="err">⚠️ <?= htmlspecialchars($faviconError) ?></div><?php endif; ?>

        <div class="layout">

            <nav class="tabs" id="tabs">
                <div class="tab-group">Apparence</div>
                <button type="button" class="tab active" data-target="p-theme"><span class="ico">🎨</span> Thème &amp; couleurs</button>
                <button type="button" class="tab" data-target="p-style-global"><span class="ico">🎛️</span> Style global</button>
                <button type="button" class="tab" data-target="p-favicon"><span class="ico">🖼️</span> Favicon</button>

                <div class="tab-group">Contenu</div>
                <button type="button" class="tab" data-target="p-accueil"><span class="ico">🏠</span> Page d'accueil</button>
                <button type="button" class="tab" data-target="p-articles"><span class="ico">📰</span> Articles</button>

                <div class="tab-group">Système</div>
                <button type="button" class="tab" data-target="p-membres"><span class="ico">👥</span> Membres</button>
                <button type="button" class="tab" data-target="p-google"><span class="ico">🔑</span> Connexion Google</button>
                <button type="button" class="tab" data-target="p-securite"><span class="ico">🔒</span> Sécurité</button>
                <button type="button" class="tab" data-target="p-api"><span class="ico">🔌</span> Clé API</button>
                <button type="button" class="tab" data-target="p-paiements"><span class="ico">💳</span> Paiements</button>
                <button type="button" class="tab" data-target="p-sauvegarde"><span class="ico">💾</span> Sauvegarde</button>
            </nav>

            <div class="content">

                <!-- ============ APPARENCE : Thème & couleurs ============ -->
                <section class="panel active" id="p-theme">
                    <form class="card" method="post" action="<?= url('admin/settings') ?>">
                        <input type="hidden" name="section" value="p-theme">
                        <h2>🎨 Thème &amp; couleurs</h2>
                        <p class="lead">Le thème s'applique à tout le site, articles compris. Aperçu en direct dès la sélection.</p>
                        <label>Thème de la page</label>
                        <select name="theme" id="themeSelect">
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
                        <p class="hint">S'applique à l'accueil, l'espace membre et les articles. Choisis <b>🎨 Personnalisé</b> pour définir tes propres couleurs. <b>Note :</b> les pages d'administration utilisent toujours l'<b>interface dorée admin</b> (indépendante de ce thème) — l'aperçu ci-dessus montre le rendu côté membres.</p>

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
                        <div class="actions">
                            <span class="note">S'applique à tout le site après enregistrement.</span>
                            <button class="btn" type="submit">Enregistrer</button>
                        </div>
                    </form>
                </section>

                <!-- ============ APPARENCE : Style global ============ -->
                <section class="panel" id="p-style-global">
                    <form method="post" action="<?= url('admin/style') ?>">
                        <div class="card">
                            <h2>✒️ Police du site</h2>
                            <p class="lead">Force une police pour l'ensemble du site. Si désactivé, la police par défaut (Poppins) s'applique.</p>
                            <label class="check">
                                <input type="checkbox" id="gsFontEnabled" name="gs_font_enabled" value="1" <?= $gsFontEnabled ? 'checked' : '' ?>>
                                <span>Activer une police personnalisée</span>
                            </label>
                            <label for="gsFont">Police</label>
                            <select id="gsFont" name="gs_font" <?= $gsFontEnabled ? '' : 'disabled' ?>>
                                <?php foreach ($gsFonts as $key => $f): ?>
                                    <option value="<?= htmlspecialchars($key) ?>" data-family="<?= htmlspecialchars($f['css']) ?>" <?= $key === $gsFontKey ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($f['label']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="card">
                            <h2>⬜ Arrondi des éléments</h2>
                            <p class="lead">Arrondi des cartes, panneaux et boutons principaux, en pixels (0 = coins droits).</p>
                            <div class="range-row">
                                <input type="range" id="gsRadius" name="gs_radius" min="0" max="28" step="1" value="<?= (int) $gsRadius ?>">
                                <span class="pct" id="gsRadiusVal"><?= (int) $gsRadius ?>px</span>
                            </div>
                        </div>

                        <div class="card">
                            <h2>🌑 Ombres</h2>
                            <p class="lead">Profondeur des ombres portées sous les cartes.</p>
                            <div class="seg" id="gsShadowSeg">
                                <?php foreach ($gsShadows as $key => $s): ?>
                                    <label>
                                        <input type="radio" name="gs_shadow" value="<?= htmlspecialchars($key) ?>" <?= $key === $gsShadowKey ? 'checked' : '' ?>>
                                        <span class="opt"><?= htmlspecialchars($s['label']) ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="card">
                            <h2>✨ Animations</h2>
                            <p class="lead">Active les transitions et animations sur tout le site. Désactivé = interface instantanée (et respecte mieux les préférences d'accessibilité).</p>
                            <label class="check">
                                <input type="checkbox" id="gsAnim" name="gs_anim" value="1" <?= $gsAnim ? 'checked' : '' ?>>
                                <span>Activer les animations <small>— transitions, survols, défilement fluide…</small></span>
                            </label>
                        </div>

                        <div class="card pv">
                            <div class="pv-head">👁️ Aperçu en direct</div>
                            <div class="pv-stage" id="gsStage">
                                <div class="pv-card" id="gsPvCard">
                                    <h3>Exemple de carte</h3>
                                    <p>Voici à quoi ressembleront les éléments du site : titres, textes, boutons et champs adoptent la police, l'arrondi et les ombres choisis ci-dessus.</p>
                                </div>
                                <div class="pv-row">
                                    <button type="button" class="pv-btn" id="gsPvBtn">Bouton principal</button>
                                    <button type="button" class="pv-btn ghost" id="gsPvBtnGhost">Bouton secondaire</button>
                                    <input type="text" class="pv-input" id="gsPvInput" placeholder="Champ de saisie…">
                                    <span class="pv-chip">Étiquette</span>
                                </div>
                            </div>
                        </div>

                        <div class="actions">
                            <span class="note">Police, arrondi, ombres et animations de toutes les pages.</span>
                            <button class="btn" type="submit">Enregistrer le style global</button>
                        </div>
                    </form>
                </section>

                <!-- ============ APPARENCE : Favicon ============ -->
                <section class="panel" id="p-favicon">
                    <div class="card fav-current">
                        <img src="<?= url('assets/favicon.png') . '?v=' . rawurlencode($favVersion) ?>" alt="favicon actuel">
                        <div>
                            <b>Favicon actuel</b><br>
                            <span style="font-size:13px;color:var(--muted);"><?= $favCustom ? 'Icône personnalisée.' : 'Icône par défaut du site.' ?></span>
                        </div>
                    </div>
                    <form class="card" method="post" action="<?= url('admin/favicon') ?>" enctype="multipart/form-data">
                        <h2>🖼️ Générer le favicon</h2>
                        <p class="lead">L'icône de l'onglet et de l'appli installée. À partir d'un texte ou d'une image.</p>
                        <div class="fav-modes">
                            <label><input type="radio" name="mode" value="text" checked> ✏️ À partir d'un texte</label>
                            <label><input type="radio" name="mode" value="image"> 🖼️ À partir d'une image</label>
                        </div>

                        <label for="favShape">Forme de l'icône</label>
                        <select id="favShape" name="shape">
                            <option value="round" selected>Coins arrondis</option>
                            <option value="square">Carré</option>
                            <option value="circle">Cercle</option>
                        </select>

                        <div id="favBlockText">
                            <label for="favText">Lettres (1 à 3)</label>
                            <input type="text" id="favText" name="text" maxlength="3" value="<?= htmlspecialchars(mb_substr(Settings::get('main_title', 'R'), 0, 3)) ?>" placeholder="Ex : R">
                            <div class="grid2">
                                <div>
                                    <label for="favBg">Couleur de fond</label>
                                    <input type="color" id="favBg" name="bg" value="#14110f">
                                </div>
                                <div>
                                    <label for="favFg">Couleur du texte</label>
                                    <input type="color" id="favFg" name="fg" value="#f4c14b">
                                </div>
                            </div>
                            <label for="favFont">Police du texte</label>
                            <select id="favFont" name="font">
                                <option value="bold" selected>Moderne (gras)</option>
                                <option value="regular">Moderne (normale)</option>
                                <option value="serif">Classique (serif)</option>
                                <option value="mono">Machine à écrire</option>
                            </select>
                            <label class="check" style="margin-top:12px;"><input type="checkbox" id="favTransparent" name="transparent" value="1"> <span>Fond transparent <small style="color:var(--muted)">(lettre seule, sans rectangle de couleur)</small></span></label>

                            <div class="fav-preview">
                                <canvas id="favPreview" width="96" height="96"></canvas>
                                <div>
                                    <div class="tabsim"><canvas id="favTabIcon" width="18" height="18"></canvas> <?= htmlspecialchars(Settings::get('main_title', 'RPN')) ?></div>
                                    <p class="hint" style="margin-top:8px;">Aperçu en direct. La vraie icône est générée côté serveur en haute qualité.</p>
                                </div>
                            </div>
                        </div>

                        <div id="favBlockImage" class="hidden">
                            <label for="favImg">Image (JPG, PNG, GIF ou WEBP)</label>
                            <input type="file" id="favImg" name="favicon_img" accept="image/jpeg,image/png,image/gif,image/webp">
                            <p class="hint">L'image est recadrée au carré (centre) puis réduite, et découpée selon la <b>forme</b> choisie. Idéalement, choisis une image déjà carrée et bien lisible en tout petit.</p>
                        </div>

                        <div class="actions">
                            <span class="note">S'applique à tout le site (onglets + icône d'installation).</span>
                            <button class="btn" type="submit">💾 Générer et appliquer</button>
                        </div>
                    </form>

                    <div class="card">
                        <h2>🔄 L'icône ne se met pas à jour ?</h2>
                        <p class="lead">Les navigateurs gardent l'ancien favicon en cache très longtemps. Ce bouton vide le cache local (et le service worker) puis recharge la page pour afficher la nouvelle icône immédiatement.</p>
                        <button type="button" class="btn" id="favRefresh">🔄 Rafraîchir l'icône maintenant</button>
                    </div>
                </section>

                <!-- ============ CONTENU : Page d'accueil ============ -->
                <section class="panel" id="p-accueil">
                    <form class="card" method="post" action="<?= url('admin/settings') ?>">
                        <input type="hidden" name="section" value="p-accueil">
                        <h2>🏠 Page d'accueil</h2>
                        <p class="lead">Textes affichés sur la page d'accueil / de connexion.</p>
                        <label>Titre</label>
                        <input type="text" name="main_title" value="<?= htmlspecialchars($mainTitle) ?>">
                        <label>Message d'accueil</label>
                        <textarea name="main_message"><?= htmlspecialchars($mainMessage) ?></textarea>
                        <label>Bas de page (petit texte)</label>
                        <input type="text" name="main_footer" value="<?= htmlspecialchars($mainFooter) ?>">
                        <div class="actions">
                            <button class="btn" type="submit">Enregistrer</button>
                        </div>
                    </form>
                </section>

                <!-- ============ CONTENU : Articles (défaut + style) ============ -->
                <section class="panel" id="p-articles">
                    <form class="card" method="post" action="<?= url('admin/articles/apply_style') ?>">
                        <h2>📐 Mise en page générale (style général)</h2>
                        <p class="lead">Le « style général » est un modèle de mise en page. Il sert de modèle <b>par défaut</b> aux nouveaux articles, et peut être <b>appliqué à TOUS les articles existants</b> d'un seul coup.</p>
                        <label for="general_template">Modèle du style général</label>
                        <select id="general_template" name="general_template">
                            <?php foreach ($articleTemplates as $key => $label): ?>
                                <option value="<?= htmlspecialchars($key) ?>" <?= $key === $defaultTemplate ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="hint">« Enregistrer par défaut » : appliqué seulement aux <b>nouveaux</b> articles. « Appliquer à tous » : remplace la mise en page de <b>chaque article existant</b> par ce modèle (les choix individuels sont écrasés ; vous pourrez re-personnaliser un article ensuite via son éditeur).</p>
                        <div class="actions">
                            <button class="btn" type="submit" name="action" value="default"
                                    style="background:var(--card-bg);color:var(--text);border:1px solid var(--card-border);">💾 Enregistrer par défaut</button>
                            <button class="btn" type="submit" name="action" value="all"
                                    onclick="return confirm('Appliquer cette mise en page à TOUS les articles existants ? Les mises en page choisies individuellement seront écrasées.');">📐 Appliquer à tous les articles</button>
                        </div>
                    </form>

                    <form method="post" action="<?= url('admin/articles/style') ?>">
                        <div class="card">
                            <h2>🔠 Style des articles — taille du texte</h2>
                            <p class="lead">Agrandit ou réduit le texte des articles, en % par rapport à la normale (100&nbsp;%).</p>
                            <div class="range-row">
                                <input type="range" id="artScale" name="art_text_scale" min="70" max="200" step="5" value="<?= (int) $artScale ?>">
                                <span class="pct" id="artScaleVal"><?= (int) $artScale ?>%</span>
                            </div>

                            <h2 style="margin-top:18px;">✒️ Police d'écriture</h2>
                            <p class="lead">Force une police pour tous les articles. Si décoché, c'est la police du thème/site qui s'applique.</p>
                            <label class="check">
                                <input type="checkbox" id="artFontEnabled" name="art_font_enabled" value="1" <?= $artFontEnabled ? 'checked' : '' ?>>
                                <span>Activer une police personnalisée</span>
                            </label>
                            <label for="artFont">Police</label>
                            <select id="artFont" name="art_font" <?= $artFontEnabled ? '' : 'disabled' ?>>
                                <?php foreach ($artFonts as $key => $family): ?>
                                    <option value="<?= htmlspecialchars($key) ?>" data-family="<?= htmlspecialchars($family) ?>" <?= $key === $artFontKey ? 'selected' : '' ?>>
                                        <?= htmlspecialchars(ucfirst($key)) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <h2 style="margin-top:18px;">📏 Largeur des articles</h2>
                            <p class="lead">Largeur maximale de la zone de lecture, pour tous les articles. « Selon le modèle » conserve la largeur propre à chaque mise en page.</p>
                            <label for="artWidth">Largeur</label>
                            <select id="artWidth" name="art_width">
                                <?php foreach ($artWidths as $key => $w): ?>
                                    <option value="<?= htmlspecialchars($key) ?>" <?= $key === $artWidthKey ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($w['label']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="card">
                            <h2>👁️ Aperçu en direct — rendu réel</h2>
                            <p class="lead">À quoi ressemblera un article avec ces réglages.</p>
                            <div class="pva-frame">
                                <div class="pva-hero">🖼️</div>
                                <div class="pva-content">
                                    <h1 class="pva-title">Le titre de votre article</h1>
                                    <p class="pva-meta">Publié le <?= date('d/m/Y') ?> · par Un membre</p>
                                    <div id="artPreviewBody">
                                        <p>Voici un <strong>aperçu fidèle</strong> du texte de vos articles : c'est exactement le même rendu que sur la page publique. Vous pouvez <a href="#" onclick="return false;">insérer des liens</a> et mettre des mots en valeur.</p>
                                        <h2>Un sous-titre de section</h2>
                                        <p>Le corps du texte s'adapte à la <em>taille</em> et à la <em>police</em> choisies. Les titres gardent une taille fixe pour rester cohérents.</p>
                                        <ul>
                                            <li>Une première idée présentée en liste ;</li>
                                            <li>une deuxième, tout aussi lisible.</li>
                                        </ul>
                                        <blockquote>« Une citation se détache du reste grâce à sa barre colorée et son fond léger. »</blockquote>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="actions">
                            <span class="note">S'applique à tous les articles du site.</span>
                            <button class="btn" type="submit">Enregistrer le style des articles</button>
                        </div>
                    </form>
                </section>

                <!-- ============ SYSTÈME : Membres ============ -->
                <section class="panel" id="p-membres">
                    <form class="card" method="post" action="<?= url('admin/settings') ?>">
                        <input type="hidden" name="section" value="p-membres">
                        <h2>👥 Membres — visibilité par défaut</h2>
                        <p class="lead">Définit si un nouveau membre est <b>trouvable</b> (visible dans la recherche / l'annuaire public) dès son inscription. Chaque membre peut ensuite changer ce choix à tout moment depuis son tableau de bord.</p>
                        <label for="default_discoverable">Nouveaux inscrits</label>
                        <select id="default_discoverable" name="default_discoverable">
                            <option value="0" <?= (int) $defaultDiscoverable === 0 ? 'selected' : '' ?>>🔒 Invisible par défaut (le membre doit s'activer lui-même)</option>
                            <option value="1" <?= (int) $defaultDiscoverable === 1 ? 'selected' : '' ?>>✅ Visible par défaut (trouvable dès l'inscription)</option>
                        </select>
                        <p class="hint">Rappel : les <b>administrateurs</b> peuvent rechercher <b>tous</b> les membres (visibles ou non) dans l'annuaire. Ce réglage ne concerne que la visibilité entre membres.</p>

                        <h2 style="margin-top:18px;">✉️ E-mail expéditeur</h2>
                        <p class="lead">Adresse utilisée pour les e-mails du site (ex. « mot de passe oublié »). Laisse vide pour une adresse automatique. Pour une bonne délivrabilité, utilise une adresse de <b>ton domaine</b>.</p>
                        <label for="mail_from">Adresse expéditeur</label>
                        <input type="email" id="mail_from" name="mail_from" value="<?= htmlspecialchars($mailFrom ?? '') ?>" placeholder="no-reply@ton-domaine.com">

                        <div class="actions">
                            <button class="btn" type="submit">Enregistrer</button>
                        </div>
                    </form>
                </section>

                <!-- ============ SYSTÈME : Connexion Google ============ -->
                <section class="panel" id="p-google">
                    <form class="card" method="post" action="<?= url('admin/settings') ?>">
                        <input type="hidden" name="section" value="p-google">
                        <h2>🔑 Clés API Google</h2>
                        <p class="lead">Pour la connexion « Se connecter avec Google ».</p>
                        <label>Client ID</label>
                        <input type="text" name="client_id" value="<?= htmlspecialchars($clientId) ?>">
                        <label>Client Secret</label>
                        <input type="text" name="client_secret" value="<?= htmlspecialchars($clientSecret) ?>">
                        <p class="hint">Récupérables sur Google Cloud Console → Identifiants.</p>
                        <div class="actions">
                            <button class="btn" type="submit">Enregistrer</button>
                        </div>
                    </form>
                </section>

                <!-- ============ SYSTÈME : Sécurité ============ -->
                <section class="panel" id="p-securite">
                    <form class="card" method="post" action="<?= url('admin/settings') ?>">
                        <input type="hidden" name="section" value="p-securite">
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
                        <div class="actions">
                            <button class="btn" type="submit">Enregistrer</button>
                        </div>
                    </form>
                </section>

                <!-- ============ SYSTÈME : Clé API ============ -->
                <section class="panel" id="p-api">
                    <form class="card" method="post" action="<?= url('admin/settings') ?>">
                        <input type="hidden" name="section" value="p-api">
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
                        <div class="actions">
                            <button class="btn" type="submit">Enregistrer</button>
                        </div>
                    </form>
                </section>

                <!-- ============ SYSTÈME : Paiements (Stripe) ============ -->
                <section class="panel" id="p-paiements">
                    <form class="card" method="post" action="<?= url('admin/settings') ?>">
                        <input type="hidden" name="section" value="p-paiements">
                        <h2>💳 Paiements (Stripe)</h2>
                        <p class="lead">Active les paiements en ligne (dons ponctuels + abonnements) via Stripe Checkout. Crée un compte sur <b>stripe.com</b>, puis copie tes clés (Développeurs → Clés API).</p>

                        <label for="stripe_secret">Clé secrète (sk_…)</label>
                        <input type="text" id="stripe_secret" name="stripe_secret" value="<?= htmlspecialchars($stripeSecret ?? '') ?>" placeholder="sk_live_… ou sk_test_…" autocomplete="off">
                        <label for="stripe_publishable">Clé publique (pk_…)</label>
                        <input type="text" id="stripe_publishable" name="stripe_publishable" value="<?= htmlspecialchars($stripePublishable ?? '') ?>" placeholder="pk_live_… ou pk_test_…" autocomplete="off">
                        <label for="stripe_webhook_secret">Secret du webhook (whsec_…)</label>
                        <input type="text" id="stripe_webhook_secret" name="stripe_webhook_secret" value="<?= htmlspecialchars($stripeWebhook ?? '') ?>" placeholder="whsec_…" autocomplete="off">
                        <p class="hint">Dans Stripe → Développeurs → Webhooks, ajoute un point de terminaison vers :<br>
                            <code><?= htmlspecialchars(url('paiement/webhook')) ?></code> (préfixe-le de ton domaine), événements <code>checkout.session.completed</code>.</p>

                        <div class="row">
                            <div>
                                <label for="stripe_currency">Devise</label>
                                <input type="text" id="stripe_currency" name="stripe_currency" value="<?= htmlspecialchars($stripeCurrency ?? 'eur') ?>" maxlength="3" placeholder="eur">
                            </div>
                            <div>
                                <label for="stripe_donation_amounts">Montants suggérés (don)</label>
                                <input type="text" id="stripe_donation_amounts" name="stripe_donation_amounts" value="<?= htmlspecialchars($stripeDonationAmounts ?? '5,10,20,50') ?>" placeholder="5,10,20,50">
                            </div>
                        </div>
                        <label for="stripe_donation_label">Libellé du don / cotisation</label>
                        <input type="text" id="stripe_donation_label" name="stripe_donation_label" value="<?= htmlspecialchars($stripeDonationLabel ?? '') ?>" placeholder="Soutenir la communauté">

                        <h2 style="margin-top:18px;">🔄 Plans d'abonnement</h2>
                        <p class="lead">Jusqu'à 3 formules récurrentes. Laisse le nom vide pour ignorer une ligne.</p>
                        <?php foreach (($stripePlans ?? []) as $i => $pl): ?>
                            <div class="row" style="margin-bottom:6px;">
                                <div style="flex:2;">
                                    <input type="text" name="plan_name[]" value="<?= htmlspecialchars($pl['name']) ?>" placeholder="Nom (ex. Adhésion)">
                                </div>
                                <div>
                                    <input type="text" name="plan_amount[]" value="<?= htmlspecialchars($pl['amount']) ?>" placeholder="Montant (€)">
                                </div>
                                <div>
                                    <select name="plan_interval[]">
                                        <option value="month" <?= $pl['interval'] === 'month' ? 'selected' : '' ?>>par mois</option>
                                        <option value="year" <?= $pl['interval'] === 'year' ? 'selected' : '' ?>>par an</option>
                                    </select>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <div class="actions">
                            <span class="note"><?= !empty($stripeSecret) ? '🟢 Stripe configuré' : '⚪ Stripe non configuré' ?></span>
                            <button class="btn" type="submit">Enregistrer</button>
                        </div>
                    </form>
                </section>

                <!-- ============ SYSTÈME : Sauvegarde ============ -->
                <section class="panel" id="p-sauvegarde">
                    <div class="card">
                        <h2>💾 Sauvegarde — Export / Import</h2>
                        <p class="lead">Télécharge une copie complète du site (articles + sous-articles + questionnaires + questions + associations), ou restaure depuis un fichier <code>.zip</code>.</p>
                        <div class="backup-actions">
                            <a class="bk-btn export" href="<?= url('admin/articles/export') ?>" download>⬇️ Exporter TOUT le site (.zip)</a>
                            <a class="bk-btn import" href="<?= url('profile/import') ?>">⬆️ Importer un fichier (.zip)</a>
                        </div>
                        <p class="hint">L'import recrée le contenu <b>en brouillon</b>, <b>sans doublon</b> (les éléments déjà présents sont ignorés). Tu peux importer <b>un seul projet</b> ou <b>tout</b> — c'est le même format.</p>
                    </div>
                </section>

            </div>
        </div>
    </div>

    <script>
    // ---- Navigation par onglets (avec mémorisation dans l'URL #ancre) ----
    (function () {
        var tabs = document.querySelectorAll('.tab');
        function activate(id) {
            var tab = document.querySelector('.tab[data-target="' + id + '"]');
            var panel = document.getElementById(id);
            if (!tab || !panel) { return false; }
            document.querySelectorAll('.tab').forEach(function (t) { t.classList.remove('active'); });
            document.querySelectorAll('.panel').forEach(function (p) { p.classList.remove('active'); });
            tab.classList.add('active');
            panel.classList.add('active');
            return true;
        }
        tabs.forEach(function (tab) {
            tab.addEventListener('click', function () {
                var id = tab.getAttribute('data-target');
                if (activate(id) && history.replaceState) { history.replaceState(null, '', '#' + id); }
            });
        });
        // Ouvre l'onglet désigné par l'ancre (ex. après enregistrement d'une section).
        var hash = (location.hash || '').replace('#', '');
        if (hash) { activate(hash); }
    })();

    // ---- Aperçu en direct du thème ----
    (function () {
        var THEMES = <?= json_encode(array_map(static fn ($t) => $t['vars'], $themes), JSON_UNESCAPED_SLASHES) ?>;
        var root = document.documentElement;
        var select = document.getElementById('themeSelect');
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
        document.querySelectorAll('#customColors input[type=color]').forEach(function (inp) {
            inp.addEventListener('input', function () {
                root.style.setProperty(inp.getAttribute('data-var'), inp.value);
            });
        });
    })();

    // ---- Aperçu du style global (police, arrondi, ombre, animations) ----
    (function () {
        var fontEnabled = document.getElementById('gsFontEnabled');
        var fontSel     = document.getElementById('gsFont');
        var radius      = document.getElementById('gsRadius');
        var radiusVal   = document.getElementById('gsRadiusVal');
        var anim        = document.getElementById('gsAnim');
        var stage       = document.getElementById('gsStage');
        if (!fontEnabled || !fontSel || !radius || !stage) { return; }
        var rounded = [document.getElementById('gsPvCard'), document.getElementById('gsPvBtn'),
                       document.getElementById('gsPvBtnGhost'), document.getElementById('gsPvInput')];
        function apply() {
            var opt = fontSel.options[fontSel.selectedIndex];
            stage.style.fontFamily = (fontEnabled.checked && opt) ? opt.getAttribute('data-family') : '';
            fontSel.disabled = !fontEnabled.checked;
            var r = radius.value + 'px';
            radiusVal.textContent = r;
            rounded.forEach(function (el) { if (el) { el.style.borderRadius = r; } });
            var sh = (document.querySelector('input[name=gs_shadow]:checked') || {}).value;
            var map = { none:'none', soft:'0 8px 26px rgba(0,0,0,.18)', normal:'', strong:'0 36px 90px rgba(0,0,0,.60)' };
            var card = document.getElementById('gsPvCard');
            if (card) { card.style.boxShadow = (sh && map[sh] !== undefined) ? map[sh] : ''; }
            stage.style.transition = anim.checked ? '' : 'none';
        }
        fontEnabled.addEventListener('change', apply);
        fontSel.addEventListener('change', apply);
        radius.addEventListener('input', apply);
        anim.addEventListener('change', apply);
        document.querySelectorAll('input[name=gs_shadow]').forEach(function (r) { r.addEventListener('change', apply); });
        apply();
    })();

    // ---- Aperçu du style des articles (taille + police) ----
    (function () {
        var scale   = document.getElementById('artScale');
        var val     = document.getElementById('artScaleVal');
        var enabled = document.getElementById('artFontEnabled');
        var sel     = document.getElementById('artFont');
        var body    = document.getElementById('artPreviewBody');
        if (!scale || !body) { return; }
        function apply() {
            body.style.fontSize = scale.value + '%';
            var opt = sel.options[sel.selectedIndex];
            body.style.fontFamily = (enabled.checked && opt) ? opt.getAttribute('data-family') : '';
            val.textContent = scale.value + '%';
            sel.disabled = !enabled.checked;
        }
        scale.addEventListener('input', apply);
        enabled.addEventListener('change', apply);
        sel.addEventListener('change', apply);
        apply();
    })();

    // ---- Favicon : bascule texte/image + aperçu canvas en direct ----
    (function () {
        var radios = document.querySelectorAll('input[name="mode"]');
        var bText  = document.getElementById('favBlockText');
        var bImage = document.getElementById('favBlockImage');
        if (!bText || !bImage) { return; }
        function syncMode() {
            var mode = (document.querySelector('input[name="mode"]:checked') || {}).value;
            bText.classList.toggle('hidden', mode !== 'text');
            bImage.classList.toggle('hidden', mode !== 'image');
        }
        radios.forEach(function (r) { r.addEventListener('change', syncMode); });
        syncMode();

        var txt   = document.getElementById('favText');
        var bg    = document.getElementById('favBg');
        var fg    = document.getElementById('favFg');
        var shape = document.getElementById('favShape');
        var trans = document.getElementById('favTransparent');
        var fontSel = document.getElementById('favFont');
        var FONTS = { bold: "800 §px Arial, sans-serif", regular: "400 §px Arial, sans-serif",
                      serif: "700 §px Georgia, 'Times New Roman', serif", mono: "700 §px 'Courier New', monospace" };
        function roundRect(ctx, x, y, w, h, r) {
            ctx.beginPath(); ctx.moveTo(x + r, y);
            ctx.arcTo(x + w, y, x + w, y + h, r); ctx.arcTo(x + w, y + h, x, y + h, r);
            ctx.arcTo(x, y + h, x, y, r); ctx.arcTo(x, y, x + w, y, r); ctx.closePath();
        }
        function drawOn(canvas) {
            if (!canvas) { return; }
            var s = canvas.width, ctx = canvas.getContext('2d');
            ctx.clearRect(0, 0, s, s);
            var sh = shape ? shape.value : 'round';
            if (!(trans && trans.checked)) {                 // fond (sauf transparent)
                ctx.fillStyle = bg.value;
                if (sh === 'circle') { ctx.beginPath(); ctx.arc(s / 2, s / 2, s / 2, 0, 2 * Math.PI); ctx.closePath(); }
                else { roundRect(ctx, 0, 0, s, s, sh === 'round' ? s * 0.20 : 0); }
                ctx.fill();
            }
            var t = (txt.value || 'R').toUpperCase().slice(0, 3);
            ctx.fillStyle = fg.value; ctx.textAlign = 'center'; ctx.textBaseline = 'middle';
            var size = t.length >= 3 ? s * 0.42 : (t.length === 2 ? s * 0.55 : s * 0.66);
            var tpl = FONTS[fontSel ? fontSel.value : 'bold'] || FONTS.bold;
            ctx.font = tpl.replace('§', String(Math.round(size)));
            ctx.fillText(t, s / 2, s / 2 + s * 0.04);
        }
        function render() { drawOn(document.getElementById('favPreview')); drawOn(document.getElementById('favTabIcon')); }
        [txt, bg, fg, shape, trans, fontSel].forEach(function (el) { if (el) { el.addEventListener('input', render); el.addEventListener('change', render); } });
        render();
    })();

    // ---- Favicon : bouton « Rafraîchir l'icône » (vide caches + SW puis recharge) ----
    (function () {
        var btn = document.getElementById('favRefresh');
        if (!btn) { return; }
        btn.addEventListener('click', function () {
            btn.disabled = true; btn.textContent = '⏳ Nettoyage…';
            (async function () {
                try {
                    if ('serviceWorker' in navigator) {
                        var regs = await navigator.serviceWorker.getRegistrations();
                        await Promise.all(regs.map(function (r) { return r.unregister(); }));
                    }
                    if (window.caches) {
                        var ks = await caches.keys();
                        await Promise.all(ks.map(function (k) { return caches.delete(k); }));
                    }
                } catch (e) {}
                // Recharge en contournant le cache, en restant sur l'onglet Favicon.
                location.replace(location.pathname + '?_=' + Date.now() + '#p-favicon');
            })();
        });
    })();

    // ---- Bouton « Copier » de la clé API ----
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
