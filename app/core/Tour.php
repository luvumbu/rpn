<?php
/**
 * CLASSE Tour
 * Visite guidée interactive « en direct » : surligne les vrais éléments de la
 * page (spotlight) et explique chaque partie avec une bulle (Précédent / Suivant).
 * Les étapes sont choisies selon la page courante (URL). La visite s'enchaîne
 * d'une page à l'autre (?tour=auto) pour couvrir TOUTE l'application :
 *   Tableau de bord → Articles → Agenda → Notifications.
 *
 * Injecté dans le <head> de toutes les pages via Theme::css(), seulement pour
 * les membres connectés. Lancé par le bouton flottant « 🎓 Visite guidée »
 * ou automatiquement quand l'URL contient ?tour=auto.
 */
class Tour
{
    public static function html(): string
    {
        if (!\Session::has('user')) {
            return '';
        }
        $base = url(''); // ex: /rpm/

        return <<<HTML
<style>
  #rpm-tour-launch { position:fixed; left:16px; bottom:16px; z-index:9990; font-family:inherit; font-size:13px; font-weight:700;
    cursor:pointer; border:none; border-radius:999px; padding:10px 16px; color:var(--accent-ink,#14110f);
    background:var(--accent,#f4c14b); box-shadow:0 8px 24px rgba(0,0,0,.3); }
  #rpm-tour-launch:active { transform:translateY(1px); }
  .rpm-tour-mask { position:fixed; inset:0; z-index:10000; background:transparent; }
  .rpm-tour-hole { position:fixed; z-index:10001; border-radius:12px; pointer-events:none;
    box-shadow:0 0 0 9999px rgba(10,8,6,.72); transition:all .25s ease; }
  .rpm-tour-tip { position:fixed; z-index:10002; max-width:300px; width:calc(100% - 32px);
    background:var(--bg-base,#14110f); /* couleur du thème, OPAQUE (lisible sur le voile sombre) */
    color:var(--text,#fff); border:1px solid var(--accent,#f4c14b); border-radius:16px;
    box-shadow:0 18px 50px rgba(0,0,0,.6); padding:16px 18px; font-family:inherit; }
  .rpm-tour-tip h4 { font-size:15px; margin:0 0 6px; color:var(--accent,#f4c14b); }
  .rpm-tour-tip p { font-size:13.5px; line-height:1.5; margin:0 0 14px; color:var(--text,#fff); }
  .rpm-tour-row { display:flex; align-items:center; justify-content:space-between; gap:10px; }
  .rpm-tour-step { font-size:12px; color:var(--muted,#aaa); }
  .rpm-tour-btns { display:flex; gap:8px; }
  .rpm-tour-btns button { font:inherit; font-size:13px; font-weight:700; cursor:pointer; border-radius:9px; padding:7px 13px; border:1px solid var(--card-border,rgba(244,193,75,.35)); background:transparent; color:var(--text,#fff); }
  .rpm-tour-btns button.primary { background:var(--accent,#f4c14b); color:var(--accent-ink,#14110f); border-color:var(--accent,#f4c14b); }
  .rpm-tour-skip { background:transparent; border:none; color:var(--muted,#aaa); cursor:pointer; font:inherit; font-size:12px; text-decoration:underline; padding:0; }
</style>
<script>
(function () {
  if (window.top !== window.self) { return; }   // pas dans l'iframe d'aperçu
  var BASE = "$base";

  // --- Étapes par page (ciblent de vrais éléments ; les manquants sont sautés) ---
  var PAGES = {
    'dashboard': {
      next: 'articles',
      steps: [
        { sel: '.profile',                       t: '👤 Ton profil', d: 'Ton nom, ton rôle, ta photo et ton e-mail. Tu peux changer ta photo ici.' },
        { sel: '.level-card',                    t: '🏅 Ton niveau', d: 'Héritier → Sage : gagne des points en publiant, en passant des QCM, en participant. Suis ta progression ici.' },
        { sel: '.role-card',                     t: '🎓 Ton rôle & ton adresse', d: 'Choisis tes matières par catégorie et renseigne ton adresse pour être trouvé·e par les autres.' },
        { sel: '.stats',                         t: '📊 Tes statistiques', d: 'Articles publiés, tes articles, questionnaires — en un coup d\\'œil (clique pour y aller).' },
        { sel: '.action[href*="/articles"]',     t: '📰 Articles', d: 'Lis les publications, écris les tiennes, ajoute photos et pièces jointes.' },
        { sel: '.action[href*="/agenda"]',       t: '📅 Agenda & rendez-vous', d: 'Propose des créneaux, réserve ceux des autres, cherche par proximité.' },
        { sel: '.action[href*="/professeurs"]',  t: '🔍 Rechercher un profil', d: 'Trouve un membre ou un professeur par nom, matière ou ville.' },
        { sel: '.action[href*="/messages"]',     t: '✉️ Messages', d: 'Discute en privé avec les autres membres. Le badge indique les non-lus.' },
        { sel: '.action[href*="/diaspora"]',     t: '🗺️ Carte de la diaspora', d: 'Explore la communauté afro-descendante sur une carte interactive.' },
        { sel: '.action[href*="/assistant"]',    t: '🤖 Assistant Sankofa', d: 'Pose une question : l\\'assistant (un algorithme) te guide vers la bonne ressource.' },
        { sel: '.action[href*="/notifications"]',t: '🔔 Notifications', d: 'Tu es prévenu·e en temps réel : messages, réservations, nouveaux créneaux…' },
        { sel: '.action.accent',                 t: '⚙️ Espace admin', d: 'Si tu es administrateur : modération des membres, des articles et réglages.' },
        { sel: '.logout',                        t: '⏻ Déconnexion', d: 'Pour quitter ta session en toute sécurité.' }
      ]
    },
    'articles': {
      next: 'agenda',
      steps: [
        { sel: '.tab[data-tab="public"]', t: '🌍 Articles publics', d: 'Tous les articles publiés, y compris les tiens. Clique une carte pour le lire en entier (photos, avis, discussion).' },
        { sel: '.tab[data-tab="mine"]',   t: '🗂️ Mes articles', d: 'Tes publications et tes brouillons, pour les retrouver et les modifier.' },
        { sel: '.tab[data-tab="search"]', t: '🔎 Rechercher', d: 'Filtre les articles par leur titre, instantanément.' },
        { sel: '.tab[data-tab="write"]',  t: '✍️ Écrire', d: 'Rédige un nouvel article : éditeur riche, mises en page, image de couverture et galerie.' },
        { sel: '#panel-public .card',     t: '📄 Une carte d\\'article', d: 'Aperçu : image, note moyenne, nombre de vues. Clique pour ouvrir l\\'article complet.' }
      ]
    },
    'agenda': {
      next: 'professeurs',
      steps: [
        { sel: '.tab[data-tab="find"]', t: '🔎 Trouver un cours', d: 'Cherche des créneaux publics par proximité (ville/adresse + rayon) ou par code privé.' },
        { sel: '.tab[data-tab="cal"]',  t: '🗓️ Calendrier', d: 'Vue Jour / Semaine / Mois / Liste de tes créneaux et réservations.' },
        { sel: '.tab[data-tab="book"]', t: '📅 Mes réservations', d: 'Les créneaux que tu as réservés, avec la possibilité de noter l\\'hôte.' },
        { sel: '.tab[data-tab="mine"]', t: '🗂️ Mes créneaux', d: 'Tes créneaux à venir : modifier l\\'heure, le lieu, les places, voir les inscrits.' },
        { sel: '.tab[data-tab="past"]', t: '🕒 Événements passés', d: 'Tes créneaux terminés (lecture seule) : confirmer la présence des inscrits.' },
        { sel: '.tab[data-tab="new"]',  t: '➕ Proposer', d: 'Crée un créneau : titre, date/heure, lieu (présentiel ou en ligne), places, public ou privé.' }
      ]
    },
    'professeurs': {
      next: 'messages',
      steps: [
        { sel: '.search', t: '🔍 Rechercher un profil', d: 'Tape un nom, une matière ou une ville pour trouver un membre / professeur.' },
        { sel: '.chips',  t: '🏷️ Filtres par matière', d: 'Clique une matière pour ne voir que les professeurs concernés.' },
        { sel: '.teacher', t: '🧑‍🏫 Une fiche', d: 'Photo, niveau, matières et ville. Le bouton « Message » ouvre une conversation privée.' }
      ]
    },
    'messages': {
      next: 'diaspora',
      steps: [
        { sel: 'h1', t: '✉️ Messagerie privée', d: 'Tes conversations avec les autres membres. Le compteur rouge indique les messages non lus.' },
        { sel: '.thread', t: '💬 Une conversation', d: 'Clique pour ouvrir l\\'échange et répondre.' }
      ]
    },
    'diaspora': {
      next: 'assistant',
      steps: [
        { sel: '#map', t: '🗺️ Carte de la diaspora', d: 'Les grands foyers afro-descendants (Afrique, Amériques, Caraïbes, Europe). Les points dorés sont les membres trouvables.' }
      ]
    },
    'assistant': {
      next: 'notifications',
      steps: [
        { sel: '.ask',  t: '🤖 Assistant Sankofa', d: 'Pose une question ou un mot-clé : l\\'algorithme cherche dans les articles, les QCM et t\\'oriente.' },
        { sel: '.hint', t: '💡 Exemples', d: 'Clique un exemple pour voir comment l\\'assistant répond.' }
      ]
    },
    'notifications': {
      next: null,
      steps: [
        { sel: 'h1', t: '🔔 Notifications', d: 'Tout ce qui te concerne arrive ici, en temps réel : messages, réservations, annulations, nouveaux créneaux publics…' }
      ]
    }
  };

  function pageKey() {
    var p = location.pathname.replace(/\\/+$/, '');
    if (p.endsWith('/dashboard'))      { return 'dashboard'; }
    if (p.endsWith('/notifications'))  { return 'notifications'; }
    if (p.endsWith('/agenda'))         { return 'agenda'; }
    if (p.endsWith('/articles'))       { return 'articles'; }
    if (p.endsWith('/professeurs'))    { return 'professeurs'; }
    if (p.endsWith('/messages'))       { return 'messages'; }
    if (p.endsWith('/diaspora'))       { return 'diaspora'; }
    if (p.endsWith('/assistant'))      { return 'assistant'; }
    return null;
  }

  // CONF peut être nul (page sans étapes) : le bouton « Visite guidée » reste
  // affiché PARTOUT et lance la visite complète depuis le tableau de bord.
  var KEY  = pageKey();
  var CONF = (KEY && PAGES[KEY]) ? PAGES[KEY] : null;

  var mask, hole, tip, idx = 0, steps = [];

  function teardown() {
    [mask, hole, tip].forEach(function (e) { if (e && e.parentNode) { e.remove(); } });
    mask = hole = tip = null;
    document.removeEventListener('keydown', onKey);
    window.removeEventListener('resize', reposition);
  }
  function onKey(e) {
    if (e.key === 'Escape') { teardown(); }
    else if (e.key === 'ArrowRight') { go(1); }
    else if (e.key === 'ArrowLeft') { go(-1); }
  }
  function reposition() { if (steps.length) { render(); } }

  function go(dir) {
    var n = idx + dir;
    // saute les étapes dont l'élément est absent
    while (n >= 0 && n < steps.length && !document.querySelector(steps[n].sel)) { n += dir; }
    if (n < 0) { return; }
    if (n >= steps.length) { finish(); return; }
    idx = n; render();
  }

  function finish() {
    teardown();
    if (CONF.next) {
      location.href = BASE + CONF.next + '?tour=auto';
    }
  }

  function render() {
    var step = steps[idx];
    var el = document.querySelector(step.sel);
    if (!el) { go(1); return; }
    // Défilement INSTANTANÉ : la position lue juste après est fiable (le mode
    // « smooth » lisait une position périmée → bulle hors de l'écran).
    el.scrollIntoView({ block: 'center', behavior: 'auto' });
    var r = el.getBoundingClientRect();
    var vw0 = window.innerWidth, vh0 = window.innerHeight, pad = 6;
    // Spotlight clampé à l'écran (ne déborde pas).
    var hl = Math.max(0, r.left - pad), ht = Math.max(0, r.top - pad);
    hole.style.left   = hl + 'px';
    hole.style.top    = ht + 'px';
    hole.style.width  = Math.min(r.width + pad * 2, vw0 - hl) + 'px';
    hole.style.height = Math.min(r.height + pad * 2, vh0 - ht) + 'px';

    var isLast = idx >= steps.length - 1;
    var nextLbl = isLast ? (CONF.next ? 'Continuer →' : 'Terminer ✓') : 'Suivant →';
    tip.innerHTML =
      '<h4>' + step.t + '</h4><p>' + step.d + '</p>' +
      '<div class="rpm-tour-row"><span class="rpm-tour-step">Étape ' + (idx + 1) + ' / ' + steps.length + '</span>' +
      '<span class="rpm-tour-btns">' +
        (idx > 0 ? '<button data-prev>← Précédent</button>' : '') +
        '<button class="primary" data-next>' + nextLbl + '</button>' +
      '</span></div>' +
      '<div style="margin-top:10px;text-align:right;"><button class="rpm-tour-skip" data-skip>Passer la visite</button></div>';

    // Positionnement de la bulle — TOUJOURS entièrement dans l'écran.
    var vh = window.innerHeight, vw = window.innerWidth, M = 12;
    tip.style.visibility = 'hidden';
    // Ne jamais dépasser la hauteur de l'écran (sinon : ascenseur interne).
    tip.style.maxHeight = (vh - 2 * M) + 'px';
    tip.style.overflowY = 'auto';
    if (vw <= 640) {
      // MOBILE : bulle ancrée en BAS, pleine largeur, jamais coupée.
      tip.style.left = M + 'px'; tip.style.right = M + 'px'; tip.style.width = 'auto'; tip.style.maxWidth = 'none';
      var thm = Math.min(tip.offsetHeight, vh - 2 * M);
      tip.style.top = Math.max(M, vh - thm - M) + 'px';
    } else {
      // Bureau : sous l'élément si ça tient, sinon au-dessus, sinon collé en bas ;
      // puis clamp strict sur les deux axes pour rester dans l'écran.
      tip.style.right = 'auto'; tip.style.width = ''; tip.style.maxWidth = '';
      var th = tip.offsetHeight, tw = tip.offsetWidth;
      var top;
      if (r.bottom + M + th <= vh - M) { top = r.bottom + M; }
      else if (r.top - M - th >= M)    { top = r.top - th - M; }
      else                              { top = vh - th - M; }
      top = Math.max(M, Math.min(top, vh - th - M));
      var left = Math.max(M, Math.min(r.left, vw - tw - M));
      tip.style.top = top + 'px';
      tip.style.left = left + 'px';
    }
    tip.style.visibility = 'visible';

    tip.querySelector('[data-next]').onclick = function () { go(1); };
    var pv = tip.querySelector('[data-prev]'); if (pv) { pv.onclick = function () { go(-1); }; }
    tip.querySelector('[data-skip]').onclick = teardown;
  }

  function start() {
    // Page sans étapes → on lance la visite complète depuis le tableau de bord.
    if (!CONF) { location.href = BASE + 'dashboard?tour=auto'; return; }
    // ne garde que les étapes dont l'élément existe
    steps = CONF.steps.filter(function (s) { return document.querySelector(s.sel); });
    if (!steps.length) {
      if (CONF.next) { location.href = BASE + CONF.next + '?tour=auto'; } // saute vers la page suivante
      return;
    }
    idx = 0;
    mask = document.createElement('div'); mask.className = 'rpm-tour-mask';
    hole = document.createElement('div'); hole.className = 'rpm-tour-hole';
    tip  = document.createElement('div'); tip.className = 'rpm-tour-tip';
    document.body.appendChild(mask); document.body.appendChild(hole); document.body.appendChild(tip);
    mask.addEventListener('click', function () {});  // bloque les clics derrière
    document.addEventListener('keydown', onKey);
    window.addEventListener('resize', reposition);
    render();
  }

  function init() {
    // bouton lanceur flottant
    var btn = document.createElement('button');
    btn.id = 'rpm-tour-launch'; btn.type = 'button'; btn.textContent = '🎓 Visite guidée';
    btn.addEventListener('click', start);
    document.body.appendChild(btn);
    // démarrage auto si on arrive via l'enchaînement
    if (/[?&]tour=auto/.test(location.search)) { setTimeout(start, 500); }
  }
  if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', init); } else { init(); }
})();
</script>
HTML;
    }
}
