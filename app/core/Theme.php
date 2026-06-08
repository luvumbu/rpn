<?php
/**
 * CLASSE Theme
 * Gère l'apparence globale des pages. Le thème choisi dans l'admin définit
 * des variables CSS (couleurs, fond...) utilisées par les vues.
 */
class Theme
{
    /** Couleurs décoratives communes (drapeau panafricain). */
    private const BASE = [
        '--rouge' => '#e63946',
        '--vert'  => '#2a9d4a',
        '--or'    => '#f4c14b',
    ];

    /** Tous les thèmes disponibles. */
    public static function all(): array
    {
        return [
            'panafricain' => [
                'label' => 'Panafricain (défaut)',
                'vars'  => [
                    '--bg-base'      => '#14110f',
                    '--glow1'        => 'rgba(230,57,70,.35)',
                    '--glow2'        => 'rgba(42,157,74,.35)',
                    '--text'         => '#ffffff',
                    '--muted'        => 'rgba(255,255,255,.7)',
                    '--card-bg'      => 'rgba(255,255,255,.04)',
                    '--card-border'  => 'rgba(244,193,75,.25)',
                    '--card-shadow'  => '0 30px 80px rgba(0,0,0,.55)',
                    '--accent'       => '#f4c14b',
                    '--accent-ink'   => '#14110f',
                    '--bar'          => 'linear-gradient(90deg,#e63946 0 33%,#14110f 33% 66%,#2a9d4a 66% 100%)',
                ],
            ],
            'classique' => [
                'label' => 'Classique (clair)',
                'vars'  => [
                    '--bg-base'      => '#eef1f5',
                    '--glow1'        => 'transparent',
                    '--glow2'        => 'transparent',
                    '--text'         => '#1f2937',
                    '--muted'        => '#6b7280',
                    '--card-bg'      => '#ffffff',
                    '--card-border'  => '#e5e7eb',
                    '--card-shadow'  => '0 10px 40px rgba(0,0,0,.08)',
                    '--accent'       => '#2563eb',
                    '--accent-ink'   => '#ffffff',
                    '--bar'          => '#2563eb',
                ],
            ],
            'sombre' => [
                'label' => 'Sombre',
                'vars'  => [
                    '--bg-base'      => '#0f172a',
                    '--glow1'        => 'rgba(56,189,248,.18)',
                    '--glow2'        => 'rgba(129,140,248,.18)',
                    '--text'         => '#e5e7eb',
                    '--muted'        => 'rgba(229,231,235,.65)',
                    '--card-bg'      => 'rgba(255,255,255,.05)',
                    '--card-border'  => 'rgba(255,255,255,.12)',
                    '--card-shadow'  => '0 20px 60px rgba(0,0,0,.5)',
                    '--accent'       => '#38bdf8',
                    '--accent-ink'   => '#0f172a',
                    '--bar'          => 'linear-gradient(90deg,#38bdf8,#818cf8)',
                ],
            ],
            'clair' => [
                'label' => 'Clair',
                'vars'  => [
                    '--bg-base'      => '#fdfbf6',
                    '--glow1'        => 'rgba(244,193,75,.18)',
                    '--glow2'        => 'rgba(42,157,74,.12)',
                    '--text'         => '#2b2b2b',
                    '--muted'        => '#7a7a7a',
                    '--card-bg'      => '#ffffff',
                    '--card-border'  => '#ece5d8',
                    '--card-shadow'  => '0 12px 40px rgba(0,0,0,.08)',
                    '--accent'       => '#d4a017',
                    '--accent-ink'   => '#ffffff',
                    '--bar'          => 'linear-gradient(90deg,#e63946,#f4c14b,#2a9d4a)',
                ],
            ],
            'simpson' => [
                'label' => 'Simpson (jaune & ciel)',
                'vars'  => [
                    '--bg-base'      => '#0b4a86',
                    '--glow1'        => 'rgba(255,217,15,.30)',
                    '--glow2'        => 'rgba(255,255,255,.22)',
                    '--text'         => '#ffffff',
                    '--muted'        => 'rgba(255,255,255,.72)',
                    '--card-bg'      => 'rgba(255,255,255,.07)',
                    '--card-border'  => 'rgba(255,217,15,.38)',
                    '--card-shadow'  => '0 24px 64px rgba(0,20,50,.55)',
                    '--accent'       => '#ffd90f',
                    '--accent-ink'   => '#0b3a66',
                    '--bar'          => 'linear-gradient(90deg,#ffd90f 0 33%,#0a9bdc 33% 66%,#f14e28 66% 100%)',
                    '--rouge'        => '#f14e28',
                    '--vert'         => '#7ac142',
                    '--or'           => '#ffd90f',
                ],
            ],
            'ocean' => [
                'label' => 'Océan (bleu)',
                'vars'  => [
                    '--bg-base'     => '#04293a', '--glow1' => 'rgba(56,189,248,.30)', '--glow2' => 'rgba(45,212,191,.25)',
                    '--text'        => '#eafcff', '--muted' => 'rgba(234,252,255,.66)',
                    '--card-bg'     => 'rgba(255,255,255,.05)', '--card-border' => 'rgba(56,189,248,.30)',
                    '--card-shadow' => '0 24px 64px rgba(0,20,40,.55)', '--accent' => '#2dd4bf', '--accent-ink' => '#04293a',
                    '--bar'         => 'linear-gradient(90deg,#38bdf8,#2dd4bf)',
                ],
            ],
            'foret' => [
                'label' => 'Forêt (vert)',
                'vars'  => [
                    '--bg-base'     => '#0f2417', '--glow1' => 'rgba(74,222,128,.25)', '--glow2' => 'rgba(250,204,21,.18)',
                    '--text'        => '#eafff1', '--muted' => 'rgba(234,255,241,.66)',
                    '--card-bg'     => 'rgba(255,255,255,.05)', '--card-border' => 'rgba(74,222,128,.28)',
                    '--card-shadow' => '0 24px 64px rgba(0,30,15,.55)', '--accent' => '#4ade80', '--accent-ink' => '#0f2417',
                    '--bar'         => 'linear-gradient(90deg,#4ade80,#facc15)',
                ],
            ],
            'sunset' => [
                'label' => 'Coucher de soleil',
                'vars'  => [
                    '--bg-base'     => '#2a0e2e', '--glow1' => 'rgba(251,113,133,.30)', '--glow2' => 'rgba(251,191,36,.25)',
                    '--text'        => '#fff0f5', '--muted' => 'rgba(255,240,245,.68)',
                    '--card-bg'     => 'rgba(255,255,255,.06)', '--card-border' => 'rgba(251,146,60,.32)',
                    '--card-shadow' => '0 24px 64px rgba(40,0,30,.55)', '--accent' => '#fb7185', '--accent-ink' => '#2a0e2e',
                    '--bar'         => 'linear-gradient(90deg,#fb7185,#fbbf24)',
                ],
            ],
            'violet' => [
                'label' => 'Violet (néon)',
                'vars'  => [
                    '--bg-base'     => '#160d27', '--glow1' => 'rgba(167,139,250,.30)', '--glow2' => 'rgba(236,72,153,.22)',
                    '--text'        => '#f3eafe', '--muted' => 'rgba(243,234,254,.66)',
                    '--card-bg'     => 'rgba(255,255,255,.05)', '--card-border' => 'rgba(167,139,250,.32)',
                    '--card-shadow' => '0 24px 64px rgba(20,0,40,.55)', '--accent' => '#a78bfa', '--accent-ink' => '#160d27',
                    '--bar'         => 'linear-gradient(90deg,#a78bfa,#ec4899)',
                ],
            ],
            'menthe' => [
                'label' => 'Menthe (clair)',
                'vars'  => [
                    '--bg-base'     => '#f1faf6', '--glow1' => 'rgba(16,185,129,.14)', '--glow2' => 'rgba(56,189,248,.10)',
                    '--text'        => '#10302a', '--muted' => '#5b7d76',
                    '--card-bg'     => '#ffffff', '--card-border' => '#d4ebe2',
                    '--card-shadow' => '0 12px 40px rgba(0,40,30,.08)', '--accent' => '#10b981', '--accent-ink' => '#ffffff',
                    '--bar'         => 'linear-gradient(90deg,#10b981,#38bdf8)',
                ],
            ],
            'problack' => [
                'label' => 'Pro Black (blanc & or)',
                'vars'  => [
                    '--bg-base'      => '#ffffff',
                    '--glow1'        => 'transparent',
                    '--glow2'        => 'transparent',
                    '--text'         => '#0e0e0e',
                    '--muted'        => '#6b6b6b',
                    '--card-bg'      => '#ffffff',
                    '--card-border'  => '#e6e6e6',
                    '--card-shadow'  => '0 8px 30px rgba(0,0,0,.08)',
                    '--accent'       => '#0e0e0e',
                    '--accent-ink'   => '#ffffff',
                    '--bar'          => 'linear-gradient(90deg,#0e0e0e 0 55%, #c9a227 55% 100%)',
                    '--or'           => '#c9a227',
                ],
            ],
            'blackpanther' => [
                'label' => 'Black Panther (Wakanda)',
                'vars'  => [
                    '--bg-base'     => '#0d0a17', '--glow1' => 'rgba(168,85,247,.32)', '--glow2' => 'rgba(45,212,191,.16)',
                    '--text'        => '#f3effb', '--muted' => 'rgba(243,239,251,.66)',
                    '--card-bg'     => 'rgba(255,255,255,.05)', '--card-border' => 'rgba(168,85,247,.34)',
                    '--card-shadow' => '0 24px 64px rgba(10,0,25,.6)', '--accent' => '#a855f7', '--accent-ink' => '#0d0a17',
                    '--bar'         => 'linear-gradient(90deg,#a855f7,#0d0a17 55%,#c4b5fd)',
                ],
            ],
            'batman' => [
                'label' => 'Batman (Dark Knight)',
                'vars'  => [
                    '--bg-base'     => '#0b0e11', '--glow1' => 'rgba(255,217,59,.20)', '--glow2' => 'rgba(148,163,184,.14)',
                    '--text'        => '#e8eaed', '--muted' => 'rgba(232,234,237,.6)',
                    '--card-bg'     => 'rgba(255,255,255,.045)', '--card-border' => 'rgba(255,217,59,.30)',
                    '--card-shadow' => '0 24px 64px rgba(0,0,0,.65)', '--accent' => '#ffd93b', '--accent-ink' => '#0b0e11',
                    '--bar'         => 'linear-gradient(90deg,#0b0e11 0 60%,#ffd93b 60% 100%)', '--or' => '#ffd93b',
                ],
            ],
            'rayman' => [
                'label' => 'Rayman (vif & coloré)',
                'vars'  => [
                    '--bg-base'     => '#141a4a', '--glow1' => 'rgba(255,93,162,.30)', '--glow2' => 'rgba(56,189,248,.26)',
                    '--text'        => '#fef7ff', '--muted' => 'rgba(254,247,255,.7)',
                    '--card-bg'     => 'rgba(255,255,255,.07)', '--card-border' => 'rgba(255,207,51,.40)',
                    '--card-shadow' => '0 24px 64px rgba(5,10,45,.55)', '--accent' => '#ffcf33', '--accent-ink' => '#141a4a',
                    '--bar'         => 'linear-gradient(90deg,#ff5da2,#ffcf33,#38bdf8)',
                    '--rouge'       => '#ff5da2', '--vert' => '#3ddc97', '--or' => '#ffcf33',
                ],
            ],
            'custom' => [
                'label' => '🎨 Personnalisé',
                'vars'  => self::customVars(),
            ],
        ];
    }

    /**
     * Thèmes regroupés par FAMILLE (pour des menus rangés, avec <optgroup>).
     * Renvoie [ 'Nom de famille' => [ clé => thème, … ], … ]. Tout thème non
     * classé tombe automatiquement dans « Autres ».
     */
    public static function byFamily(): array
    {
        $all    = self::all();
        $groups = [
            'Communauté'             => ['panafricain', 'problack'],
            'Clairs'                 => ['classique', 'clair', 'menthe'],
            'Sombres & néon'         => ['sombre', 'ocean', 'foret', 'sunset', 'violet'],
            'Héros & dessins animés' => ['simpson', 'blackpanther', 'batman', 'rayman'],
            'Sur mesure'             => ['custom'],
        ];
        $out    = [];
        $listed = [];
        foreach ($groups as $fam => $keys) {
            foreach ($keys as $k) {
                if (isset($all[$k])) {
                    $out[$fam][$k] = $all[$k];
                    $listed[] = $k;
                }
            }
        }
        foreach ($all as $k => $t) {
            if (!in_array($k, $listed, true)) {
                $out['Autres'][$k] = $t;
            }
        }
        return $out;
    }

    /**
     * Variables du thème PERSONNALISÉ : 7 couleurs choisies par l'admin
     * (stockées dans Settings) ; les nuances (cartes, bordures, halos) sont
     * dérivées automatiquement via color-mix pour rester cohérentes.
     */
    public static function customVars(): array
    {
        $bg     = self::hex(Settings::get('theme_custom_bg', '#14110f'),     '#14110f');
        $text   = self::hex(Settings::get('theme_custom_text', '#ffffff'),   '#ffffff');
        $accent = self::hex(Settings::get('theme_custom_accent', '#f4c14b'), '#f4c14b');
        $ink    = self::hex(Settings::get('theme_custom_ink', '#14110f'),    '#14110f');
        $rouge  = self::hex(Settings::get('theme_custom_rouge', '#e63946'),  '#e63946');
        $vert   = self::hex(Settings::get('theme_custom_vert', '#2a9d4a'),   '#2a9d4a');
        $or     = self::hex(Settings::get('theme_custom_or', '#f4c14b'),     '#f4c14b');

        return [
            '--rouge'       => $rouge,
            '--vert'        => $vert,
            '--or'          => $or,
            '--bg-base'     => $bg,
            '--text'        => $text,
            '--muted'       => "color-mix(in srgb, {$text} 62%, transparent)",
            '--card-bg'     => "color-mix(in srgb, {$text} 6%, {$bg})",
            '--card-border' => "color-mix(in srgb, {$accent} 30%, transparent)",
            '--card-shadow' => '0 24px 64px rgba(0,0,0,.45)',
            '--accent'      => $accent,
            '--accent-ink'  => $ink,
            '--glow1'       => "color-mix(in srgb, {$rouge} 35%, transparent)",
            '--glow2'       => "color-mix(in srgb, {$vert} 35%, transparent)",
            '--bar'         => "linear-gradient(90deg, {$rouge}, {$or}, {$vert})",
        ];
    }

    /** Valide une couleur hex #rrggbb (sinon valeur par défaut). */
    public static function hex(?string $v, string $default): string
    {
        $v = trim((string) $v);
        return preg_match('/^#[0-9a-fA-F]{6}$/', $v) ? $v : $default;
    }

    /** Clé du thème actif : préférence PERSO du membre (session) sinon thème du site. */
    public static function key(): string
    {
        $all  = self::all();
        $pref = $_SESSION['theme_pref'] ?? '';
        if ($pref !== '' && array_key_exists($pref, $all)) {
            return $pref; // thème personnel du membre connecté
        }
        $k = Settings::get('theme', 'panafricain');
        return array_key_exists($k, $all) ? $k : 'panafricain';
    }

    /**
     * Balises « application installable » (PWA), communes à toutes les pages :
     * lien du manifeste, couleur de thème, icônes Apple, et enregistrement du
     * service worker. Couplé à manifest.json + sw.js + icon-192/512.png à la
     * racine, cela rend RPN installable (mobile/PC) et empaquetable en APK
     * (via PWABuilder). Voir docs/application.html.
     */
    public static function pwa(): string
    {
        $manifest = url('manifest.json');
        $sw       = url('sw.js');
        $scope    = url('');            // /rpm/
        $icon192  = url('icon-192.png?v=1');

        return '<link rel="manifest" href="' . $manifest . '">'
            . "\n  " . '<meta name="theme-color" content="#14110f">'
            . "\n  " . '<meta name="mobile-web-app-capable" content="yes">'
            . "\n  " . '<meta name="apple-mobile-web-app-capable" content="yes">'
            . "\n  " . '<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">'
            . "\n  " . '<meta name="apple-mobile-web-app-title" content="RPN">'
            . "\n  " . '<link rel="apple-touch-icon" href="' . $icon192 . '">'
            . "\n  " . '<script>'
            . "if('serviceWorker' in navigator){window.addEventListener('load',function(){"
            . "navigator.serviceWorker.register('" . $sw . "',{scope:'" . $scope . "'}).catch(function(){});"
            . "});}"
            . '</script>';
    }

    /**
     * Runtime de l'application (injecté sur toutes les pages) :
     *  1. Bannière « Mise à jour disponible » : compare la version embarquée à
     *     version.json (interrogé périodiquement) → bouton qui désinscrit le
     *     service worker, vide les caches et recharge (forceUpdate).
     *  2. Notifications « temps réel » pour les membres connectés : sondage
     *     adaptatif de notifications/poll (30 s quand l'onglet est visible,
     *     en pause sinon), notification système (ou toast en repli), mise à
     *     jour du badge et du titre. Ne s'exécute pas dans l'iframe d'aperçu.
     */
    public static function appRuntime(): string
    {
        $verFile = defined('APP_ROOT') ? APP_ROOT . '/version.json' : __DIR__ . '/../../version.json';
        $ver     = 'v1';
        if (is_file($verFile)) {
            $j = json_decode((string) @file_get_contents($verFile), true);
            if (!empty($j['version'])) {
                $ver = (string) $j['version'];
            }
        }
        $verUrl    = url('version.json');
        $pollUrl   = url('notifications/poll');
        $notifsUrl = url('notifications');
        $icon      = url('icon-192.png');
        $loggedIn  = \Session::has('user') ? 'true' : 'false';

        return <<<HTML
<style>
  #rpm-update-banner { position:fixed; left:0; right:0; top:0; z-index:9999; display:flex; align-items:center;
    justify-content:center; gap:12px; padding:10px 14px; font-family:inherit; font-size:14px; font-weight:600;
    background:var(--accent,#f4c14b); color:var(--accent-ink,#14110f); box-shadow:0 4px 18px rgba(0,0,0,.25); }
  #rpm-update-banner button { font:inherit; font-weight:700; cursor:pointer; border:none; border-radius:8px;
    padding:6px 14px; background:var(--accent-ink,#14110f); color:var(--accent,#f4c14b); }
  #rpm-update-check { position:fixed; right:16px; bottom:16px; z-index:9991; font-family:inherit; font-size:13px; font-weight:700;
    cursor:pointer; border-radius:999px; padding:10px 14px; color:var(--text,#fff); background:var(--card-bg,#1b1714);
    border:1px solid var(--card-border,rgba(244,193,75,.35)); box-shadow:0 8px 24px rgba(0,0,0,.3); }
  #rpm-update-check:active { transform:translateY(1px); }
  #rpm-update-check:disabled { opacity:.7; cursor:default; }
  .rpm-toast { position:fixed; right:16px; bottom:72px; z-index:9998; max-width:320px; padding:13px 16px; cursor:pointer;
    background:var(--card-bg,#1b1714); color:var(--text,#fff); border:1px solid var(--card-border,rgba(244,193,75,.3));
    border-radius:14px; box-shadow:0 14px 40px rgba(0,0,0,.45); font-size:14px; line-height:1.4;
    transform:translateY(20px); opacity:0; transition:transform .3s ease, opacity .3s ease; }
  .rpm-toast.show { transform:none; opacity:1; }
  #rpm-install-banner { position:fixed; left:0; right:0; bottom:16px; margin:0 auto; width:max-content; max-width:92%;
    z-index:9997; display:flex; align-items:center; gap:10px; padding:10px 12px 10px 16px; font-family:inherit; font-size:14px;
    background:var(--card-bg,#1b1714); color:var(--text,#fff); border:1px solid var(--card-border,rgba(244,193,75,.3));
    border-radius:14px; box-shadow:0 14px 40px rgba(0,0,0,.45); }
  #rpm-install-banner .rpm-install-go { font:inherit; font-weight:700; cursor:pointer; border:none; border-radius:9px;
    padding:8px 14px; background:var(--accent,#f4c14b); color:var(--accent-ink,#14110f); white-space:nowrap; }
  #rpm-install-banner .rpm-install-close { background:transparent; border:none; color:var(--muted,#aaa); font-size:16px;
    cursor:pointer; padding:4px 6px; line-height:1; }
</style>
<script>
(function () {
  if (window.top !== window.self) { return; }            // pas dans l'iframe d'aperçu
  var APP_VERSION = "$ver", VERSION_URL = "$verUrl", POLL_URL = "$pollUrl",
      NOTIFS_URL = "$notifsUrl", ICON = "$icon", LOGGED_IN = $loggedIn;

  function forceUpdate() {
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
      location.replace(location.pathname + '?_=' + Date.now());
    })();
  }
  /* ---- proposer l'installation de l'app aux visiteurs ---- */
  var deferredPrompt = null, DISMISS_KEY = 'rpm_install_dismissed';
  function isStandalone() {
    return window.matchMedia('(display-mode: standalone)').matches || navigator.standalone === true;
  }
  function dismissedRecently() {
    var t = parseInt(localStorage.getItem(DISMISS_KEY) || '0', 10);
    return t && (Date.now() - t < 7 * 24 * 3600 * 1000);   // re-proposer après 7 jours
  }
  function removeInstall() { var b = document.getElementById('rpm-install-banner'); if (b) { b.remove(); } }
  function buildInstall(inner) {
    if (!document.body || document.getElementById('rpm-install-banner')) { return null; }
    if (isStandalone() || dismissedRecently()) { return null; }
    var b = document.createElement('div'); b.id = 'rpm-install-banner'; b.innerHTML = inner;
    document.body.appendChild(b);
    b.querySelector('.rpm-install-close').addEventListener('click', function () {
      localStorage.setItem(DISMISS_KEY, String(Date.now())); removeInstall();
    });
    return b;
  }
  function showInstallButton() {        // Android / Chrome / Edge : vrai prompt
    var b = buildInstall('<span>📲 Installe l\\'application RPN pour un accès rapide.</span>'
      + '<button type="button" class="rpm-install-go">Installer</button>'
      + '<button type="button" class="rpm-install-close" aria-label="Fermer">✕</button>');
    if (!b) { return; }
    b.querySelector('.rpm-install-go').addEventListener('click', function () {
      if (!deferredPrompt) { return; }
      deferredPrompt.prompt();
      deferredPrompt.userChoice.then(function () { deferredPrompt = null; removeInstall(); });
    });
  }
  function showIosInstall() {           // iPhone/iPad Safari : pas d'API → instructions
    var ua = navigator.userAgent;
    if (!/iphone|ipad|ipod/i.test(ua)) { return; }
    if (!/safari/i.test(ua) || /crios|fxios|edgios/i.test(ua)) { return; }
    buildInstall('<span>📲 Pour installer RPN : <b>Partager</b> ⬆️ puis <b>« Sur l\\'écran d\\'accueil »</b>.</span>'
      + '<button type="button" class="rpm-install-close" aria-label="Fermer">✕</button>');
  }
  window.addEventListener('beforeinstallprompt', function (e) {
    e.preventDefault(); deferredPrompt = e;
    if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', showInstallButton); }
    else { showInstallButton(); }
  });
  window.addEventListener('appinstalled', function () { deferredPrompt = null; removeInstall(); });

  function showUpdateBanner() {
    if (document.getElementById('rpm-update-banner')) { return; }
    var b = document.createElement('div');
    b.id = 'rpm-update-banner';
    b.innerHTML = '🚀 Nouvelle version disponible. <button type="button">Mettre à jour</button>';
    b.querySelector('button').addEventListener('click', forceUpdate);
    document.body.appendChild(b);
  }
  function checkVersion() {
    fetch(VERSION_URL + '?_=' + Date.now(), { cache: 'no-store' })
      .then(function (r) { return r.json(); })
      .then(function (d) { if (d && d.version && d.version !== APP_VERSION) { showUpdateBanner(); } })
      .catch(function () {});
  }
  function flash(msg) {
    var f = document.createElement('div'); f.className = 'rpm-toast show'; f.textContent = msg; f.style.cursor = 'default';
    document.body.appendChild(f);
    setTimeout(function () { f.classList.remove('show'); setTimeout(function () { f.remove(); }, 320); }, 2600);
  }
  function manualCheck(ev) {                      // clic sur un bouton « 🔄 Mise à jour »
    var btn = ev && ev.currentTarget ? ev.currentTarget : null;
    if (btn) { if (!btn.dataset.lbl) { btn.dataset.lbl = btn.textContent; } btn.disabled = true; btn.textContent = '⏳ Vérification…'; }
    function reset() { if (btn) { btn.disabled = false; btn.textContent = btn.dataset.lbl || '🔄 Mise à jour'; } }
    fetch(VERSION_URL + '?_=' + Date.now(), { cache: 'no-store' })
      .then(function (r) { return r.json(); })
      .then(function (d) {
        if (d && d.version && d.version !== APP_VERSION) { flash('🚀 Mise à jour en cours…'); forceUpdate(); }
        else { flash('✓ Application à jour (' + APP_VERSION + ')'); reset(); }
      })
      .catch(function () { flash('Vérification impossible (hors ligne ?)'); reset(); });
  }

  function initNotifs() {
    var LS_KEY = 'rpm_notif_lastid', BASE_TITLE = document.title, asked = false, timer = null;
    function askPerm() {
      if (asked) { return; } asked = true;
      if ('Notification' in window && Notification.permission === 'default') {
        try { Notification.requestPermission(); } catch (e) {}
      }
    }
    document.addEventListener('click', askPerm, { once: true });

    // Petit son « ding » (synthétisé, sans fichier). Le contexte audio est
    // créé/réactivé au 1er clic (politique d'autoplay des navigateurs).
    var audioCtx = null;
    document.addEventListener('click', function () {
      try {
        if (!audioCtx) { audioCtx = new (window.AudioContext || window.webkitAudioContext)(); }
        else if (audioCtx.state === 'suspended') { audioCtx.resume(); }
      } catch (e) {}
    });
    function ding() {
      if (!audioCtx) { return; }
      try {
        var o = audioCtx.createOscillator(), g = audioCtx.createGain();
        o.type = 'sine'; o.frequency.value = 880; o.connect(g); g.connect(audioCtx.destination);
        var t0 = audioCtx.currentTime;
        g.gain.setValueAtTime(0.0001, t0);
        g.gain.exponentialRampToValueAtTime(0.25, t0 + 0.01);
        g.gain.exponentialRampToValueAtTime(0.0001, t0 + 0.35);
        o.start(t0); o.stop(t0 + 0.36);
      } catch (e) {}
    }

    function toast(text) {
      var t = document.createElement('div'); t.className = 'rpm-toast'; t.textContent = text;
      t.addEventListener('click', function () { location.href = NOTIFS_URL; });
      document.body.appendChild(t);
      setTimeout(function () { t.classList.add('show'); }, 10);
      setTimeout(function () { t.classList.remove('show'); setTimeout(function () { t.remove(); }, 320); }, 6000);
    }
    function popOne(item) {
      ding(); // son d'alerte
      if ('Notification' in window && Notification.permission === 'granted') {
        var opts = { body: item.message, icon: ICON, badge: ICON, tag: 'rpm-' + item.id,
                     data: { url: NOTIFS_URL }, vibrate: [80, 40, 80], renotify: true };
        // Notification SYSTÈME via le service worker (bannière style SMS sur mobile + vibration).
        if (navigator.serviceWorker && navigator.serviceWorker.ready) {
          navigator.serviceWorker.ready
            .then(function (reg) { return reg.showNotification(item.icon + ' RPN', opts); })
            .catch(function () { toast(item.icon + ' ' + item.message); });
          return;
        }
        // Repli (desktop) : notification niveau page.
        try {
          var n = new Notification(item.icon + ' RPN', opts);
          n.onclick = function () { window.focus(); location.href = NOTIFS_URL; n.close(); };
          return;
        } catch (e) {}
      }
      toast(item.icon + ' ' + item.message);
    }
    function updateBadges(n) {
      document.title = (n > 0 ? '(' + n + ') ' : '') + BASE_TITLE;
      document.querySelectorAll('.nav-badge, .notif-badge').forEach(function (b) {
        b.textContent = n > 9 ? '9+' : n; b.style.display = n > 0 ? '' : 'none';
      });
    }
    function poll() {
      fetch(POLL_URL + '?_=' + Date.now(), { cache: 'no-store', credentials: 'same-origin' })
        .then(function (r) { return r.json(); })
        .then(function (d) {
          if (!d || !d.ok) { return; }
          updateBadges(d.unread || 0);
          var last = parseInt(localStorage.getItem(LS_KEY) || '0', 10);
          if (!last) { localStorage.setItem(LS_KEY, String(d.maxId || 0)); return; }  // 1er chargement : pas de pop
          if ((d.maxId || 0) > last) {
            (d.items || []).filter(function (it) { return it.id > last; })
              .sort(function (a, b) { return a.id - b.id; }).forEach(popOne);
            localStorage.setItem(LS_KEY, String(d.maxId));
          }
        }).catch(function () {});
    }
    function start() { if (timer) { return; } poll(); timer = setInterval(poll, 30000); }
    function stop() { if (timer) { clearInterval(timer); timer = null; } }
    document.addEventListener('visibilitychange', function () { document.hidden ? stop() : start(); });
    if (!document.hidden) { start(); }
  }

  function init() {
    // Bouton « Mise à jour » : PLUS de bouton flottant partout. On se branche
    // seulement sur les boutons présents dans la page (ex. le tableau de bord,
    // sous l'adresse e-mail) qui portent l'attribut data-rpm-update-check.
    document.querySelectorAll('[data-rpm-update-check]').forEach(function (btn) {
      btn.addEventListener('click', manualCheck);
    });

    checkVersion();
    setInterval(checkVersion, 120000);                   // re-vérifie la version toutes les 2 min
    showIosInstall();                                    // proposition d'install iOS (Android = événement)
    if (LOGGED_IN) { initNotifs(); }
  }
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else { init(); }
})();
</script>
HTML;
    }

    /**
     * Liens du favicon, communs à toutes les pages. Un numéro de version
     * (?v=…) force le rechargement après un changement via l'espace admin.
     * Si l'admin a généré un favicon personnalisé, on n'émet PLUS le favicon.svg
     * historique (qui aurait la priorité dans les navigateurs qui gèrent le SVG).
     */
    public static function favicon(): string
    {
        $v      = '?v=' . rawurlencode((string) Settings::get('favicon_version', '1'));
        $png    = url('favicon.png') . $v;
        $ico    = url('favicon.ico') . $v;
        $custom = (int) Settings::get('favicon_custom', 0) === 1;

        $out = '<link rel="icon" href="' . $ico . '" sizes="any">';
        if (!$custom) {
            $out .= '<link rel="icon" href="' . url('favicon.svg') . $v . '" type="image/svg+xml">';
        }
        $out .= '<link rel="icon" href="' . $png . '" sizes="any" type="image/png">'
              . '<link rel="apple-touch-icon" href="' . $png . '">';
        return $out;
    }

    /**
     * Améliorations UX appliquées à TOUT le site (injectées avant les styles
     * de chaque page, donc surchargeables par celles-ci) : accessibilité,
     * défilement fluide, micro-interactions, barres de défilement, etc.
     */
    public static function ux(): string
    {
        return <<<CSS
<style>
    html { scroll-behavior:smooth; scroll-padding-top:24px; -webkit-text-size-adjust:100%; }
    body { -webkit-font-smoothing:antialiased; text-rendering:optimizeLegibility; }
    * { -webkit-tap-highlight-color:transparent; }
    ::selection { background:var(--accent); color:var(--accent-ink); }

    /* Accessibilité : anneau de focus visible et cohérent au clavier */
    :focus-visible { outline:2px solid var(--accent); outline-offset:2px; border-radius:6px; }
    :focus:not(:focus-visible) { outline:none; }

    /* Micro-interactions cohérentes sur les éléments cliquables */
    a, button, .btn, .act, input[type=submit], select, summary {
        transition:color .15s, background-color .15s, border-color .15s, box-shadow .2s, transform .08s, filter .15s;
    }
    button:not(:disabled):active, .btn:not(:disabled):active, .act:not(:disabled):active, input[type=submit]:not(:disabled):active {
        transform:translateY(1px);
    }
    button, summary, label[for], input[type=checkbox], input[type=radio], input[type=color], input[type=range] { cursor:pointer; }
    input, textarea, select { transition:border-color .15s, box-shadow .15s, background-color .15s; }

    /* Barre de défilement discrète, accordée au thème */
    * { scrollbar-width:thin; scrollbar-color:var(--card-border) transparent; }
    ::-webkit-scrollbar { width:10px; height:10px; }
    ::-webkit-scrollbar-thumb { background:var(--card-border); border-radius:8px; }
    ::-webkit-scrollbar-thumb:hover { background:var(--accent); }
    ::-webkit-scrollbar-track { background:transparent; }

    /* Bouton en cours d'envoi : léger retour visuel + mini spinner */
    .is-loading { opacity:.75; pointer-events:none; }
    .is-loading::after { content:''; display:inline-block; width:.8em; height:.8em; margin-left:.5em; vertical-align:-.1em;
        border:2px solid currentColor; border-right-color:transparent; border-radius:50%; animation:ux-spin .6s linear infinite; }
    @keyframes ux-spin { to { transform:rotate(360deg); } }

    /* Respecte la préférence système « réduire les animations » */
    @media (prefers-reduced-motion: reduce) {
        html { scroll-behavior:auto; }
        *, *::before, *::after { animation-duration:.001ms !important; animation-iteration-count:1 !important; transition-duration:.001ms !important; }
    }
</style>
<script>
// Anti double-soumission + retour visuel, pour TOUS les formulaires du site.
document.addEventListener('submit', function (e) {
    if (e.defaultPrevented) { return; }            // soumission annulée (ex : confirmation refusée)
    var form = e.target;
    if (form.__submitting) { e.preventDefault(); return; }
    form.__submitting = true;
    var btn = form.querySelector('button[type=submit], input[type=submit], button:not([type])');
    // On désactive APRÈS l'envoi (setTimeout) pour ne pas perdre la valeur du bouton.
    if (btn) { setTimeout(function () { btn.disabled = true; btn.classList.add('is-loading'); }, 0); }
});
</script>
CSS;
    }

    /**
     * Bloc <head> commun : variables CSS du thème actif + polish UX + favicon.
     * (Inclus dans le <head> de toutes les vues via <?= Theme::css() ?>.)
     */
    public static function css(): string
    {
        $vars = array_merge(self::BASE, self::all()[self::key()]['vars']);
        $lines = '';
        foreach ($vars as $name => $value) {
            $lines .= "      {$name}: {$value};\n";
        }
        return "<style>\n    :root {\n{$lines}    }\n  </style>\n  " . self::ux() . "\n  " . self::favicon()
             . "\n  " . self::pwa()
             . "\n  " . self::appRuntime()
             . "\n  " . Tour::html()
             . "\n  " . GlobalStyle::fontLink() . "\n  " . GlobalStyle::css();
    }
}
