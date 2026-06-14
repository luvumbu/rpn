<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carte de la diaspora — <?= htmlspecialchars(Settings::get('main_title', 'RPN')) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
          integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
    <?= Theme::css() ?>
    <style>
        * { box-sizing:border-box; margin:0; padding:0; }
        body { font-family:'Poppins',sans-serif; min-height:100vh; color:var(--text); padding:28px 18px 50px;
            background:radial-gradient(circle at 12% 0%, var(--glow1), transparent 42%),
                radial-gradient(circle at 88% 100%, var(--glow2), transparent 44%), var(--bg-base); }
        body::before { content:""; position:fixed; top:0; left:0; right:0; height:6px; background:var(--bar); z-index:1000; }
        .wrap { max-width:1040px; margin:0 auto; }
        .top { display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; margin-bottom:8px; }
        h1 { font-size:24px; } h1 span { color:var(--accent); }
        .back { font-size:14px; color:var(--muted); text-decoration:none; border:1px solid var(--card-border); padding:8px 14px; border-radius:10px; }
        .lead { color:var(--muted); font-size:14px; margin-bottom:16px; max-width:720px; }
        #map { height:540px; width:100%; border-radius:18px; border:1px solid var(--card-border); box-shadow:var(--card-shadow); }
        .legend { display:flex; flex-wrap:wrap; gap:10px; margin-top:14px; font-size:13px; color:var(--muted); }
        .legend span { display:inline-flex; align-items:center; gap:6px; background:var(--card-bg); border:1px solid var(--card-border); border-radius:20px; padding:5px 12px; }
        .leaflet-popup-content { font-family:'Poppins',sans-serif; }
        .leaflet-popup-content h3 { margin:0 0 4px; font-size:15px; }
        .leaflet-popup-content p { margin:0; font-size:12.5px; color:#555; }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="top">
            <h1>🗺️ Carte de la <span>diaspora</span> afro-descendante</h1>
            <a class="back" href="<?= url('dashboard') ?>">← Tableau de bord</a>
        </div>
        <p class="lead">Du berceau africain aux Amériques, aux Caraïbes et à l'Europe : visualise les grands foyers de la communauté afro-descendante. Les points dorés 🟡 sont les <strong>membres trouvables</strong> de l'Institut Sankofa.</p>

        <?= meet_link_widget() ?>

        <div id="map"></div>

        <div class="legend">
            <span>🔴 Régions de la diaspora</span>
            <span>🟡 Membres de la communauté (<?= count($members) ?>)</span>
        </div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
            integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script>
        var regions = [
            { n:"Afrique", lat:2, lng:18, t:"Le berceau. Origine des langues et cultures (lingala, wolof, xhosa, lari…) transmises à toute la diaspora." },
            { n:"États-Unis", lat:38, lng:-97, t:"Communauté afro-américaine : histoire de résistance, de musique et de luttes pour les droits civiques." },
            { n:"Canada", lat:56, lng:-106, t:"Communautés afro-canadiennes et caribéennes, notamment à Toronto et Montréal." },
            { n:"Caraïbes", lat:18, lng:-70, t:"Antilles, Haïti, Cuba, Jamaïque… créolités, métissages et héritages africains vivaces." },
            { n:"Guyane", lat:4, lng:-53, t:"Créole guyanais, peuples bushinengués et héritages afro-amérindiens." },
            { n:"Brésil", lat:-10, lng:-52, t:"La plus grande population afro-descendante hors d'Afrique : candomblé, capoeira, samba." },
            { n:"Europe", lat:48, lng:7, t:"Diasporas africaines et afro-caribéennes (France, Royaume-Uni, Belgique, Portugal…)." }
        ];
        var members = <?= json_encode($members, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

        var map = L.map('map', { worldCopyJump:true }).setView([12, -25], 3);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 18, attribution: '&copy; OpenStreetMap'
        }).addTo(map);

        // Régions de la diaspora (cercles rouges)
        regions.forEach(function (r) {
            L.circleMarker([r.lat, r.lng], { radius:13, color:'#e63946', fillColor:'#e63946', fillOpacity:.55, weight:2 })
                .addTo(map)
                .bindPopup('<h3>'+r.n+'</h3><p>'+r.t+'</p>');
        });

        // Membres trouvables (points dorés)
        members.forEach(function (m) {
            L.circleMarker([m.lat, m.lng], { radius:6, color:'#b8860b', fillColor:'#f4c14b', fillOpacity:.95, weight:1 })
                .addTo(map)
                .bindPopup('<h3>🟡 '+ (m.name||'Membre') +'</h3><p>'+ (m.city||'') +'</p>');
        });
    </script>
</body>
</html>
