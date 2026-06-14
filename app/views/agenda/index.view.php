<?php
// Formatage d'un créneau : "lun. 09/06/2026 · 14:00–15:00".
$jours = ['Sun' => 'dim.', 'Mon' => 'lun.', 'Tue' => 'mar.', 'Wed' => 'mer.', 'Thu' => 'jeu.', 'Fri' => 'ven.', 'Sat' => 'sam.'];
$fmt = function ($start, $dur) use ($jours) {
    $s = strtotime($start);
    $e = $s + ((int) $dur) * 60;
    return $jours[date('D', $s)] . ' ' . date('d/m/Y', $s) . ' · ' . date('H:i', $s) . '–' . date('H:i', $e);
};
// Bloc d'affichage du lieu d'un créneau (en ligne ou présentiel + carte Google).
$locBlock = function ($a) {
    $mode = ($a['mode'] ?? 'presentiel');
    $loc  = trim((string) ($a['location'] ?? ''));

    if ($mode === 'en_ligne') {
        if ($loc === '') {
            return '<div class="slot-loc">🔗 En ligne</div>';
        }
        if (preg_match('#^https?://#i', $loc)) {
            return '<div class="slot-loc">🔗 <a href="' . htmlspecialchars($loc) . '" target="_blank" rel="noopener">Rejoindre en ligne</a></div>';
        }
        return '<div class="slot-loc">🔗 En ligne — ' . htmlspecialchars($loc) . '</div>';
    }

    // Présentiel : adresse + itinéraire + carte intégrée (chargée au clic).
    if ($loc === '') {
        return '<div class="slot-loc">📍 En présentiel</div>';
    }
    $enc   = rawurlencode($loc);
    $embed = 'https://maps.google.com/maps?q=' . $enc . '&z=15&output=embed';
    $dir   = 'https://www.google.com/maps/dir/?api=1&destination=' . $enc;
    return '<div class="slot-loc">📍 ' . htmlspecialchars($loc)
         . ' · <a href="' . $dir . '" target="_blank" rel="noopener">Itinéraire</a>'
         . ' · <button type="button" class="map-toggle" aria-expanded="false">🗺️ Voir la carte</button></div>'
         . '<div class="map-box" hidden><iframe class="map-frame" data-src="' . htmlspecialchars($embed) . '" loading="lazy" title="Carte du lieu"></iframe></div>';
};

// Historique des modifications, groupé par champ. On reconstitue la SUITE
// complète des valeurs (titre, horaire, durée, places, lieu, délai…) : toutes
// les valeurs précédentes sont BARRÉES, seule la dernière (actuelle) reste nette,
// peu importe le nombre de modifications.
$historyBlock = function ($changes) {
    if (empty($changes)) {
        return '';
    }
    $labels = [
        'title'    => '✏️ Titre',
        'horaire'  => '🗓️ Date & heure',
        'duration' => '⏱️ Durée',
        'places'   => '👥 Places',
        'location' => '📍 Lieu',
        'delai'    => '⏳ Délai',
    ];
    // Les lignes arrivent du plus récent au plus ancien → on remet en ordre
    // chronologique pour enchaîner old → new → … sans rupture.
    $asc = array_reverse($changes);
    $byField = [];
    foreach ($asc as $c) {
        $f = $c['field'] ?? 'location';
        if (!isset($byField[$f])) {
            $byField[$f] = ['seq' => [(string) $c['old_value']], 'when' => $c['changed_at']];
        }
        $byField[$f]['seq'][] = (string) $c['new_value'];
        $byField[$f]['when']  = $c['changed_at'];
    }
    $h = '<div class="hist"><div class="hist-h">🕒 Historique des modifications</div>';
    foreach ($byField as $f => $info) {
        $lbl  = $labels[$f] ?? 'Modification';
        $seq  = $info['seq'];
        $last = count($seq) - 1;
        $when = date('d/m/Y à H:i', strtotime($info['when']));
        $chain = '';
        foreach ($seq as $i => $val) {
            if ($i > 0) {
                $chain .= ' <span class="hist-arrow">→</span> ';
            }
            $cls = ($i === $last) ? 'new' : 'old';
            $chain .= '<span class="' . $cls . '">' . htmlspecialchars($val !== '' ? $val : '∅') . '</span>';
        }
        $h .= '<div class="hist-row"><b>' . $lbl . '</b> · maj le ' . htmlspecialchars($when) . '<br>' . $chain . '</div>';
    }
    return $h . '</div>';
};

// Affichage de la réputation d'un hôte en étoiles (ou « Pas encore noté »).
$starsHtml = function ($summary) {
    $count = (int) ($summary['count'] ?? 0);
    if ($count <= 0) {
        return '<span class="rating none">☆☆☆☆☆ <em>Pas encore noté</em></span>';
    }
    $avg  = (float) ($summary['avg'] ?? 0);
    $full = (int) round($avg);
    $s    = '';
    for ($i = 1; $i <= 5; $i++) {
        $s .= $i <= $full ? '★' : '☆';
    }
    return '<span class="rating"><span class="rstars">' . $s . '</span> <em>'
         . number_format($avg, 1, ',', ' ') . '/5 · ' . $count . ' avis</em></span>';
};

$uid = (int) ($user['id'] ?? 0);

// Rendu d'un créneau RÉSERVABLE (liste publique ou résultat de recherche par code).
$reserveCard = function ($a) use ($fmt, $locBlock, $historyBlock, $starsHtml, $ratings, $myBookedIds, $uid, $bookerRatings, $eventPhotos, $eventRatings, $myEventRatings) {
    $cap     = (int) $a['capacity'];
    $booked  = (int) ($a['booked'] ?? 0);
    $free    = max(0, $cap - $booked);
    $pct     = $cap > 0 ? round($booked / $cap * 100) : 0;
    $iBooked = in_array((int) $a['id'], $myBookedIds, true);
    $isOwn   = (int) $a['owner_id'] === $uid;
    $isPriv  = ($a['visibility'] ?? 'public') === 'private';
    ob_start(); ?>
    <article class="card slot<?= (int) ($a['urgent'] ?? 0) === 1 ? ' is-urgent' : '' ?>">
        <div class="slot-main">
            <?php if ((int) ($a['urgent'] ?? 0) === 1): ?><span class="urg-pill">🟥 URGENT</span><?php endif; ?>
            <div class="slot-when"><?= htmlspecialchars($fmt($a['start_at'], $a['duration_min'])) ?><?= $isPriv ? ' · 🔒 privé' : '' ?></div>
            <div class="cd-badge">⏳ <?= countdown_html($a['start_at'], (int) $a['duration_min']) ?></div>
            <div class="slot-title"><?= htmlspecialchars($a['title']) ?></div>
            <div class="slot-by">proposé par <?= htmlspecialchars($a['owner_name'] ?: 'Membre') ?></div>
            <a class="slot-detail" href="<?= url('agenda/event') ?>?id=<?= (int) $a['id'] ?>">📄 Voir la fiche complète →</a>
            <div><a class="slot-gcal" href="<?= htmlspecialchars(google_calendar_url($a['title'], $a['start_at'], (int) $a['duration_min'], $a['mode'] ?? 'presentiel', (string) ($a['location'] ?? ''))) ?>" target="_blank" rel="noopener">📅 Ajouter à Google Agenda</a></div>
            <div class="slot-rating"><?= $starsHtml($ratings[(int) $a['owner_id']] ?? []) ?></div>
            <?php $er = $eventRatings[(int) $a['id']] ?? null; if ($er && $er['count'] > 0): ?>
                <div class="evt-rating">⭐ Événement : <b><?= number_format((float) $er['avg'], 1, ',', ' ') ?>/5</b> <small>(<?= (int) $er['count'] ?> avis)</small></div>
            <?php endif; ?>
            <?php if (isset($a['distance'])): ?>
                <div class="slot-dist">📍 à <?= number_format((float) $a['distance'], 1, ',', ' ') ?> km</div>
            <?php endif; ?>
            <?php if (!empty($a['description'])): ?>
                <div class="slot-desc"><?= htmlspecialchars($a['description']) ?></div>
            <?php endif; ?>
            <?= $locBlock($a) ?>
            <?php $photos = $eventPhotos[(int) $a['id']] ?? []; if ($photos): ?>
                <div class="evt-photos">
                    <?php foreach ($photos as $ph): ?>
                        <a href="<?= url('uploads/agenda/' . $ph) ?>" target="_blank" rel="noopener">
                            <img src="<?= url('uploads/agenda/' . $ph) ?>" alt="photo de l'événement" loading="lazy">
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <?php if ((int) ($a['min_notice_hours'] ?? 0) > 0): ?>
                <div class="slot-notice">⏳ Réservation jusqu'à <b><?= htmlspecialchars(delai_texte((int) $a['min_notice_hours'])) ?></b> avant le début</div>
            <?php endif; ?>
            <?= $historyBlock($a['changes'] ?? []) ?>
            <?php
                // Inscrits visibles publiquement (uniquement si l'hôte l'a activé).
                $pubBookers = (int) ($a['show_booker_ratings'] ?? 0) === 1 ? ($a['bookers'] ?? []) : [];
            ?>
            <?php if ($pubBookers): ?>
                <div class="pub-bookers">
                    <div class="pb-h">👥 Inscrits (<?= count($pubBookers) ?>)</div>
                    <div class="pb-list">
                        <?php foreach ($pubBookers as $pb):
                            $rs = ($bookerRatings ?? [])[(int) $pb['user_id']] ?? null; ?>
                            <span class="pb">👤 <?= htmlspecialchars($pb['user_name'] ?: 'Membre') ?><?php
                                if ($rs && (int) $rs['count'] > 0): ?> <em class="bk-rate" title="<?= (int) $rs['count'] ?> avis">★ <?= number_format((float) $rs['avg'], 1, ',', ' ') ?>/5</em><?php
                                else: ?> <em class="bk-rate none">non noté</em><?php endif; ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <div class="slot-side">
            <span class="places <?= $free > 0 ? 'free' : 'full' ?>"><?= $booked ?>/<?= $cap ?> · <?= $free > 0 ? $free . ' libre' . ($free > 1 ? 's' : '') : 'complet' ?></span>
            <div class="meter"><i style="width:<?= $pct ?>%"></i></div>
            <span class="pct-label"><?= $pct ?>% occupé</span>
            <?php if ($isOwn): ?>
                <span class="places">C'est ton créneau</span>
            <?php elseif ($iBooked): ?>
                <form method="post" action="<?= url('agenda/cancel') ?>">
                    <input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
                    <button class="act cancel" type="submit">✓ Réservé — annuler</button>
                </form>
            <?php elseif ($free <= 0): ?>
                <button class="act" type="button" disabled>Complet</button>
            <?php else: ?>
                <form method="post" action="<?= url('agenda/book') ?>">
                    <input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
                    <button class="act book" type="submit">Réserver une place</button>
                </form>
            <?php endif; ?>
            <?php if ($iBooked && strtotime($a['start_at']) <= time()): $myr = $myEventRatings[(int) $a['id']] ?? 0; ?>
                <form method="post" action="<?= url('agenda/rate_event') ?>" class="evt-rate-form">
                    <input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
                    <div class="evt-rate-label"><?= $myr ? 'Ta note :' : "Noter l'événement :" ?></div>
                    <div class="evt-stars">
                        <?php for ($s = 1; $s <= 5; $s++): ?>
                            <button type="submit" name="stars" value="<?= $s ?>" class="evt-star<?= $s <= $myr ? ' on' : '' ?>" title="<?= $s ?>/5">★</button>
                        <?php endfor; ?>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </article>
    <?php return ob_get_clean();
};

// Aujourd'hui par défaut dans le formulaire.
$today = date('Y-m-d');

// ----- Aperçu calendrier (style Google Agenda) -----
$activeTab     = $activeTab ?? 'find';
$calShowPublic = $calShowPublic ?? false;
$calBase   = url('agenda');
$moisNoms  = [1 => 'janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
$joursLong = ['Sun' => 'dimanche', 'Mon' => 'lundi', 'Tue' => 'mardi', 'Wed' => 'mercredi', 'Thu' => 'jeudi', 'Fri' => 'vendredi', 'Sat' => 'samedi'];
$jShort    = ['Mon' => 'lun.', 'Tue' => 'mar.', 'Wed' => 'mer.', 'Thu' => 'jeu.', 'Fri' => 'ven.', 'Sat' => 'sam.', 'Sun' => 'dim.'];
// Classe couleur d'un événement : mon créneau / ma réservation / public (autres).
$calCls = static function ($e) {
    $k = $e['cal_kind'] ?? '';
    return $k === 'mine' ? 'mine' : ($k === 'public' ? 'public' : 'booked');
};
// Lien de navigation du calendrier (conserve l'onglet « Calendrier » actif et
// l'option « événements publics » si elle est activée).
$calUrl = function (string $view, array $params = []) use ($calBase, $calShowPublic) {
    $q = array_merge(['tab' => 'cal', 'cview' => $view], $params);
    if ($calShowPublic) { $q['cpublic'] = 1; }
    return $calBase . '?' . http_build_query($q) . '#panel-cal';
};
// Lien vers le formulaire « Proposer » avec une date pré-remplie (clic « + Ajouter »).
$calAddUrl = function (string $date) use ($calBase) {
    return $calBase . '?' . http_build_query(['tab' => 'new', 'date' => $date]) . '#panel-new';
};
// Date pré-remplie du formulaire de création (si on vient d'un « + Ajouter »).
$prefillDate = (isset($_GET['date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $_GET['date']) && $_GET['date'] >= $today)
    ? (string) $_GET['date'] : $today;

// Options du délai minimum de réservation (heures => libellé), réutilisées
// dans le formulaire de création ET la modification depuis « Mes créneaux ».
$delaiOpts = [
    0 => 'Aucun (jusqu\'au début)', 1 => '1 h avant', 2 => '2 h avant', 3 => '3 h avant',
    6 => '6 h avant', 12 => '12 h avant', 24 => '1 jour avant', 48 => '2 jours avant',
    72 => '3 jours avant', 168 => '1 semaine avant',
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(Settings::get('main_title', 'RPN')) ?> — Agenda & rendez-vous</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <?= Theme::css() ?>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <style>
        * { box-sizing:border-box; margin:0; padding:0; }
        body {
            font-family:'Poppins',sans-serif; min-height:100vh; color:var(--text); padding:42px 20px 60px;
            background:
                radial-gradient(circle at 12% 0%, var(--glow1), transparent 42%),
                radial-gradient(circle at 88% 100%, var(--glow2), transparent 44%),
                var(--bg-base);
        }
        body::before { content:""; position:fixed; top:0; left:0; right:0; height:6px; background:var(--bar); }
        .wrap { max-width:880px; margin:0 auto; }
        .topbar { display:flex; justify-content:space-between; align-items:center; gap:16px; flex-wrap:wrap; margin-bottom:24px; }
        h1 { font-size:24px; font-weight:800; } h1 span { color:var(--accent); }
        .nav { display:flex; flex-wrap:wrap; gap:8px; align-items:center; }
        .nav a { color:var(--text); text-decoration:none; font-size:14px; padding:9px 16px; border-radius:10px; border:1px solid var(--card-border); }
        .nav a:hover { border-color:var(--accent); color:var(--accent); }
        .nav-badge { display:inline-block; min-width:18px; padding:0 5px; height:18px; line-height:18px; text-align:center;
            border-radius:999px; background:var(--rouge); color:#fff; font-size:11px; font-weight:700; }
        /* Mobile : le titre seul en haut, la barre de liens passe DESSOUS, aérée. */
        @media (max-width:640px) {
            .topbar { flex-direction:column; align-items:stretch; gap:14px; margin-bottom:18px; }
            .nav { gap:10px; }
            .nav a { flex:1 1 auto; text-align:center; }
        }

        .card { background:var(--card-bg); border:1px solid var(--card-border); border-radius:18px; box-shadow:var(--card-shadow); }
        .section-title { font-size:12px; text-transform:uppercase; letter-spacing:1.5px; color:var(--muted); margin:26px 4px 14px; }

        .flash { padding:12px 16px; border-radius:12px; margin-bottom:18px; font-size:14px; }
        .flash.ok { background:rgba(42,157,74,.15); border:1px solid rgba(42,157,74,.45); color:#2a9d4a; }
        .flash.err { background:rgba(230,57,70,.15); border:1px solid rgba(230,57,70,.4); color:#e0566a; }

        /* Formulaire de création */
        .form-card { padding:24px 26px; }
        .grid { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
        .grid .full { grid-column:1 / -1; }
        @media (max-width:560px) { .grid { grid-template-columns:1fr; } }
        label { display:block; font-size:13px; margin:0 0 6px 2px; color:var(--muted); }
        input, textarea, select { width:100%; padding:11px 13px; border-radius:10px; border:1px solid var(--card-border);
            background:rgba(127,127,127,.08); color:var(--text); font-size:14px; font-family:inherit; }
        input:focus, textarea:focus, select:focus { outline:none; border-color:var(--accent); }
        select option { background:var(--bg-base); color:var(--text); }
        textarea { resize:vertical; min-height:64px; }
        .hint-sm { font-size:11px; color:var(--muted); margin:6px 0 0 2px; }
        /* Toggle segmenté (boutons radio stylés, fonctionne sans JS) */
        .seg { display:flex; border:1px solid var(--card-border); border-radius:10px; overflow:hidden; }
        .seg input { position:absolute; opacity:0; width:0; height:0; pointer-events:none; }
        .seg label { flex:1; text-align:center; padding:11px 8px; margin:0; cursor:pointer; font-size:13px; font-weight:600;
            color:var(--muted); background:rgba(127,127,127,.05); transition:background .15s, color .15s; }
        .seg label + input + label, .seg input + label + input { }
        .seg label:not(:first-of-type) { border-left:1px solid var(--card-border); }
        .seg input:checked + label { background:var(--accent); color:var(--accent-ink); }
        .seg input:focus-visible + label { outline:2px solid var(--accent); outline-offset:-2px; }
        .seg-radius label { padding:11px 4px; font-size:12px; }
        .slot-dist { font-size:13px; color:var(--accent); font-weight:700; margin-top:2px; }
        /* Pagination */
        .pager { display:flex; align-items:center; justify-content:center; gap:14px; margin-top:18px; flex-wrap:wrap; }
        .pager .pg { text-decoration:none; font-size:14px; font-weight:600; color:var(--text);
            border:1px solid var(--card-border); border-radius:10px; padding:9px 16px; }
        .pager .pg:hover { border-color:var(--accent); color:var(--accent); }
        .pager .pg.disabled { opacity:.4; pointer-events:none; }
        .pager .pg-info { font-size:13px; color:var(--muted); }
        .btn { padding:12px 24px; border:none; border-radius:10px; cursor:pointer; font-family:inherit;
            font-weight:700; font-size:14px; color:var(--accent-ink); background:var(--accent); margin-top:4px; }
        .btn:hover { filter:brightness(1.05); }

        /* Liste des créneaux */
        .slots { display:flex; flex-direction:column; gap:12px; }
        .slot { padding:16px 18px; display:flex; align-items:flex-start; gap:16px; flex-wrap:wrap; }
        .slot-main { flex:1; min-width:200px; }
        .slot-when { font-size:12px; color:var(--accent); font-weight:600; text-transform:uppercase; letter-spacing:.5px; }
        .cd-badge { display:inline-flex; align-items:center; gap:5px; margin:4px 0 2px; font-size:13px; font-weight:700; color:var(--accent); }
        .countdown { font-variant-numeric:tabular-nums; }
        .countdown.soon { color:var(--rouge); font-family:'Courier New', monospace; letter-spacing:1px; font-size:15px; }
        .countdown.done { color:#2a9d4a; font-weight:700; }
        /* « En direct » : pastille rouge bien visible qui clignote pendant le créneau. */
        .countdown.live { color:#fff; background:var(--rouge); font-weight:800; letter-spacing:.5px;
            padding:2px 10px; border-radius:999px; animation:cd-blink 1s steps(1,end) infinite; }
        @keyframes cd-blink { 50% { opacity:.25; } }
        .slot-title { font-size:17px; font-weight:700; margin:3px 0 4px; }
        .slot-by { font-size:13px; color:var(--muted); }
        .slot-desc { font-size:13px; color:var(--muted); margin-top:6px; line-height:1.5; white-space:pre-line; }
        .slot-code { font-size:13px; color:var(--muted); margin:4px 0 2px; }
        .slot-code b { color:var(--accent); letter-spacing:1px; font-size:15px; }
        /* Note + photos d'un événement */
        .evt-rating { font-size:13px; color:var(--text); margin:2px 0; }
        .evt-rating b { color:var(--or, var(--accent)); }
        .evt-photos { display:flex; gap:8px; flex-wrap:wrap; margin:10px 0; }
        .evt-photos a { display:block; }
        .evt-photos img { width:92px; height:92px; object-fit:cover; border-radius:10px;
            border:1px solid var(--card-border); background:rgba(127,127,127,.1); }
        .evt-rate-form { margin-top:10px; text-align:center; }
        .evt-rate-label { font-size:12px; color:var(--muted); margin-bottom:2px; }
        .evt-stars { display:inline-flex; gap:2px; }
        .evt-star { background:none; border:none; cursor:pointer; font-size:20px; line-height:1;
            color:var(--card-border); padding:0 1px; transition:color .12s; }
        .evt-star.on { color:var(--or, #f4c14b); }
        .evt-star:hover { color:var(--or, #f4c14b); transform:scale(1.15); }
        .prot-badge { font-size:11px; color:var(--or, #f4c14b); border:1px solid var(--card-border); border-radius:6px; padding:1px 7px; margin-left:4px; }
        .vis-btn.prot-on { border-color:var(--or, #f4c14b); color:var(--or, #f4c14b); }
        .vis-btn.urg-on { border-color:#e63946; color:#e63946; }
        .prot-badge.urg { color:#fff; background:#e63946; border-color:#e63946; font-weight:800; }
        .copy-code { cursor:pointer; font-family:inherit; font-size:11px; font-weight:600; color:var(--accent);
            background:rgba(127,127,127,.10); border:1px solid var(--card-border); border-radius:7px; padding:2px 8px; margin-left:6px; }
        .copy-code:hover { border-color:var(--accent); }
        .vis-toggle { display:inline; margin-left:8px; }
        .vis-btn { cursor:pointer; font-family:inherit; font-size:11px; font-weight:600; color:var(--accent);
            background:rgba(127,127,127,.10); border:1px solid var(--card-border); border-radius:999px; padding:3px 11px; }
        .vis-btn:hover { border-color:var(--accent); }
        .search-row { display:flex; gap:10px; flex-wrap:wrap; }
        .search-row input { max-width:200px; text-transform:uppercase; letter-spacing:2px; font-weight:700; }
        .slot-notice { font-size:13px; color:var(--text); margin-top:8px; }
        .slot-notice b { color:var(--accent); }
        select[multiple] { height:auto; padding:6px; }
        select[multiple] option { padding:6px 8px; border-radius:6px; }
        .slot-loc { font-size:13px; color:var(--text); margin-top:8px; }
        .slot-loc a { color:var(--accent); text-decoration:none; }
        .slot-loc a:hover { text-decoration:underline; }
        .map-toggle { cursor:pointer; font-family:inherit; font-size:12px; font-weight:600; color:var(--accent);
            background:rgba(127,127,127,.10); border:1px solid var(--card-border); border-radius:8px; padding:3px 9px; }
        .map-toggle:hover { border-color:var(--accent); }
        .map-box { margin-top:10px; border:1px solid var(--card-border); border-radius:12px; overflow:hidden; }
        .map-frame { display:block; width:100%; height:300px; border:0; }

        /* Historique du lieu */
        .hist { margin-top:10px; padding:10px 12px; border:1px dashed var(--card-border); border-radius:10px; background:rgba(127,127,127,.04); }
        .hist-h { font-size:11px; text-transform:uppercase; letter-spacing:.5px; color:var(--muted); margin-bottom:6px; }
        .hist-row { font-size:12px; color:var(--muted); margin:3px 0; line-height:1.45; }
        .hist-row .old { color:var(--muted); text-decoration:line-through; }
        .hist-row .new { color:var(--accent); font-weight:600; }
        .hist-row .hist-arrow { color:var(--muted); margin:0 2px; }
        /* Événements passés (archive, lecture seule) */
        .slot.is-past { opacity:.9; }
        .past-tag { display:inline-block; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.4px;
            color:var(--muted); border:1px solid var(--card-border); border-radius:999px; padding:1px 8px; }

        /* Modifier le lieu */
        .edit-loc { margin-top:10px; }
        .edit-loc summary { cursor:pointer; font-size:13px; font-weight:600; color:var(--accent); list-style:none; }
        .edit-loc summary::-webkit-details-marker { display:none; }
        .edit-loc-form { margin-top:10px; display:flex; flex-direction:column; gap:10px; max-width:420px; }
        .edit-rdv label { color:var(--muted); }
        .edit-rdv-row { display:flex; gap:10px; flex-wrap:wrap; }
        .edit-rdv-row label { flex:1; min-width:90px; margin:0; }

        /* Ajout d'un membre par l'hôte */
        .add-member { margin-top:10px; }
        .add-member summary { cursor:pointer; font-size:13px; font-weight:600; color:var(--accent); list-style:none; }
        .add-member summary::-webkit-details-marker { display:none; }
        .add-member-form { margin-top:10px; display:flex; gap:10px; flex-wrap:wrap; align-items:center; }
        .add-member-form select { flex:1; min-width:180px; padding:9px 12px; border-radius:10px; border:1px solid var(--card-border);
            background:rgba(127,127,127,.08); color:var(--text); font-family:inherit; font-size:13px; }
        .add-member-form select option { background:var(--bg-base); color:var(--text); }
        .add-member-form .am-or { font-size:12px; color:var(--muted); }
        .add-member-form .am-code { width:110px; padding:9px 12px; border-radius:10px; border:1px solid var(--card-border);
            background:rgba(127,127,127,.08); color:var(--text); font-family:inherit; font-size:13px; text-transform:uppercase; }
        .hint-mini { display:block; margin-top:6px; font-size:11px; color:var(--muted); }
        /* Liste claire des membres autorisés (cases à cocher nom + code) */
        .member-pick { display:flex; flex-direction:column; gap:6px; max-height:240px; overflow-y:auto;
            border:1px solid var(--card-border); border-radius:12px; padding:8px; background:rgba(127,127,127,.05); }
        .mp-item { display:flex; align-items:center; gap:10px; padding:8px 10px; border-radius:9px; cursor:pointer; font-size:14px; }
        .mp-item:hover { background:rgba(127,127,127,.10); }
        .mp-item input { width:17px; height:17px; accent-color:var(--accent); flex:0 0 auto; }
        .mp-name { flex:1; font-weight:600; }
        .mp-code { font-family:Consolas,Monaco,monospace; font-size:12px; color:var(--accent);
            border:1px solid var(--card-border); border-radius:6px; padding:1px 7px; }

        /* Réglage du nombre de places (− / +) */
        .cap-ctrl { display:flex; align-items:center; gap:8px; }
        .cap-btn { width:30px; height:30px; border-radius:9px; border:1px solid var(--card-border); cursor:pointer;
            background:rgba(127,127,127,.10); color:var(--text); font-size:18px; line-height:1; font-family:inherit;
            display:flex; align-items:center; justify-content:center; padding:0; }
        .cap-btn:hover { border-color:var(--accent); color:var(--accent); }
        .cap-btn:disabled { opacity:.4; cursor:not-allowed; }
        .cap-val { font-size:13px; font-weight:600; color:var(--text); min-width:64px; text-align:center; }
        .slot-side { display:flex; flex-direction:column; align-items:flex-end; gap:8px; }

        .places { font-size:13px; font-weight:700; padding:6px 12px; border-radius:999px; border:1px solid var(--card-border); white-space:nowrap; }
        .places.free { color:#2a9d4a; }
        .places.full { color:var(--rouge); }

        .meter { width:120px; height:8px; border-radius:99px; background:rgba(127,127,127,.18); overflow:hidden; }
        .meter > i { display:block; height:100%; background:var(--accent); }
        .pct-label { font-size:11px; font-weight:700; color:var(--muted); margin-top:3px; }
        .slot-detail { display:inline-block; margin-top:6px; font-size:12.5px; color:var(--accent); text-decoration:none; font-weight:600; }
        .slot-detail:hover { text-decoration:underline; }
        .slot-gcal { display:inline-flex; align-items:center; gap:7px; margin-top:8px; font-size:12.5px; font-weight:700;
            text-decoration:none; color:#fff; background:#1a73e8; border-radius:9px; padding:8px 14px;
            box-shadow:0 6px 16px rgba(26,115,232,.45); transition:transform .15s, box-shadow .2s, filter .15s; }
        .slot-gcal:hover { transform:translateY(-1px); filter:brightness(1.06); box-shadow:0 10px 24px rgba(26,115,232,.55); }
        .urg-pill { display:inline-block; background:#e63946; color:#fff; font-weight:800; font-size:11px;
            padding:4px 9px; border-radius:6px; letter-spacing:.5px; margin-bottom:6px; }
        .slot.is-urgent { border-color:#e63946; box-shadow:0 0 0 1px #e63946 inset; }
        .evt-search { margin-bottom:14px; }
        .evt-search input { width:100%; padding:12px 16px; border-radius:12px; border:1px solid var(--card-border);
            background:var(--card-bg); color:var(--text); font-family:inherit; font-size:15px; }
        .evt-search input:focus { outline:none; border-color:var(--accent); }

        .act { padding:9px 16px; border:none; border-radius:10px; cursor:pointer; font-family:inherit; font-weight:600; font-size:13px; color:#fff; }
        .act.book { background:var(--accent); color:var(--accent-ink); }
        .act.cancel { background:#64748b; }
        .act.del { background:var(--rouge); }
        .act:hover { filter:brightness(1.08); }
        .act:disabled { background:rgba(127,127,127,.25); color:var(--muted); cursor:not-allowed; }

        .bookers { margin-top:10px; width:100%; }
        .bookers .bk-h { font-size:12px; color:var(--muted); margin-bottom:6px; display:flex; align-items:center; gap:12px; flex-wrap:wrap; }
        .bk-note-hint { font-size:11px; color:var(--muted); margin:-2px 0 8px; font-style:italic; }
        /* Inscrits affichés sur la fiche publique (quand l'hôte l'autorise) */
        .pub-bookers { margin-top:10px; padding:10px 12px; border:1px solid var(--card-border); border-radius:12px; background:rgba(127,127,127,.05); }
        .pub-bookers .pb-h { font-size:12px; color:var(--muted); margin-bottom:6px; }
        .pub-bookers .pb-list { display:flex; flex-wrap:wrap; gap:6px 14px; }
        .pub-bookers .pb { font-size:13px; }
        .bk-toggle-wrap { display:inline-flex; align-items:center; gap:6px; }
        .bk-toggle { margin:0; }
        .bk-toggle-btn { cursor:pointer; font-family:inherit; font-size:11px; font-weight:700; border-radius:999px; padding:4px 11px;
            border:1px solid var(--card-border); }
        .bk-toggle-btn.on  { color:#2a9d4a; background:rgba(42,157,74,.12); border-color:rgba(42,157,74,.4); }
        .bk-toggle-btn.off { color:var(--muted); background:rgba(127,127,127,.12); }
        .bk-toggle-btn:hover { filter:brightness(1.05); border-color:var(--accent); }
        .bk-list { display:flex; flex-direction:column; gap:7px; }
        .bk { display:flex; align-items:center; justify-content:space-between; gap:10px; flex-wrap:wrap;
            font-size:13px; padding:7px 12px; border-radius:12px; background:rgba(127,127,127,.10); border:1px solid var(--card-border); }
        .bk-name { font-weight:500; }
        .bk-rate { font-style:normal; font-weight:700; color:#f4b400; margin-left:4px; }
        .bk-rate.none { color:var(--muted); font-weight:500; }
        .bk-pres { display:inline-flex; align-items:center; gap:6px; }
        .pres-form { margin:0; }
        .pres-wait { font-size:11px; color:var(--muted); font-style:italic; }
        .pres-btn { cursor:pointer; font-family:inherit; font-size:11px; font-weight:700; border-radius:999px; padding:4px 11px;
            border:1px solid var(--card-border); background:rgba(127,127,127,.10); color:var(--muted); }
        .pres-btn:hover { border-color:var(--accent); }
        .pres-btn.ok.on  { color:#fff; background:#2a9d4a; border-color:#2a9d4a; }
        .pres-btn.no.on  { color:#fff; background:var(--rouge); border-color:var(--rouge); }

        /* Case à cocher d'option (afficher la note des inscrits) */
        .check-line { display:flex; align-items:flex-start; gap:10px; cursor:pointer; font-size:14px; color:var(--text); }
        .check-line input { width:18px; height:18px; margin-top:2px; accent-color:var(--accent); flex:0 0 auto; }
        .check-line small { display:block; color:var(--muted); font-size:12px; margin-top:2px; }
        .empty { color:var(--muted); font-size:14px; padding:14px 4px; }

        /* Onglets */
        .tabs { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:22px; }
        .tabs .tab { cursor:pointer; font-family:inherit; font-size:14px; font-weight:600; color:var(--text);
            background:var(--card-bg); border:1px solid var(--card-border); border-radius:12px; padding:11px 18px;
            transition:border-color .15s, color .15s, background .15s; }
        .tabs .tab:hover { border-color:var(--accent); }
        .tabs .tab.active { background:var(--accent); color:var(--accent-ink); border-color:var(--accent); }
        /* Onglet de création : vert, bien visible (action « créer ») */
        .tabs .tab.create { background:var(--vert,#2a9d4a); color:#fff; border-color:var(--vert,#2a9d4a);
            box-shadow:0 6px 18px rgba(42,157,74,.35); }
        .tabs .tab.create:hover { filter:brightness(1.08); border-color:var(--vert,#2a9d4a); }
        .tabs .tab.create.active { background:var(--vert,#2a9d4a); color:#fff; border-color:var(--vert,#2a9d4a); }
        /* Bouton de validation « Créer l'événement » en vert */
        .btn.create-go { background:var(--vert,#2a9d4a); color:#fff; box-shadow:0 8px 22px rgba(42,157,74,.35); }
        .tabs .tab .count { opacity:.85; font-weight:700; }
        .tab-panel { display:none; }
        .tab-panel.active { display:block; }
        .first-title { margin-top:6px; }

        /* Carte des résultats (Leaflet, multi-marqueurs) */
        #resultsMap { height:340px; border-radius:16px; overflow:hidden; border:1px solid var(--card-border); margin:14px 0; }
        .leaflet-popup-content { font-family:'Poppins',sans-serif; font-size:13px; }
        .near-actions { display:flex; gap:10px; flex-wrap:wrap; align-items:center; margin-top:14px; }
        .geo-btn { cursor:pointer; font-family:inherit; font-size:13px; font-weight:600; color:var(--accent);
            background:rgba(127,127,127,.08); border:1px solid var(--card-border); border-radius:10px; padding:12px 16px; }
        .geo-btn:hover { border-color:var(--accent); }
        /* Réglage « ville par défaut » */
        .default-city { margin-top:12px; }
        .default-city > summary { cursor:pointer; font-size:13px; font-weight:600; color:var(--accent); list-style:none;
            padding:10px 14px; border:1px solid var(--card-border); border-radius:10px; background:var(--card-bg); display:inline-block; }
        .default-city > summary::-webkit-details-marker { display:none; }
        .default-city[open] > summary { border-color:var(--accent); }
        .dc-wrap { display:flex; gap:10px; flex-wrap:wrap; align-items:center; margin-top:12px; }
        .dc-form { display:flex; gap:10px; flex-wrap:wrap; align-items:center; flex:1; min-width:240px; }
        .dc-form .loc-input { flex:1; min-width:160px; }
        .dc-clear { margin:0; }
        .slot-anchor { scroll-margin-top:80px; border-radius:18px; transition:box-shadow .2s; }
        .slot-anchor.hi { box-shadow:0 0 0 3px var(--accent); }
        .map-pop b { font-size:14px; }
        .map-pop-head { font-weight:700; font-size:12px; color:#777; margin-bottom:6px; }
        .map-pop-item { padding:8px 0; border-top:1px solid #eee; }
        .map-pop-item:first-of-type { border-top:none; padding-top:0; }
        .map-pop-meta { margin:4px 0 2px; color:#333; font-size:12.5px; line-height:1.5; }
        .map-count { background:var(--rouge); color:#fff; border:2px solid #fff; border-radius:999px;
            font-weight:800; font-size:11px; padding:1px 6px; box-shadow:0 2px 6px rgba(0,0,0,.4); }
        .map-count::before { display:none; }
        .map-pop-btn { display:inline-block; margin-top:8px; cursor:pointer; font-family:'Poppins',sans-serif;
            font-size:12px; font-weight:700; color:var(--accent-ink); background:var(--accent);
            border:none; border-radius:8px; padding:7px 12px; }

        /* Notation (étoiles) */
        .slot-rating { margin-top:3px; }
        .rating { font-size:13px; }
        .rating .rstars { color:#f4b400; letter-spacing:1px; }
        .rating em { color:var(--muted); font-style:normal; font-size:12px; }
        .rating.none { color:var(--card-border); }
        .rating.none em { color:var(--muted); }
        .booked-wrap { display:flex; flex-direction:column; gap:0; }
        .rate-form { display:flex; align-items:center; gap:10px; flex-wrap:wrap; margin:-4px 0 4px;
            padding:10px 14px; background:rgba(127,127,127,.05); border:1px solid var(--card-border); border-radius:12px; }
        .rate-label { font-size:13px; color:var(--muted); }
        .stars-input { display:inline-flex; flex-direction:row-reverse; }
        .stars-input input { display:none; }
        .stars-input label { font-size:24px; line-height:1; color:var(--card-border); cursor:pointer; padding:0 1px; transition:color .1s; }
        .stars-input input:checked ~ label,
        .stars-input label:hover, .stars-input label:hover ~ label { color:#f4b400; }

        /* ============ Aperçu calendrier (style Google Agenda) ============ */
        /* Sélecteur de vue Jour / Semaine / Mois / Liste */
        .view-switch { display:inline-flex; gap:6px; margin:0 0 6px; padding:5px; border-radius:12px;
            background:var(--card-bg); border:1px solid var(--card-border); flex-wrap:wrap; }
        .vs-btn { cursor:pointer; font-family:inherit; font-size:13px; font-weight:600; color:var(--text);
            background:transparent; border:none; border-radius:9px; padding:9px 16px; text-decoration:none; }
        .vs-btn:hover { color:var(--accent); }
        .vs-btn.active { background:var(--accent); color:var(--accent-ink); }
        .cal-panel { margin-top:8px; }

        /* Barre de navigation (précédent / pastille / suivant / aujourd'hui) */
        .cal-bar { display:flex; align-items:center; gap:12px; margin:14px 0; flex-wrap:wrap; }
        .cal-nav { text-decoration:none; font-size:20px; font-weight:700; line-height:1; color:var(--text);
            width:40px; height:40px; display:flex; align-items:center; justify-content:center;
            border:1px solid var(--card-border); border-radius:10px; }
        .cal-nav:hover { border-color:var(--accent); color:var(--accent); }
        .cal-pill { font-size:16px; font-weight:800; color:var(--accent-ink); background:var(--accent);
            padding:9px 20px; border-radius:999px; text-transform:capitalize; min-width:170px; text-align:center; }
        .cal-today { margin-left:auto; text-decoration:none; font-size:13px; font-weight:600; color:var(--text);
            border:1px solid var(--card-border); border-radius:10px; padding:9px 14px; }
        .cal-today:hover { border-color:var(--accent); color:var(--accent); }

        /* Grille du mois */
        .cal-grid { display:grid; grid-template-columns:repeat(7, 1fr); gap:6px; }
        .cal-head { margin-bottom:6px; }
        .cal-dow { text-align:center; font-size:12px; font-weight:700; color:var(--muted); text-transform:uppercase; letter-spacing:.5px; padding:4px 0; }
        .cal-cell { position:relative; min-height:96px; background:var(--card-bg); border:1px solid var(--card-border); border-radius:12px;
            padding:6px 7px; display:flex; flex-direction:column; gap:4px; overflow:hidden; text-decoration:none; color:inherit; }
        .cal-cell:hover { border-color:var(--accent); }
        .cal-cell-link { position:absolute; inset:0; z-index:0; border-radius:12px; }
        .cal-cell.empty { background:transparent; border:1px dashed var(--card-border); opacity:.4; }
        .cal-cell.today { border-color:var(--accent); box-shadow:0 0 0 2px var(--accent) inset; }
        /* Le numéro et les événements laissent passer le clic vers le lien de couverture. */
        .cal-num, .cal-events { position:relative; z-index:1; pointer-events:none; }
        /* Bouton « + Ajouter » : apparaît au survol de la case. */
        .cal-add { position:absolute; top:5px; right:6px; z-index:2; opacity:0; transition:opacity .12s;
            font-size:11px; font-weight:700; text-decoration:none; color:var(--accent-ink); background:var(--accent);
            border-radius:8px; padding:3px 8px; box-shadow:0 2px 6px rgba(0,0,0,.2); }
        .cal-cell:hover .cal-add { opacity:1; }
        .cal-add:hover { filter:brightness(1.06); }
        .cal-num { font-size:13px; font-weight:700; display:flex; align-items:center; justify-content:space-between; }
        .cal-count { font-size:10px; font-weight:700; color:var(--accent-ink); background:var(--accent);
            border-radius:999px; padding:1px 7px; }
        .cal-events { display:flex; flex-direction:column; gap:3px; }
        .cal-ev { font-size:10.5px; line-height:1.25; padding:2px 5px; border-radius:6px; white-space:nowrap;
            overflow:hidden; text-overflow:ellipsis; border-left:3px solid var(--accent); background:rgba(127,127,127,.10); }
        .cal-ev.mine   { border-left-color:var(--accent); background:rgba(244,193,75,.16); }
        .cal-ev.booked { border-left-color:#2a9d4a; background:rgba(42,157,74,.12); }
        .cal-ev.public { border-left-color:#0a9bdc; background:rgba(10,155,220,.12); }
        .cal-more { font-size:10px; color:var(--muted); padding-left:4px; }
        .cal-legend { display:flex; gap:18px; flex-wrap:wrap; margin-top:14px; font-size:12px; color:var(--muted); }
        .cal-legend i.lg { display:inline-block; width:12px; height:12px; border-radius:3px; margin-right:5px; vertical-align:-1px; }
        .cal-legend i.mine   { background:var(--accent); }
        .cal-legend i.booked { background:#2a9d4a; }
        .cal-legend i.public { background:#0a9bdc; }
        .cal-hint { margin-left:auto; font-style:italic; }

        /* Case à cocher « afficher tous les événements publics » */
        .cal-toggle { display:inline-flex; align-items:center; gap:11px; cursor:pointer; margin:4px 0 2px;
            padding:11px 15px; border:1px solid var(--card-border); border-radius:12px; background:var(--card-bg);
            font-size:13px; color:var(--text); }
        .cal-toggle:hover { border-color:var(--accent); }
        .cal-toggle.on { border-color:var(--accent); background:rgba(244,193,75,.10); }
        .cal-toggle input { width:20px; height:20px; accent-color:var(--accent); cursor:pointer; flex:0 0 auto; }
        .cal-toggle small { display:block; color:var(--muted); font-size:11px; margin-top:2px; }
        /* Bouton « + Ajouter ce jour » (vue Jour) */
        .cal-addday { text-decoration:none; font-size:13px; font-weight:700; color:var(--accent-ink); background:var(--accent);
            border-radius:10px; padding:9px 14px; }
        .cal-addday:hover { filter:brightness(1.06); }

        /* Vues Semaine & Jour (grille horaire) */
        .wk-scroll { overflow-x:auto; }
        .wk-head { display:flex; min-width:560px; }
        .wk-head.day-head-row { min-width:0; }
        .wk-corner { flex:0 0 54px; }
        .wk-dayhead { flex:1; text-align:center; padding:8px 4px; border-bottom:1px solid var(--card-border); text-decoration:none; color:inherit; }
        a.wk-dayhead:hover { color:var(--accent); }
        .wk-dayhead.today { color:var(--accent); }
        .wk-dn { display:block; font-size:11px; text-transform:uppercase; letter-spacing:.5px; color:var(--muted); }
        .wk-dayhead.today .wk-dn { color:var(--accent); }
        .wk-dd { display:block; font-size:18px; font-weight:800; }
        .wk-body { display:flex; position:relative; min-width:560px; }
        .wk-body.day-body { min-width:0; }
        .wk-gutter { flex:0 0 54px; position:relative; }
        .wk-hour { position:absolute; right:7px; font-size:11px; color:var(--muted); transform:translateY(-50%); white-space:nowrap; }
        .wk-col { flex:1; position:relative; border-left:1px solid var(--card-border); }
        .wk-col.today { background:rgba(127,127,127,.05); }
        .wk-line { position:absolute; left:0; right:0; border-top:1px solid var(--card-border); opacity:.5; }
        .wk-ev { position:absolute; left:3px; right:3px; padding:3px 6px; border-radius:7px; overflow:hidden;
            font-size:11px; line-height:1.3; color:#fff; background:#2a9d4a; box-shadow:0 2px 6px rgba(0,0,0,.25); cursor:default; }
        .wk-ev b { font-weight:700; }
        .wk-ev.mine   { background:var(--accent); color:var(--accent-ink); }
        .wk-ev.booked { background:#2a9d4a; }
        .wk-ev.public { background:#0a9bdc; }
        @media (max-width:620px) {
            .cal-cell { min-height:70px; }
            .cal-ev { font-size:0; padding:3px; }
            .cal-ev::before { content:""; display:block; width:100%; height:5px; border-radius:3px; background:currentColor; }
            .cal-pill { min-width:130px; font-size:14px; }
        }

        /* Liste / agenda chronologique */
        .day-head { font-size:14px; font-weight:700; color:var(--accent); margin:20px 2px 10px;
            padding-bottom:6px; border-bottom:1px solid var(--card-border); text-transform:capitalize; }
        .day-slots { display:flex; flex-direction:column; gap:10px; }
        .g-slot { display:flex; align-items:center; gap:16px; padding:13px 16px; background:var(--card-bg);
            border:1px solid var(--card-border); border-radius:14px; box-shadow:var(--card-shadow); }
        .g-slot.past { opacity:.6; }
        .g-time { flex:0 0 auto; font-size:13px; font-weight:700; color:var(--text); width:104px; }
        .g-body { flex:1; min-width:0; }
        .g-title { font-size:15px; font-weight:700; display:flex; align-items:center; gap:8px; flex-wrap:wrap; }
        .g-meta { font-size:12px; color:var(--muted); margin-top:2px; }
        .g-tag { font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.5px; padding:2px 8px; border-radius:999px; }
        .g-tag.mine   { background:var(--accent); color:var(--accent-ink); }
        .g-tag.booked { background:rgba(42,157,74,.18); color:#2a9d4a; }
    </style>
</head>
<body>
    <div class="wrap">
        <header class="topbar">
            <h1>📅 <span>Agenda</span> & rendez-vous</h1>
            <div class="nav">
                <a href="<?= url('agenda/global') ?>">🗓️ Agenda global</a>
                <a href="<?= url('notifications') ?>">🔔 Notifications<?php if (!empty($unreadNotifs)): ?> <span class="nav-badge"><?= (int) $unreadNotifs > 9 ? '9+' : (int) $unreadNotifs ?></span><?php endif; ?></a>
                <a href="<?= url('dashboard') ?>">← Tableau de bord</a>
            </div>
        </header>

        <?php if (!empty($notice)): ?><div class="flash ok"><?= htmlspecialchars($notice) ?></div><?php endif; ?>
        <?php if (!empty($error)): ?><div class="flash err"><?= htmlspecialchars($error) ?></div><?php endif; ?>

        <?= meet_link_widget() ?>

        <div class="tabs">
            <button type="button" class="tab<?= $activeTab === 'find' ? ' active' : '' ?>" data-tab="find">🔎 Trouver un cours</button>
            <button type="button" class="tab<?= $activeTab === 'cal' ? ' active' : '' ?>" data-tab="cal">🗓️ Calendrier</button>
            <button type="button" class="tab<?= $activeTab === 'book' ? ' active' : '' ?>" data-tab="book">📅 Mes réservations <span class="count">(<?= count($myBookings) ?>)</span></button>
            <button type="button" class="tab<?= $activeTab === 'mine' ? ' active' : '' ?>" data-tab="mine">🗂️ Mes créneaux <span class="count">(<?= count($mineUpcoming) ?>)</span></button>
            <?php if (!empty($minePast)): ?>
            <button type="button" class="tab<?= $activeTab === 'past' ? ' active' : '' ?>" data-tab="past">🕒 Événements passés <span class="count">(<?= count($minePast) ?>)</span></button>
            <?php endif; ?>
            <button type="button" class="tab create<?= $activeTab === 'new' ? ' active' : '' ?>" data-tab="new">＋ Créer un événement</button>
        </div>

        <!-- PANEL : Calendrier (aperçu style Google Agenda) -->
        <section class="tab-panel<?= $activeTab === 'cal' ? ' active' : '' ?>" id="panel-cal">
        <p class="section-title first-title">Mon calendrier <span style="text-transform:none; letter-spacing:0; color:var(--muted);">— mes créneaux et mes réservations</span></p>

        <!-- Sélecteur de vue : Jour / Semaine / Mois / Liste (façon Google Agenda) -->
        <div class="view-switch">
            <a class="vs-btn <?= $calView === 'day' ? 'active' : '' ?>" href="<?= $calUrl('day', ['cday' => $calDay]) ?>">📆 Jour</a>
            <a class="vs-btn <?= $calView === 'week' ? 'active' : '' ?>" href="<?= $calUrl('week', ['cweek' => $calWeekMonday]) ?>">🗓️ Semaine</a>
            <a class="vs-btn <?= $calView === 'month' ? 'active' : '' ?>" href="<?= $calUrl('month', ['cmonth' => $calMonth]) ?>">📅 Mois</a>
            <a class="vs-btn <?= $calView === 'list' ? 'active' : '' ?>" href="<?= $calUrl('list') ?>">📋 Liste</a>
        </div>

        <?php
            // Bascule « événements publics » : URL on/off en conservant la vue + sa date courante.
            $curParam = $calView === 'month' ? ['cmonth' => $calMonth]
                      : ($calView === 'week' ? ['cweek' => $calWeekMonday]
                      : ($calView === 'day' ? ['cday' => $calDay] : []));
            $toggleUrl = function (bool $pub) use ($calBase, $calView, $curParam) {
                $q = array_merge(['tab' => 'cal', 'cview' => $calView], $curParam);
                if ($pub) { $q['cpublic'] = 1; }
                return $calBase . '?' . http_build_query($q) . '#panel-cal';
            };
        ?>
        <label class="cal-toggle<?= $calShowPublic ? ' on' : '' ?>">
            <input type="checkbox" id="calPublicToggle"
                   data-on="<?= htmlspecialchars($toggleUrl(true)) ?>"
                   data-off="<?= htmlspecialchars($toggleUrl(false)) ?>"
                   <?= $calShowPublic ? 'checked' : '' ?>>
            <span>🌐 <b>Voir tout</b> (publics des autres inclus)
                <small><?= $calShowPublic
                    ? 'Activé : tu vois l\'ensemble des créneaux publics + les tiens.'
                    : 'Désactivé : tu vois seulement tes créneaux et tes réservations.' ?></small></span>
        </label>

        <?php $todayK = date('Y-m-d'); ?>

        <!-- ===== VUE MOIS ===== -->
        <?php if ($calView === 'month'):
            $mTs   = strtotime($calMonth . '-01');
            $y     = (int) date('Y', $mTs);
            $mNum  = (int) date('n', $mTs);
            $prev  = date('Y-m', strtotime('-1 month', $mTs));
            $next  = date('Y-m', strtotime('+1 month', $mTs));
            $daysIn = (int) date('t', $mTs);
            $lead   = (int) date('N', $mTs) - 1;
            $cells  = array_fill(0, $lead, null);
            for ($d = 1; $d <= $daysIn; $d++) { $cells[] = $d; }
            while (count($cells) % 7 !== 0) { $cells[] = null; }
        ?>
            <div class="cal-bar">
                <a class="cal-nav" href="<?= $calUrl('month', ['cmonth' => $prev]) ?>" title="Mois précédent">‹</a>
                <span class="cal-pill"><?= $moisNoms[$mNum] ?> <?= $y ?></span>
                <a class="cal-nav" href="<?= $calUrl('month', ['cmonth' => $next]) ?>" title="Mois suivant">›</a>
                <a class="cal-today" href="<?= $calUrl('month', ['cmonth' => date('Y-m')]) ?>">Aujourd'hui</a>
            </div>
            <div class="cal-grid cal-head">
                <?php foreach (['lun.', 'mar.', 'mer.', 'jeu.', 'ven.', 'sam.', 'dim.'] as $dn): ?>
                    <div class="cal-dow"><?= $dn ?></div>
                <?php endforeach; ?>
            </div>
            <div class="cal-grid">
                <?php foreach ($cells as $d): ?>
                    <?php if ($d === null): ?>
                        <div class="cal-cell empty"></div>
                    <?php else:
                        $key     = sprintf('%s-%02d', $calMonth, $d);
                        $items   = $calByDate[$key] ?? [];
                        $isToday = ($key === $todayK);
                        $canAdd  = ($key >= $todayK);
                    ?>
                        <div class="cal-cell<?= $isToday ? ' today' : '' ?>">
                            <a class="cal-cell-link" href="<?= $calUrl('day', ['cday' => $key]) ?>" title="Voir le <?= $d ?>"></a>
                            <div class="cal-num"><?= $d ?><?php if ($items): ?><span class="cal-count"><?= count($items) ?></span><?php endif; ?></div>
                            <div class="cal-events">
                                <?php foreach (array_slice($items, 0, 3) as $ev): ?>
                                    <span class="cal-ev <?= $calCls($ev) ?>" title="<?= htmlspecialchars(date('H:i', strtotime($ev['start_at'])) . ' · ' . $ev['title']) ?>">
                                        <?= date('H:i', strtotime($ev['start_at'])) ?> <?= htmlspecialchars($ev['title']) ?>
                                    </span>
                                <?php endforeach; ?>
                                <?php if (count($items) > 3): ?>
                                    <span class="cal-more">+<?= count($items) - 3 ?> autre<?= count($items) - 3 > 1 ? 's' : '' ?></span>
                                <?php endif; ?>
                            </div>
                            <?php if ($canAdd): ?>
                                <a class="cal-add" href="<?= $calAddUrl($key) ?>" title="Ajouter un créneau le <?= $d ?>">＋ Ajouter</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
            <div class="cal-legend">
                <span><i class="lg mine"></i> Mes créneaux</span>
                <span><i class="lg booked"></i> Mes réservations</span>
                <?php if ($calShowPublic): ?><span><i class="lg public"></i> Publics (autres)</span><?php endif; ?>
                <span class="cal-hint">Survole un jour pour ＋ ajouter · clique pour le détail →</span>
            </div>
        <?php endif; ?>

        <!-- ===== VUE SEMAINE ===== -->
        <?php if ($calView === 'week'):
            $wkStart = strtotime($calWeekMonday);
            $days    = [];
            for ($i = 0; $i < 7; $i++) { $days[] = strtotime("+$i day", $wkStart); }
            $prevW = date('Y-m-d', strtotime('-7 day', $wkStart));
            $nextW = date('Y-m-d', strtotime('+7 day', $wkStart));
            $endTs = strtotime('+6 day', $wkStart);
            $minH = 8; $maxH = 20;
            foreach ($days as $dts) {
                foreach ($calByDate[date('Y-m-d', $dts)] ?? [] as $ev) {
                    $s    = strtotime($ev['start_at']);
                    $eMin = (int) date('G', $s) * 60 + (int) date('i', $s) + (int) $ev['duration_min'];
                    $minH = min($minH, (int) date('G', $s));
                    $maxH = max($maxH, (int) ceil($eMin / 60));
                }
            }
            $minH   = max(0, min($minH, 8));
            $maxH   = min(24, max($maxH, 20));
            $hourPx = 46;
            $colH   = ($maxH - $minH) * $hourPx;
        ?>
            <div class="cal-bar">
                <a class="cal-nav" href="<?= $calUrl('week', ['cweek' => $prevW]) ?>" title="Semaine précédente">‹</a>
                <span class="cal-pill">Sem. du <?= (int) date('j', $wkStart) ?> <?= $moisNoms[(int) date('n', $wkStart)] ?><?= date('n', $wkStart) !== date('n', $endTs) ? ' – ' . (int) date('j', $endTs) . ' ' . $moisNoms[(int) date('n', $endTs)] : '' ?> <?= date('Y', $wkStart) ?></span>
                <a class="cal-nav" href="<?= $calUrl('week', ['cweek' => $nextW]) ?>" title="Semaine suivante">›</a>
                <a class="cal-today" href="<?= $calUrl('week', ['cweek' => date('Y-m-d')]) ?>">Cette semaine</a>
            </div>
            <div class="wk-scroll">
                <div class="wk-head">
                    <div class="wk-corner"></div>
                    <?php foreach ($days as $dts): $isT = date('Y-m-d', $dts) === $todayK; ?>
                        <a class="wk-dayhead<?= $isT ? ' today' : '' ?>" href="<?= $calUrl('day', ['cday' => date('Y-m-d', $dts)]) ?>" title="Voir cette journée">
                            <span class="wk-dn"><?= $jShort[date('D', $dts)] ?></span>
                            <span class="wk-dd"><?= (int) date('j', $dts) ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
                <div class="wk-body">
                    <div class="wk-gutter" style="height:<?= $colH ?>px">
                        <?php for ($h = $minH; $h <= $maxH; $h++): ?>
                            <div class="wk-hour" style="top:<?= ($h - $minH) * $hourPx ?>px"><?= sprintf('%02d:00', $h) ?></div>
                        <?php endfor; ?>
                    </div>
                    <?php foreach ($days as $dts): $isT = date('Y-m-d', $dts) === $todayK; ?>
                        <div class="wk-col<?= $isT ? ' today' : '' ?>" style="height:<?= $colH ?>px">
                            <?php for ($h = $minH; $h <= $maxH; $h++): ?>
                                <div class="wk-line" style="top:<?= ($h - $minH) * $hourPx ?>px"></div>
                            <?php endfor; ?>
                            <?php foreach ($calByDate[date('Y-m-d', $dts)] ?? [] as $ev):
                                $s    = strtotime($ev['start_at']);
                                $sMin = (int) date('G', $s) * 60 + (int) date('i', $s);
                                $top  = ($sMin - $minH * 60) / 60 * $hourPx;
                                $hgt  = max(24, (int) $ev['duration_min'] / 60 * $hourPx);
                            ?>
                                <div class="wk-ev <?= $calCls($ev) ?>" style="top:<?= $top ?>px; height:<?= $hgt ?>px"
                                     title="<?= htmlspecialchars(date('H:i', $s) . ' · ' . $ev['title']) ?>">
                                    <b><?= date('H:i', $s) ?></b> <?= htmlspecialchars($ev['title']) ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- ===== VUE JOUR ===== -->
        <?php if ($calView === 'day'):
            $dTs   = strtotime($calDay);
            $prevD = date('Y-m-d', strtotime('-1 day', $dTs));
            $nextD = date('Y-m-d', strtotime('+1 day', $dTs));
            $items = $calByDate[$calDay] ?? [];
            $minH = 8; $maxH = 20;
            foreach ($items as $ev) {
                $s    = strtotime($ev['start_at']);
                $eMin = (int) date('G', $s) * 60 + (int) date('i', $s) + (int) $ev['duration_min'];
                $minH = min($minH, (int) date('G', $s));
                $maxH = max($maxH, (int) ceil($eMin / 60));
            }
            $minH   = max(0, min($minH, 8));
            $maxH   = min(24, max($maxH, 20));
            $hourPx = 52;
            $colH   = ($maxH - $minH) * $hourPx;
            $isT    = $calDay === $todayK;
        ?>
            <div class="cal-bar">
                <a class="cal-nav" href="<?= $calUrl('day', ['cday' => $prevD]) ?>" title="Jour précédent">‹</a>
                <span class="cal-pill" style="text-transform:none;"><?= $joursLong[date('D', $dTs)] ?> <?= (int) date('j', $dTs) ?> <?= $moisNoms[(int) date('n', $dTs)] ?></span>
                <a class="cal-nav" href="<?= $calUrl('day', ['cday' => $nextD]) ?>" title="Jour suivant">›</a>
                <a class="cal-today" href="<?= $calUrl('day', ['cday' => date('Y-m-d')]) ?>">Aujourd'hui</a>
                <?php if ($calDay >= $todayK): ?>
                    <a class="cal-addday" href="<?= $calAddUrl($calDay) ?>">＋ Ajouter ce jour</a>
                <?php endif; ?>
            </div>
            <?php if (!$items): ?>
                <p class="empty">Aucun rendez-vous ce jour-là.</p>
            <?php endif; ?>
            <div class="wk-scroll">
                <div class="wk-body day-body">
                    <div class="wk-gutter" style="height:<?= $colH ?>px">
                        <?php for ($h = $minH; $h <= $maxH; $h++): ?>
                            <div class="wk-hour" style="top:<?= ($h - $minH) * $hourPx ?>px"><?= sprintf('%02d:00', $h) ?></div>
                        <?php endfor; ?>
                    </div>
                    <div class="wk-col<?= $isT ? ' today' : '' ?>" style="height:<?= $colH ?>px">
                        <?php for ($h = $minH; $h <= $maxH; $h++): ?>
                            <div class="wk-line" style="top:<?= ($h - $minH) * $hourPx ?>px"></div>
                        <?php endfor; ?>
                        <?php foreach ($items as $ev):
                            $s    = strtotime($ev['start_at']);
                            $sMin = (int) date('G', $s) * 60 + (int) date('i', $s);
                            $top  = ($sMin - $minH * 60) / 60 * $hourPx;
                            $hgt  = max(28, (int) $ev['duration_min'] / 60 * $hourPx);
                        ?>
                            <div class="wk-ev <?= $calCls($ev) ?>" style="top:<?= $top ?>px; height:<?= $hgt ?>px"
                                 title="<?= htmlspecialchars($ev['title']) ?>">
                                <b><?= date('H:i', $s) ?></b> <?= htmlspecialchars($ev['title']) ?> — <?= htmlspecialchars($ev['owner_name'] ?: 'Membre') ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- ===== VUE LISTE ===== -->
        <?php if ($calView === 'list'):
            $dates = array_keys($calByDate);
            sort($dates);
        ?>
            <?php if (!$dates): ?>
                <div class="card empty" style="padding:18px 20px;">Aucun rendez-vous dans ton calendrier. Tes créneaux et tes réservations s'afficheront ici.</div>
            <?php else: ?>
                <?php foreach ($dates as $day):
                    $ts    = strtotime($day);
                    $items = $calByDate[$day];
                ?>
                    <div class="day-head"><?= $joursLong[date('D', $ts)] ?> <?= (int) date('j', $ts) ?> <?= $moisNoms[(int) date('n', $ts)] ?> <?= date('Y', $ts) ?></div>
                    <div class="day-slots">
                        <?php foreach ($items as $ev):
                            $s    = strtotime($ev['start_at']);
                            $e    = $s + (int) $ev['duration_min'] * 60;
                            $past = $s < time();
                        ?>
                            <article class="g-slot<?= $past ? ' past' : '' ?>">
                                <div class="g-time"><?= date('H:i', $s) ?> – <?= date('H:i', $e) ?></div>
                                <div class="g-body">
                                    <div class="g-title">
                                        <?= htmlspecialchars($ev['title']) ?>
                                        <?php if ($calCls($ev) === 'mine'): ?>
                                            <span class="g-tag mine">mon créneau</span>
                                        <?php else: ?>
                                            <span class="g-tag booked">réservé</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="g-meta">par <?= htmlspecialchars($ev['owner_name'] ?: 'Membre') ?></div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        <?php endif; ?>
        </section>

        <!-- PANEL : Proposer un créneau -->
        <section class="tab-panel<?= $activeTab === 'new' ? ' active' : '' ?>" id="panel-new">
        <p class="section-title first-title">Créer un événement</p>
        <form class="card form-card" method="post" action="<?= url('agenda/create') ?>" enctype="multipart/form-data">
            <div class="grid">
                <div class="full">
                    <label for="title">Titre du rendez-vous</label>
                    <input type="text" id="title" name="title" placeholder="Ex : Cours de guitare, séance d'aide…" required>
                </div>
                <div>
                    <label for="date">Date</label>
                    <input type="date" id="date" name="date" value="<?= $prefillDate ?>" min="<?= $today ?>" required>
                </div>
                <div>
                    <label for="time">Heure</label>
                    <?php // Heure par défaut « à venir » si la date est aujourd'hui (évite un créneau déjà passé). ?>
                    <input type="time" id="time" name="time" value="<?= $prefillDate === $today ? date('H:i', strtotime('+2 hours')) : '14:00' ?>" required>
                </div>
                <div>
                    <label for="duration_min">Durée (minutes)</label>
                    <input type="number" id="duration_min" name="duration_min" value="60" min="5" max="600" step="5">
                </div>
                <div>
                    <label for="capacity">Nombre de places</label>
                    <input type="number" id="capacity" name="capacity" value="5" min="1" max="20">
                </div>
                <div>
                    <label>Type de rendez-vous</label>
                    <div class="seg">
                        <input type="radio" id="m_pres" name="mode" value="presentiel" checked>
                        <label for="m_pres">📍 Présentiel</label>
                        <input type="radio" id="m_onl" name="mode" value="en_ligne">
                        <label for="m_onl">🔗 En ligne</label>
                    </div>
                </div>
                <div>
                    <label>Visibilité</label>
                    <div class="seg">
                        <input type="radio" id="v_pub" name="visibility" value="public" checked>
                        <label for="v_pub">🌐 Public</label>
                        <input type="radio" id="v_priv" name="visibility" value="private">
                        <label for="v_priv">🔒 Privé</label>
                    </div>
                </div>
                <div>
                    <label for="min_notice_hours">Délai minimum de réservation</label>
                    <select id="min_notice_hours" name="min_notice_hours">
                        <?php foreach ($delaiOpts as $h => $lbl): ?>
                            <option value="<?= $h ?>"><?= htmlspecialchars($lbl) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <p class="hint-sm">Ferme les réservations ce délai avant le début du créneau.</p>
                </div>
                <div class="full">
                    <label>Ajouter des participants (facultatif)</label>
                    <?php $pickable = array_values(array_filter($members, static fn ($mb) => (int) $mb['id'] !== $uid)); ?>
                    <?php if ($pickable): ?>
                        <div class="member-pick">
                            <?php foreach ($pickable as $mb): ?>
                                <label class="mp-item">
                                    <input type="checkbox" name="participants[]" value="<?= (int) $mb['id'] ?>">
                                    <span class="mp-name"><?= htmlspecialchars($mb['name']) ?></span>
                                    <?php if ($mb['code']): ?><span class="mp-code"><?= htmlspecialchars($mb['code']) ?></span><?php endif; ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        <p class="hint-sm">Coche les membres à inscrire d'office (ils reçoivent une notification). Seuls ceux ayant <b>autorisé</b> d'être ajoutés apparaissent ici.</p>
                    <?php else: ?>
                        <p class="hint-sm">Aucun membre n'a encore autorisé d'être ajouté à des événements. Tu peux les ajouter par <b>code</b> ci-dessous.</p>
                    <?php endif; ?>
                </div>
                <div class="full">
                    <label for="participant_codes">… ou ajouter par <b>code membre</b> (facultatif)</label>
                    <input type="text" id="participant_codes" name="participant_codes" autocomplete="off"
                           placeholder="Ex : A202 B713 (séparés par un espace)">
                    <p class="hint-sm">Pour les membres qui t'ont communiqué leur code, même s'ils n'apparaissent pas dans la liste ci-dessus.</p>
                </div>
                <div class="full">
                    <label for="location" id="locLabel">Adresse du lieu</label>
                    <input type="text" id="location" name="location" class="loc-input" autocomplete="off"
                           list="cityList" placeholder="Commence à taper une ville ou une adresse…">
                    <datalist id="cityList"></datalist>
                    <p class="hint-sm" id="locHint">Suggestions d'adresses pendant la saisie (OpenStreetMap).</p>
                </div>
                <div class="full">
                    <label for="description">Description (facultatif)</label>
                    <textarea id="description" name="description" placeholder="Précisions, lieu, niveau…"></textarea>
                </div>
                <div class="full">
                    <label for="event_photos">Photos de l'événement (facultatif)</label>
                    <input type="file" id="event_photos" name="event_photos[]" accept="image/*" multiple class="evt-file">
                    <p class="hint-sm">Une ou plusieurs photos (JPG, PNG, GIF, WEBP). Elles s'affichent sur la fiche de l'événement.</p>
                </div>
                <div class="full">
                    <label class="check-line">
                        <input type="checkbox" name="show_ratings" value="1">
                        <span>Rendre les inscrits et leur note visibles sur la fiche publique
                            <small>— par défaut masqué : toi seul·e les vois dans « Mes créneaux ». Coche pour que les autres membres les voient aussi (modifiable à tout moment).</small></span>
                    </label>
                </div>
                <div class="full">
                    <label class="check-line">
                        <input type="checkbox" name="urgent" value="1">
                        <span>🟥 Marquer comme <b>URGENT</b>
                            <small>— une alerte rouge s'affichera dans le tableau de bord de <b>tous les membres</b> pour mettre cet événement en avant.</small></span>
                    </label>
                </div>
            </div>
            <button class="btn create-go" type="submit">＋ Créer l'événement</button>
        </form>
        </section>

        <!-- PANEL : Mes créneaux -->
        <section class="tab-panel<?= $activeTab === 'mine' ? ' active' : '' ?>" id="panel-mine">
        <p class="section-title first-title">Mes créneaux à venir</p>
        <?php if (empty($mineUpcoming)): ?>
            <div class="card empty" style="padding:18px 20px;">
                Tu n'as pas de créneau à venir.
                <?php if (!empty($minePast)): ?> Tes créneaux terminés sont dans l'onglet <b>🕒 Événements passés</b>.<?php endif; ?>
            </div>
        <?php else: ?>
            <div class="slots">
                <?php foreach ($mineUpcoming as $a): ?>
                    <?php
                        $cap    = (int) $a['capacity'];
                        $booked = (int) $a['booked'];
                        $free   = max(0, $cap - $booked);
                        $pct    = $cap > 0 ? round($booked / $cap * 100) : 0;
                    ?>
                    <article class="card slot">
                        <div class="slot-main">
                            <div class="slot-when"><?= htmlspecialchars($fmt($a['start_at'], $a['duration_min'])) ?></div>
                            <div class="cd-badge">⏳ <?= countdown_html($a['start_at'], (int) $a['duration_min']) ?></div>
                            <div class="slot-title"><?= htmlspecialchars($a['title']) ?></div>
                            <a class="slot-detail" href="<?= url('agenda/event') ?>?id=<?= (int) $a['id'] ?>">📄 Voir la fiche complète →</a>
                            <div><a class="slot-gcal" href="<?= htmlspecialchars(google_calendar_url($a['title'], $a['start_at'], (int) $a['duration_min'], $a['mode'] ?? 'presentiel', (string) ($a['location'] ?? ''))) ?>" target="_blank" rel="noopener">📅 Ajouter à Google Agenda</a></div>
                            <?php $isPrivate = ($a['visibility'] ?? 'public') === 'private'; ?>
                            <div class="slot-code">
                                <?= $isPrivate ? '🔒 Privé' : '🌐 Public' ?> · code : <b><?= htmlspecialchars($a['code']) ?></b>
                                <button type="button" class="copy-code" data-code="<?= htmlspecialchars($a['code']) ?>">copier</button>
                                <form method="post" action="<?= url('agenda/visibility') ?>" class="vis-toggle"
                                      onsubmit="return confirm(<?= $isPrivate ? "'Rendre ce créneau PUBLIC (visible par tout le monde) ?'" : "'Repasser ce créneau en PRIVÉ (accessible par code) ?'" ?>);">
                                    <input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
                                    <button type="submit" class="vis-btn">
                                        <?= $isPrivate ? '🌐 Rendre public' : '🔒 Rendre privé' ?>
                                    </button>
                                </form>
                                <?php $isProt = (int) ($a['protected'] ?? 0) === 1; ?>
                                <form method="post" action="<?= url('agenda/protect') ?>" class="vis-toggle">
                                    <input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
                                    <button type="submit" class="vis-btn<?= $isProt ? ' prot-on' : '' ?>" title="<?= $isProt ? 'Retirer la protection' : 'Protéger : conservé même en cas d\'effacement global' ?>"><?= $isProt ? '🔓 Déprotéger' : '🔒 Protéger' ?></button>
                                </form>
                                <?php if ($isProt): ?><span class="prot-badge">🔒 Protégé</span><?php endif; ?>
                                <?php $isUrg = (int) ($a['urgent'] ?? 0) === 1; ?>
                                <form method="post" action="<?= url('agenda/urgent') ?>" class="vis-toggle">
                                    <input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
                                    <button type="submit" class="vis-btn<?= $isUrg ? ' urg-on' : '' ?>"><?= $isUrg ? '🟥 Retirer URGENT' : '🟥 Marquer URGENT' ?></button>
                                </form>
                                <?php if ($isUrg): ?><span class="prot-badge urg">🟥 URGENT</span><?php endif; ?>
                            </div>
                            <?php if (!empty($a['description'])): ?>
                                <div class="slot-desc"><?= htmlspecialchars($a['description']) ?></div>
                            <?php endif; ?>
                            <?php $photos = $eventPhotos[(int) $a['id']] ?? []; if ($photos): ?>
                                <div class="evt-photos">
                                    <?php foreach ($photos as $ph): ?>
                                        <a href="<?= url('uploads/agenda/' . $ph) ?>" target="_blank" rel="noopener"><img src="<?= url('uploads/agenda/' . $ph) ?>" alt="photo de l'événement" loading="lazy"></a>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            <?php $sTs = strtotime($a['start_at']); ?>
                            <details class="edit-loc">
                                <summary>✏️ Modifier le rendez-vous</summary>
                                <form method="post" action="<?= url('agenda/update') ?>" class="edit-loc-form edit-rdv">
                                    <input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
                                    <label>Titre
                                        <input type="text" name="title" value="<?= htmlspecialchars($a['title']) ?>" required>
                                    </label>
                                    <div class="edit-rdv-row">
                                        <label>Date
                                            <input type="date" name="date" value="<?= date('Y-m-d', $sTs) ?>" required>
                                        </label>
                                        <label>Heure
                                            <input type="time" name="time" value="<?= date('H:i', $sTs) ?>" required>
                                        </label>
                                        <label>Durée (min)
                                            <input type="number" name="duration_min" value="<?= (int) $a['duration_min'] ?>" min="5" max="600" step="5">
                                        </label>
                                    </div>
                                    <label>Description
                                        <textarea name="description" rows="3" placeholder="Précisions, niveau…"><?= htmlspecialchars($a['description'] ?? '') ?></textarea>
                                    </label>
                                    <button class="act book" type="submit">Enregistrer les modifications</button>
                                </form>
                            </details>
                            <?= $locBlock($a) ?>
                            <?php $emp = ($a['mode'] ?? 'presentiel') === 'en_ligne'; ?>
                            <details class="edit-loc">
                                <summary>✏️ Modifier le lieu</summary>
                                <form method="post" action="<?= url('agenda/location') ?>" class="edit-loc-form">
                                    <input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
                                    <div class="seg">
                                        <input type="radio" id="em_pres_<?= (int) $a['id'] ?>" name="mode" value="presentiel" <?= $emp ? '' : 'checked' ?>>
                                        <label for="em_pres_<?= (int) $a['id'] ?>">📍 Présentiel</label>
                                        <input type="radio" id="em_onl_<?= (int) $a['id'] ?>" name="mode" value="en_ligne" <?= $emp ? 'checked' : '' ?>>
                                        <label for="em_onl_<?= (int) $a['id'] ?>">🔗 En ligne</label>
                                    </div>
                                    <input type="text" name="location" class="loc-input" list="cityList" autocomplete="off"
                                           value="<?= htmlspecialchars($a['location'] ?? '') ?>" placeholder="Adresse ou lien…">
                                    <button class="act book" type="submit">Enregistrer le lieu</button>
                                </form>
                            </details>
                            <?php $curNotice = (int) ($a['min_notice_hours'] ?? 0); ?>
                            <div class="slot-notice">⏳ Délai minimum de réservation : <b><?= htmlspecialchars(delai_texte($curNotice)) ?></b></div>
                            <details class="edit-loc">
                                <summary>✏️ Modifier le délai</summary>
                                <form method="post" action="<?= url('agenda/notice') ?>" class="edit-loc-form">
                                    <input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
                                    <select name="min_notice_hours">
                                        <?php foreach ($delaiOpts as $h => $lbl): ?>
                                            <option value="<?= $h ?>" <?= $h === $curNotice ? 'selected' : '' ?>><?= htmlspecialchars($lbl) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button class="act book" type="submit">Enregistrer le délai</button>
                                </form>
                            </details>
                            <?= $historyBlock($a['changes'] ?? []) ?>
                            <?php $publicNotes = (int) ($a['show_booker_ratings'] ?? 0) === 1; ?>
                            <div class="bookers">
                                <div class="bk-h">
                                    <span>Réservations : <?= $booked ?>/<?= $cap ?></span>
                                    <span class="bk-toggle-wrap">
                                        Notes visibles par les autres :
                                        <form method="post" action="<?= url('agenda/ratings_toggle') ?>" class="bk-toggle">
                                            <input type="hidden" name="slot_id" value="<?= (int) $a['id'] ?>">
                                            <button type="submit" class="bk-toggle-btn <?= $publicNotes ? 'on' : 'off' ?>"
                                                    title="<?= $publicNotes ? 'Visibles sur la fiche publique — cliquer pour les masquer aux autres' : 'Masquées aux autres — cliquer pour les afficher sur la fiche publique' ?>">
                                                <?= $publicNotes ? '👁️ Publiques' : '🙈 Masquées' ?>
                                            </button>
                                        </form>
                                    </span>
                                </div>
                                <div class="bk-note-hint"><?= $publicNotes
                                    ? '👁️ Les autres membres voient les inscrits et leurs notes sur ce créneau.'
                                    : '🔒 Toi seul·e vois les notes ci-dessous. Active pour les rendre publiques.' ?></div>
                                <?php if (!empty($a['bookers'])): ?>
                                    <?php $eventStarted = strtotime($a['start_at']) <= time(); ?>
                                    <div class="bk-list">
                                        <?php foreach ($a['bookers'] as $bk): ?>
                                            <?php $p = $bk['present']; $pi = ($p === null) ? null : (int) $p; ?>
                                            <div class="bk">
                                                <span class="bk-name">👤 <?= htmlspecialchars($bk['user_name'] ?: 'Membre') ?><?php
                                                    // L'hôte voit toujours la note de ses inscrits, même si elle est masquée au public.
                                                    $rs = ($bookerRatings ?? [])[(int) $bk['user_id']] ?? null;
                                                    if ($rs && (int) $rs['count'] > 0): ?><em class="bk-rate" title="<?= (int) $rs['count'] ?> avis">★ <?= number_format((float) $rs['avg'], 1, ',', ' ') ?>/5</em><?php
                                                    else: ?><em class="bk-rate none">non noté</em><?php
                                                    endif; ?></span>
                                                <span class="bk-pres">
                                                    <?php if (!$eventStarted): ?>
                                                        <span class="pres-wait">⏳ présence à confirmer après l'événement</span>
                                                    <?php else: ?>
                                                        <form method="post" action="<?= url('agenda/presence') ?>" class="pres-form">
                                                            <input type="hidden" name="slot_id" value="<?= (int) $a['id'] ?>">
                                                            <input type="hidden" name="member_id" value="<?= (int) $bk['user_id'] ?>">
                                                            <input type="hidden" name="present" value="<?= $pi === 1 ? '' : '1' ?>">
                                                            <button type="submit" class="pres-btn ok <?= $pi === 1 ? 'on' : '' ?>" title="Marquer présent">✅ Présent</button>
                                                        </form>
                                                        <form method="post" action="<?= url('agenda/presence') ?>" class="pres-form">
                                                            <input type="hidden" name="slot_id" value="<?= (int) $a['id'] ?>">
                                                            <input type="hidden" name="member_id" value="<?= (int) $bk['user_id'] ?>">
                                                            <input type="hidden" name="present" value="<?= $pi === 0 ? '' : '0' ?>">
                                                            <button type="submit" class="pres-btn no <?= $pi === 0 ? 'on' : '' ?>" title="Marquer absent">❌ Absent</button>
                                                        </form>
                                                    <?php endif; ?>
                                                </span>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="empty" style="padding:2px 0;">Aucune réservation pour l'instant.</div>
                                <?php endif; ?>
                            </div>
                            <details class="add-member">
                                <summary>＋ Ajouter un membre</summary>
                                <form method="post" action="<?= url('agenda/add_member') ?>" class="add-member-form">
                                    <input type="hidden" name="slot_id" value="<?= (int) $a['id'] ?>">
                                    <select name="member_id">
                                        <option value="">— Membre (ayant accepté) —</option>
                                        <?php foreach ($members as $mb): ?>
                                            <?php if ((int) $mb['id'] === $uid) { continue; } ?>
                                            <option value="<?= (int) $mb['id'] ?>"><?= htmlspecialchars($mb['label']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <span class="am-or">ou</span>
                                    <input type="text" name="member_code" class="am-code" placeholder="Code A123" maxlength="4"
                                           pattern="[A-Za-z][0-9]{3}" autocomplete="off" title="1 lettre + 3 chiffres">
                                    <button class="act book" type="submit">Ajouter</button>
                                </form>
                                <small class="hint-mini">La <b>liste</b> ne montre que les membres « trouvables ». Par <b>code</b>, tu peux ajouter tout membre qui t'a communiqué le sien.</small>
                            </details>
                        </div>
                        <div class="slot-side">
                            <span class="places <?= $free > 0 ? 'free' : 'full' ?>"><?= $free > 0 ? $free . ' place' . ($free > 1 ? 's' : '') . ' libre' . ($free > 1 ? 's' : '') : 'Complet' ?></span>
                            <div class="meter"><i style="width:<?= $pct ?>%"></i></div>
                            <span class="pct-label"><?= $booked ?>/<?= $cap ?> · <?= $pct ?>% occupé</span>
                            <div class="cap-ctrl">
                                <form method="post" action="<?= url('agenda/capacity') ?>">
                                    <input type="hidden" name="slot_id" value="<?= (int) $a['id'] ?>">
                                    <input type="hidden" name="dir" value="dec">
                                    <button class="cap-btn" type="submit" title="Réduire le nombre de places"<?= $cap <= 1 ? ' disabled' : '' ?>>−</button>
                                </form>
                                <span class="cap-val"><?= $cap ?> place<?= $cap > 1 ? 's' : '' ?></span>
                                <form method="post" action="<?= url('agenda/capacity') ?>">
                                    <input type="hidden" name="slot_id" value="<?= (int) $a['id'] ?>">
                                    <input type="hidden" name="dir" value="inc">
                                    <button class="cap-btn" type="submit" title="Augmenter le nombre de places">+</button>
                                </form>
                            </div>
                            <form method="post" action="<?= url('agenda/delete') ?>" onsubmit="return confirm('Supprimer ce créneau et ses réservations ?');">
                                <input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
                                <button class="act del" type="submit">🗑️ Supprimer</button>
                            </form>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        </section>

        <!-- PANEL : Événements passés (archive, LECTURE SEULE — aucune modification) -->
        <?php if (!empty($minePast)): ?>
        <section class="tab-panel<?= $activeTab === 'past' ? ' active' : '' ?>" id="panel-past">
        <p class="section-title first-title">🕒 Événements passés</p>
        <p class="hint-mini" style="margin:-6px 0 14px;">Ces créneaux sont terminés : ils ne sont plus modifiables. Tu peux encore confirmer la présence des inscrits et les supprimer.</p>
        <div class="slots">
            <?php foreach ($minePast as $a): ?>
                <?php
                    $cap    = (int) $a['capacity'];
                    $booked = (int) $a['booked'];
                    $isPriv = ($a['visibility'] ?? 'public') === 'private';
                ?>
                <article class="card slot is-past">
                    <div class="slot-main">
                        <div class="slot-when"><?= htmlspecialchars($fmt($a['start_at'], $a['duration_min'])) ?> · <span class="past-tag">terminé</span></div>
                        <div class="slot-title"><?= htmlspecialchars($a['title']) ?></div>
                        <a class="slot-detail" href="<?= url('agenda/event') ?>?id=<?= (int) $a['id'] ?>">📄 Voir la fiche complète →</a>
                        <div class="slot-code"><?= $isPriv ? '🔒 Privé · code : <b>' . htmlspecialchars($a['code']) . '</b>' : '🌐 Public' ?></div>
                        <?php if (!empty($a['description'])): ?>
                            <div class="slot-desc"><?= htmlspecialchars($a['description']) ?></div>
                        <?php endif; ?>
                        <?= $locBlock($a) ?>
                        <?= $historyBlock($a['changes'] ?? []) ?>
                        <div class="bookers">
                            <div class="bk-h"><span>Réservations : <?= $booked ?>/<?= $cap ?></span></div>
                            <?php if (!empty($a['bookers'])): ?>
                                <div class="bk-list">
                                    <?php foreach ($a['bookers'] as $bk): ?>
                                        <?php $p = $bk['present']; $pi = ($p === null) ? null : (int) $p; ?>
                                        <div class="bk">
                                            <span class="bk-name">👤 <?= htmlspecialchars($bk['user_name'] ?: 'Membre') ?><?php
                                                $rs = ($bookerRatings ?? [])[(int) $bk['user_id']] ?? null;
                                                if ($rs && (int) $rs['count'] > 0): ?><em class="bk-rate" title="<?= (int) $rs['count'] ?> avis">★ <?= number_format((float) $rs['avg'], 1, ',', ' ') ?>/5</em><?php
                                                else: ?><em class="bk-rate none">non noté</em><?php
                                                endif; ?></span>
                                            <span class="bk-pres">
                                                <form method="post" action="<?= url('agenda/presence') ?>" class="pres-form">
                                                    <input type="hidden" name="slot_id" value="<?= (int) $a['id'] ?>">
                                                    <input type="hidden" name="member_id" value="<?= (int) $bk['user_id'] ?>">
                                                    <input type="hidden" name="present" value="<?= $pi === 1 ? '' : '1' ?>">
                                                    <button type="submit" class="pres-btn ok <?= $pi === 1 ? 'on' : '' ?>" title="Marquer présent">✅ Présent</button>
                                                </form>
                                                <form method="post" action="<?= url('agenda/presence') ?>" class="pres-form">
                                                    <input type="hidden" name="slot_id" value="<?= (int) $a['id'] ?>">
                                                    <input type="hidden" name="member_id" value="<?= (int) $bk['user_id'] ?>">
                                                    <input type="hidden" name="present" value="<?= $pi === 0 ? '' : '0' ?>">
                                                    <button type="submit" class="pres-btn no <?= $pi === 0 ? 'on' : '' ?>" title="Marquer absent">❌ Absent</button>
                                                </form>
                                            </span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="empty" style="padding:2px 0;">Aucune réservation.</div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="slot-side">
                        <span class="places full">Terminé</span>
                        <form method="post" action="<?= url('agenda/delete') ?>" onsubmit="return confirm('Supprimer définitivement ce créneau passé et ses réservations ?');">
                            <input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
                            <button class="act del" type="submit">🗑️ Supprimer</button>
                        </form>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
        </section>
        <?php endif; ?>

        <!-- PANEL : Trouver un cours (actif par défaut) -->
        <section class="tab-panel<?= $activeTab === 'find' ? ' active' : '' ?>" id="panel-find">
        <p class="section-title first-title">Cours publics à proximité</p>
        <form class="card form-card" method="get" action="<?= url('agenda') ?>" id="nearForm">
            <label for="near">Zone (ville ou adresse)<?php if ($nearQuery === '' && !empty($defaultCity)): ?> <span style="color:var(--muted);font-weight:400;">— ta ville par défaut est pré-remplie</span><?php endif; ?></label>
            <?php // Pré-rempli avec la ville par défaut de l'utilisateur tant qu'aucune recherche n'est lancée. ?>
            <input type="text" id="near" name="near" class="loc-input" list="cityList" autocomplete="off"
                   value="<?= htmlspecialchars($nearQuery !== '' ? $nearQuery : (string) ($defaultCity ?? '')) ?>"
                   placeholder="Ex : Lyon, ou 10 rue de la Paix, Paris">
            <input type="hidden" name="near_lat" id="nearLat" value="">
            <input type="hidden" name="near_lng" id="nearLng" value="">
            <input type="hidden" name="near_submitted" value="1"><?php // marque la soumission : permet de respecter les cases décochées ?>
            <label style="margin-top:14px;">Rayon de recherche</label>
            <div class="seg seg-radius">
                <?php foreach ($radiusOptions as $r): ?>
                    <input type="radio" id="r_<?= $r ?>" name="radius" value="<?= $r ?>" <?= (int) $radius === $r ? 'checked' : '' ?>>
                    <label for="r_<?= $r ?>"><?= $r ?> km</label>
                <?php endforeach; ?>
                <?php $rAll = (int) ($radiusAll ?? 20000); ?>
                <input type="radio" id="r_all" name="radius" value="<?= $rAll ?>" <?= (int) $radius === $rAll ? 'checked' : '' ?>>
                <label for="r_all">🌐 Toutes</label>
            </div>
            <div class="near-scope" style="display:flex; flex-wrap:wrap; gap:14px; margin:12px 0 4px;">
                <label class="check-line" style="width:auto;"><input type="checkbox" name="near_others" value="1" <?= !empty($nearOthers) ? 'checked' : '' ?>> 🌍 Créneaux publics des autres</label>
                <label class="check-line" style="width:auto;"><input type="checkbox" name="near_mine" value="1" <?= !empty($nearMine) ? 'checked' : '' ?>> 🗂️ Mes créneaux</label>
            </div>
            <div class="near-actions">
                <button class="btn" type="submit">🔍 Chercher</button>
                <button class="geo-btn" type="button" id="geoBtn">📍 Autour de moi</button>
                <?php if (!empty($defaultCity)): ?>
                    <button class="geo-btn" type="button" id="useDefaultCity"
                            data-city="<?= htmlspecialchars($defaultCity) ?>"
                            data-lat="<?= $defaultLat !== null ? $defaultLat : '' ?>"
                            data-lng="<?= $defaultLng !== null ? $defaultLng : '' ?>">⭐ Ma ville (<?= htmlspecialchars($defaultCity) ?>)</button>
                <?php endif; ?>
            </div>
        </form>

        <!-- Ville par défaut : définie/changée à tout moment, réutilisée comme raccourci. -->
        <details class="default-city">
            <summary>⭐ <?= !empty($defaultCity) ? 'Ma ville par défaut : ' . htmlspecialchars($defaultCity) . ' — changer' : 'Définir ma ville par défaut' ?></summary>
            <div class="dc-wrap">
                <form method="post" action="<?= url('agenda/default_city') ?>" class="dc-form">
                    <input type="text" name="default_city" class="loc-input" list="cityList" autocomplete="off"
                           value="<?= htmlspecialchars($defaultCity) ?>" placeholder="Ex : Lyon">
                    <input type="hidden" name="default_lat" value="">
                    <input type="hidden" name="default_lng" value="">
                    <button class="btn" type="submit">💾 Enregistrer</button>
                </form>
                <?php if (!empty($defaultCity)): ?>
                    <form method="post" action="<?= url('agenda/default_city') ?>" class="dc-clear">
                        <input type="hidden" name="default_city" value="">
                        <button class="geo-btn" type="submit">🗑️ Effacer</button>
                    </form>
                <?php endif; ?>
            </div>
            <p class="hint-sm">Mémorisée pour tes prochaines recherches par kilomètres. Utilise « ⭐ Ma ville » ci-dessus pour la réutiliser en un clic.</p>
        </details>
        <?php if (!empty($nearError)): ?>
            <div class="flash err" style="margin-top:14px;"><?= htmlspecialchars($nearError) ?></div>
        <?php endif; ?>
        <?php if ($nearResults !== null): ?>
            <?php if ($nearCenter): ?>
                <?php
                    // Points (avec coordonnées) pour la carte des résultats.
                    $mapPoints = [];
                    foreach ($nearResults as $rp) {
                        if ($rp['lat'] !== null && $rp['lng'] !== null) {
                            $cap  = (int) $rp['capacity'];
                            $free = max(0, $cap - (int) ($rp['booked'] ?? 0));
                            $mapPoints[] = [
                                'id'    => (int) $rp['id'],
                                'lat'   => (float) $rp['lat'],
                                'lng'   => (float) $rp['lng'],
                                'title' => $rp['title'],
                                'dist'  => round((float) $rp['distance'], 1),
                                'when'  => $fmt($rp['start_at'], $rp['duration_min']),
                                'owner' => $rp['owner_name'] ?: 'Membre',
                                'free'  => $free,
                                'cap'   => $cap,
                            ];
                        }
                    }
                ?>
                <!-- Le plan de la zone recherchée s'affiche TOUJOURS (même sans résultat) :
                     repère de la zone + un marqueur par créneau trouvé. -->
                <div id="resultsMap"
                     data-center='<?= htmlspecialchars(json_encode($nearCenter), ENT_QUOTES) ?>'
                     data-points='<?= htmlspecialchars(json_encode($mapPoints), ENT_QUOTES) ?>'></div>
            <?php endif; ?>
            <?php if (empty($nearResults)): ?>
                <div class="card empty" style="padding:18px 20px; margin-top:14px;">
                    <?php if ((int) $radius >= (int) ($radiusAll ?? 20000)): ?>
                        <b>Aucun résultat.</b> Aucun créneau (toutes distances) autour de « <?= htmlspecialchars($nearQuery) ?> » selon les filtres choisis. Le plan ci-dessus montre la zone recherchée.
                    <?php else: ?>
                        <b>Aucun résultat.</b> Aucun créneau dans un rayon de <?= (int) $radius ?> km autour de « <?= htmlspecialchars($nearQuery) ?> ». Essaie un rayon plus grand ou « 🌐 Toutes ».
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="slots" style="margin-top:14px;">
                    <?php foreach ($nearResults as $a): ?>
                        <div id="near-slot-<?= (int) $a['id'] ?>" class="slot-anchor"><?= $reserveCard($a) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <!-- Recherche d'un RDV privé par code -->
        <p class="section-title">Trouver un rendez-vous privé</p>
        <form class="card form-card" method="get" action="<?= url('agenda') ?>">
            <label for="code">Code du rendez-vous (2 lettres + 3 chiffres)</label>
            <div class="search-row">
                <input type="text" id="code" name="code" value="<?= htmlspecialchars($searchCode) ?>"
                       placeholder="AB123" maxlength="5" autocomplete="off"
                       pattern="[A-Za-z]{2}[0-9]{3}" title="2 lettres puis 3 chiffres, ex : AB123">
                <button class="btn" type="submit">🔍 Rechercher</button>
            </div>
        </form>
        <?php if (!empty($searchError)): ?>
            <div class="flash err" style="margin-top:14px;"><?= htmlspecialchars($searchError) ?></div>
        <?php endif; ?>
        <?php if (!empty($found)): ?>
            <div class="slots" style="margin-top:14px;"><?= $reserveCard($found) ?></div>
        <?php endif; ?>

        <!-- Réserver chez les autres -->
        <p class="section-title" id="all-slots">Tous les créneaux publics</p>
        <?php if (empty($available)): ?>
            <div class="card empty" style="padding:18px 20px;">Aucun résultat — aucun créneau public proposé par les autres membres pour le moment.</div>
        <?php else: ?>
            <div class="evt-search">
                <input type="search" id="evtSearch" placeholder="🔎 Rechercher un événement (titre, hôte, lieu…)" autocomplete="off">
            </div>
            <div class="slots" id="allSlots">
                <?php foreach ($available as $a): ?>
                    <div class="slot-wrap" data-find="<?= htmlspecialchars(mb_strtolower(($a['title'] ?? '') . ' ' . ($a['owner_name'] ?? '') . ' ' . ($a['location'] ?? ''))) ?>"><?= $reserveCard($a) ?></div>
                <?php endforeach; ?>
            </div>
            <div class="card empty" id="evtNone" style="padding:18px 20px; display:none;">Aucun événement ne correspond à ta recherche.</div>
            <?php if ($availPages > 1): ?>
                <div class="pager">
                    <?php if ($availPage > 1): ?>
                        <a class="pg" href="<?= url('agenda') ?>?p=<?= $availPage - 1 ?>#all-slots">← Précédent</a>
                    <?php else: ?>
                        <span class="pg disabled">← Précédent</span>
                    <?php endif; ?>
                    <span class="pg-info">Page <?= $availPage ?> / <?= $availPages ?></span>
                    <?php if ($availPage < $availPages): ?>
                        <a class="pg" href="<?= url('agenda') ?>?p=<?= $availPage + 1 ?>#all-slots">Suivant →</a>
                    <?php else: ?>
                        <span class="pg disabled">Suivant →</span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
        </section>

        <!-- PANEL : Mes réservations -->
        <section class="tab-panel<?= $activeTab === 'book' ? ' active' : '' ?>" id="panel-book">
        <p class="section-title first-title">Mes réservations</p>
        <?php if (empty($myBookings)): ?>
            <div class="card empty" style="padding:18px 20px;">Tu n'as réservé aucun rendez-vous pour l'instant.</div>
        <?php else: ?>
            <div class="slots">
                <?php foreach ($myBookings as $a): ?>
                    <div class="booked-wrap">
                        <?= $reserveCard($a) ?>
                        <?php if ((int) $a['owner_id'] !== $uid): ?>
                            <form method="post" action="<?= url('agenda/rate') ?>" class="rate-form">
                                <input type="hidden" name="owner_id" value="<?= (int) $a['owner_id'] ?>">
                                <span class="rate-label">Noter <?= htmlspecialchars($a['owner_name'] ?: 'l\'hôte') ?> :</span>
                                <div class="stars-input">
                                    <?php for ($s = 5; $s >= 1; $s--): ?>
                                        <input type="radio" id="rt_<?= (int) $a['id'] ?>_<?= $s ?>" name="stars" value="<?= $s ?>" <?= (int) ($a['my_rating'] ?? 0) === $s ? 'checked' : '' ?>>
                                        <label for="rt_<?= (int) $a['id'] ?>_<?= $s ?>" title="<?= $s ?>/5">★</label>
                                    <?php endfor; ?>
                                </div>
                                <button class="act book" type="submit">Noter</button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        </section>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
    // Libellé d'état d'un créneau, en direct : « Dans … » / « En cours » / « Déjà passé ».
    // Échelle : < 24 h → h min s · 1–30 j → jours · > 30 j → mois.
    (function () {
        var els = document.querySelectorAll('.countdown');
        if (!els.length) { return; }
        function p2(n) { return (n < 10 ? '0' : '') + n; }
        function duree(s) {
            if (s < 0) { s = 0; }
            var days = Math.floor(s / 86400);
            if (days > 30) { return Math.floor(days / 30) + ' mois'; }
            if (s >= 86400) { return days + ' j'; }
            var h = Math.floor(s / 3600), m = Math.floor((s % 3600) / 60), sec = s % 60;
            if (h > 0) { return h + ' h ' + p2(m) + ' min ' + p2(sec) + ' s'; }
            if (m > 0) { return m + ' min ' + p2(sec) + ' s'; }
            return sec + ' s';
        }
        function tick() {
            var now = Date.now();
            els.forEach(function (el) {
                var start = parseInt(el.getAttribute('data-ts'), 10) * 1000;
                var endA  = el.getAttribute('data-end');
                var end   = endA ? parseInt(endA, 10) * 1000 : start;
                el.classList.remove('soon', 'done', 'live');
                if (now >= end)   { el.textContent = 'Déjà passé depuis ' + duree(Math.floor((now - end) / 1000)); el.classList.add('done'); return; }
                if (now >= start) { el.textContent = '🔴 EN DIRECT'; el.classList.add('live'); return; }
                var diff = Math.floor((start - now) / 1000);
                el.textContent = 'Dans ' + duree(diff);
                if (diff < 86400) { el.classList.add('soon'); } // moins de 24 h → mis en évidence
            });
        }
        tick();
        setInterval(tick, 1000);
    })();
    </script>

    <script>
    (function () {
        // Onglets : affiche un panneau, masque les autres.
        document.querySelectorAll('.tabs .tab').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var id = btn.getAttribute('data-tab');
                document.querySelectorAll('.tabs .tab').forEach(function (t) { t.classList.remove('active'); });
                document.querySelectorAll('.tab-panel').forEach(function (p) { p.classList.remove('active'); });
                btn.classList.add('active');
                var panel = document.getElementById('panel-' + id);
                if (panel) { panel.classList.add('active'); }
            });
        });

        // Calendrier : la case « afficher tous les événements publics » recharge la vue.
        var pubToggle = document.getElementById('calPublicToggle');
        if (pubToggle) {
            pubToggle.addEventListener('change', function () {
                var dest = this.checked ? this.getAttribute('data-on') : this.getAttribute('data-off');
                if (dest) { window.location.href = dest; }
            });
        }

        // Bouton « Autour de moi » : géolocalise puis lance la recherche.
        var geoBtn = document.getElementById('geoBtn');
        if (geoBtn && navigator.geolocation) {
            geoBtn.addEventListener('click', function () {
                geoBtn.textContent = '📍 localisation…';
                navigator.geolocation.getCurrentPosition(function (pos) {
                    document.getElementById('nearLat').value = pos.coords.latitude;
                    document.getElementById('nearLng').value = pos.coords.longitude;
                    document.getElementById('nearForm').submit();
                }, function () {
                    geoBtn.textContent = '📍 Autour de moi';
                    alert('Localisation indisponible. Saisis plutôt une ville ou une adresse.');
                });
            });
        } else if (geoBtn) {
            geoBtn.style.display = 'none';
        }

        // Bouton « ⭐ Ma ville » : remplit la zone avec la ville par défaut (+ ses
        // coordonnées mémorisées) puis lance la recherche.
        var useCity = document.getElementById('useDefaultCity');
        if (useCity) {
            useCity.addEventListener('click', function () {
                document.getElementById('near').value    = useCity.getAttribute('data-city') || '';
                document.getElementById('nearLat').value = useCity.getAttribute('data-lat') || '';
                document.getElementById('nearLng').value = useCity.getAttribute('data-lng') || '';
                document.getElementById('nearForm').submit();
            });
        }

        // Carte des résultats (Leaflet) : ma zone + les cours trouvés, par distance.
        var mapEl = document.getElementById('resultsMap');
        if (mapEl && window.L) {
            try {
                var center = JSON.parse(mapEl.getAttribute('data-center'));
                var points = JSON.parse(mapEl.getAttribute('data-points') || '[]');
                var map = L.map('resultsMap');
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© OpenStreetMap', maxZoom: 19
                }).addTo(map);
                var bounds = [];
                L.circleMarker([center.lat, center.lng], { radius: 9, color: '#e63946', fillColor: '#e63946', fillOpacity: .9 })
                    .addTo(map).bindPopup('📍 Votre zone');
                bounds.push([center.lat, center.lng]);

                // Défile jusqu'à la fiche du créneau et la met en surbrillance.
                function goToCard(id) {
                    var el = document.getElementById('near-slot-' + id);
                    if (!el) { return; }
                    el.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    el.classList.add('hi');
                    setTimeout(function () { el.classList.remove('hi'); }, 1800);
                }

                // Plusieurs créneaux peuvent partager le MÊME lieu (même ville =
                // mêmes coordonnées) : sans regroupement, leurs marqueurs se
                // superposent et on n'en voit qu'un. On regroupe donc par
                // coordonnée → 1 marqueur par lieu, dont le popup liste TOUS les
                // créneaux de ce lieu. Ainsi chaque élément reste accessible.
                var groups = {};
                points.forEach(function (p) {
                    var key = p.lat.toFixed(5) + ',' + p.lng.toFixed(5);
                    (groups[key] = groups[key] || []).push(p);
                });
                function slotItem(p) {
                    var item = document.createElement('div'); item.className = 'map-pop-item';
                    var b = document.createElement('b'); b.textContent = p.title; item.appendChild(b);
                    var meta = document.createElement('div'); meta.className = 'map-pop-meta';
                    var lines = [];
                    if (p.when)  { lines.push('📅 ' + p.when); }
                    if (p.owner) { lines.push('👤 ' + p.owner); }
                    lines.push('📍 à ' + p.dist + ' km');
                    if (typeof p.cap !== 'undefined') { lines.push('👥 ' + p.free + ' place' + (p.free > 1 ? 's' : '') + ' libre' + (p.free > 1 ? 's' : '') + ' / ' + p.cap); }
                    meta.innerHTML = lines.join('<br>');
                    item.appendChild(meta);
                    var btn = document.createElement('button');
                    btn.type = 'button'; btn.className = 'map-pop-btn'; btn.textContent = '👀 Voir / réserver →';
                    btn.addEventListener('click', function () { goToCard(p.id); });
                    item.appendChild(btn);
                    return item;
                }
                Object.keys(groups).forEach(function (key) {
                    var grp = groups[key], first = grp[0];
                    var box = document.createElement('div'); box.className = 'map-pop';
                    if (grp.length > 1) {
                        var h = document.createElement('div'); h.className = 'map-pop-head';
                        h.textContent = '📍 ' + grp.length + ' créneaux à ce lieu';
                        box.appendChild(h);
                    }
                    grp.forEach(function (p) { box.appendChild(slotItem(p)); });
                    // Pastille avec le nombre quand plusieurs créneaux au même point.
                    var marker = L.marker([first.lat, first.lng]).addTo(map).bindPopup(box, { minWidth: 210, maxHeight: 320 });
                    if (grp.length > 1) {
                        marker.bindTooltip(String(grp.length), { permanent: true, direction: 'top', offset: [0, -8], className: 'map-count' });
                    }
                    bounds.push([first.lat, first.lng]);
                });
                if (bounds.length > 1) { map.fitBounds(bounds, { padding: [34, 34] }); }
                else { map.setView([center.lat, center.lng], 12); }
                setTimeout(function () { map.invalidateSize(); }, 200);
            } catch (e) {}
        }

        // Adapte le champ « lieu » selon le type (présentiel = adresse, en ligne = lien).
        var locLabel = document.getElementById('locLabel');
        var locInput = document.getElementById('location');
        var locHint  = document.getElementById('locHint');
        function currentMode() {
            var c = document.querySelector('input[name="mode"]:checked');
            return c ? c.value : 'presentiel';
        }
        function syncMode() {
            if (!locLabel || !locInput) { return; }
            if (currentMode() === 'en_ligne') {
                locLabel.textContent = 'Lien de la visio';
                locInput.placeholder = 'Ex : https://meet.google.com/xxx-xxxx-xxx';
                if (locHint) { locHint.style.display = 'none'; }
            } else {
                locLabel.textContent = 'Adresse du lieu';
                locInput.placeholder = 'Commence à taper une ville ou une adresse…';
                if (locHint) { locHint.style.display = ''; }
            }
        }
        document.querySelectorAll('input[name="mode"]').forEach(function (r) { r.addEventListener('change', syncMode); });
        syncMode();

        // Autocomplétion d'adresses (OpenStreetMap / Nominatim, sans clé API)
        // sur tous les champs adresse (création, édition, recherche par zone).
        var cityList = document.getElementById('cityList');
        function bindAutocomplete(input) {
            if (!input || !cityList) { return; }
            var t = null, last = '';
            input.addEventListener('input', function () {
                var f = input.closest('form');
                var m = f ? f.querySelector('input[name="mode"]:checked') : null;
                if (m && m.value === 'en_ligne') { return; } // en visio : pas d'adresse
                var q = input.value.trim();
                if (q.length < 3 || q === last) { return; }
                last = q;
                clearTimeout(t);
                t = setTimeout(function () {
                    var url = 'https://nominatim.openstreetmap.org/search?format=json&accept-language=fr&limit=6&addressdetails=0&q=' + encodeURIComponent(q);
                    fetch(url, { headers: { 'Accept': 'application/json' } })
                        .then(function (r) { return r.json(); })
                        .then(function (rows) {
                            cityList.innerHTML = '';
                            (rows || []).forEach(function (row) {
                                var opt = document.createElement('option');
                                opt.value = row.display_name;
                                cityList.appendChild(opt);
                            });
                        })
                        .catch(function () {});
                }, 400);
            });
        }
        document.querySelectorAll('.loc-input').forEach(bindAutocomplete);

        // Bascule des cartes (l'iframe Google Maps n'est chargée qu'au clic).
        document.querySelectorAll('.map-toggle').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var loc = btn.closest('.slot-loc');
                var box = loc ? loc.nextElementSibling : null;
                if (!box || !box.classList.contains('map-box')) { return; }
                var iframe = box.querySelector('iframe');
                if (box.hasAttribute('hidden')) {
                    if (iframe && !iframe.src) { iframe.src = iframe.getAttribute('data-src'); }
                    box.removeAttribute('hidden');
                    btn.textContent = '✕ Masquer la carte';
                    btn.setAttribute('aria-expanded', 'true');
                } else {
                    box.setAttribute('hidden', '');
                    btn.textContent = '🗺️ Voir la carte';
                    btn.setAttribute('aria-expanded', 'false');
                }
            });
        });

        // Copier le code d'un RDV privé.
        document.querySelectorAll('.copy-code').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var code = btn.getAttribute('data-code') || '';
                var done = function () { var t = btn.textContent; btn.textContent = 'copié ✓'; setTimeout(function () { btn.textContent = t; }, 1500); };
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(code).then(done, done);
                } else { done(); }
            });
        });
    })();

    // Recherche d'événements (filtre les créneaux publics par titre/hôte/lieu)
    (function () {
        var input = document.getElementById('evtSearch');
        var list  = document.getElementById('allSlots');
        var none  = document.getElementById('evtNone');
        if (!input || !list) { return; }
        input.addEventListener('input', function () {
            var q = input.value.trim().toLowerCase(), shown = 0;
            list.querySelectorAll('.slot-wrap').forEach(function (w) {
                var ok = q === '' || (w.getAttribute('data-find') || '').indexOf(q) !== -1;
                w.style.display = ok ? '' : 'none';
                if (ok) { shown++; }
            });
            if (none) { none.style.display = shown === 0 ? 'block' : 'none'; }
        });
    })();
    </script>
</body>
</html>
