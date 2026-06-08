<?php
/**
 * AGENDA GLOBAL — vue d'ensemble de tous les rendez-vous (à venir + passés),
 * tous membres confondus, regroupés par jour. Lecture seule : pour réserver /
 * gérer, on renvoie vers l'agenda principal.
 * Variables : $upcoming, $past, $total, $totalPlaces, $totalBooked, $myBookedIds, $isAdmin.
 */
$jours = ['Sun' => 'dimanche', 'Mon' => 'lundi', 'Tue' => 'mardi', 'Wed' => 'mercredi', 'Thu' => 'jeudi', 'Fri' => 'vendredi', 'Sat' => 'samedi'];
$mois  = [1 => 'janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];

// Libellé long d'un jour : "lundi 9 juin 2026".
$dayLabel = function ($ts) use ($jours, $mois) {
    return $jours[date('D', $ts)] . ' ' . (int) date('j', $ts) . ' ' . $mois[(int) date('n', $ts)] . ' ' . date('Y', $ts);
};
// Plage horaire d'un créneau : "14:00 – 15:00".
$timeRange = function ($start, $dur) {
    $s = strtotime($start);
    $e = $s + ((int) $dur) * 60;
    return date('H:i', $s) . ' – ' . date('H:i', $e);
};
// Résumé court du lieu.
$locShort = function ($a) {
    $mode = $a['mode'] ?? 'presentiel';
    $loc  = trim((string) ($a['location'] ?? ''));
    if ($mode === 'en_ligne') {
        return '🔗 En ligne';
    }
    return '📍 ' . ($loc !== '' ? htmlspecialchars($loc) : 'En présentiel');
};

$uid = (int) ($user['id'] ?? 0);

// Affiche une liste de créneaux regroupés par jour.
$renderGroups = function (array $list, bool $isPast) use ($dayLabel, $timeRange, $locShort, $uid, $myBookedIds) {
    if (!$list) {
        echo '<p class="empty">Aucun rendez-vous ' . ($isPast ? 'passé' : 'à venir') . '.</p>';
        return;
    }
    $currentDay = null;
    foreach ($list as $a) {
        $ts  = strtotime($a['start_at']);
        $day = date('Y-m-d', $ts);
        if ($day !== $currentDay) {
            if ($currentDay !== null) { echo '</div>'; }
            echo '<div class="day-head">' . htmlspecialchars($dayLabel($ts)) . '</div><div class="day-slots">';
            $currentDay = $day;
        }
        $cap     = (int) $a['capacity'];
        $booked  = (int) ($a['booked'] ?? 0);
        $free    = max(0, $cap - $booked);
        $isOwn   = (int) $a['owner_id'] === $uid;
        $iBooked = in_array((int) $a['id'], $myBookedIds, true);
        $isPriv  = ($a['visibility'] ?? 'public') === 'private';
        ?>
        <a class="g-slot<?= $isPast ? ' past' : '' ?>" href="<?= url('agenda/event') ?>?id=<?= (int) $a['id'] ?>">
            <div class="g-time"><?= htmlspecialchars($timeRange($a['start_at'], $a['duration_min'])) ?></div>
            <div class="g-body">
                <div class="g-title">
                    <?= htmlspecialchars($a['title']) ?>
                    <?php if ($isPriv): ?><span class="g-tag priv">🔒 privé</span><?php endif; ?>
                    <?php if ($isOwn): ?><span class="g-tag mine">le tien</span><?php endif; ?>
                    <?php if ($iBooked): ?><span class="g-tag booked">réservé</span><?php endif; ?>
                </div>
                <div class="g-meta">
                    par <?= htmlspecialchars($a['owner_name'] ?: 'Membre') ?> · <?= $locShort($a) ?>
                </div>
                <?php if (!$isPast): ?>
                    <div class="g-cd">⏳ <?= countdown_html($a['start_at'], (int) $a['duration_min']) ?></div>
                <?php endif; ?>
            </div>
            <div class="g-places <?= $free > 0 ? 'free' : 'full' ?>">
                <?= $booked ?>/<?= $cap ?><br><span><?= $isPast ? 'terminé' : ($free > 0 ? $free . ' libre' . ($free > 1 ? 's' : '') : 'complet') ?></span>
            </div>
        </a>
        <?php
    }
    echo '</div>';
};
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(Settings::get('main_title', 'RPN')) ?> — Agenda global</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <?= Theme::css() ?>
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
        .nav { display:flex; gap:10px; flex-wrap:wrap; }
        .nav a { color:var(--text); text-decoration:none; font-size:14px; padding:9px 16px; border-radius:10px; border:1px solid var(--card-border); }
        .nav a:hover { border-color:var(--accent); color:var(--accent); }
        .nav a.primary { background:var(--accent); color:var(--accent-ink); border-color:var(--accent); font-weight:700; }
        /* Mobile : titre en haut, barre de liens DESSOUS, aérée. */
        @media (max-width:640px) {
            .topbar { flex-direction:column; align-items:stretch; gap:14px; margin-bottom:18px; }
            .nav a { flex:1 1 auto; text-align:center; }
        }
        .nav-badge { display:inline-block; min-width:18px; padding:0 5px; height:18px; line-height:18px; text-align:center;
            border-radius:999px; background:var(--rouge); color:#fff; font-size:11px; font-weight:700; }

        /* Sélecteur de vue */
        .view-switch { display:inline-flex; gap:6px; margin:24px 0 4px; padding:5px; border-radius:12px;
            background:var(--card-bg); border:1px solid var(--card-border); }
        .vs-btn { cursor:pointer; font-family:inherit; font-size:13px; font-weight:600; color:var(--text);
            background:transparent; border:none; border-radius:9px; padding:9px 16px; }
        .vs-btn.active { background:var(--accent); color:var(--accent-ink); }
        .view-panel { margin-top:8px; }

        /* Barre de navigation des mois + pastille */
        .cal-bar { display:flex; align-items:center; gap:12px; margin:14px 0 14px; flex-wrap:wrap; }
        .cal-nav { text-decoration:none; font-size:20px; font-weight:700; line-height:1; color:var(--text);
            width:40px; height:40px; display:flex; align-items:center; justify-content:center;
            border:1px solid var(--card-border); border-radius:10px; }
        .cal-nav:hover { border-color:var(--accent); color:var(--accent); }
        .cal-pill { font-size:16px; font-weight:800; color:var(--accent-ink); background:var(--accent);
            padding:9px 20px; border-radius:999px; text-transform:capitalize; min-width:170px; text-align:center; }
        .cal-today { margin-left:auto; text-decoration:none; font-size:13px; font-weight:600; color:var(--text);
            border:1px solid var(--card-border); border-radius:10px; padding:9px 14px; }
        .cal-today:hover { border-color:var(--accent); color:var(--accent); }

        /* Grille du calendrier */
        .cal-grid { display:grid; grid-template-columns:repeat(7, 1fr); gap:6px; }
        .cal-head { margin-bottom:6px; }
        .cal-dow { text-align:center; font-size:12px; font-weight:700; color:var(--muted); text-transform:uppercase; letter-spacing:.5px; padding:4px 0; }
        .cal-cell { min-height:96px; background:var(--card-bg); border:1px solid var(--card-border); border-radius:12px;
            padding:6px 7px; display:flex; flex-direction:column; gap:4px; overflow:hidden; text-decoration:none; color:inherit; }
        a.cal-cell:hover { border-color:var(--accent); }
        .cal-cell.empty { background:transparent; border:1px dashed var(--card-border); opacity:.4; }
        .cal-cell.today { border-color:var(--accent); box-shadow:0 0 0 2px var(--accent) inset; }
        .cal-num { font-size:13px; font-weight:700; display:flex; align-items:center; justify-content:space-between; }
        .cal-daylink { text-decoration:none; color:inherit; padding:1px 5px; border-radius:6px; }
        .cal-daylink:hover { color:var(--accent); background:rgba(127,127,127,.12); }
        .cal-count { font-size:10px; font-weight:700; color:var(--accent-ink); background:var(--accent);
            border-radius:999px; padding:1px 7px; }
        .cal-events { display:flex; flex-direction:column; gap:3px; margin-top:3px; }
        .cal-ev { display:block; text-decoration:none; color:inherit; cursor:pointer; font-size:10.5px; line-height:1.25;
            padding:2px 5px; border-radius:6px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
            border-left:3px solid var(--accent); background:rgba(127,127,127,.10); transition:filter .12s, background .12s; }
        a.cal-ev:hover { filter:brightness(1.05); background:rgba(127,127,127,.2); }
        .cal-ev.pub  { border-left-color:#2a9d4a; }
        .cal-ev.mine { border-left-color:var(--accent); background:rgba(244,193,75,.16); }
        .cal-ev.priv { border-left-color:#64748b; }
        .cal-more { display:inline-block; font-size:10px; color:var(--muted); padding-left:4px; text-decoration:none; }
        .cal-more:hover { color:var(--accent); }
        .cal-legend { display:flex; gap:18px; flex-wrap:wrap; margin-top:14px; font-size:12px; color:var(--muted); }
        .cal-legend i.lg { display:inline-block; width:12px; height:12px; border-radius:3px; margin-right:5px; vertical-align:-1px; }
        .cal-legend i.pub  { background:#2a9d4a; }
        .cal-legend i.mine { background:var(--accent); }
        .cal-legend i.priv { background:#64748b; }
        .cal-hint { margin-left:auto; font-style:italic; }

        /* Vue Semaine (grille horaire façon Google Agenda) */
        .wk-scroll { overflow-x:auto; }
        .wk-head { display:flex; min-width:560px; }
        .wk-corner { flex:0 0 54px; }
        .wk-dayhead { flex:1; text-align:center; padding:8px 4px; border-bottom:1px solid var(--card-border); }
        .wk-dayhead.today { color:var(--accent); }
        .wk-dn { display:block; font-size:11px; text-transform:uppercase; letter-spacing:.5px; color:var(--muted); }
        .wk-dayhead.today .wk-dn { color:var(--accent); }
        .wk-dd { display:block; font-size:18px; font-weight:800; }
        .wk-body { display:flex; position:relative; min-width:560px; }
        .wk-gutter { flex:0 0 54px; position:relative; }
        .wk-hour { position:absolute; right:7px; font-size:11px; color:var(--muted); transform:translateY(-50%); white-space:nowrap; }
        .wk-col { flex:1; position:relative; border-left:1px solid var(--card-border); }
        .wk-col.today { background:rgba(127,127,127,.05); }
        .wk-line { position:absolute; left:0; right:0; border-top:1px solid var(--card-border); opacity:.5; }
        .wk-ev { position:absolute; left:3px; right:3px; padding:3px 6px; border-radius:7px; overflow:hidden;
            font-size:11px; line-height:1.3; color:#fff; background:#2a9d4a; box-shadow:0 2px 6px rgba(0,0,0,.25); cursor:default; }
        .wk-ev b { font-weight:700; }
        .wk-ev.pub  { background:#2a9d4a; }
        .wk-ev.mine { background:var(--accent); color:var(--accent-ink); }
        .wk-ev.priv { background:#64748b; }
        @media (max-width:620px) {
            .cal-cell { min-height:70px; }
            .cal-ev { font-size:0; padding:3px; }   /* sur mobile : pastilles compactes */
            .cal-ev::before { content:""; display:block; width:100%; height:5px; border-radius:3px; background:currentColor; }
            .cal-pill { min-width:130px; font-size:14px; }
        }

        /* Statistiques globales */
        .stats { display:grid; grid-template-columns:repeat(4, 1fr); gap:14px; margin-bottom:10px; }
        @media (max-width:560px) { .stats { grid-template-columns:repeat(2, 1fr); } }
        .stat { background:var(--card-bg); border:1px solid var(--card-border); border-radius:16px;
            box-shadow:var(--card-shadow); padding:18px 16px; text-align:center; cursor:pointer; font-family:inherit;
            width:100%; transition:border-color .15s, transform .12s; }
        .stat:hover { border-color:var(--accent); transform:translateY(-2px); }
        .stat.active { border-color:var(--accent); box-shadow:0 0 0 2px var(--accent) inset; }
        .stat b { display:block; font-size:28px; color:var(--accent); line-height:1.1; }
        .stat span { font-size:12px; color:var(--muted); text-transform:uppercase; letter-spacing:.5px; }
        .stats-hint { font-size:12px; color:var(--muted); margin:0 4px 10px; }
        /* Panneaux dépliables sous les statistiques */
        .stat-detail { background:var(--card-bg); border:1px solid var(--accent); border-radius:14px; padding:8px;
            margin-bottom:14px; display:flex; flex-direction:column; gap:4px; box-shadow:var(--card-shadow); }
        .sd-row { display:flex; align-items:center; gap:12px; padding:10px 12px; border-radius:10px; text-decoration:none;
            color:inherit; border:1px solid transparent; }
        .sd-row:hover { border-color:var(--accent); background:rgba(127,127,127,.06); }
        .sd-when { font-size:12px; color:var(--muted); flex:0 0 auto; min-width:150px; }
        .sd-title { flex:1; min-width:0; font-weight:600; font-size:14px; }
        .sd-places { font-size:13px; color:var(--accent); font-weight:700; flex:0 0 auto; }
        .sd-empty { color:var(--muted); font-size:13px; padding:12px; text-align:center; }
        @media (max-width:560px) { .sd-when { min-width:0; } .sd-row { flex-wrap:wrap; gap:4px 12px; } }
        /* Les créneaux (liste) et événements (semaine) sont des liens : pas de soulignement */
        a.g-slot { text-decoration:none; color:inherit; transition:border-color .15s, transform .12s; }
        a.g-slot:hover { border-color:var(--accent); transform:translateX(3px); }
        a.wk-ev { text-decoration:none; cursor:pointer; }
        a.wk-ev:hover { filter:brightness(1.08); box-shadow:0 4px 12px rgba(0,0,0,.35); }

        .section-title { font-size:12px; text-transform:uppercase; letter-spacing:1.5px; color:var(--muted); margin:30px 4px 14px; }

        .day-head { font-size:14px; font-weight:700; color:var(--accent); margin:20px 2px 10px;
            padding-bottom:6px; border-bottom:1px solid var(--card-border); text-transform:capitalize; }
        .day-slots { display:flex; flex-direction:column; gap:10px; }
        .g-slot { display:flex; align-items:center; gap:16px; padding:13px 16px; background:var(--card-bg);
            border:1px solid var(--card-border); border-radius:14px; box-shadow:var(--card-shadow); }
        .g-slot.past { opacity:.6; }
        .g-time { flex:0 0 auto; font-size:13px; font-weight:700; color:var(--text); width:96px; }
        .g-body { flex:1; min-width:0; }
        .g-title { font-size:15px; font-weight:700; display:flex; align-items:center; gap:8px; flex-wrap:wrap; }
        .g-meta { font-size:12px; color:var(--muted); margin-top:2px; }
        .g-cd { font-size:13px; font-weight:700; color:var(--accent); margin-top:4px; }
        .countdown { font-variant-numeric:tabular-nums; }
        .countdown.soon { color:var(--rouge); font-family:'Courier New', monospace; letter-spacing:1px; }
        .countdown.done { color:#2a9d4a; font-weight:700; }
        /* « En direct » : pastille rouge bien visible qui clignote pendant le créneau. */
        .countdown.live { color:#fff; background:var(--rouge); font-weight:800; letter-spacing:.5px;
            padding:2px 10px; border-radius:999px; animation:cd-blink 1s steps(1,end) infinite; }
        @keyframes cd-blink { 50% { opacity:.25; } }
        .g-tag { font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.5px; padding:2px 8px; border-radius:999px; }
        .g-tag.priv { background:rgba(127,127,127,.15); color:var(--muted); }
        .g-tag.mine { background:var(--accent); color:var(--accent-ink); }
        .g-tag.booked { background:rgba(42,157,74,.18); color:#2a9d4a; }
        .g-places { flex:0 0 auto; text-align:center; font-size:15px; font-weight:700; min-width:64px; }
        .g-places span { display:block; font-size:10px; font-weight:600; text-transform:uppercase; letter-spacing:.5px; }
        .g-places.free { color:#2a9d4a; }
        .g-places.full { color:var(--muted); }
        .empty { color:var(--muted); font-size:14px; padding:8px 4px; }

        details.past-wrap { margin-top:8px; }
        details.past-wrap > summary { cursor:pointer; font-size:13px; font-weight:600; color:var(--accent);
            list-style:none; padding:8px 0; }
        details.past-wrap > summary::-webkit-details-marker { display:none; }
        details.past-wrap > summary::before { content:"▸ "; }
        details.past-wrap[open] > summary::before { content:"▾ "; }
    </style>
</head>
<body>
    <div class="wrap">
        <header class="topbar">
            <h1>🗓️ <span>Agenda</span> global</h1>
            <div class="nav">
                <a href="<?= url('agenda') ?>">← Mon agenda</a>
                <a href="<?= url('notifications') ?>">🔔 Notifications<?php if (!empty($unreadNotifs)): ?> <span class="nav-badge"><?= (int) $unreadNotifs > 9 ? '9+' : (int) $unreadNotifs ?></span><?php endif; ?></a>
                <a class="primary" href="<?= url('agenda') ?>">＋ Proposer un créneau</a>
            </div>
        </header>

        <p class="section-title">Vue d'ensemble de tous les rendez-vous<?= $isAdmin ? ' (admin : créneaux privés inclus)' : '' ?></p>

        <?php
            // Données des panneaux dépliables sous les statistiques.
            $allRdv     = array_merge($upcoming, $past);
            $bookedRdv  = array_values(array_filter($allRdv, fn ($a) => (int) ($a['booked'] ?? 0) > 0));
            // Mini-liste cliquable : chaque ligne mène à la page de l'événement.
            $miniList = function (array $items) use ($dayLabel, $timeRange) {
                if (empty($items)) { echo '<p class="sd-empty">Aucun élément à afficher.</p>'; return; }
                foreach ($items as $a) {
                    $cap = (int) $a['capacity']; $bk = (int) ($a['booked'] ?? 0);
                    echo '<a class="sd-row" href="' . url('agenda/event') . '?id=' . (int) $a['id'] . '">'
                       . '<span class="sd-when">' . htmlspecialchars($dayLabel(strtotime($a['start_at']))) . ' · ' . htmlspecialchars($timeRange($a['start_at'], $a['duration_min'])) . '</span>'
                       . '<span class="sd-title">' . htmlspecialchars($a['title']) . '</span>'
                       . '<span class="sd-places">👥 ' . $bk . '/' . $cap . '</span>'
                       . '</a>';
                }
            };
        ?>
        <div class="stats">
            <button type="button" class="stat" data-target="sd-total"><b><?= (int) $total ?></b><span>RDV au total</span></button>
            <button type="button" class="stat" data-target="sd-upcoming"><b><?= count($upcoming) ?></b><span>À venir</span></button>
            <button type="button" class="stat" data-target="sd-booked"><b><?= (int) $totalBooked ?></b><span>Places réservées</span></button>
            <button type="button" class="stat" data-target="sd-places"><b><?= (int) $totalPlaces ?></b><span>Places au total</span></button>
        </div>
        <p class="stats-hint">👆 Clique sur une statistique pour voir les rendez-vous concernés.</p>

        <div class="stat-detail" id="sd-total" hidden><?php $miniList($allRdv); ?></div>
        <div class="stat-detail" id="sd-upcoming" hidden><?php $miniList($upcoming); ?></div>
        <div class="stat-detail" id="sd-booked" hidden><?php $miniList($bookedRdv); ?></div>
        <div class="stat-detail" id="sd-places" hidden><?php $miniList($allRdv); ?></div>

        <!-- Sélecteur de vue : Mois ⇄ Semaine ⇄ Liste (façon Google Agenda) -->
        <div class="view-switch">
            <button type="button" class="vs-btn <?= $view === 'cal' ? 'active' : '' ?>" data-view="cal">📅 Mois</button>
            <button type="button" class="vs-btn <?= $view === 'week' ? 'active' : '' ?>" data-view="week">📆 Semaine</button>
            <button type="button" class="vs-btn <?= $view === 'list' ? 'active' : '' ?>" data-view="list">📋 Liste</button>
        </div>

        <!-- ============ VUE CALENDRIER (mois) ============ -->
        <section class="view-panel" id="view-cal" <?= $view === 'cal' ? '' : 'hidden' ?>>
            <?php
                // Mois affiché + navigation (mois précédent / suivant).
                $mTs    = strtotime($month . '-01');
                $y      = (int) date('Y', $mTs);
                $mNum   = (int) date('n', $mTs);
                $prev   = date('Y-m', strtotime('-1 month', $mTs));
                $next   = date('Y-m', strtotime('+1 month', $mTs));
                $base   = url('agenda/global');
                $todayK = date('Y-m-d');

                // Construction de la grille (semaines commençant le lundi).
                $daysIn  = (int) date('t', $mTs);
                $lead    = (int) date('N', $mTs) - 1;   // cases vides avant le 1er (0=lundi)
                $cells   = array_fill(0, $lead, null);
                for ($d = 1; $d <= $daysIn; $d++) { $cells[] = $d; }
                while (count($cells) % 7 !== 0) { $cells[] = null; }
            ?>
            <div class="cal-bar">
                <a class="cal-nav" href="<?= $base ?>?view=cal&month=<?= $prev ?>" title="Mois précédent">‹</a>
                <span class="cal-pill"><?= $mois[$mNum] ?> <?= $y ?></span>
                <a class="cal-nav" href="<?= $base ?>?view=cal&month=<?= $next ?>" title="Mois suivant">›</a>
                <a class="cal-today" href="<?= $base ?>?view=cal&month=<?= date('Y-m') ?>">Aujourd'hui</a>
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
                        $key   = sprintf('%s-%02d', $month, $d);
                        $items = $byDate[$key] ?? [];
                        $isToday = ($key === $todayK);
                    ?>
                        <div class="cal-cell<?= $isToday ? ' today' : '' ?><?= $items ? ' has' : '' ?>">
                            <div class="cal-num">
                                <a class="cal-daylink" href="<?= $base ?>?view=week&week=<?= $key ?>" title="Voir la semaine du <?= $d ?>"><?= $d ?></a>
                                <?php if ($items): ?><span class="cal-count"><?= count($items) ?></span><?php endif; ?>
                            </div>
                            <div class="cal-events">
                                <?php foreach (array_slice($items, 0, 3) as $ev):
                                    $own  = (int) $ev['owner_id'] === $uid;
                                    $priv = ($ev['visibility'] ?? 'public') === 'private';
                                    $cls  = $own ? 'mine' : ($priv ? 'priv' : 'pub');
                                ?>
                                    <a class="cal-ev <?= $cls ?>" href="<?= url('agenda/event') ?>?id=<?= (int) $ev['id'] ?>"
                                       title="<?= htmlspecialchars(date('H:i', strtotime($ev['start_at'])) . ' · ' . $ev['title'] . ' — ' . ($ev['owner_name'] ?: 'Membre')) ?>">
                                        <?= date('H:i', strtotime($ev['start_at'])) ?> <?= htmlspecialchars($ev['title']) ?>
                                    </a>
                                <?php endforeach; ?>
                                <?php if (count($items) > 3): ?>
                                    <a class="cal-more" href="<?= $base ?>?view=week&week=<?= $key ?>">+<?= count($items) - 3 ?> autre<?= count($items) - 3 > 1 ? 's' : '' ?></a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
            <div class="cal-legend">
                <span><i class="lg pub"></i> Public</span>
                <span><i class="lg mine"></i> Le tien</span>
                <span><i class="lg priv"></i> Privé</span>
                <span class="cal-hint">Clique un jour pour voir la semaine →</span>
            </div>
        </section>

        <!-- ============ VUE SEMAINE (grille horaire, façon Google Agenda) ============ -->
        <section class="view-panel" id="view-week" <?= $view === 'week' ? '' : 'hidden' ?>>
            <?php
                $wkStart = strtotime($weekMonday);
                $days    = [];
                for ($i = 0; $i < 7; $i++) { $days[] = strtotime("+$i day", $wkStart); }
                $prevW = date('Y-m-d', strtotime('-7 day', $wkStart));
                $nextW = date('Y-m-d', strtotime('+7 day', $wkStart));
                $endTs = strtotime('+6 day', $wkStart);

                // Plage horaire : ajustée aux événements de la semaine (défaut 8 h–20 h).
                $minH = 8; $maxH = 20;
                foreach ($days as $dts) {
                    foreach ($byDate[date('Y-m-d', $dts)] ?? [] as $ev) {
                        $s   = strtotime($ev['start_at']);
                        $eMin = (int) date('G', $s) * 60 + (int) date('i', $s) + (int) $ev['duration_min'];
                        $minH = min($minH, (int) date('G', $s));
                        $maxH = max($maxH, (int) ceil($eMin / 60));
                    }
                }
                $minH = max(0, min($minH, 8));
                $maxH = min(24, max($maxH, 20));
                $hourPx  = 46;
                $colH    = ($maxH - $minH) * $hourPx;
                $jShort  = ['Mon' => 'lun.', 'Tue' => 'mar.', 'Wed' => 'mer.', 'Thu' => 'jeu.', 'Fri' => 'ven.', 'Sat' => 'sam.', 'Sun' => 'dim.'];
            ?>
            <div class="cal-bar">
                <a class="cal-nav" href="<?= $base ?>?view=week&week=<?= $prevW ?>" title="Semaine précédente">‹</a>
                <span class="cal-pill">Sem. du <?= (int) date('j', $wkStart) ?> <?= $mois[(int) date('n', $wkStart)] ?>
                    <?= date('n', $wkStart) !== date('n', $endTs) ? ' – ' . (int) date('j', $endTs) . ' ' . $mois[(int) date('n', $endTs)] : '' ?> <?= date('Y', $wkStart) ?></span>
                <a class="cal-nav" href="<?= $base ?>?view=week&week=<?= $nextW ?>" title="Semaine suivante">›</a>
                <a class="cal-today" href="<?= $base ?>?view=week&week=<?= date('Y-m-d') ?>">Cette semaine</a>
            </div>

            <div class="wk-scroll">
                <div class="wk-head">
                    <div class="wk-corner"></div>
                    <?php foreach ($days as $dts): $isT = date('Y-m-d', $dts) === $todayK; ?>
                        <div class="wk-dayhead<?= $isT ? ' today' : '' ?>">
                            <span class="wk-dn"><?= $jShort[date('D', $dts)] ?></span>
                            <span class="wk-dd"><?= (int) date('j', $dts) ?></span>
                        </div>
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
                            <?php foreach ($byDate[date('Y-m-d', $dts)] ?? [] as $ev):
                                $s    = strtotime($ev['start_at']);
                                $sMin = (int) date('G', $s) * 60 + (int) date('i', $s);
                                $top  = ($sMin - $minH * 60) / 60 * $hourPx;
                                $hgt  = max(24, (int) $ev['duration_min'] / 60 * $hourPx);
                                $own  = (int) $ev['owner_id'] === $uid;
                                $priv = ($ev['visibility'] ?? 'public') === 'private';
                                $cls  = $own ? 'mine' : ($priv ? 'priv' : 'pub');
                            ?>
                                <a class="wk-ev <?= $cls ?>" href="<?= url('agenda/event') ?>?id=<?= (int) $ev['id'] ?>"
                                   style="top:<?= $top ?>px; height:<?= $hgt ?>px"
                                   title="<?= htmlspecialchars(date('H:i', $s) . ' · ' . $ev['title'] . ' — ' . ($ev['owner_name'] ?: 'Membre')) ?>">
                                    <b><?= date('H:i', $s) ?></b> <?= htmlspecialchars($ev['title']) ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <!-- ============ VUE LISTE ============ -->
        <section class="view-panel" id="view-list" <?= $view === 'list' ? '' : 'hidden' ?>>
            <p class="section-title">📌 À venir (<?= count($upcoming) ?>)</p>
            <?php $renderGroups($upcoming, false); ?>

            <p class="section-title">🕓 Passés (<?= count($past) ?>)</p>
            <?php if (!empty($past)): ?>
                <details class="past-wrap">
                    <summary>Afficher les rendez-vous passés</summary>
                    <?php $renderGroups($past, true); ?>
                </details>
            <?php else: ?>
                <p class="empty">Aucun rendez-vous passé.</p>
            <?php endif; ?>
        </section>
    </div>

    <script>
    // Libellé d'état d'un créneau, en direct : « Dans … » / « 🔴 EN DIRECT » / « Déjà passé ».
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
                if (diff < 86400) { el.classList.add('soon'); }
            });
        }
        tick();
        setInterval(tick, 1000);
    })();
    </script>

    <script>
    // Statistiques cliquables : afficher/masquer la liste des rendez-vous concernés.
    (function () {
        var stats = document.querySelectorAll('.stat[data-target]');
        function closeAll() {
            document.querySelectorAll('.stat-detail').forEach(function (p) { p.hidden = true; });
            stats.forEach(function (s) { s.classList.remove('active'); });
        }
        stats.forEach(function (s) {
            s.addEventListener('click', function () {
                var panel = document.getElementById(s.getAttribute('data-target'));
                var wasOpen = panel && !panel.hidden;
                closeAll();
                if (panel && !wasOpen) {
                    panel.hidden = false;
                    s.classList.add('active');
                    panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                }
            });
        });
    })();
    </script>

    <script>
    // Bascule Mois ⇄ Semaine ⇄ Liste (amélioration progressive ; l'état initial vient du serveur).
    (function () {
        var btns   = document.querySelectorAll('.vs-btn');
        var panels = {
            cal:  document.getElementById('view-cal'),
            week: document.getElementById('view-week'),
            list: document.getElementById('view-list')
        };
        btns.forEach(function (b) {
            b.addEventListener('click', function () {
                var v = b.getAttribute('data-view');
                btns.forEach(function (x) { x.classList.toggle('active', x === b); });
                Object.keys(panels).forEach(function (k) {
                    if (panels[k]) { panels[k].hidden = (k !== v); }
                });
            });
        });
    })();
    </script>
</body>
</html>
