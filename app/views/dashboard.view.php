<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(Settings::get('main_title', 'RPN')) ?> — Tableau de bord</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
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

        /* En-tête */
        .topbar { display:flex; justify-content:space-between; align-items:center; gap:16px; flex-wrap:wrap; margin-bottom:26px; }
        .greet .hi { color:var(--muted); font-size:14px; }
        .greet h1 { font-size:26px; font-weight:800; line-height:1.2; }
        .greet h1 span { color:var(--accent); }
        .logout { display:inline-flex; align-items:center; gap:8px; text-decoration:none; font-weight:600; font-size:14px;
            color:#fff; background:linear-gradient(135deg, var(--rouge), #b71c2c); padding:11px 20px; border-radius:12px;
            transition:transform .15s, box-shadow .2s; }
        .logout:hover { transform:translateY(-2px); box-shadow:0 12px 30px rgba(230,57,70,.35); }

        .card { background:var(--card-bg); border:1px solid var(--card-border); border-radius:20px; box-shadow:var(--card-shadow); }

        /* Carte profil */
        .profile { display:flex; align-items:center; gap:24px; padding:28px 30px; margin-bottom:24px; position:relative; overflow:hidden; }
        .profile::after { content:""; position:absolute; right:-50px; top:-50px; width:180px; height:180px; border-radius:50%;
            background:radial-gradient(circle, var(--glow1), transparent 70%); pointer-events:none; }
        .avatar-ring { width:92px; height:92px; flex:0 0 92px; border-radius:50%; padding:4px;
            background:linear-gradient(135deg, var(--rouge), var(--or), var(--vert)); }
        .avatar { width:100%; height:100%; border-radius:50%; object-fit:cover; border:3px solid var(--bg-base); display:block; }
        .pmeta { min-width:0; position:relative; z-index:1; }
        .pmeta .pname { font-size:22px; font-weight:800; margin-bottom:8px; }
        .badge { display:inline-block; padding:4px 13px; border-radius:999px; font-size:12px; font-weight:600;
            background:var(--card-border); color:var(--accent); border:1px solid var(--accent); margin-bottom:10px; }
        .pmeta .email { color:var(--muted); font-size:14px; word-break:break-all; }
        .update-btn { margin-top:10px; font:inherit; font-size:13px; font-weight:600; cursor:pointer;
            color:var(--text); background:var(--card-bg); border:1px solid var(--card-border); border-radius:10px; padding:8px 14px; }
        .update-btn:hover { border-color:var(--accent); color:var(--accent); }
        .update-btn:disabled { opacity:.7; cursor:default; }
        /* Photo de profil : ajout / changement / retrait */
        .photo-form { display:flex; align-items:center; gap:10px; flex-wrap:wrap; margin-top:12px; }
        .photo-pick { display:inline-flex; align-items:center; gap:7px; cursor:pointer; font-size:13px; font-weight:600;
            color:var(--accent); border:1px solid var(--accent); border-radius:10px; padding:8px 14px; transition:background .15s, color .15s; }
        .photo-pick:hover { background:var(--accent); color:var(--accent-ink); }
        .photo-pick input { display:none; }
        .photo-remove { font:inherit; font-size:13px; font-weight:600; cursor:pointer; color:var(--muted);
            background:none; border:1px solid var(--card-border); border-radius:10px; padding:8px 12px; }
        .photo-remove:hover { color:var(--rouge,#e63946); border-color:var(--rouge,#e63946); }
        .photo-error { background:rgba(230,57,70,.12); border:1px solid var(--rouge,#e63946); color:var(--text);
            padding:11px 14px; border-radius:12px; margin-bottom:16px; font-size:14px; }

        /* Statistiques */
        .stats { display:grid; grid-template-columns:repeat(auto-fit, minmax(150px, 1fr)); gap:16px; margin-bottom:26px; }
        .stat { padding:22px 24px; text-decoration:none; color:inherit; display:block;
            transition:transform .15s, border-color .15s; }
        a.stat:hover { transform:translateY(-3px); border-color:var(--accent); }
        .stat .num { font-size:34px; font-weight:800; color:var(--accent); line-height:1; }
        .stat .lbl { font-size:13px; color:var(--muted); margin-top:8px; }

        /* Actions rapides */
        .section-title { font-size:12px; text-transform:uppercase; letter-spacing:1.5px; color:var(--muted); margin:0 4px 14px; }
        .actions { display:grid; grid-template-columns:repeat(auto-fit, minmax(250px, 1fr)); gap:16px; }
        .action { display:flex; align-items:center; gap:16px; padding:20px 22px; text-decoration:none; color:inherit;
            transition:transform .15s, border-color .15s, box-shadow .2s; }
        .action:hover { transform:translateY(-3px); border-color:var(--accent); }
        .action .ico { position:relative; width:50px; height:50px; flex:0 0 50px; border-radius:14px; display:flex; align-items:center; justify-content:center;
            font-size:24px; background:rgba(127,127,127,.12); }
        .notif-badge { position:absolute; top:-6px; right:-6px; min-width:20px; height:20px; padding:0 5px; border-radius:999px;
            background:var(--rouge); color:#fff; font-size:11px; font-weight:700; display:flex; align-items:center; justify-content:center;
            border:2px solid var(--card-bg); }
        .action.accent .ico { background:var(--accent); color:var(--accent-ink); }
        .action .txt { flex:1; min-width:0; }
        .action h3 { font-size:16px; font-weight:700; margin-bottom:2px; }
        .action p { font-size:13px; color:var(--muted); }
        .action .arrow { color:var(--accent); font-size:20px; transition:transform .15s; }
        .action:hover .arrow { transform:translateX(5px); }

        /* Alertes URGENT (carré rouge) */
        .urgents { display:flex; flex-direction:column; gap:10px; margin-bottom:22px; }
        .urgent { display:flex; align-items:center; gap:12px; background:rgba(230,57,70,.10);
            border:1px solid var(--rouge, #e63946); border-left:6px solid var(--rouge, #e63946); border-radius:12px; padding:12px 14px; }
        .urgent .sq { background:var(--rouge, #e63946); color:#fff; font-weight:800; font-size:11px;
            padding:5px 9px; border-radius:6px; letter-spacing:.5px; flex:0 0 auto; }
        .urgent .u-thumb { width:42px; height:42px; flex:0 0 42px; border-radius:8px; object-fit:cover; display:block;
            border:1px solid var(--card-border); }
        .urgent .u-link { flex:1; min-width:0; color:var(--text); text-decoration:none; font-weight:600; font-size:14px; }
        .urgent .u-link:hover { color:var(--rouge, #e63946); }
        .urgent form { flex:0 0 auto; margin:0; }
        .urgent .u-x { background:none; border:none; cursor:pointer; color:var(--muted); font-size:20px; line-height:1; padding:2px 8px; border-radius:6px; }
        .urgent .u-x:hover { background:rgba(230,57,70,.18); color:var(--rouge, #e63946); }

        /* Rappel : rendez-vous du jour */
        .today-rdv { padding:22px 26px; margin-bottom:24px; border:1px solid var(--accent); }
        .today-rdv h2 { font-size:16px; color:var(--accent); margin-bottom:14px; }
        .rdv-list { display:flex; flex-direction:column; gap:10px; }
        .rdv { display:flex; align-items:center; gap:14px; text-decoration:none; color:inherit; padding:12px 14px;
            border-radius:12px; border:1px solid var(--card-border); background:rgba(127,127,127,.05);
            transition:border-color .15s, transform .15s; }
        .rdv:hover { border-color:var(--accent); transform:translateX(3px); }
        .rdv-time { font-size:18px; font-weight:800; color:var(--accent); min-width:54px; text-align:center; }
        .rdv-info { flex:1; min-width:0; display:flex; flex-direction:column; gap:2px; }
        .rdv-title { font-weight:700; font-size:15px; }
        .rdv-meta { font-size:12.5px; color:var(--muted); }
        .rdv-arrow { color:var(--accent); font-size:18px; }

        /* Code membre & visibilité */
        .member-id { padding:22px 26px; margin-bottom:24px; }
        .member-id h2 { font-size:16px; color:var(--accent); margin-bottom:14px; }
        .mid-row { display:flex; gap:20px; flex-wrap:wrap; align-items:center; justify-content:space-between; }
        .mid-label { font-size:12px; color:var(--muted); }
        .mid-code { font-size:30px; font-weight:800; letter-spacing:4px; color:var(--accent); font-family:Consolas,Monaco,monospace; }
        .mid-hint { font-size:12px; color:var(--muted); }
        .mid-state { font-size:13px; font-weight:600; margin-bottom:8px; }
        .mid-state.on { color:var(--vert); } .mid-state.off { color:var(--muted); }
        .mid-btn { font:inherit; font-weight:700; cursor:pointer; border:1px solid var(--card-border);
            background:var(--card-bg); color:var(--text); border-radius:10px; padding:10px 16px; }
        .mid-btn.primary { background:var(--accent); color:var(--accent-ink); border-color:var(--accent); }
        .mid-note { font-size:12.5px; color:var(--muted); margin-top:12px; }

        /* Thème personnel */
        .theme-card { padding:22px 26px; margin-bottom:24px; }
        .theme-card h2 { font-size:16px; color:var(--accent); margin-bottom:6px; }
        .theme-note { font-size:13px; color:var(--muted); margin-bottom:14px; }
        .theme-form { display:flex; gap:10px; flex-wrap:wrap; align-items:center; }
        .theme-form select { flex:1; min-width:200px; padding:11px 13px; border-radius:10px;
            border:1px solid var(--card-border); background:var(--card-bg); color:var(--text); font-family:inherit; font-size:14px; }
        .theme-form select option { background:var(--bg-base); color:var(--text); }
        .theme-btn { font:inherit; font-weight:700; cursor:pointer; border:none; border-radius:10px;
            padding:11px 20px; background:var(--accent); color:var(--accent-ink); }
        /* Rôle / domaine */
        .role-tags { display:flex; flex-wrap:wrap; gap:6px; margin:8px 0 2px; }
        .role-tag { font-size:11.5px; font-weight:600; color:var(--accent); background:var(--card-border);
            border:1px solid var(--accent); border-radius:999px; padding:3px 10px; }
        .role-card { padding:22px 26px; margin-bottom:24px; }
        .role-card h2 { font-size:16px; color:var(--accent); margin-bottom:6px; }
        .role-note { font-size:13px; color:var(--muted); margin-bottom:14px; }
        /* Carte rôle repliable + carte recherche */
        .role-acc > summary { list-style:none; cursor:pointer; display:flex; align-items:center; justify-content:space-between; gap:10px; }
        .role-acc > summary::-webkit-details-marker { display:none; }
        .role-acc .acc-title { font-size:16px; font-weight:800; color:var(--accent); }
        .role-acc .acc-hint { font-size:12px; color:var(--muted); }
        .role-acc[open] .acc-hint { display:none; }
        .country-card { padding:22px 26px; margin-bottom:24px; }
        .country-card h2 { font-size:16px; color:var(--accent); margin-bottom:6px; }
        .search-card { padding:18px 22px; margin-bottom:24px; }
        .search-card h2 { font-size:16px; color:var(--accent); margin-bottom:12px; }
        .srch { display:flex; gap:10px; flex-wrap:wrap; }
        .srch input { flex:1; min-width:200px; padding:12px 14px; border-radius:11px; border:1px solid var(--card-border); background:rgba(127,127,127,.06); color:var(--text); font:inherit; }
        .srch button { font:inherit; font-weight:700; border:none; border-radius:11px; padding:12px 20px; cursor:pointer; background:var(--accent); color:var(--accent-ink); }
        .srch-quick { margin-top:12px; font-size:12.5px; color:var(--muted); display:flex; flex-wrap:wrap; gap:8px; align-items:center; }
        .srch-quick a { text-decoration:none; color:var(--text); background:rgba(127,127,127,.08); border:1px solid var(--card-border); border-radius:20px; padding:4px 11px; }
        .srch-quick a:hover { border-color:var(--accent); color:var(--accent); }
        .role-cat { font-size:12px; font-weight:800; text-transform:uppercase; letter-spacing:.6px; color:var(--accent); margin:14px 2px 8px; }
        .role-pick { display:flex; flex-wrap:wrap; gap:8px; margin-bottom:6px; }
        .role-chip { cursor:pointer; }
        .role-chip input { display:none; }
        .role-chip span { display:inline-block; font-size:13px; font-weight:600; color:var(--text);
            border:1px solid var(--card-border); border-radius:999px; padding:7px 13px; transition:background .15s, color .15s, border-color .15s; }
        .role-chip input:checked + span { background:var(--accent); color:var(--accent-ink); border-color:var(--accent); }
        .role-lbl { display:block; font-size:13px; font-weight:600; color:var(--muted); margin-bottom:6px; }
        .role-card input[type=text] { width:100%; padding:11px 13px; border-radius:10px; border:1px solid var(--card-border);
            background:rgba(127,127,127,.08); color:var(--text); font-family:inherit; font-size:14px; margin-bottom:14px; }
        .role-card input[type=text]:focus { outline:none; border-color:var(--accent); }
        .role-btn { font:inherit; font-weight:700; cursor:pointer; border:none; border-radius:10px;
            padding:11px 20px; background:var(--accent); color:var(--accent-ink); }
        /* Sauvegarde / export-import */
        .dash-notice { background:rgba(42,157,74,.12); border:1px solid var(--vert,#2a9d4a); color:var(--text);
            padding:12px 16px; border-radius:12px; margin-bottom:16px; font-size:14px; }
        .backup-card { padding:22px 26px; margin-bottom:24px; }
        .backup-card h2 { font-size:16px; color:var(--accent); margin-bottom:6px; }
        .backup-note { font-size:13px; color:var(--muted); margin-bottom:14px; line-height:1.5; }
        .backup-row { display:flex; gap:14px; flex-wrap:wrap; align-items:center; }
        .backup-exp { display:inline-flex; align-items:center; gap:8px; text-decoration:none; font-weight:700; font-size:14px;
            color:var(--accent-ink); background:var(--accent); border-radius:11px; padding:11px 18px; }
        .backup-exp:hover { filter:brightness(1.05); }
        .backup-imp { margin:0; }
        .backup-imp-btn { display:inline-flex; align-items:center; gap:8px; cursor:pointer; font-weight:700; font-size:14px;
            color:var(--text); background:var(--card-bg); border:1px solid var(--card-border); border-radius:11px; padding:11px 18px; }
        .backup-imp-btn:hover { border-color:var(--accent); color:var(--accent); }
        .backup-imp-btn input[type=file] { display:none; }
        .backup-admin { font-size:12.5px; color:var(--muted); margin-top:12px; }
        .backup-admin a { color:var(--accent); }
        .backup-feedback { margin-top:12px; font-size:13px; color:var(--text); background:rgba(42,157,74,.12);
            border:1px solid var(--vert,#2a9d4a); border-radius:10px; padding:11px 14px; }
        .backup-feedback a { color:var(--accent); }
        /* Carte niveau (Héritier → Sage) */
        .level-card { padding:22px 26px; margin-bottom:24px; }
        .lvl-head { display:flex; align-items:center; gap:14px; margin-bottom:14px; }
        .lvl-emoji { font-size:40px; line-height:1; }
        .lvl-name { font-size:17px; font-weight:800; color:var(--text); }
        .lvl-pts { font-size:13px; color:var(--accent); font-weight:700; }
        .lvl-bar { height:12px; border-radius:20px; background:rgba(127,127,127,.18); overflow:hidden; }
        .lvl-bar span { display:block; height:100%; background:linear-gradient(90deg,var(--accent),var(--or,#f4c14b)); border-radius:20px; transition:width .4s; }
        .lvl-next { font-size:13px; color:var(--muted); margin-top:10px; }
        .lvl-detail { display:flex; flex-wrap:wrap; gap:8px 14px; margin-top:12px; font-size:12.5px; color:var(--muted); }
        .lvl-track { display:flex; justify-content:space-between; margin-top:14px; padding-top:12px; border-top:1px solid var(--card-border); }
        .lvl-step { font-size:22px; filter:grayscale(1) opacity(.4); transition:.2s; }
        .lvl-step.on { filter:none; transform:scale(1.08); }
        .lvl-more { display:inline-block; margin-top:14px; font-size:13px; font-weight:700; color:var(--accent); text-decoration:none; }
        .lvl-more:hover { text-decoration:underline; }
    </style>
</head>
<body>
    <?php
        $fullName  = $user['name'] ?: 'Membre';
        $firstName = trim(explode(' ', $fullName)[0]);
        $isAdmin   = (($user['role'] ?? '') === 'admin');
        $picture   = (string) ($user['picture'] ?? '');
        $avatar    = avatar_url($picture, $fullName);
        $hasPhoto  = $picture !== '';
    ?>
    <div class="wrap">

        <header class="topbar">
            <div class="greet">
                <div class="hi">Bienvenue 👋</div>
                <h1>Bonjour, <span><?= htmlspecialchars($firstName) ?></span></h1>
            </div>
            <a class="logout" href="<?= url('logout') ?>">⏻ Se déconnecter</a>
        </header>

        <?php if (!empty($urgents)): ?>
        <div class="urgents">
            <?php foreach ($urgents as $ur): ?>
                <div class="urgent">
                    <span class="sq">URGENT</span>
                    <?php if (!empty($ur['image'])): ?>
                        <a href="<?= htmlspecialchars($ur['link']) ?>"><img class="u-thumb" src="<?= htmlspecialchars($ur['image']) ?>" alt="" referrerpolicy="no-referrer"></a>
                    <?php endif; ?>
                    <a class="u-link" href="<?= htmlspecialchars($ur['link']) ?>"><?= $ur['icon'] ?> <?= htmlspecialchars($ur['title']) ?></a>
                    <form method="post" action="<?= url('profile/dismiss_urgent') ?>">
                        <input type="hidden" name="type" value="<?= htmlspecialchars($ur['type']) ?>">
                        <input type="hidden" name="id" value="<?= (int) $ur['id'] ?>">
                        <button class="u-x" type="submit" title="Fermer cette alerte">✕</button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($photoError)): ?>
            <div class="photo-error"><?= htmlspecialchars($photoError) ?></div>
        <?php endif; ?>
        <?php if (!empty($dashError)): ?>
            <div class="photo-error"><?= htmlspecialchars($dashError) ?></div>
        <?php endif; ?>
        <?php if (!empty($dashNotice)): ?>
            <div class="dash-notice"><?= htmlspecialchars($dashNotice) ?></div>
        <?php endif; ?>

        <section class="card profile">
            <div class="avatar-ring">
                <img class="avatar" src="<?= htmlspecialchars($avatar) ?>" alt="avatar" referrerpolicy="no-referrer">
            </div>
            <div class="pmeta">
                <div class="pname"><?= htmlspecialchars($fullName) ?></div>
                <span class="badge"><?= $isAdmin ? '👑 Administrateur' : '🟢 Membre' ?></span>
                <?php if (!empty($myDomains) || !empty($myCountries)): ?>
                    <div class="role-tags">
                        <?php foreach ($myDomains as $d): ?><span class="role-tag">🎓 <?= htmlspecialchars($d) ?></span><?php endforeach; ?>
                        <?php foreach (($myCountries ?? []) as $c): ?><span class="role-tag"><?= country_flag_img($c, 18, 13) ?> <?= htmlspecialchars($c) ?></span><?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <?php if (!empty($user['email'])): ?>
                    <p class="email"><?= htmlspecialchars($user['email']) ?></p>
                <?php endif; ?>
                <button type="button" class="update-btn" data-rpm-update-check title="Vérifier les mises à jour de l'application">🔄 Mise à jour</button>
                <form class="photo-form" method="post" action="<?= url('profile/photo') ?>" enctype="multipart/form-data">
                    <label class="photo-pick">
                        📷 <?= $hasPhoto ? 'Changer ma photo' : 'Ajouter une photo' ?>
                        <input type="file" name="photo" accept="image/jpeg,image/png,image/gif,image/webp" class="js-autoresize" data-autosubmit>
                    </label>
                    <?php if ($hasPhoto): ?>
                        <button type="submit" name="remove_photo" value="1" class="photo-remove" title="Retirer ma photo">Retirer</button>
                    <?php endif; ?>
                </form>
                <?= image_resize_js() ?>
            </div>
        </section>

        <?php if (!empty($level)): ?>
        <section class="card level-card">
            <div class="lvl-head">
                <span class="lvl-emoji"><?= $level['emoji'] ?></span>
                <div class="lvl-meta">
                    <div class="lvl-name">Niveau <?= (int) $level['level'] ?> — <?= htmlspecialchars($level['name']) ?></div>
                    <div class="lvl-pts"><?= (int) $level['points'] ?> points</div>
                </div>
            </div>
            <div class="lvl-bar"><span style="width:<?= (int) $level['progress'] ?>%"></span></div>
            <div class="lvl-next">
                <?php if ($level['next']): ?>
                    Plus que <b><?= (int) $level['toNext'] ?> pts</b> pour devenir <?= $level['nextEmoji'] ?> <b><?= htmlspecialchars($level['next']) ?></b>.
                <?php else: ?>
                    🏆 Niveau maximum atteint — tu es un <b>Sage</b> !
                <?php endif; ?>
            </div>
            <?php if (!empty($levelDetail)): ?>
            <div class="lvl-detail">
                <span>📰 <?= (int) $levelDetail['article'] ?> article·s</span>
                <span>❓ <?= (int) $levelDetail['quiz'] ?> QCM</span>
                <span>⭐ <?= (int) $levelDetail['review'] ?> avis</span>
                <span>💬 <?= (int) $levelDetail['comment'] ?> messages</span>
                <span>📅 <?= (int) $levelDetail['booking'] ?> réservations</span>
                <span>⏳ <?= (int) $levelDetail['weeks'] ?> sem.</span>
            </div>
            <?php endif; ?>
            <div class="lvl-track">
                <?php foreach (\Level::LEVELS as $n => $d): ?>
                    <span class="lvl-step <?= (int) $level['level'] >= $n ? 'on' : '' ?>" title="<?= htmlspecialchars($d['name']) ?>"><?= $d['emoji'] ?></span>
                <?php endforeach; ?>
            </div>
            <a class="lvl-more" href="<?= url('niveaux') ?>">📋 Voir tous les niveaux & comment gagner des points →</a>
        </section>
        <?php endif; ?>

        <?php if (!empty($todayEvents)): ?>
        <section class="card today-rdv">
            <h2>📌 Tes rendez-vous aujourd'hui (<?= count($todayEvents) ?>)</h2>
            <div class="rdv-list">
                <?php foreach ($todayEvents as $ev): ?>
                    <a class="rdv" href="<?= url('agenda') ?>?tab=book">
                        <span class="rdv-time"><?= date('H\hi', strtotime($ev['start_at'])) ?></span>
                        <span class="rdv-info">
                            <span class="rdv-title"><?= htmlspecialchars($ev['title']) ?></span>
                            <span class="rdv-meta">par <?= htmlspecialchars($ev['owner_name'] ?: 'Membre') ?> · <?= htmlspecialchars(rdv_lieu_texte($ev['mode'] ?? 'presentiel', (string) ($ev['location'] ?? ''))) ?></span>
                        </span>
                        <span class="rdv-arrow">→</span>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <?php if (!empty($myCode)): ?>
        <section class="card member-id">
            <h2>🪪 Mon code & visibilité</h2>
            <div class="mid-row">
                <div>
                    <div class="mid-label">Ton code membre</div>
                    <div class="mid-code"><?= htmlspecialchars($myCode) ?></div>
                    <div class="mid-hint">Partage-le pour qu'on te trouve directement.</div>
                </div>
                <form method="post" action="<?= url('profile/discoverable') ?>">
                    <?php if (!empty($discoverable)): ?>
                        <input type="hidden" name="discoverable" value="0">
                        <div class="mid-state on">✅ Tu es trouvable</div>
                        <button class="mid-btn" type="submit">🔒 Me rendre privé</button>
                    <?php else: ?>
                        <input type="hidden" name="discoverable" value="1">
                        <div class="mid-state off">🔒 Tu n'es pas trouvable</div>
                        <button class="mid-btn primary" type="submit">✅ M'autoriser à être ajouté</button>
                    <?php endif; ?>
                </form>
            </div>
            <p class="mid-note">🔑 Ton <b>code marche toujours</b> : partage-le pour qu'on puisse t'ajouter directement. L'option « trouvable » te rend en plus visible dans la <b>liste publique</b> (sans avoir à donner ton code).</p>
        </section>
        <?php endif; ?>

        <?php if (!empty($user)): ?>
        <section class="card theme-card">
            <h2>🎨 Mon thème</h2>
            <p class="theme-note">Choisis l'apparence du site — elle s'applique <b>uniquement à toi</b> (les autres gardent le thème du site).</p>
            <form method="post" action="<?= url('profile/theme') ?>" class="theme-form">
                <select name="theme_pref" id="themePref">
                    <option value="">— Thème du site (par défaut) —</option>
                    <?php foreach (Theme::byFamily() as $famille => $group): ?>
                        <optgroup label="<?= htmlspecialchars($famille) ?>">
                            <?php foreach ($group as $key => $t): ?>
                                <option value="<?= htmlspecialchars($key) ?>" <?= $key === $myTheme ? 'selected' : '' ?>><?= htmlspecialchars($t['label']) ?></option>
                            <?php endforeach; ?>
                        </optgroup>
                    <?php endforeach; ?>
                </select>
                <button class="theme-btn" type="submit">Appliquer</button>
            </form>
        </section>
        <?php endif; ?>

        <?php if (!empty($user) && (int) ($user['id'] ?? 0) > 0): ?>
        <section class="card country-card">
            <h2>🌍 Mes pays d'origine</h2>
            <p class="role-note">Indique un ou plusieurs pays (avec leur drapeau). Tape pour rechercher.</p>
            <form method="post" action="<?= url('profile/countries') ?>">
                <?= country_picker($myCountries ?? []) ?>
                <button class="role-btn" type="submit" style="margin-top:14px;">Enregistrer mes pays</button>
            </form>
        </section>

        <section class="card role-card">
            <details class="role-acc" open>
                <summary><span class="acc-title">🎓 Mon rôle / domaine</span><span class="acc-hint">▾</span></summary>
                <p class="role-note">Tape une matière ou un rôle (ex. « Prof de maths ») et choisis dans la liste, ou écris le tien. Renseigne aussi ton <b>adresse</b> pour être trouvé·e.</p>
                <form method="post" action="<?= url('profile/roles') ?>">
                    <label class="role-lbl">Mes matières / rôles</label>
                    <?= domain_picker($myDomains ?? [], $domainCategories ?? []) ?>
                    <label class="role-lbl" for="addr" style="margin-top:14px;display:block;">📍 Mon adresse / ville</label>
                    <span class="city-wrap">
                        <input type="text" id="addr" name="address" class="js-city" autocomplete="off" value="<?= htmlspecialchars($myAddress ?? '') ?>" placeholder="Tape ta ville… (ex. Dakar)">
                        <input type="hidden" name="addr_lat" class="city-lat">
                        <input type="hidden" name="addr_lng" class="city-lng">
                    </span>
                    <button class="role-btn" type="submit" style="margin-top:14px;">Enregistrer mon profil</button>
                    <?= city_autocomplete_js() ?>
                </form>
            </details>
        </section>

        <section class="card search-card">
            <h2>🔎 Rechercher un membre</h2>
            <form method="get" action="<?= url('professeurs') ?>" class="srch">
                <span class="member-wrap" style="flex:1;min-width:200px;">
                    <input type="text" name="q" class="js-member" autocomplete="off" placeholder="Nom, matière, pays, ville…" style="width:100%;">
                </span>
                <button type="submit">Chercher</button>
            </form>
            <?= member_autocomplete_js() ?>
            <?php $mine = array_merge($myDomains ?? [], $myCountries ?? []); ?>
            <?php if (!empty($mine)): ?>
                <div class="srch-quick">Selon ton profil :
                    <?php foreach ($mine as $tag): ?>
                        <a href="<?= url('professeurs') ?>?q=<?= urlencode($tag) ?>"><?= htmlspecialchars($tag) ?></a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
        <?php endif; ?>

        <div class="stats">
            <a class="card stat" href="<?= url('articles') ?>">
                <div class="num"><?= (int) $totalArticles ?></div>
                <div class="lbl">Articles publiés</div>
            </a>
            <a class="card stat" href="<?= url('articles') ?>#panel-mine">
                <div class="num"><?= (int) $myArticles ?></div>
                <div class="lbl">Mes articles</div>
            </a>
            <a class="card stat" href="<?= url('quiz') ?>">
                <div class="num"><?= (int) ($totalQuizzes ?? 0) ?></div>
                <div class="lbl">Questionnaires</div>
            </a>
        </div>

        <p class="section-title">👥 Communauté</p>
        <div class="actions">
            <a class="card action" href="<?= url('professeurs') ?>">
                <div class="ico">🔍</div>
                <div class="txt"><h3>Rechercher un profil</h3><p>Par nom, matière, pays ou ville</p></div>
                <span class="arrow">→</span>
            </a>
            <a class="card action" href="<?= url('messages') ?>">
                <div class="ico">✉️<?php if (!empty($unreadMsgs)): ?><span class="notif-badge"><?= (int) $unreadMsgs > 9 ? '9+' : (int) $unreadMsgs ?></span><?php endif; ?></div>
                <div class="txt"><h3>Messages</h3><p><?= !empty($unreadMsgs) ? (int) $unreadMsgs . ' non lu' . ((int) $unreadMsgs > 1 ? 's' : '') : 'Discuter en privé' ?></p></div>
                <span class="arrow">→</span>
            </a>
            <a class="card action" href="<?= url('agenda') ?>">
                <div class="ico">📅</div>
                <div class="txt"><h3>Agenda & rendez-vous</h3><p>Proposer des créneaux, réserver</p></div>
                <span class="arrow">→</span>
            </a>
            <a class="card action" href="<?= url('diaspora') ?>">
                <div class="ico">🗺️</div>
                <div class="txt"><h3>Carte de la diaspora</h3><p>Explorer la communauté afro-descendante</p></div>
                <span class="arrow">→</span>
            </a>
        </div>

        <p class="section-title">📚 Apprendre & contenus</p>
        <div class="actions">
            <a class="card action" href="<?= url('articles') ?>">
                <div class="ico">📰</div>
                <div class="txt"><h3>Articles</h3><p>Lire, écrire, rechercher</p></div>
                <span class="arrow">→</span>
            </a>
            <a class="card action" href="<?= url('quiz') ?>">
                <div class="ico">❓</div>
                <div class="txt"><h3>Questionnaires</h3><p>Créer un quiz, répondre, voir son score</p></div>
                <span class="arrow">→</span>
            </a>
            <a class="card action" href="<?= url('assistant') ?>">
                <div class="ico">🤖</div>
                <div class="txt"><h3>Assistant Sankofa</h3><p>Te guider vers la bonne ressource</p></div>
                <span class="arrow">→</span>
            </a>
        </div>

        <p class="section-title">🧭 Mon espace</p>
        <div class="actions">
            <a class="card action" href="<?= url('niveaux') ?>">
                <div class="ico">🏅</div>
                <div class="txt"><h3>Mes niveaux</h3><p>Progression Héritier → Sage</p></div>
                <span class="arrow">→</span>
            </a>
            <a class="card action" href="<?= url('notifications') ?>">
                <div class="ico">🔔<?php if (!empty($unreadNotifs)): ?><span class="notif-badge"><?= (int) $unreadNotifs > 9 ? '9+' : (int) $unreadNotifs ?></span><?php endif; ?></div>
                <div class="txt"><h3>Notifications</h3><p><?= !empty($unreadNotifs) ? (int) $unreadNotifs . ' non lue' . ((int) $unreadNotifs > 1 ? 's' : '') : 'Confirmations, rappels…' ?></p></div>
                <span class="arrow">→</span>
            </a>
            <a class="card action" href="<?= url('profile/export') ?>" download>
                <div class="ico">📦</div>
                <div class="txt"><h3>Exporter mon projet</h3><p>Télécharge tes articles + quiz (.zip)</p></div>
                <span class="arrow">⬇️</span>
            </a>
            <a class="card action" href="<?= url('profile/import') ?>">
                <div class="ico">⬆️</div>
                <div class="txt"><h3>Importer un projet</h3><p>Restaurer un .zip (un seul ou tout)</p></div>
                <span class="arrow">→</span>
            </a>
            <a class="card action" href="<?= url('docs/index.html') ?>">
                <div class="ico">📖</div>
                <div class="txt"><h3>Aide & tutoriels</h3><p>Guides pour tout le site</p></div>
                <span class="arrow">→</span>
            </a>
        </div>

        <?php if ($isAdmin): ?>
        <p class="section-title">👑 Administration</p>
        <div class="actions">
            <a class="card action accent" href="<?= url('admin/dashboard') ?>">
                <div class="ico">⚙️</div>
                <div class="txt"><h3>Espace administrateur</h3><p>Membres, articles, paramètres</p></div>
                <span class="arrow">→</span>
            </a>
        </div>
        <?php endif; ?>

        <?php if (!empty($user) && (int) ($user['id'] ?? 0) > 0): ?>
        <section class="card backup-card" id="backup">
            <h2>📦 Sauvegarde de mon projet</h2>
            <p class="backup-note">Télécharge <b>tes</b> articles et questionnaires (avec images, fichiers et associations) dans un fichier <code>.zip</code>, ou réimporte-les. À l'import, tout revient <b>en brouillon</b>, à ton nom.</p>
            <div class="backup-row">
                <a class="backup-exp" id="exportBtn" href="<?= url('profile/export') ?>" download>⬇️ Exporter mon projet (.zip)</a>
                <a class="backup-imp-btn" href="<?= url('profile/import') ?>">⬆️ Importer un projet (.zip)</a>
            </div>
            <p class="backup-feedback" id="exportFeedback" hidden>⏳ Téléchargement de ton projet… vérifie ta barre de téléchargements / ton dossier <b>Téléchargements</b>. Si rien n'apparaît, <a href="<?= url('profile/export') ?>" target="_blank" rel="noopener">ouvre le fichier dans un nouvel onglet</a>.</p>
            <p class="backup-feedback" id="importMsg" hidden>⏳ Import en cours… la page va se recharger avec le résultat.</p>
            <?php if ($isAdmin): ?>
                <p class="backup-admin">👑 En tant qu'admin, tu peux aussi <a href="<?= url('admin/articles') ?>">exporter / importer <b>tout le site</b></a> (tous les membres).</p>
            <?php endif; ?>
            <script>
            (function(){
                var b=document.getElementById('exportBtn'), f=document.getElementById('exportFeedback');
                if(b&&f){ b.addEventListener('click', function(){ f.hidden=false; }); }
            })();
            </script>
        </section>
        <?php endif; ?>

    </div>
    <?php if (!empty($user)): ?>
    <script>
        // Aperçu en direct du thème personnel (avant d'appliquer).
        (function () {
            var THEMES = <?= json_encode(array_map(static fn ($t) => $t['vars'], $themes), JSON_UNESCAPED_SLASHES) ?>;
            var SITE_KEY = <?= json_encode($siteThemeKey) ?>;
            var sel = document.getElementById('themePref');
            var root = document.documentElement;
            if (!sel) { return; }
            sel.addEventListener('change', function () {
                var vars = THEMES[this.value || SITE_KEY] || {};
                for (var name in vars) { root.style.setProperty(name, vars[name]); }
            });
        })();
    </script>
    <?php endif; ?>
</body>
</html>
