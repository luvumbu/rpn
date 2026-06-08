<?php
/**
 * Page dédiée d'un événement — agencement inspiré de problackunivers
 * (grande couverture pleine largeur → badge + titre → date → « À propos »
 * → billetterie/réservation), couleurs du thème (noir/or sur blanc).
 * Variables : $a (enrichi : booked, bookers, changes), $photos, $evtRating,
 * $hostRating, $myRating, $isOwner, $iBooked, $showBookers, $user.
 */
$ts      = strtotime($a['start_at']);
$cap     = (int) $a['capacity'];
$booked  = (int) ($a['booked'] ?? 0);
$free    = max(0, $cap - $booked);
$pct     = $cap > 0 ? (int) round($booked / $cap * 100) : 0;
$isPriv  = ($a['visibility'] ?? 'public') === 'private';
$enLigne = ($a['mode'] ?? 'presentiel') === 'en_ligne';
$hasMap  = !$enLigne && !empty($a['lat']) && !empty($a['lng']);
$started = $ts <= time();
$canSeeCode = !empty($isOwner) || Session::isAdmin();
$mapsDest = trim((string) ($a['location'] ?? ''));
if ($mapsDest === '' && !empty($a['lat'])) { $mapsDest = $a['lat'] . ',' . $a['lng']; }
$mapsUrl  = 'https://www.google.com/maps/dir/?api=1&destination=' . rawurlencode($mapsDest);
$cover    = !empty($photos) ? $photos[0] : null;
$rest     = !empty($photos) ? array_slice($photos, 1) : [];
$moisFr   = [1=>'janvier','février','mars','avril','mai','juin','juillet','août','septembre','octobre','novembre','décembre'];
$joursFr  = ['Sun'=>'dimanche','Mon'=>'lundi','Tue'=>'mardi','Wed'=>'mercredi','Thu'=>'jeudi','Fri'=>'vendredi','Sat'=>'samedi'];
$dateTxt  = $joursFr[date('D', $ts)] . ' ' . (int) date('j', $ts) . ' ' . $moisFr[(int) date('n', $ts)] . ' ' . date('Y', $ts);
$endTs    = $ts + (int) $a['duration_min'] * 60;
$heureTxt = 'de ' . date('H\hi', $ts) . ' à ' . date('H\hi', $endTs); // heure exacte début → fin

// Lien « Ajouter à Google Agenda » (pré-rempli, fuseau de Paris) + lien .ics universel.
$__scheme   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$eventUrl   = $__scheme . '://' . ($_SERVER['HTTP_HOST'] ?? '') . url('agenda/event') . '?id=' . (int) $a['id'];
$gcDetails  = trim((string) ($a['description'] ?? ''));
$gcDetails .= ($gcDetails !== '' ? "\n\n" : '') . 'Fiche de l\'événement : ' . $eventUrl;
$googleCalUrl = 'https://calendar.google.com/calendar/render?action=TEMPLATE'
    . '&text='     . rawurlencode((string) $a['title'])
    . '&dates='    . date('Ymd\THis', $ts) . '/' . date('Ymd\THis', $endTs)
    . '&ctz=Europe/Paris'
    . '&location=' . rawurlencode(rdv_lieu_texte($a['mode'] ?? 'presentiel', (string) ($a['location'] ?? '')))
    . '&details='  . rawurlencode($gcDetails);
$icsUrl = url('agenda/ics') . '?id=' . (int) $a['id'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($a['title']) ?> — <?= htmlspecialchars(Settings::get('main_title', 'RPN')) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <?php if ($hasMap): ?>
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <?php endif; ?>
    <?= Theme::css() ?>
    <style>
        * { box-sizing:border-box; margin:0; padding:0; }
        body { font-family:'Poppins',sans-serif; min-height:100vh; color:var(--text); background:var(--bg-base); padding-bottom:60px; }
        body::before { content:""; position:fixed; top:0; left:0; right:0; height:6px; background:var(--bar); z-index:5; }
        .bar-top { max-width:960px; margin:0 auto; padding:18px 20px 0; }
        .back { color:var(--text); text-decoration:none; font-size:14px; padding:9px 16px; border-radius:10px; border:1px solid var(--card-border); }
        .back:hover { border-color:var(--accent); color:var(--accent); }
        .wrap { max-width:960px; margin:0 auto; padding:0 20px; }

        /* HERO pleine largeur */
        .hero { width:100%; margin-top:16px; border-radius:18px; overflow:hidden; border:1px solid var(--card-border); background:rgba(127,127,127,.1); }
        .hero img { width:100%; max-height:420px; object-fit:cover; display:block; }
        .hero.none { height:160px; background:var(--bar); }

        /* En-tête titre */
        .head { padding:24px 2px 8px; }
        .badges { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:12px; }
        .badge { font-size:12px; font-weight:800; padding:5px 13px; border-radius:999px; letter-spacing:.3px;
            background:var(--accent); color:var(--accent-ink); }
        .badge.ghost { background:transparent; color:var(--text); border:1px solid var(--card-border); }
        .badge.urgent { background:#e63946; color:#fff; }
        h1 { font-size:38px; line-height:1.12; font-weight:900; margin-bottom:12px; }
        .when { font-size:17px; font-weight:700; color:var(--accent); }
        .by { color:var(--muted); font-size:14px; margin-top:8px; }
        .cal-actions { display:flex; flex-wrap:wrap; gap:10px; margin-top:14px; }
        .cal-btn { display:inline-flex; align-items:center; gap:8px; text-decoration:none; font-size:13.5px;
            font-weight:700; padding:10px 16px; border-radius:11px; transition:transform .15s, box-shadow .2s, border-color .15s; }
        .cal-btn.google { background:#1a73e8; color:#fff; box-shadow:0 8px 20px rgba(26,115,232,.45); }
        .cal-btn.google:hover { transform:translateY(-2px); filter:brightness(1.06); box-shadow:0 12px 28px rgba(26,115,232,.55); }
        .cal-btn.ics { background:transparent; color:var(--text); border:1px solid var(--card-border); }
        .cal-btn.ics:hover { border-color:var(--accent); color:var(--accent); }
        .stars { color:var(--or, #f4c14b); }

        .card { background:var(--card-bg); border:1px solid var(--card-border); border-radius:16px; box-shadow:var(--card-shadow); padding:24px 26px; margin-top:18px; }
        h2 { font-size:20px; font-weight:800; margin-bottom:14px; }
        .desc { line-height:1.75; white-space:pre-line; font-size:15px; }
        .meta-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(170px,1fr)); gap:14px; }
        .meta { background:rgba(127,127,127,.05); border:1px solid var(--card-border); border-radius:12px; padding:12px 14px; }
        .meta .k { font-size:11px; color:var(--muted); text-transform:uppercase; letter-spacing:.5px; }
        .meta .v { font-size:15px; font-weight:600; margin-top:3px; word-break:break-word; }
        .meta .v a { color:var(--accent); }
        .codebox { font-family:Consolas,Monaco,monospace; letter-spacing:2px; color:var(--accent); }

        .gallery { display:grid; grid-template-columns:repeat(auto-fill,minmax(150px,1fr)); gap:10px; }
        .gallery a img { width:100%; height:150px; object-fit:cover; border-radius:12px; border:1px solid var(--card-border); }
        #map { height:300px; border-radius:14px; border:1px solid var(--card-border); }

        /* Billetterie / réservation */
        .resa-head { display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap; }
        .places { font-size:16px; font-weight:800; }
        .places.free { color:var(--vert); } .places.full { color:var(--rouge); }
        .meter { height:10px; border-radius:99px; background:rgba(127,127,127,.18); overflow:hidden; margin:12px 0 6px; }
        .meter > i { display:block; height:100%; background:var(--accent); }
        .pctn { font-size:13px; color:var(--muted); font-weight:700; }
        .actions { margin-top:18px; display:flex; gap:12px; flex-wrap:wrap; }
        .act { display:inline-block; border:none; cursor:pointer; font-family:inherit; font-weight:800; font-size:15px;
            padding:14px 28px; border-radius:12px; text-decoration:none; text-align:center; }
        .act.book { background:var(--accent); color:var(--accent-ink); }
        .act.cancel { background:var(--rouge); color:#fff; }
        .act.maps { background:transparent; color:var(--text); border:1px solid var(--card-border); }
        .act.maps:hover { border-color:var(--accent); color:var(--accent); }
        .act.muted { background:rgba(127,127,127,.18); color:var(--muted); cursor:default; }
        .back-bottom { text-align:center; margin-top:26px; }
        .rate { margin-top:18px; }
        .rate button { background:none; border:none; cursor:pointer; font-size:28px; line-height:1; color:var(--card-border); padding:0 2px; }
        .rate button.on { color:var(--or, #f4c14b); }
        .rate button:hover { color:var(--or, #f4c14b); transform:scale(1.12); }
        .bookers { display:flex; gap:8px; flex-wrap:wrap; }
        .bk { background:rgba(127,127,127,.08); border:1px solid var(--card-border); border-radius:999px; padding:5px 12px; font-size:13px; }
        .flash { max-width:960px; margin:14px auto 0; padding:11px 15px; border-radius:10px; font-size:14px;
            background:rgba(42,157,74,.15); border:1px solid rgba(42,157,74,.4); color:var(--vert); }
        @media(max-width:600px){ h1 { font-size:28px; } .hero img { max-height:240px; } }
    </style>
</head>
<body>
    <div class="bar-top"><a class="back" href="<?= url('agenda') ?>">← Agenda</a></div>

    <?php if ($note = Session::get('agenda_notice')): Session::remove('agenda_notice'); ?>
        <div class="flash"><?= htmlspecialchars($note) ?></div>
    <?php endif; ?>

    <div class="wrap">
        <!-- COUVERTURE pleine largeur -->
        <?php if ($cover): ?>
            <div class="hero"><img src="<?= url('uploads/agenda/' . $cover) ?>" alt="<?= htmlspecialchars($a['title']) ?>"></div>
        <?php else: ?>
            <div class="hero none"></div>
        <?php endif; ?>

        <!-- TITRE -->
        <div class="head">
            <div class="badges">
                <?php if ((int) ($a['urgent'] ?? 0) === 1): ?><span class="badge urgent">🟥 URGENT</span><?php endif; ?>
                <span class="badge"><?= $enLigne ? '🔗 En ligne' : '📍 Présentiel' ?></span>
                <span class="badge ghost"><?= $isPriv ? '🔒 Privé' : '🌐 Public' ?></span>
                <?php if ((int) ($a['protected'] ?? 0) === 1): ?><span class="badge ghost">🔒 Protégé</span><?php endif; ?>
            </div>
            <h1><?= htmlspecialchars($a['title']) ?></h1>
            <div class="when">📅 <?= htmlspecialchars($dateTxt) ?> &nbsp;·&nbsp; 🕒 <b><?= htmlspecialchars($heureTxt) ?></b> &nbsp;·&nbsp; ⏳ <?= countdown_html($a['start_at'], (int) $a['duration_min']) ?></div>
            <div class="by">Proposé par <b><?= htmlspecialchars($a['owner_name'] ?: 'Membre') ?></b>
                <?php if ($hostRating && (int) $hostRating['count'] > 0): ?>
                    · <span class="stars">★ <?= number_format((float) $hostRating['avg'], 1, ',', ' ') ?>/5</span> <small>(<?= (int) $hostRating['count'] ?> avis)</small>
                <?php endif; ?>
            </div>
            <?php if ($endTs > time()): ?>
                <div class="cal-actions">
                    <a class="cal-btn google" href="<?= htmlspecialchars($googleCalUrl) ?>" target="_blank" rel="noopener">📅 Ajouter à Google Agenda</a>
                    <a class="cal-btn ics" href="<?= htmlspecialchars($icsUrl) ?>">⬇️ Autre agenda (.ics)</a>
                </div>
            <?php endif; ?>
        </div>

        <!-- À PROPOS -->
        <?php if (!empty($a['description'])): ?>
            <div class="card">
                <h2>À propos de l'événement</h2>
                <div class="desc"><?= nl2br(htmlspecialchars($a['description'])) ?></div>
            </div>
        <?php endif; ?>

        <!-- INFOS -->
        <div class="card">
            <h2>Informations</h2>
            <div class="meta-grid">
                <div class="meta"><div class="k">Durée</div><div class="v"><?= (int) $a['duration_min'] ?> min</div></div>
                <div class="meta"><div class="k"><?= $enLigne ? 'Lien de connexion' : 'Lieu' ?></div>
                    <div class="v">
                        <?php $loc = trim((string) ($a['location'] ?? '')); ?>
                        <?php if ($enLigne && $loc !== '' && preg_match('#^https?://#i', $loc)): ?>
                            <a href="<?= htmlspecialchars($loc) ?>" target="_blank" rel="noopener">Rejoindre la visio →</a>
                        <?php else: ?>
                            <?= $loc !== '' ? htmlspecialchars($loc) : '<span style="color:var(--muted)">Non précisé</span>' ?>
                        <?php endif; ?>
                    </div>
                </div>
                <?php if ($evtRating && (int) $evtRating['count'] > 0): ?>
                    <div class="meta"><div class="k">Note de l'événement</div><div class="v"><span class="stars">★ <?= number_format((float) $evtRating['avg'], 1, ',', ' ') ?>/5</span> <small>(<?= (int) $evtRating['count'] ?>)</small></div></div>
                <?php endif; ?>
                <?php if ((int) ($a['min_notice_hours'] ?? 0) > 0): ?>
                    <div class="meta"><div class="k">Clôture des réservations</div><div class="v"><?= htmlspecialchars(delai_texte((int) $a['min_notice_hours'])) ?> avant</div></div>
                <?php endif; ?>
                <?php if ($canSeeCode): ?>
                    <div class="meta"><div class="k">Code</div><div class="v codebox"><?= htmlspecialchars($a['code'] ?? '') ?></div></div>
                <?php endif; ?>
            </div>
        </div>

        <!-- GALERIE (photos restantes) -->
        <?php if ($rest): ?>
            <div class="card">
                <h2>📷 Photos</h2>
                <div class="gallery">
                    <?php foreach ($rest as $ph): ?>
                        <a href="<?= url('uploads/agenda/' . $ph) ?>" target="_blank" rel="noopener"><img src="<?= url('uploads/agenda/' . $ph) ?>" alt="photo" loading="lazy"></a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- LIEU & ITINÉRAIRE -->
        <?php if (!$enLigne && $mapsDest !== ''): ?>
            <div class="card">
                <h2>📍 Lieu & itinéraire</h2>
                <?php if ($hasMap): ?>
                    <div id="map" data-lat="<?= htmlspecialchars((string) $a['lat']) ?>" data-lng="<?= htmlspecialchars((string) $a['lng']) ?>" style="margin-bottom:14px"></div>
                <?php endif; ?>
                <a class="act maps" href="<?= htmlspecialchars($mapsUrl) ?>" target="_blank" rel="noopener">🧭 M'y rendre — itinéraire Google Maps</a>
            </div>
        <?php endif; ?>

        <!-- BILLETTERIE / RÉSERVATION -->
        <div class="card">
            <h2>🎟️ Réservation</h2>
            <div class="resa-head">
                <span class="places <?= $free > 0 ? 'free' : 'full' ?>"><?= $booked ?>/<?= $cap ?> · <?= $free > 0 ? $free . ' place' . ($free > 1 ? 's' : '') . ' libre' . ($free > 1 ? 's' : '') : 'complet' ?></span>
                <span class="pctn"><?= $pct ?>% occupé</span>
            </div>
            <div class="meter"><i style="width:<?= $pct ?>%"></i></div>
            <div class="actions">
                <?php if (empty($user)): ?>
                    <a class="act book" href="<?= url('') ?>">Connecte-toi pour réserver</a>
                <?php elseif ($isOwner): ?>
                    <span class="act muted">C'est ton événement</span>
                <?php elseif ($iBooked): ?>
                    <form method="post" action="<?= url('agenda/cancel') ?>">
                        <input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
                        <button class="act cancel" type="submit">✓ Réservé — annuler</button>
                    </form>
                <?php elseif ($free <= 0): ?>
                    <span class="act muted">Complet</span>
                <?php else: ?>
                    <form method="post" action="<?= url('agenda/book') ?>">
                        <input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
                        <button class="act book" type="submit">Réserver une place</button>
                    </form>
                <?php endif; ?>
            </div>

            <?php if ($iBooked && $started): ?>
                <div class="rate">
                    <div style="font-size:13px;color:var(--muted);margin-bottom:4px"><?= $myRating ? 'Ta note :' : "Noter l'événement :" ?></div>
                    <form method="post" action="<?= url('agenda/rate_event') ?>">
                        <input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
                        <?php for ($s = 1; $s <= 5; $s++): ?>
                            <button type="submit" name="stars" value="<?= $s ?>" class="<?= $s <= $myRating ? 'on' : '' ?>" title="<?= $s ?>/5">★</button>
                        <?php endfor; ?>
                    </form>
                </div>
            <?php endif; ?>
        </div>

        <!-- INSCRITS -->
        <?php if ($showBookers && !empty($a['bookers'])): ?>
            <div class="card">
                <h2>👥 Inscrits (<?= count($a['bookers']) ?>)</h2>
                <div class="bookers">
                    <?php foreach ($a['bookers'] as $bk): ?>
                        <span class="bk">👤 <?= htmlspecialchars($bk['user_name'] ?: 'Membre') ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="back-bottom">
            <a class="act maps" href="<?= url('agenda') ?>">← Retour à l'agenda</a>
        </div>
    </div>

    <?php if ($hasMap): ?>
    <script>
        (function () {
            var el = document.getElementById('map');
            if (!el || typeof L === 'undefined') { return; }
            var lat = parseFloat(el.dataset.lat), lng = parseFloat(el.dataset.lng);
            var map = L.map('map').setView([lat, lng], 14);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '© OpenStreetMap', maxZoom: 19 }).addTo(map);
            L.marker([lat, lng]).addTo(map).bindPopup(<?= json_encode(htmlspecialchars($a['title'])) ?>);
        })();
    </script>
    <?php endif; ?>
</body>
</html>
