<?php
/**
 * Carte d'un article (couverture + titre + méta + contenu), selon le modèle.
 * PARTAGÉ entre la page publique (show.view.php) et l'aperçu (preview.view.php).
 * Variables attendues dans la portée : $tpl, $imgUrl, $article, $metaHtml.
 * (et $images pour les modèles « carrousel », qui fusionnent couverture + galerie)
 *
 * Quatre familles de STRUCTURE (le reste des différences est purement CSS) :
 *  - « carrousel » : couverture + galerie fusionnées en diaporama interactif
 *  - « vedette »   : grande image de fond avec le titre par-dessus (magazine, affiche)
 *  - « côté »      : image à côté du texte (cote, portrait, fiche)
 *  - sinon         : image en haut puis le texte (standard, vitrine, journal, etc.)
 */
$famVedette  = ['magazine', 'affiche'];
$famCote     = ['cote', 'portrait', 'fiche'];
$famCarousel = ArticleTemplate::carouselKeys();
?>
        <article class="art">
            <?php if (in_array($tpl, $famCarousel, true)):
                // Diaporama = couverture (en tête) + photos de galerie, dans l'ordre.
                $blank  = 'data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==';
                $slides = [];
                if (!empty($imgUrl)) { $slides[] = $imgUrl; }
                foreach (($images ?? []) as $gi) {
                    $fn = $gi['filename'] ?? '';
                    $slides[] = $fn !== '' ? url('uploads/articles/' . rawurlencode($fn)) : $blank;
                }
                $isFade = ($tpl === 'diaporama');
            ?>
                <?php if ($slides): ?>
                    <div class="carousel" data-effect="<?= $isFade ? 'fade' : 'slide' ?>" data-autoplay="<?= $isFade ? '4500' : '6000' ?>">
                        <div class="carousel-viewport">
                            <div class="carousel-track">
                                <?php foreach ($slides as $i => $src): ?>
                                    <div class="cslide<?= $i === 0 ? ' active' : '' ?>">
                                        <?php /* Pas de loading="lazy" : les diapos sont hors écran
                                           (décalées horizontalement) et ne se chargeraient jamais
                                           avant le clic « suivant » → image vide. On charge tout. */ ?>
                                        <img src="<?= $src ?>" alt="" decoding="async">
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <?php if ($isFade): ?>
                                <div class="carousel-caption">
                                    <h1><?= htmlspecialchars($article['title']) ?></h1>
                                    <p class="meta"><?= $metaHtml ?></p>
                                </div>
                            <?php endif; ?>
                            <?php if (count($slides) > 1): ?>
                                <button type="button" class="carousel-nav prev" aria-label="Image précédente">‹</button>
                                <button type="button" class="carousel-nav next" aria-label="Image suivante">›</button>
                                <div class="carousel-dots">
                                    <?php foreach ($slides as $i => $src): ?>
                                        <button type="button" class="cdot<?= $i === 0 ? ' active' : '' ?>" data-i="<?= $i ?>" aria-label="Image <?= $i + 1 ?>"></button>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
                <div class="content">
                    <?php if (!$isFade): ?>
                        <h1><?= htmlspecialchars($article['title']) ?></h1>
                        <p class="meta"><?= $metaHtml ?></p>
                    <?php endif; ?>
                    <div class="article-body"><?= $article['content'] ?></div>
                </div>

            <?php elseif (in_array($tpl, $famVedette, true) && $imgUrl): ?>
                <header class="mag-hero" style="background-image:url('<?= $imgUrl ?>')">
                    <div class="mag-overlay">
                        <h1><?= htmlspecialchars($article['title']) ?></h1>
                        <p class="meta"><?= $metaHtml ?></p>
                    </div>
                </header>
                <div class="content">
                    <div class="article-body"><?= $article['content'] ?></div>
                </div>

            <?php elseif (in_array($tpl, $famCote, true)): ?>
                <div class="split">
                    <?php if ($imgUrl): ?>
                        <div class="split-img"><img src="<?= $imgUrl ?>" alt=""></div>
                    <?php endif; ?>
                    <div class="content">
                        <h1><?= htmlspecialchars($article['title']) ?></h1>
                        <p class="meta"><?= $metaHtml ?></p>
                        <div class="article-body"><?= $article['content'] ?></div>
                    </div>
                </div>

            <?php else: // standard, minimal, carte, galerie-zoom ?>
                <?php if ($imgUrl && $tpl !== 'minimal'): ?>
                    <img class="hero" src="<?= $imgUrl ?>" alt="">
                <?php endif; ?>
                <div class="content">
                    <h1><?= htmlspecialchars($article['title']) ?></h1>
                    <p class="meta"><?= $metaHtml ?></p>
                    <div class="article-body"><?= $article['content'] ?></div>
                </div>
            <?php endif; ?>
        </article>

        <?php if (in_array($tpl, $famCarousel, true)): ?>
        <script>
        /* Carrousel / diaporama : navigation (flèches + points) et lecture auto.
           S'exécute dans la page réelle ET dans l'iframe d'aperçu (script inclus
           avec le markup). Initialise chaque .carousel présent dans le document. */
        (function () {
            function init(root) {
                var track  = root.querySelector('.carousel-track');
                var slides = root.querySelectorAll('.cslide');
                var dots   = root.querySelectorAll('.cdot');
                if (slides.length < 1) { return; }
                var effect   = root.getAttribute('data-effect') || 'slide';
                var autoplay = parseInt(root.getAttribute('data-autoplay'), 10) || 0;
                var idx = 0, timer = null;

                function show(n) {
                    idx = (n + slides.length) % slides.length;
                    if (effect === 'slide' && track) {
                        track.style.transform = 'translateX(' + (-idx * 100) + '%)';
                    }
                    slides.forEach(function (s, k) { s.classList.toggle('active', k === idx); });
                    dots.forEach(function (d, k) { d.classList.toggle('active', k === idx); });
                }
                function restart() {
                    if (!autoplay || slides.length < 2) { return; }
                    clearInterval(timer);
                    timer = setInterval(function () { show(idx + 1); }, autoplay);
                }

                var prev = root.querySelector('.carousel-nav.prev');
                var next = root.querySelector('.carousel-nav.next');
                if (prev) { prev.addEventListener('click', function () { show(idx - 1); restart(); }); }
                if (next) { next.addEventListener('click', function () { show(idx + 1); restart(); }); }
                dots.forEach(function (d) {
                    d.addEventListener('click', function () { show(parseInt(d.getAttribute('data-i'), 10)); restart(); });
                });

                show(0);
                restart();
            }
            var all = document.querySelectorAll('.carousel');
            for (var i = 0; i < all.length; i++) { init(all[i]); }
        })();
        </script>
        <?php endif; ?>
