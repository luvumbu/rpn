<?php
/**
 * Fonctions utilitaires réutilisées dans tout le projet.
 */

if (!function_exists('url')) {
    /**
     * Construit une URL interne à partir d'une route.
     *   url()                 → racine de l'app (ex: /rpm/)
     *   url('admin/members')  → /rpm/admin/members
     */
    function url(string $path = ''): string
    {
        return BASE_PATH . '/' . ltrim($path, '/');
    }
}

if (!function_exists('redirect')) {
    /**
     * Redirige vers une route interne (ex: redirect('admin/login')) puis stoppe.
     * Une URL absolue (http…) est laissée telle quelle.
     */
    function redirect(string $to): void
    {
        $location = preg_match('#^https?://#i', $to) ? $to : url($to);
        header('Location: ' . $location);
        exit;
    }
}

if (!function_exists('human_filesize')) {
    /** Taille de fichier lisible (ex: 1,2 Mo). */
    function human_filesize(int $bytes): string
    {
        $units = ['o', 'Ko', 'Mo', 'Go'];
        $i = 0;
        $n = (float) max(0, $bytes);
        while ($n >= 1024 && $i < count($units) - 1) {
            $n /= 1024;
            $i++;
        }
        $val = $i === 0 ? (string) (int) $n : number_format($n, 1, ',', ' ');
        return $val . ' ' . $units[$i];
    }
}

if (!function_exists('file_type_icon')) {
    /** Émoji représentant le type d'un fichier d'après son extension. */
    function file_type_icon(string $ext): string
    {
        switch (strtolower($ext)) {
            case 'pdf':                     return '📕';
            case 'doc': case 'docx': case 'odt': return '📝';
            case 'xls': case 'xlsx': case 'ods': case 'csv': return '📊';
            case 'ppt': case 'pptx': case 'odp': return '📑';
            case 'zip':                     return '🗜️';
            case 'txt':                     return '📃';
            default:                        return '📎';
        }
    }
}

if (!function_exists('rating_stars')) {
    /** Étoiles en lecture seule (moyenne + nombre d'avis), ou « Pas encore noté ». */
    function rating_stars(float $avg, int $count): string
    {
        if ($count <= 0) {
            return '<span class="rating none">☆☆☆☆☆ <em>Pas encore noté</em></span>';
        }
        $full = (int) round($avg);
        $s = '';
        for ($i = 1; $i <= 5; $i++) {
            $s .= $i <= $full ? '★' : '☆';
        }
        return '<span class="rating"><span class="rstars">' . $s . '</span> <em>'
             . number_format($avg, 1, ',', ' ') . '/5 · ' . $count . ' avis</em></span>';
    }
}

if (!function_exists('rdv_lieu_texte')) {
    /** Représentation lisible du lieu d'un RDV (mode + valeur), pour affichage/historique. */
    function rdv_lieu_texte(string $mode, string $location): string
    {
        $loc = trim($location);
        if ($mode === 'en_ligne') {
            return $loc !== '' ? 'En ligne — ' . $loc : 'En ligne';
        }
        return $loc !== '' ? 'Présentiel — ' . $loc : 'Présentiel (lieu non précisé)';
    }
}

if (!function_exists('rdv_horaire_texte')) {
    /** Horaire lisible d'un créneau pour l'historique : « lun. 09/06/2026 à 14:00 ». */
    function rdv_horaire_texte(string $startAt): string
    {
        $ts = strtotime($startAt);
        if (!$ts) {
            return $startAt;
        }
        $jours = ['Sun' => 'dim.', 'Mon' => 'lun.', 'Tue' => 'mar.', 'Wed' => 'mer.', 'Thu' => 'jeu.', 'Fri' => 'ven.', 'Sat' => 'sam.'];
        return $jours[date('D', $ts)] . ' ' . date('d/m/Y', $ts) . ' à ' . date('H:i', $ts);
    }
}

if (!function_exists('client_ip')) {
    /**
     * Adresse IP du visiteur. Tient compte des en-têtes de proxy courants
     * (X-Forwarded-For, etc.) puis valide le format ; chaîne vide si introuvable.
     */
    function client_ip(): string
    {
        $candidates = [];
        foreach (['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'] as $key) {
            if (!empty($_SERVER[$key])) {
                // X-Forwarded-For peut contenir plusieurs IP « client, proxy1, proxy2 ».
                foreach (explode(',', (string) $_SERVER[$key]) as $part) {
                    $candidates[] = trim($part);
                }
            }
        }
        foreach ($candidates as $ip) {
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
        return '';
    }
}

if (!function_exists('delai_texte')) {
    /** Texte lisible d'un délai en heures : « Aucun », « 6 h », « 2 jours »… */
    function delai_texte(int $hours): string
    {
        if ($hours <= 0) {
            return 'Aucun (jusqu\'au début)';
        }
        if ($hours % 24 === 0) {
            $j = $hours / 24;
            return $j . ' jour' . ($j > 1 ? 's' : '');
        }
        return $hours . ' h';
    }
}

if (!function_exists('duree_texte')) {
    /**
     * Met en mots une durée (en secondes) selon l'échelle voulue partout dans l'agenda :
     *   - moins de 24 h  → « 3 h 05 min 12 s » (ou « 12 min 04 s », ou « 9 s ») ;
     *   - de 1 à 30 jours → « 5 j » ;
     *   - plus de 30 jours → « 2 mois » (1 mois ≈ 30 jours).
     */
    function duree_texte(int $seconds): string
    {
        $s    = max(0, $seconds);
        $days = intdiv($s, 86400);

        if ($days > 30) {
            $months = intdiv($days, 30);
            return $months . ' mois';
        }
        if ($s >= 86400) {
            return $days . ' j';
        }
        $h   = intdiv($s, 3600);
        $m   = intdiv($s % 3600, 60);
        $sec = $s % 60;
        if ($h > 0) {
            return sprintf('%d h %02d min %02d s', $h, $m, $sec);
        }
        if ($m > 0) {
            return sprintf('%d min %02d s', $m, $sec);
        }
        return $sec . ' s';
    }
}

if (!function_exists('countdown_text')) {
    /**
     * Libellé d'état d'un créneau, à trois temps :
     *   - à venir   → « Dans <durée> » (échelle de duree_texte) ;
     *   - en cours  → « En cours » (l'instant présent est entre le début et la fin) ;
     *   - terminé   → « Déjà passé ».
     * $endTs (fin du créneau) sert à distinguer « en cours » de « déjà passé ».
     * (Rendu serveur = repli sans JS ; le JS prend ensuite le relais en direct.)
     */
    function countdown_text(int $startTs, ?int $endTs = null): string
    {
        $now = time();
        $end = $endTs ?? $startTs;
        if ($now >= $end) {
            return 'Déjà passé depuis ' . duree_texte($now - $end);
        }
        if ($now >= $startTs) {
            return 'En cours';
        }
        return 'Dans ' . duree_texte($startTs - $now);
    }
}

if (!function_exists('countdown_html')) {
    /**
     * Élément <span class="countdown" data-ts data-end> animé par le JS
     * (le préfixe ⏳ reste hors du span). $durationMin (durée du créneau) permet
     * au libellé de passer à « En cours » puis « Déjà passé ».
     */
    function countdown_html(string $startAt, ?int $durationMin = null): string
    {
        $ts = strtotime($startAt);
        if (!$ts) {
            return '';
        }
        $end = $ts + max(0, (int) $durationMin) * 60;
        return '<span class="countdown" data-ts="' . $ts . '" data-end="' . $end . '">'
            . htmlspecialchars(countdown_text($ts, $end)) . '</span>';
    }
}

if (!function_exists('google_calendar_url')) {
    /**
     * Lien « Ajouter à Google Agenda » pré-rempli pour un créneau (fuseau Paris).
     * $startAt = 'Y-m-d H:i:s', $durationMin = durée en minutes.
     */
    function google_calendar_url(string $title, string $startAt, int $durationMin, string $mode = 'presentiel', string $location = '', string $details = ''): string
    {
        $ts  = strtotime($startAt);
        if (!$ts) {
            return '';
        }
        $end = $ts + max(0, $durationMin) * 60;
        $loc = function_exists('rdv_lieu_texte') ? rdv_lieu_texte($mode, $location) : $location;
        return 'https://calendar.google.com/calendar/render?action=TEMPLATE'
            . '&text='     . rawurlencode($title)
            . '&dates='    . date('Ymd\THis', $ts) . '/' . date('Ymd\THis', $end)
            . '&ctz=Europe/Paris'
            . '&location=' . rawurlencode($loc)
            . ($details !== '' ? '&details=' . rawurlencode($details) : '');
    }
}

if (!function_exists('member_autocomplete_js')) {
    /**
     * Autocomplétion de MEMBRES VISIBLES sur les champs <input class="js-member">
     * (placés dans <span class="member-wrap">). Suggère nom + matière/ville + avatar ;
     * cliquer ouvre la fiche du membre (annuaire filtré sur son nom).
     */
    function member_autocomplete_js(): string
    {
        $sug  = url('professeurs/suggest');
        $dest = url('professeurs');
        return '<style>'
            . '.member-wrap{position:relative;display:block;}'
            . '.mem-menu{position:absolute;left:0;right:0;top:100%;margin-top:4px;background:var(--card-bg);border:1px solid var(--card-border);border-radius:11px;box-shadow:0 14px 34px rgba(0,0,0,.18);max-height:300px;overflow:auto;z-index:60;}'
            . '.mem-item{display:flex;align-items:center;gap:10px;padding:9px 12px;text-decoration:none;color:var(--text);}'
            . '.mem-item:hover{background:rgba(127,127,127,.12);}'
            . '.mem-item img{width:34px;height:34px;border-radius:50%;object-fit:cover;flex:0 0 auto;}'
            . '.mem-item small{color:var(--muted);font-size:12px;}'
            . '</style>'
            . '<script>(function(){var SUG="' . $sug . '";var DEST="' . $dest . '";var t;'
            . 'document.addEventListener("input",function(e){var inp=e.target;if(!inp.classList||!inp.classList.contains("js-member"))return;'
            . 'var wrap=inp.closest(".member-wrap");if(!wrap)return;var menu=wrap.querySelector(".mem-menu");if(!menu){menu=document.createElement("div");menu.className="mem-menu";wrap.appendChild(menu);}'
            . 'var q=inp.value.trim();clearTimeout(t);if(q.length<2){menu.hidden=true;return;}'
            . 't=setTimeout(function(){fetch(SUG+"?q="+encodeURIComponent(q)).then(function(r){return r.json();}).then(function(list){menu.innerHTML="";if(!list||!list.length){menu.hidden=true;return;}'
            . 'list.forEach(function(m){var a=document.createElement("a");a.href=DEST+"?q="+encodeURIComponent(m.name);a.className="mem-item";'
            . 'var img=document.createElement("img");img.src=m.avatar;img.alt="";a.appendChild(img);'
            . 'var s=document.createElement("span");s.innerHTML="<b>"+m.name.replace(/</g,"&lt;")+"</b>"+(m.sub?"<br><small>"+m.sub.replace(/</g,"&lt;")+"</small>":"");a.appendChild(s);'
            . 'menu.appendChild(a);});menu.hidden=false;}).catch(function(){menu.hidden=true;});},300);});'
            . 'document.addEventListener("blur",function(e){if(e.target.classList&&e.target.classList.contains("js-member")){var w=e.target.closest(".member-wrap");var m=w&&w.querySelector(".mem-menu");if(m)setTimeout(function(){m.hidden=true;},200);}},true);'
            . '})();</script>';
    }
}

if (!function_exists('city_autocomplete_js')) {
    /**
     * Autocomplétion de VILLES/ADRESSES sur les champs <input class="js-city">.
     * Place l'input dans <span class="city-wrap"> avec, à l'intérieur, deux champs
     * cachés .city-lat / .city-lng (remplis quand on choisit une suggestion).
     */
    function city_autocomplete_js(): string
    {
        $u = url('geo/suggest');
        return '<style>'
            . '.city-wrap{position:relative;display:block;}'
            . '.city-menu{position:absolute;left:0;right:0;top:100%;margin-top:4px;background:var(--card-bg);border:1px solid var(--card-border);border-radius:11px;box-shadow:0 14px 34px rgba(0,0,0,.18);max-height:240px;overflow:auto;z-index:60;}'
            . '.city-menu button{display:block;width:100%;text-align:left;border:none;background:transparent;color:var(--text);font:inherit;font-size:13.5px;padding:9px 12px;cursor:pointer;}'
            . '.city-menu button:hover{background:rgba(127,127,127,.12);}'
            . '</style>'
            . '<script>(function(){var SUGGEST="' . $u . '";var t;'
            . 'document.addEventListener("input",function(e){var inp=e.target;if(!inp.classList||!inp.classList.contains("js-city"))return;'
            . 'var wrap=inp.closest(".city-wrap");if(!wrap)return;var menu=wrap.querySelector(".city-menu");'
            . 'if(!menu){menu=document.createElement("div");menu.className="city-menu";wrap.appendChild(menu);}'
            . 'var la=wrap.querySelector(".city-lat"),lo=wrap.querySelector(".city-lng");if(la)la.value="";if(lo)lo.value="";'
            . 'var q=inp.value.trim();clearTimeout(t);if(q.length<3){menu.hidden=true;return;}'
            . 't=setTimeout(function(){fetch(SUGGEST+"?q="+encodeURIComponent(q)).then(function(r){return r.json();}).then(function(list){'
            . 'menu.innerHTML="";if(!list||!list.length){menu.hidden=true;return;}'
            . 'list.forEach(function(it){var b=document.createElement("button");b.type="button";b.textContent="📍 "+it.label;'
            . 'b.addEventListener("mousedown",function(ev){ev.preventDefault();inp.value=it.label;'
            . 'var la=wrap.querySelector(".city-lat"),lo=wrap.querySelector(".city-lng");if(la)la.value=it.lat;if(lo)lo.value=it.lng;menu.hidden=true;});'
            . 'menu.appendChild(b);});menu.hidden=false;}).catch(function(){menu.hidden=true;});},350);});'
            . 'document.addEventListener("blur",function(e){if(e.target.classList&&e.target.classList.contains("js-city")){var w=e.target.closest(".city-wrap");var m=w&&w.querySelector(".city-menu");if(m)setTimeout(function(){m.hidden=true;},150);}},true);'
            . '})();</script>';
    }
}

if (!function_exists('domain_picker')) {
    /**
     * Sélecteur de RÔLES / MATIÈRES avec autocomplétion + tags (multi),
     * et ajout LIBRE (tape ton propre rôle puis Entrée). Champs : domains[].
     * $categories = ['Catégorie' => ['item', ...]] pour les suggestions.
     */
    function domain_picker(array $selected, array $categories): string
    {
        $items = [];
        foreach ($categories as $cat => $list) {
            $g = trim(preg_replace('/^[^\p{L}]+/u', '', (string) $cat)); // enlève l'emoji de tête
            foreach ($list as $n) {
                $items[] = ['n' => $n, 'g' => $g];
            }
        }
        $data = json_encode($items, JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT);
        $tags = '';
        foreach ($selected as $s) {
            $esc = htmlspecialchars($s);
            $tags .= '<span class="dp-tag">🎓 ' . $esc . '<button type="button" class="dp-rm" aria-label="retirer">×</button>'
                . '<input type="hidden" name="domains[]" value="' . $esc . '"></span>';
        }
        return <<<HTML
<div class="dp" id="dp">
  <style>
    .dp { position:relative; }
    .dp-box { display:flex; flex-wrap:wrap; gap:6px; align-items:center; border:1px solid var(--card-border); border-radius:11px; padding:7px 9px; background:rgba(127,127,127,.06); }
    .dp-tag { display:inline-flex; align-items:center; gap:6px; background:var(--accent); color:var(--accent-ink); border-radius:20px; padding:4px 6px 4px 11px; font-size:13px; font-weight:600; }
    .dp-tag .dp-rm { border:none; background:rgba(0,0,0,.18); color:inherit; width:18px; height:18px; border-radius:50%; cursor:pointer; font-size:13px; line-height:1; }
    .dp-box input.dp-input { flex:1; min-width:160px; border:none; background:transparent; color:var(--text); font:inherit; padding:6px; outline:none; }
    .dp-menu { position:absolute; left:0; right:0; top:100%; margin-top:4px; background:var(--card-bg); border:1px solid var(--card-border); border-radius:11px; box-shadow:0 14px 34px rgba(0,0,0,.18); max-height:240px; overflow:auto; z-index:50; }
    .dp-menu button { display:flex; align-items:center; justify-content:space-between; gap:10px; width:100%; text-align:left; border:none; background:transparent; color:var(--text); font:inherit; font-size:14px; padding:9px 12px; cursor:pointer; }
    .dp-menu button:hover, .dp-menu button.active { background:rgba(127,127,127,.12); }
    .dp-menu .g { font-size:11px; color:var(--muted); }
    .dp-menu .add { color:var(--accent); font-weight:700; }
  </style>
  <div class="dp-box" id="dpBox">
    $tags
    <input type="text" class="dp-input" id="dpInput" placeholder="Tape une matière ou un rôle (ex. Prof de maths)…" autocomplete="off">
  </div>
  <div class="dp-menu" id="dpMenu" hidden></div>
</div>
<script>
(function () {
  var DATA = $data;
  var box = document.getElementById('dpBox'), inp = document.getElementById('dpInput'), menu = document.getElementById('dpMenu');
  if (!box || !inp || !menu) { return; }
  function norm(s){ return s.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g,''); }
  function chosen(){ return Array.prototype.map.call(box.querySelectorAll('input[name="domains[]"]'), function(i){ return norm(i.value); }); }
  function addTag(name){
    name = name.trim(); if (!name) { return; }
    if (chosen().indexOf(norm(name)) !== -1) { return; }
    var t = document.createElement('span'); t.className = 'dp-tag';
    t.innerHTML = '🎓 ' + name.replace(/</g,'&lt;') + '<button type="button" class="dp-rm">×</button><input type="hidden" name="domains[]" value="">';
    t.querySelector('input').value = name;
    box.insertBefore(t, inp);
  }
  function render(q){
    var nq = norm(q); menu.innerHTML = '';
    if (!nq) { menu.hidden = true; return; }
    var sel = chosen();
    var matches = DATA.filter(function(c){ return norm(c.n).indexOf(nq) !== -1 && sel.indexOf(norm(c.n)) === -1; }).slice(0, 8);
    matches.forEach(function(c, i){
      var b = document.createElement('button'); b.type = 'button'; if (i===0) b.className='active';
      b.innerHTML = '<span>'+c.n.replace(/</g,'&lt;')+'</span><span class="g">'+c.g+'</span>';
      b.addEventListener('mousedown', function(e){ e.preventDefault(); addTag(c.n); inp.value=''; render(''); inp.focus(); });
      menu.appendChild(b);
    });
    // Ajout libre de ce qui est tapé (si pas déjà une suggestion exacte)
    if (q.trim() && !DATA.some(function(c){ return norm(c.n) === nq; })) {
      var a = document.createElement('button'); a.type = 'button';
      a.innerHTML = '<span class="add">+ Ajouter « '+q.trim().replace(/</g,'&lt;')+' »</span>';
      a.addEventListener('mousedown', function(e){ e.preventDefault(); addTag(q.trim()); inp.value=''; render(''); inp.focus(); });
      menu.appendChild(a);
    }
    menu.hidden = menu.children.length === 0;
  }
  inp.addEventListener('input', function(){ render(this.value); });
  inp.addEventListener('keydown', function(e){
    if (e.key === 'Enter') { e.preventDefault(); var first = menu.querySelector('button'); if (first && !menu.hidden) { first.dispatchEvent(new MouseEvent('mousedown')); } else if (this.value.trim()) { addTag(this.value); this.value=''; render(''); } }
    else if (e.key === 'Backspace' && this.value === '') { var tags = box.querySelectorAll('.dp-tag'); if (tags.length) tags[tags.length-1].remove(); }
  });
  inp.addEventListener('blur', function(){ setTimeout(function(){ menu.hidden = true; }, 150); });
  box.addEventListener('click', function(e){ if (e.target.classList.contains('dp-rm')) { e.target.closest('.dp-tag').remove(); } else { inp.focus(); } });
})();
</script>
HTML;
    }
}

if (!function_exists('country_flag_img')) {
    /** Image de drapeau (flagcdn) pour un NOM de pays — s'affiche partout, même sur Windows. */
    function country_flag_img(string $name, int $w = 24, int $h = 18): string
    {
        $code = Countries::codeForName($name);
        if ($code === '') {
            return '🌍';
        }
        return '<img class="flag-img" src="https://flagcdn.com/' . $w . 'x' . $h . '/' . $code . '.png"'
            . ' alt="" width="' . $w . '" height="' . $h . '" loading="lazy"'
            . ' style="vertical-align:middle;border-radius:2px;">';
    }
}

if (!function_exists('country_picker')) {
    /**
     * Sélecteur de pays avec AUTOCOMPLÉTION + drapeaux (multi-sélection).
     * $selected = liste de noms de pays déjà choisis. Champs envoyés : countries[].
     */
    function country_picker(array $selected = []): string
    {
        $data = json_encode(Countries::autocomplete(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_APOS | JSON_HEX_QUOT);
        $tags = '';
        foreach ($selected as $c) {
            $flag = country_flag_img($c, 20, 15);
            $esc  = htmlspecialchars($c);
            $tags .= '<span class="cp-tag">' . $flag . ' ' . $esc
                . '<button type="button" class="cp-rm" aria-label="retirer">×</button>'
                . '<input type="hidden" name="countries[]" value="' . $esc . '"></span>';
        }
        return <<<HTML
<div class="cp" id="cp">
  <style>
    .cp { position:relative; }
    .cp-box { display:flex; flex-wrap:wrap; gap:6px; align-items:center; border:1px solid var(--card-border); border-radius:11px; padding:7px 9px; background:rgba(127,127,127,.06); }
    .cp-tag { display:inline-flex; align-items:center; gap:6px; background:var(--accent); color:var(--accent-ink); border-radius:20px; padding:4px 6px 4px 11px; font-size:13px; font-weight:600; }
    .cp-tag .cp-rm { border:none; background:rgba(0,0,0,.18); color:inherit; width:18px; height:18px; border-radius:50%; cursor:pointer; font-size:13px; line-height:1; }
    .cp-box input.cp-input { flex:1; min-width:140px; border:none; background:transparent; color:var(--text); font:inherit; padding:6px; outline:none; }
    .cp-menu { position:absolute; left:0; right:0; top:100%; margin-top:4px; background:var(--card-bg); border:1px solid var(--card-border); border-radius:11px; box-shadow:0 14px 34px rgba(0,0,0,.18); max-height:230px; overflow:auto; z-index:50; }
    .cp-menu button { display:flex; align-items:center; gap:9px; width:100%; text-align:left; border:none; background:transparent; color:var(--text); font:inherit; font-size:14px; padding:9px 12px; cursor:pointer; }
    .cp-menu button:hover, .cp-menu button.active { background:rgba(127,127,127,.12); }
  </style>
  <div class="cp-box" id="cpBox">
    $tags
    <input type="text" class="cp-input" id="cpInput" placeholder="Tape un pays… (ex. Sénégal, France 🇫🇷)" autocomplete="off">
  </div>
  <div class="cp-menu" id="cpMenu" hidden></div>
</div>
<script>
(function () {
  var DATA = $data;
  var box = document.getElementById('cpBox'), inp = document.getElementById('cpInput'), menu = document.getElementById('cpMenu');
  if (!box || !inp || !menu) { return; }
  function norm(s){ return s.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g,''); }
  function chosen(){ return Array.prototype.map.call(box.querySelectorAll('input[name="countries[]"]'), function(i){ return norm(i.value); }); }
  function flagImg(code){ return '<img src="https://flagcdn.com/20x15/'+code+'.png" width="20" height="15" alt="" style="vertical-align:middle;border-radius:2px;">'; }
  function addTag(name, code){
    if (chosen().indexOf(norm(name)) !== -1) { return; }
    var t = document.createElement('span'); t.className = 'cp-tag';
    t.innerHTML = flagImg(code) + ' ' + name.replace(/</g,'&lt;') + '<button type="button" class="cp-rm">×</button><input type="hidden" name="countries[]" value="">';
    t.querySelector('input').value = name;
    box.insertBefore(t, inp);
  }
  function render(q){
    var nq = norm(q); menu.innerHTML = '';
    if (!nq) { menu.hidden = true; return; }
    var sel = chosen();
    var matches = DATA.filter(function(c){ return norm(c.n).indexOf(nq) !== -1 && sel.indexOf(norm(c.n)) === -1; }).slice(0, 8);
    if (!matches.length) { menu.hidden = true; return; }
    matches.forEach(function(c, i){
      var b = document.createElement('button'); b.type = 'button'; if (i===0) b.className='active';
      b.innerHTML = flagImg(c.c) + '<span>'+c.n.replace(/</g,'&lt;')+'</span>';
      b.addEventListener('mousedown', function(e){ e.preventDefault(); addTag(c.n, c.c); inp.value=''; render(''); inp.focus(); });
      menu.appendChild(b);
    });
    menu.hidden = false;
  }
  inp.addEventListener('input', function(){ render(this.value); });
  inp.addEventListener('keydown', function(e){
    if (e.key === 'Enter') { var first = menu.querySelector('button'); if (first && !menu.hidden) { e.preventDefault(); first.dispatchEvent(new MouseEvent('mousedown')); } }
    else if (e.key === 'Backspace' && this.value === '') { var tags = box.querySelectorAll('.cp-tag'); if (tags.length) tags[tags.length-1].remove(); }
  });
  inp.addEventListener('blur', function(){ setTimeout(function(){ menu.hidden = true; }, 150); });
  box.addEventListener('click', function(e){ if (e.target.classList.contains('cp-rm')) { e.target.closest('.cp-tag').remove(); } else { inp.focus(); } });
})();
</script>
HTML;
    }
}

if (!function_exists('geo_distance_km')) {
    /** Distance en km entre deux points (formule de Haversine). null si coords manquantes. */
    function geo_distance_km($lat1, $lng1, $lat2, $lng2): ?float
    {
        if ($lat1 === null || $lng1 === null || $lat2 === null || $lng2 === null) {
            return null;
        }
        $lat1 = (float) $lat1; $lng1 = (float) $lng1; $lat2 = (float) $lat2; $lng2 = (float) $lng2;
        $r = 6371.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
        return $r * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}

if (!function_exists('image_resize_js')) {
    /**
     * Script de REDIMENSIONNEMENT CÔTÉ NAVIGATEUR (canvas) pour les champs
     * <input type="file" class="js-autoresize">. Si l'image dépasse ~1280 px ou
     * ~1,5 Mo, elle est réduite/recompressée en JPEG avant l'envoi → plus de
     * blocage « image trop lourde » (ni côté serveur, ni limites PHP).
     * Ajoute data-autosubmit pour envoyer le formulaire une fois le redimensionnement fini.
     */
    function image_resize_js(): string
    {
        return <<<'HTML'
<script>
(function () {
  var MAXDIM = 1280, MAXBYTES = 1.5 * 1024 * 1024;
  document.addEventListener('change', function (e) {
    var inp = e.target;
    if (!inp || !inp.matches || !inp.matches('input[type=file].js-autoresize')) { return; }
    var file = inp.files && inp.files[0];
    if (!file || !/^image\//.test(file.type)) { return; }
    var autosubmit = inp.hasAttribute('data-autosubmit');
    var done = function () { if (autosubmit && inp.form) { inp.form.submit(); } };

    var img = new Image();
    var url = URL.createObjectURL(file);
    img.onload = function () {
      URL.revokeObjectURL(url);
      var w = img.width, h = img.height;
      if (Math.max(w, h) <= MAXDIM && file.size <= MAXBYTES) { done(); return; }
      var scale = Math.min(1, MAXDIM / Math.max(w, h));
      var cw = Math.max(1, Math.round(w * scale)), ch = Math.max(1, Math.round(h * scale));
      var c = document.createElement('canvas'); c.width = cw; c.height = ch;
      c.getContext('2d').drawImage(img, 0, 0, cw, ch);
      c.toBlob(function (blob) {
        if (blob) {
          try {
            var name = (file.name || 'image').replace(/\.(png|gif|webp|bmp|heic|heif|tiff?)$/i, '.jpg');
            var f = new File([blob], name, { type: 'image/jpeg' });
            var dt = new DataTransfer(); dt.items.add(f); inp.files = dt.files;
          } catch (err) { /* navigateur sans DataTransfer : on garde l'original */ }
        }
        done();
      }, 'image/jpeg', 0.85);
    };
    img.onerror = function () { URL.revokeObjectURL(url); done(); };
    img.src = url;
  });
})();
</script>
HTML;
    }
}

if (!function_exists('math_assets')) {
    /**
     * Ressources KaTeX (CDN) + rendu automatique des formules mathématiques.
     * Les équations s'écrivent entre $…$ (en ligne) ou $$…$$ (centré), ou avec
     * \( … \) et \[ … \]. À inclure dans le <head> des pages affichant du contenu
     * (articles, questionnaires). NOWDOC : aucune interpolation, $ et \ littéraux.
     */
    function math_assets(): string
    {
        return <<<'HTML'
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/katex@0.16.11/dist/katex.min.css" crossorigin="anonymous">
<script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.11/dist/katex.min.js" crossorigin="anonymous"></script>
<script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.11/dist/contrib/auto-render.min.js" crossorigin="anonymous"
  onload="renderMathInElement(document.body,{delimiters:[{left:'$$',right:'$$',display:true},{left:'\\[',right:'\\]',display:true},{left:'$',right:'$',display:false},{left:'\\(',right:'\\)',display:false}],throwOnError:false,ignoredTags:['script','noscript','style','textarea','pre','code']});"></script>
<style>.katex-display{margin:1.1em 0;overflow-x:auto;overflow-y:hidden;padding:2px 0}.katex{font-size:1.08em}</style>
HTML;
    }
}

if (!function_exists('avatar_url')) {
    /**
     * URL d'affichage de la photo de profil d'un membre.
     *  - une URL http(s) (photo Google) est renvoyée telle quelle ;
     *  - un nom de fichier correspond à une photo envoyée (uploads/avatars/) ;
     *  - sinon (aucune photo) → avatar généré à partir des initiales.
     */
    function avatar_url(?string $picture, string $name = ''): string
    {
        $picture = trim((string) $picture);
        if ($picture !== '') {
            if (preg_match('#^https?://#i', $picture)) {
                return $picture;
            }
            return url('uploads/avatars/' . rawurlencode($picture));
        }
        return 'https://ui-avatars.com/api/?name=' . urlencode($name !== '' ? $name : 'Membre')
            . '&background=14110f&color=f4c14b';
    }
}

if (!function_exists('view')) {
    /**
     * Affiche une vue située dans app/views/.
     * @param string $name  nom de la vue, ex: 'login' → app/views/login.view.php
     * @param array  $data  variables passées à la vue
     */
    function view(string $name, array $data = []): void
    {
        extract($data);
        require __DIR__ . '/../views/' . $name . '.view.php';
    }
}
