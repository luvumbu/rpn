<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($other['name'] ?? 'Conversation') ?> — Messages</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <?= Theme::css() ?>
    <style>
        * { box-sizing:border-box; margin:0; padding:0; }
        body { font-family:'Poppins',sans-serif; min-height:100vh; color:var(--text); padding:24px 16px 40px;
            background:radial-gradient(circle at 12% 0%, var(--glow1), transparent 42%),
                radial-gradient(circle at 88% 100%, var(--glow2), transparent 44%), var(--bg-base); }
        body::before { content:""; position:fixed; top:0; left:0; right:0; height:6px; background:var(--bar); }
        .wrap { max-width:680px; margin:0 auto; }
        .head { display:flex; align-items:center; gap:12px; background:var(--card-bg); border:1px solid var(--card-border);
            border-radius:14px; padding:12px 16px; margin-bottom:14px; position:sticky; top:14px; z-index:5; }
        .head img { width:44px; height:44px; border-radius:50%; object-fit:cover; }
        .head .nm { font-weight:800; flex:1; }
        .presence { display:inline-flex; align-items:center; gap:5px; font-size:11.5px; font-weight:600; margin-left:8px; vertical-align:middle; }
        .presence .dot { width:9px; height:9px; border-radius:50%; background:#9aa0a6; display:inline-block; }
        .presence.on .dot { background:#2a9d4a; box-shadow:0 0 0 3px rgba(42,157,74,.2); }
        .presence.on .ptxt { color:#2a9d4a; }
        .presence.off .ptxt, .presence .ptxt { color:var(--muted); }
        .head a { font-size:13px; color:var(--muted); text-decoration:none; border:1px solid var(--card-border); padding:7px 12px; border-radius:9px; }
        .meet-btn { flex:0 0 auto; font:inherit; font-size:13px; font-weight:700; cursor:pointer; border:none; border-radius:11px;
            padding:0 14px; background:rgba(127,127,127,.12); color:var(--accent); white-space:nowrap; }
        .meet-btn:hover { background:rgba(127,127,127,.22); }
        /* Pièces jointes dans les bulles */
        .bubble .msg-img { display:block; margin-top:8px; }
        .bubble .msg-img img { max-width:220px; max-height:220px; border-radius:10px; display:block; }
        .bubble .msg-file { display:inline-flex; align-items:center; gap:7px; margin-top:8px; font-weight:600;
            text-decoration:none; color:inherit; padding:8px 12px; border-radius:10px; background:rgba(127,127,127,.18); }
        .bubble.mine .msg-file { background:rgba(0,0,0,.14); }
        .bubble a { color:inherit; text-decoration:underline; }
        /* Bouton « joindre un fichier » */
        .attach-btn { display:flex; align-items:center; justify-content:center; width:44px; flex:0 0 44px; cursor:pointer;
            font-size:20px; border-radius:11px; background:rgba(127,127,127,.12); }
        .attach-btn:hover { background:rgba(127,127,127,.22); }
        .attach-btn input { display:none; }
        .attach-note { font-size:12.5px; color:var(--accent); margin:6px 4px 0; }
        .conv { display:flex; flex-direction:column; gap:10px; margin-bottom:16px; }
        .bubble { max-width:78%; padding:10px 14px; border-radius:16px; font-size:14.5px; line-height:1.45; word-wrap:break-word; }
        .bubble .when { display:block; font-size:10.5px; opacity:.6; margin-top:4px; }
        .mine { align-self:flex-end; background:var(--accent); color:var(--accent-ink); border-bottom-right-radius:5px; }
        .theirs { align-self:flex-start; background:var(--card-bg); border:1px solid var(--card-border); border-bottom-left-radius:5px; }
        .empty-conv { text-align:center; color:var(--muted); padding:30px; }
        .send { display:flex; gap:10px; position:sticky; bottom:10px; background:var(--card-bg); border:1px solid var(--card-border);
            border-radius:14px; padding:10px; }
        .send textarea { flex:1; resize:none; border:none; background:transparent; color:var(--text); font:inherit; padding:8px; outline:none; min-height:44px; }
        .send button { font:inherit; font-weight:700; border:none; border-radius:11px; padding:0 20px; cursor:pointer; background:var(--accent); color:var(--accent-ink); }
        .err { background:rgba(230,57,70,.12); border:1px solid var(--rouge,#e63946); border-radius:11px; padding:10px 14px; font-size:13px; margin-bottom:12px; }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="head">
            <img src="<?= htmlspecialchars(avatar_url($other['picture'] ?? null, $other['name'] ?? '')) ?>" alt="" referrerpolicy="no-referrer">
            <span class="nm"><?= htmlspecialchars($other['name'] ?? 'Membre') ?>
                <span class="presence" id="presence" title="hors ligne"><span class="dot"></span><span class="ptxt">…</span></span>
            </span>
            <a href="<?= url('messages') ?>">← Tous</a>
        </div>

        <?php if (!empty($error)): ?><div class="err"><?= htmlspecialchars($error) ?></div><?php endif; ?>

        <div class="conv" id="conv">
            <?php if (empty($messages)): ?>
                <div class="empty-conv">Aucun message. Écris le premier 👇</div>
            <?php else: ?>
                <?php foreach ($messages as $m): ?>
                    <div class="bubble <?= (int) $m['sender_id'] === (int) $meId ? 'mine' : 'theirs' ?>">
                        <?= message_bubble_body($m) ?>
                        <span class="when"><?= date('d/m H\hi', strtotime($m['created_at'])) ?></span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Formulaire séparé pour créer le salon visio (lien Jitsi) -->
        <form id="meetForm" method="post" action="<?= url('messages/meet') ?>" style="display:none;"
              onsubmit="return confirm('Créer un lien de salon (visio, audio, partage d\'écran) et l\'envoyer à <?= htmlspecialchars(addslashes($other['name'] ?? 'ce membre')) ?> ?');">
            <input type="hidden" name="to" value="<?= (int) $other['id'] ?>">
        </form>

        <form class="send" method="post" action="<?= url('messages/send') ?>" enctype="multipart/form-data">
            <input type="hidden" name="to" value="<?= (int) $other['id'] ?>">
            <label class="attach-btn" title="Joindre un fichier (image, PDF, document, zip…)">📎
                <input type="file" name="attachment[]" class="js-autoresize"
                       accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.csv,.zip,.odt,.ods,.odp">
            </label>
            <button type="submit" form="meetForm" class="meet-btn" title="Créer un lien de salon visio (type Zoom)">🎥&nbsp;Lien</button>
            <textarea name="body" placeholder="Écris ton message…"></textarea>
            <button type="submit">Envoyer</button>
        </form>
        <p class="attach-note" id="attachNote" hidden>📎 <span></span></p>
    </div>
    <script>
    (function () {
        var WITH = <?= (int) $other['id'] ?>;
        var POLL = '<?= url('messages/poll') ?>?with=' + WITH;
        var conv = document.getElementById('conv');
        var presence = document.getElementById('presence');
        var lastCount = <?= count($messages) ?>;
        var timer = null, delay = 60000;

        function bottom() { window.scrollTo(0, document.body.scrollHeight); }
        bottom();

        function setPresence(online) {
            if (!presence) { return; }
            presence.className = 'presence ' + (online ? 'on' : 'off');
            presence.title = online ? 'en ligne' : 'hors ligne';
            var t = presence.querySelector('.ptxt'); if (t) { t.textContent = online ? 'en ligne' : 'hors ligne'; }
        }

        function tick() {
            fetch(POLL, { headers: { 'X-Requested-With': 'fetch' } })
                .then(function (r) { return r.json(); })
                .then(function (d) {
                    if (!d || !d.ok) { return; }
                    setPresence(d.otherOnline);
                    if (typeof d.count === 'number' && d.count !== lastCount) {
                        conv.innerHTML = d.html || conv.innerHTML;
                        lastCount = d.count;
                        bottom();
                    }
                    // Cadence adaptative : 10 s si l'autre est en ligne, sinon 60 s.
                    var next = d.otherOnline ? 10000 : 60000;
                    if (next !== delay) { delay = next; }
                })
                .catch(function () {})
                .finally(function () { timer = setTimeout(tick, delay); });
        }
        // 1er sondage rapide pour récupérer le statut, puis cadence adaptative.
        timer = setTimeout(tick, 1500);
        // Met en pause quand l'onglet est caché (économie), reprend au retour.
        document.addEventListener('visibilitychange', function () {
            if (document.hidden) { clearTimeout(timer); }
            else { clearTimeout(timer); timer = setTimeout(tick, 800); }
        });
    })();
    </script>
    <script>
    // Affiche le nom du fichier joint sélectionné.
    (function () {
        var inp = document.querySelector('.send input[type=file]');
        var note = document.getElementById('attachNote');
        if (!inp || !note) { return; }
        inp.addEventListener('change', function () {
            var f = inp.files && inp.files[0];
            if (f) { note.hidden = false; note.querySelector('span').textContent = f.name; }
            else { note.hidden = true; }
        });
    })();
    </script>
    <?= image_resize_js() ?>
</body>
</html>
