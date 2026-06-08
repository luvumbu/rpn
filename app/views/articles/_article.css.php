<?php
/**
 * Styles de présentation d'un article — PARTAGÉS entre la page publique
 * (show.view.php) et l'aperçu en temps réel du formulaire (preview.view.php).
 * Source unique : modifier ici met à jour l'affichage réel ET l'aperçu.
 */
?>
    <style>
        * { box-sizing:border-box; margin:0; padding:0; }
        body {
            font-family:'Poppins',sans-serif; min-height:100vh; color:var(--text); padding:40px 20px;
            background:
                radial-gradient(circle at 10% 0%, var(--glow1), transparent 40%),
                radial-gradient(circle at 90% 100%, var(--glow2), transparent 42%),
                var(--bg-base);
        }
        body::before { content:""; position:fixed; top:0; left:0; right:0; height:6px; background:var(--bar); }
        .wrap { max-width:760px; margin:0 auto; }
        .nav { display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap; margin-bottom:22px; }
        .nav > a { color:var(--text); text-decoration:none; font-size:14px; padding:8px 16px; border-radius:10px; border:1px solid var(--card-border); }
        .nav > a:hover { border-color:var(--accent); color:var(--accent); }
        .nav .views { font-size:13px; color:var(--muted); padding:8px 14px; border-radius:10px;
            background:rgba(127,127,127,.10); border:1px solid var(--card-border); white-space:nowrap; margin-right:auto; }
        .manage { display:flex; gap:8px; align-items:center; }
        .manage a, .manage button { text-decoration:none; font-size:13px; font-weight:600; padding:8px 14px;
            border-radius:10px; border:none; cursor:pointer; color:#fff; font-family:inherit; }
        .manage a.edit { background:#6d28d9; }
        .manage button.del { background:var(--rouge); }
        .manage button.pub.on { background:#16a34a; }
        .manage button.pub.off { background:#64748b; }
        .manage a:hover, .manage button:hover { filter:brightness(1.1); color:#fff; }
        .manage .status { font-size:12px; font-weight:600; padding:6px 12px; border-radius:999px; border:1px solid var(--card-border); }
        .manage .status.live { color:#16a34a; }
        .manage .status.draft { color:var(--muted); }

        .art { background:var(--card-bg); border:1px solid var(--card-border); border-radius:20px; overflow:hidden; box-shadow:var(--card-shadow); }
        .hero { width:100%; max-height:420px; object-fit:cover; display:block; }
        .content { padding:32px 36px 40px; }
        h1 { font-size:30px; color:var(--accent); line-height:1.25; margin-bottom:10px; }
        .meta { font-size:13px; color:var(--muted); margin-bottom:24px; }

        /* Contenu mis en forme */
        .article-body { font-size:16px; line-height:1.75; color:var(--text); word-wrap:break-word; }
        .article-body p { margin:0 0 14px; }
        .article-body h2 { font-size:22px; color:var(--accent); margin:26px 0 10px; }
        .article-body h3 { font-size:18px; color:var(--accent); margin:20px 0 8px; }
        .article-body ul, .article-body ol { margin:0 0 14px 24px; }
        .article-body li { margin:4px 0; }
        .article-body a { color:var(--accent); text-decoration:underline; }
        .article-body strong, .article-body b { font-weight:700; }
        .article-body blockquote { border-left:3px solid var(--accent); margin:16px 0; padding:8px 18px;
            color:var(--muted); background:rgba(127,127,127,.06); border-radius:0 10px 10px 0; }

        /* === MODÈLE : Magazine === */
        .tpl-magazine .wrap { max-width:880px; }
        .mag-hero { position:relative; min-height:380px; background-size:cover; background-position:center;
            display:flex; align-items:flex-end; }
        .mag-overlay { width:100%; padding:44px 36px 28px; background:linear-gradient(to top, rgba(0,0,0,.78), rgba(0,0,0,0)); }
        .mag-overlay h1 { color:#fff; font-size:34px; margin-bottom:8px; text-shadow:0 2px 14px rgba(0,0,0,.5); }
        .mag-overlay .meta { color:rgba(255,255,255,.85); margin-bottom:0; }

        /* === MODÈLE : Minimal === */
        .tpl-minimal .wrap { max-width:680px; }
        .tpl-minimal .art { background:transparent; border:none; box-shadow:none; }
        .tpl-minimal .content { padding:8px 0 40px; }
        .tpl-minimal h1 { font-size:34px; text-align:center; }
        .tpl-minimal .meta { text-align:center; }

        /* === MODÈLE : Côte à côte === */
        .tpl-cote .wrap { max-width:980px; }
        .split { display:flex; }
        .split-img { flex:0 0 42%; }
        .split-img img { width:100%; height:100%; min-height:320px; object-fit:cover; display:block; }
        .split .content { flex:1; }
        @media (max-width:680px) {
            .split { flex-direction:column; }
            .split-img { flex:none; }
            .split-img img { min-height:220px; max-height:300px; }
        }

        /* === MODÈLE : Portrait (image à droite) — réutilise .split de Côte à côte === */
        .tpl-portrait .wrap { max-width:980px; }
        .tpl-portrait .split { flex-direction:row-reverse; }
        @media (max-width:680px) { .tpl-portrait .split { flex-direction:column; } }

        /* === MODÈLE : Pleine largeur === */
        .tpl-pleine .wrap { max-width:1100px; }
        .tpl-pleine .hero { max-height:520px; }
        .tpl-pleine .content { padding:40px 56px 48px; }
        .tpl-pleine h1 { font-size:38px; }
        @media (max-width:680px) { .tpl-pleine .content { padding:28px 24px 32px; } .tpl-pleine h1 { font-size:28px; } }

        /* === MODÈLE : Journal (texte en deux colonnes) === */
        .tpl-journal .wrap { max-width:920px; }
        .tpl-journal h1 { font-size:32px; }
        .tpl-journal .article-body { column-count:2; column-gap:36px; column-rule:1px solid var(--card-border); }
        .tpl-journal .article-body h2, .tpl-journal .article-body h3, .tpl-journal .article-body blockquote { column-span:all; }
        @media (max-width:680px) { .tpl-journal .article-body { column-count:1; } }

        /* === MODÈLE : Affiche (couverture plein cadre, titre centré) — famille « vedette » === */
        .tpl-affiche .wrap { max-width:880px; }
        .tpl-affiche .mag-hero { min-height:520px; align-items:center; justify-content:center; text-align:center; }
        .tpl-affiche .mag-overlay { background:linear-gradient(to top, rgba(0,0,0,.55), rgba(0,0,0,.25)); padding:40px 30px; }
        .tpl-affiche .mag-overlay h1 { font-size:40px; }

        /* === MODÈLE : Vitrine (grande image, titre centré dessous) === */
        .tpl-vitrine .wrap { max-width:1000px; }
        .tpl-vitrine .hero { max-height:480px; }
        .tpl-vitrine .content { text-align:center; padding:34px 44px 44px; }
        .tpl-vitrine h1 { font-size:36px; }

        /* === MODÈLE : Moderne (très grand titre, image arrondie) === */
        .tpl-moderne .wrap { max-width:960px; }
        .tpl-moderne .art { border-radius:28px; }
        .tpl-moderne .hero { max-height:460px; }
        .tpl-moderne .content { padding:36px 44px 46px; }
        .tpl-moderne h1 { font-size:44px; letter-spacing:-1px; line-height:1.1; }
        @media (max-width:680px) { .tpl-moderne h1 { font-size:30px; } }

        /* === MODÈLE : Bandeau (fine image panoramique) === */
        .tpl-bandeau .wrap { max-width:820px; }
        .tpl-bandeau .hero { max-height:200px; }

        /* === MODÈLE : Fiche (petite image à côté, compact) — réutilise .split === */
        .tpl-fiche .wrap { max-width:760px; }
        .tpl-fiche .split-img { flex:0 0 34%; }
        .tpl-fiche .split-img img { min-height:180px; }
        .tpl-fiche .content { padding:24px 26px 28px; }

        /* === MODÈLE : Classique (colonne étroite, justifié) === */
        .tpl-classique .wrap { max-width:680px; }
        .tpl-classique .article-body { text-align:justify; }
        .tpl-classique .article-body p + p { text-indent:1.6em; }

        /* === MODÈLE : Élégant (larges marges, lecture aérée) === */
        .tpl-elegant .wrap { max-width:640px; }
        .tpl-elegant .content { padding:42px 52px 52px; }
        .tpl-elegant .article-body { line-height:2; }
        .tpl-elegant h1 { font-weight:600; }
        @media (max-width:680px) { .tpl-elegant .content { padding:28px 24px 34px; } }

        /* === MODÈLE : Encadré (cadre accentué) === */
        .tpl-encadre .wrap { max-width:740px; }
        .tpl-encadre .content { border-left:5px solid var(--accent); background:rgba(127,127,127,.04); }

        /* === MODÈLE : Carte === */
        .tpl-carte .wrap { max-width:560px; }
        .tpl-carte .hero { max-height:320px; }
        .tpl-carte .content { padding:26px 30px 32px; }
        .tpl-carte h1 { font-size:26px; text-align:center; }
        .tpl-carte .meta { text-align:center; }

        /* === MODÈLES ANIMÉS : Carrousel & Diaporama === */
        .tpl-carrousel .wrap, .tpl-diaporama .wrap { max-width:920px; }
        .carousel { position:relative; }
        .carousel-viewport { position:relative; overflow:hidden; }
        .carousel-track { display:flex; transition:transform .5s ease; }
        .cslide { flex:0 0 100%; min-width:100%; }
        .cslide img { width:100%; height:440px; object-fit:cover; display:block; background:rgba(127,127,127,.12); }
        /* Centrage vertical via top:calc (PAS transform:translateY) : un translate
           sur le bouton crée un calque composité dont le hit-test réel diverge de
           sa position peinte → les clics « rataient » le bouton (la navigation ne
           réagissait pas). Le bouton fait 44px de haut → décalage de 22px. */
        .carousel-nav { position:absolute; top:calc(50% - 22px); width:44px; height:44px; border-radius:50%;
            border:none; cursor:pointer; background:rgba(0,0,0,.45); color:#fff; font-size:26px; line-height:1; z-index:3;
            display:flex; align-items:center; justify-content:center; transition:background .15s; }
        .carousel-nav:hover { background:rgba(0,0,0,.72); }
        .carousel-nav.prev { left:14px; }
        .carousel-nav.next { right:14px; }
        .carousel-dots { position:absolute; bottom:14px; left:0; right:0; z-index:3;
            display:flex; gap:8px; justify-content:center; }
        .cdot { width:11px; height:11px; border-radius:50%; border:2px solid #fff; background:transparent;
            cursor:pointer; padding:0; transition:background .15s, transform .15s; }
        .cdot.active { background:#fff; transform:scale(1.15); }

        /* Diaporama : fondu enchaîné + titre superposé sur l'image */
        .tpl-diaporama .carousel-track { display:block; }
        .tpl-diaporama .carousel-viewport { min-height:480px; }
        .tpl-diaporama .cslide { position:absolute; inset:0; opacity:0; transition:opacity .8s ease; }
        .tpl-diaporama .cslide:first-child { position:relative; }
        .tpl-diaporama .cslide.active { opacity:1; }
        .tpl-diaporama .cslide img { height:480px; }
        .carousel-caption { position:absolute; left:0; right:0; bottom:0; z-index:2; padding:46px 36px 30px;
            background:linear-gradient(to top, rgba(0,0,0,.8), rgba(0,0,0,0)); }
        .carousel-caption h1 { color:#fff; font-size:34px; margin-bottom:8px; text-shadow:0 2px 14px rgba(0,0,0,.5); }
        .carousel-caption .meta { color:rgba(255,255,255,.85); margin-bottom:0; }
        @media (max-width:680px) {
            .cslide img, .tpl-diaporama .cslide img { height:280px; }
            .tpl-diaporama .carousel-viewport { min-height:300px; }
            .carousel-caption h1 { font-size:24px; }
            .gallery-marquee-track a, .tpl-galerie-zoom .gallery-marquee-track a { width:150px; }
            .gallery-marquee-track img, .tpl-galerie-zoom .gallery-marquee-track img { height:115px; }
        }

        /* === MODÈLE : Galerie animée (vignettes plus grandes + zoom marqué) === */
        .tpl-galerie-zoom .wrap { max-width:980px; }
        .tpl-galerie-zoom .gallery-marquee-track a { width:240px; }
        .tpl-galerie-zoom .gallery-marquee-track img { height:180px; transition:transform .35s ease, filter .35s ease; }
        .tpl-galerie-zoom .gallery-marquee-track a:hover img { transform:scale(1.12); filter:saturate(1.18); }
        @keyframes galfade { from { opacity:0; transform:translateY(16px); } to { opacity:1; transform:none; } }

        /* === Galerie de photos : CARROUSEL AUTO (défilement continu) ===
           .gallery-marquee  = fenêtre qui masque le débordement
           .gallery-marquee-track = piste contenant le jeu d'images EN DOUBLE
           (rendu deux fois dans _gallery.php). On l'anime de 0 à -50% : la piste
           se décale d'exactement un jeu puis « reboucle » sans saut visible.
           Espacement par margin-right (y compris le dernier) pour que la couture
           entre les deux jeux soit identique aux écarts internes. */
        .gallery { margin-top:24px; }
        .gallery-title { font-size:16px; color:var(--accent); margin-bottom:12px; }
        .gallery-marquee { overflow:hidden; -webkit-mask-image:linear-gradient(to right, transparent, #000 6%, #000 94%, transparent); mask-image:linear-gradient(to right, transparent, #000 6%, #000 94%, transparent); }
        .gallery-marquee-track { display:flex; width:max-content; animation:gallery-scroll linear infinite; will-change:transform; }
        .gallery-marquee:hover .gallery-marquee-track { animation-play-state:paused; }
        .gallery-marquee-track a { flex:0 0 auto; width:200px; margin-right:12px; display:block; border-radius:12px; overflow:hidden; border:1px solid var(--card-border); }
        .gallery-marquee-track img { width:100%; height:150px; object-fit:cover; display:block; transition:transform .3s ease; }
        .gallery-marquee-track a:hover img { transform:scale(1.07); }
        @keyframes gallery-scroll { from { transform:translateX(0); } to { transform:translateX(-50%); } }
        /* Accessibilité : pas de défilement si l'utilisateur réduit les animations. */
        @media (prefers-reduced-motion: reduce) {
            .gallery-marquee { -webkit-mask-image:none; mask-image:none; overflow-x:auto; }
            .gallery-marquee-track { animation:none; }
        }

        /* Pièces jointes (documents) */
        .attachments { margin-top:24px; }
        .attach-title { font-size:16px; color:var(--accent); margin-bottom:12px; }
        .attach-list { display:flex; flex-direction:column; gap:10px; }
        .attach-wrap { display:flex; flex-direction:column; }
        .attach { display:flex; align-items:center; gap:14px; padding:12px 16px;
            background:var(--card-bg); border:1px solid var(--card-border); border-radius:12px; transition:border-color .15s; }
        .attach:hover { border-color:var(--accent); }
        .attach-ico { font-size:26px; line-height:1; flex:0 0 auto; }
        .attach-info { flex:1; min-width:0; display:flex; flex-direction:column; gap:2px; text-decoration:none; color:inherit; }
        .attach-info:hover .attach-name { color:var(--accent); }
        .attach-name { font-weight:600; font-size:15px; word-break:break-word; }
        .attach-meta { font-size:12px; color:var(--muted); text-transform:uppercase; letter-spacing:.5px; }
        .attach-view { flex:0 0 auto; cursor:pointer; font-family:inherit; font-size:13px; font-weight:600;
            color:var(--accent); background:rgba(127,127,127,.08); border:1px solid var(--card-border);
            border-radius:9px; padding:7px 12px; transition:border-color .15s, background .15s; }
        .attach-view:hover { border-color:var(--accent); background:rgba(127,127,127,.14); }
        .attach-dl { color:var(--accent); font-size:18px; flex:0 0 auto; text-decoration:none; }
        .pdf-box { margin:8px 0 2px; border:1px solid var(--card-border); border-radius:12px; overflow:hidden; background:#525659; }
        .pdf-frame { display:block; width:100%; height:75vh; min-height:420px; border:0; }
        @media (max-width:560px) { .pdf-frame { height:60vh; } }

        /* Avis & notes des membres */
        .reviews { margin-top:28px; }
        .reviews-head { display:flex; align-items:center; gap:14px; flex-wrap:wrap; margin-bottom:14px; }
        .reviews-title { font-size:18px; color:var(--accent); }
        .rating .rstars { color:#f4b400; letter-spacing:1px; }
        .rating em { color:var(--muted); font-style:normal; font-size:13px; }
        .rating.none { color:var(--card-border); }
        .rating.none em { color:var(--muted); }
        .review-form { background:var(--card-bg); border:1px solid var(--card-border); border-radius:14px; padding:18px 20px; margin-bottom:18px; }
        .review-form .rf-label { font-size:14px; font-weight:600; margin-bottom:8px; }
        .stars-input { display:inline-flex; flex-direction:row-reverse; }
        .stars-input input { display:none; }
        .stars-input label { font-size:28px; line-height:1; color:var(--card-border); cursor:pointer; padding:0 2px; transition:color .1s; }
        .stars-input input:checked ~ label, .stars-input label:hover, .stars-input label:hover ~ label { color:#f4b400; }
        .review-form textarea { width:100%; margin-top:10px; padding:11px 13px; border-radius:10px; border:1px solid var(--card-border);
            background:rgba(127,127,127,.08); color:var(--text); font-size:14px; font-family:inherit; resize:vertical; min-height:70px; }
        .review-form textarea:focus { outline:none; border-color:var(--accent); }
        .review-form button { margin-top:12px; padding:11px 22px; border:none; border-radius:10px; cursor:pointer; font-family:inherit;
            font-weight:700; font-size:14px; color:var(--accent-ink); background:var(--accent); }
        .review-form button:hover { filter:brightness(1.05); }
        .review-list { display:flex; flex-direction:column; gap:12px; }
        .review { background:var(--card-bg); border:1px solid var(--card-border); border-radius:12px; padding:14px 16px; }
        .review-top { display:flex; justify-content:space-between; gap:10px; flex-wrap:wrap; align-items:center; margin-bottom:6px; }
        .review-author { font-weight:600; font-size:14px; }
        .review-stars { color:#f4b400; font-size:15px; letter-spacing:1px; }
        .review-text { font-size:14px; color:var(--text); line-height:1.6; white-space:pre-line; }
        .review-date { font-size:12px; color:var(--muted); margin-top:6px; }
        .review-login { color:var(--muted); font-size:14px; }
        .review-login a { color:var(--accent); }
        .review-err { background:rgba(230,57,70,.15); border:1px solid rgba(230,57,70,.4); color:#e0566a;
            padding:10px 14px; border-radius:10px; margin-bottom:14px; font-size:14px; }

        /* Discussion (fil de messages) */
        .discussion { margin-top:28px; }
        .comment-list { display:flex; flex-direction:column; gap:10px; margin-bottom:18px; }
        .comment { background:var(--card-bg); border:1px solid var(--card-border); border-radius:12px; padding:12px 16px; position:relative; }
        .comment-top { display:flex; justify-content:space-between; gap:10px; flex-wrap:wrap; align-items:center; margin-bottom:5px; }
        .comment-author { font-weight:600; font-size:14px; }
        .comment-date { font-size:12px; color:var(--muted); }
        .comment-body { font-size:14px; color:var(--text); line-height:1.6; white-space:pre-line; word-wrap:break-word; }
        .comment.flagged { border-color:rgba(244,180,0,.6); box-shadow:0 0 0 1px rgba(244,180,0,.3); }
        .comment-actions { display:flex; align-items:center; gap:8px; flex-wrap:wrap; margin-top:8px; }
        .comment-del { cursor:pointer; font-family:inherit; font-size:12px; font-weight:600;
            color:#e0566a; background:transparent; border:1px solid rgba(230,57,70,.4); border-radius:8px; padding:4px 10px; }
        .comment-del:hover { background:rgba(230,57,70,.12); }
        .comment-flag { cursor:pointer; font-family:inherit; font-size:12px; font-weight:600; color:var(--muted);
            background:transparent; border:1px solid var(--card-border); border-radius:8px; padding:4px 10px; }
        .comment-flag:hover { border-color:#f4b400; color:#b8860b; }
        .comment-flag.done { color:#b8860b; border:1px solid rgba(244,180,0,.5); background:rgba(244,180,0,.10); }
        .comment-flag-badge { font-size:12px; font-weight:700; color:#b8860b;
            background:rgba(244,180,0,.14); border:1px solid rgba(244,180,0,.5); border-radius:8px; padding:4px 10px; }
        .comment-form { display:flex; flex-direction:column; gap:10px; }
        .comment-form textarea { width:100%; padding:11px 13px; border-radius:10px; border:1px solid var(--card-border);
            background:rgba(127,127,127,.08); color:var(--text); font-size:14px; font-family:inherit; resize:vertical; min-height:64px; }
        .comment-form textarea:focus { outline:none; border-color:var(--accent); }
        .comment-form button { align-self:flex-start; padding:10px 22px; border:none; border-radius:10px; cursor:pointer;
            font-family:inherit; font-weight:700; font-size:14px; color:var(--accent-ink); background:var(--accent); }
        .comment-form button:hover { filter:brightness(1.05); }

        /* Fil d'Ariane (parent) */
        .crumb { margin:-8px 0 16px; font-size:13px; color:var(--muted); }
        .crumb a { color:var(--accent); text-decoration:none; }
        .crumb a:hover { text-decoration:underline; }

        /* Sous-articles */
        .subarticles { margin-top:26px; }
        .sub-head { display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap; margin-bottom:14px; }
        .sub-title { font-size:16px; color:var(--accent); }
        .sub-add { text-decoration:none; font-size:13px; font-weight:600; color:var(--accent-ink); background:var(--accent); padding:8px 14px; border-radius:10px; }
        .sub-add:hover { filter:brightness(1.05); }
        .sub-list { display:flex; flex-direction:column; gap:10px; }
        .sub-item { display:flex; align-items:center; gap:14px; padding:12px 14px; text-decoration:none; color:inherit;
            background:var(--card-bg); border:1px solid var(--card-border); border-radius:12px; transition:transform .12s, border-color .15s; }
        .sub-item:hover { transform:translateX(3px); border-color:var(--accent); }
        .sub-item img { width:54px; height:42px; object-fit:cover; border-radius:8px; flex:0 0 54px; }
        .sub-ico { width:54px; height:42px; flex:0 0 54px; display:flex; align-items:center; justify-content:center;
            background:rgba(127,127,127,.12); border-radius:8px; font-size:20px; }
        .sub-info { flex:1; min-width:0; display:flex; flex-direction:column; gap:2px; }
        .sub-name { font-weight:600; font-size:15px; }
        .sub-name em { color:var(--muted); font-style:normal; font-size:12px; }
        .sub-date { font-size:12px; color:var(--muted); }
        .sub-arrow { color:var(--accent); }
        .sub-empty { color:var(--muted); font-size:14px; }

        /* Liste des membres ayant vu l'article (auteur/admin) */
        .viewers { margin-top:30px; background:var(--card-bg); border:1px solid var(--card-border);
            border-radius:16px; padding:6px 22px; box-shadow:var(--card-shadow); }
        .viewers summary { cursor:pointer; font-size:17px; font-weight:700; padding:14px 0; list-style:none; }
        .viewers summary::-webkit-details-marker { display:none; }
        .viewers summary::before { content:"▸ "; color:var(--accent); }
        .viewers details[open] summary::before { content:"▾ "; }
        .viewer-list { list-style:none; padding:0; margin:6px 0 14px; display:flex; flex-direction:column; gap:10px; }
        .viewer { display:flex; align-items:center; gap:12px; padding:10px 12px; border-radius:12px;
            background:rgba(127,127,127,.07); border:1px solid var(--card-border); }
        .viewer-pic { width:38px; height:38px; border-radius:50%; object-fit:cover; flex:0 0 auto;
            display:flex; align-items:center; justify-content:center; font-size:20px; background:rgba(127,127,127,.15); }
        .viewer-info { display:flex; flex-direction:column; }
        .viewer-name { font-weight:600; font-size:14px; }
        .viewer-badge { font-style:normal; font-size:11px; font-weight:700; color:var(--accent-ink);
            background:var(--accent); padding:1px 7px; border-radius:999px; margin-left:6px; }
        .viewer-date { font-size:12px; color:var(--muted); }
        .viewer-empty { color:var(--muted); font-size:14px; padding:4px 0 14px; }
        .viewer-note { font-size:12px; color:var(--muted); padding:0 0 14px; line-height:1.5; }
    </style>
    <!-- Style global des articles (réglé dans Admin → Articles → Style) -->
    <style><?= ArticleStyle::css() ?></style>
