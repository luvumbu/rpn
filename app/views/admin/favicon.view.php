<?php
/**
 * GÉNÉRATEUR DE FAVICON (admin).
 * Variables : $user, $version, $custom, $saved, $error.
 */
$v = '?v=' . rawurlencode($version);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(Settings::get('main_title', 'RPN')) ?> — Favicon</title>
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
        h1 { font-size:23px; } h1 span { color:var(--accent); }
        .back { color:var(--text); text-decoration:none; font-size:14px; padding:8px 16px; border-radius:10px; border:1px solid var(--card-border); }
        .back:hover { border-color:var(--accent); color:var(--accent); }
        .card { background:var(--card-bg); border:1px solid var(--card-border); border-radius:18px;
            box-shadow:var(--card-shadow); padding:24px 26px; margin-bottom:20px; }
        .notice { background:rgba(42,157,74,.12); border:1px solid var(--vert,#2a9d4a); padding:12px 16px; border-radius:12px; margin-bottom:18px; font-size:14px; }
        .error { background:rgba(230,57,70,.12); border:1px solid var(--rouge,#e63946); padding:12px 16px; border-radius:12px; margin-bottom:18px; font-size:14px; }

        .current { display:flex; align-items:center; gap:18px; }
        .current img { width:64px; height:64px; border-radius:14px; border:1px solid var(--card-border); background:rgba(127,127,127,.1); }
        .current .ctxt { font-size:14px; color:var(--muted); }

        .modes { display:flex; gap:10px; margin-bottom:18px; flex-wrap:wrap; }
        .modes label { cursor:pointer; font-size:14px; font-weight:600; border:1px solid var(--card-border);
            border-radius:12px; padding:10px 16px; display:flex; align-items:center; gap:8px; }
        .modes label:hover { border-color:var(--accent); }
        .modes input { accent-color:var(--accent); }

        .lbl { display:block; font-size:13px; font-weight:600; color:var(--muted); margin-bottom:7px; }
        .grid2 { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
        input[type=text] { width:100%; padding:12px 14px; border-radius:11px; border:1px solid var(--card-border);
            background:rgba(127,127,127,.08); color:var(--text); font-family:inherit; font-size:15px; }
        input[type=text]:focus { outline:none; border-color:var(--accent); }
        input[type=color] { width:100%; height:46px; border:1px solid var(--card-border); border-radius:11px; background:none; cursor:pointer; }
        input[type=file] { width:100%; font-size:13px; color:var(--muted); }
        .check { display:flex; align-items:center; gap:9px; margin-top:14px; font-size:14px; }
        .check input { width:18px; height:18px; accent-color:var(--accent); }

        .preview-row { display:flex; align-items:center; gap:20px; margin-top:18px; flex-wrap:wrap; }
        #preview { width:96px; height:96px; border-radius:16px; border:1px solid var(--card-border); }
        .preview-row .tabsim { display:flex; align-items:center; gap:8px; background:rgba(127,127,127,.12);
            border:1px solid var(--card-border); border-radius:9px 9px 0 0; padding:7px 12px; font-size:13px; }
        .preview-row .tabsim img, .preview-row .tabsim canvas { width:18px; height:18px; border-radius:4px; }

        .save { font:inherit; font-size:15px; font-weight:700; cursor:pointer; border:none; border-radius:12px;
            padding:14px 28px; background:var(--accent); color:var(--accent-ink); margin-top:20px; }
        .hint { font-size:12.5px; color:var(--muted); margin-top:10px; line-height:1.5; }
        .hidden { display:none; }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="top">
            <h1>🎨 <span>Favicon</span></h1>
            <a class="back" href="<?= url('admin/dashboard') ?>">← Tableau de bord admin</a>
        </div>

        <?php if (!empty($saved)): ?><div class="notice">✅ Favicon mis à jour. S'il n'apparaît pas tout de suite dans l'onglet, recharge la page (Ctrl+Maj+R).</div><?php endif; ?>
        <?php if (!empty($error)): ?><div class="error">⚠️ <?= htmlspecialchars($error) ?></div><?php endif; ?>

        <div class="card current">
            <img src="<?= url('favicon.png') . $v ?>" alt="favicon actuel">
            <div class="ctxt">
                <b>Favicon actuel</b><br>
                <?= $custom ? 'Icône personnalisée.' : 'Icône par défaut du site.' ?>
            </div>
        </div>

        <form class="card" method="post" action="<?= url('admin/favicon') ?>" enctype="multipart/form-data">
            <div class="modes">
                <label><input type="radio" name="mode" value="text" checked> ✏️ À partir d'un texte</label>
                <label><input type="radio" name="mode" value="image"> 🖼️ À partir d'une image</label>
            </div>

            <!-- MODE TEXTE -->
            <div id="block-text">
                <label class="lbl" for="text">Lettres (1 à 3)</label>
                <input type="text" id="text" name="text" maxlength="3" value="<?= htmlspecialchars(mb_substr(Settings::get('main_title', 'R'), 0, 3)) ?>" placeholder="Ex : R">
                <div class="grid2" style="margin-top:16px;">
                    <div>
                        <label class="lbl" for="bg">Couleur de fond</label>
                        <input type="color" id="bg" name="bg" value="#14110f">
                    </div>
                    <div>
                        <label class="lbl" for="fg">Couleur du texte</label>
                        <input type="color" id="fg" name="fg" value="#f4c14b">
                    </div>
                </div>
                <label class="check"><input type="checkbox" id="round" name="round" value="1" checked> Coins arrondis</label>

                <div class="preview-row">
                    <canvas id="preview" width="96" height="96"></canvas>
                    <div>
                        <div class="tabsim"><canvas id="tabIcon" width="18" height="18"></canvas> <?= htmlspecialchars(Settings::get('main_title', 'RPN')) ?></div>
                        <p class="hint">Aperçu en direct. La vraie icône est générée côté serveur en haute qualité.</p>
                    </div>
                </div>
            </div>

            <!-- MODE IMAGE -->
            <div id="block-image" class="hidden">
                <label class="lbl" for="favicon_img">Image (JPG, PNG, GIF ou WEBP)</label>
                <input type="file" id="favicon_img" name="favicon_img" accept="image/jpeg,image/png,image/gif,image/webp">
                <p class="hint">L'image est recadrée au carré (centre) puis réduite. Idéalement, choisis une image déjà carrée et bien lisible en tout petit.</p>
            </div>

            <button type="submit" class="save">💾 Générer et appliquer</button>
            <p class="hint">Le favicon s'applique à <b>tout le site</b> (onglets + icône d'installation de l'appli).</p>
        </form>
    </div>

    <script>
    (function () {
        var radios  = document.querySelectorAll('input[name="mode"]');
        var bText   = document.getElementById('block-text');
        var bImage  = document.getElementById('block-image');
        function syncMode() {
            var mode = document.querySelector('input[name="mode"]:checked').value;
            bText.classList.toggle('hidden', mode !== 'text');
            bImage.classList.toggle('hidden', mode !== 'image');
        }
        radios.forEach(function (r) { r.addEventListener('change', syncMode); });
        syncMode();

        // Aperçu en direct (réplique simplifiée de la génération serveur).
        var txt = document.getElementById('text');
        var bg  = document.getElementById('bg');
        var fg  = document.getElementById('fg');
        var rnd = document.getElementById('round');

        function roundRect(ctx, x, y, w, h, r) {
            ctx.beginPath();
            ctx.moveTo(x + r, y);
            ctx.arcTo(x + w, y, x + w, y + h, r);
            ctx.arcTo(x + w, y + h, x, y + h, r);
            ctx.arcTo(x, y + h, x, y, r);
            ctx.arcTo(x, y, x + w, y, r);
            ctx.closePath();
        }
        function drawOn(canvas) {
            var s = canvas.width;
            var ctx = canvas.getContext('2d');
            ctx.clearRect(0, 0, s, s);
            var r = rnd.checked ? s * 0.20 : 0;
            ctx.fillStyle = bg.value;
            roundRect(ctx, 0, 0, s, s, r);
            ctx.fill();
            var t = (txt.value || 'R').toUpperCase().slice(0, 3);
            ctx.fillStyle = fg.value;
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            var size = t.length >= 3 ? s * 0.42 : (t.length === 2 ? s * 0.55 : s * 0.66);
            ctx.font = '800 ' + size + 'px Poppins, Arial, sans-serif';
            ctx.fillText(t, s / 2, s / 2 + s * 0.04);
        }
        function render() { drawOn(document.getElementById('preview')); drawOn(document.getElementById('tabIcon')); }
        [txt, bg, fg, rnd].forEach(function (el) { el.addEventListener('input', render); el.addEventListener('change', render); });
        render();
    })();
    </script>
</body>
</html>
