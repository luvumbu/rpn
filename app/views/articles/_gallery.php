<?php
/**
 * Galerie de photos d'un article — 4 STYLES au choix (champ gallery_style) :
 *   - auto   : carrousel auto-défilant en boucle (marquee) — défaut historique
 *   - slider : une image à la fois, flèches ‹ › + points cliquables
 *   - grid   : grille / mosaïque de vignettes
 *   - thumbs : grande image active + rangée de miniatures cliquables
 *
 * PARTAGÉ entre la page publique (show.view.php) et l'aperçu (preview.view.php).
 * Variables attendues : $images (liste de ['filename' => …]) et $galleryStyle.
 *
 * Toutes les <img> portent la classe .g-img et la grande image « thumbs » porte
 * data-gmain : en aperçu, le formulaire injecte les vraies sources par index
 * (voir form.view.php → inject()). La structure, elle, vient du serveur.
 */
$galleryStyle = Article::galleryStyleKey($galleryStyle ?? 'auto');
if (!empty($images)):
    $n   = count($images);
    $src = static function ($img) { return url('uploads/articles/' . rawurlencode($img['filename'])); };
?>
            <section class="gallery gallery--<?= $galleryStyle ?>">
                <h2 class="gallery-title">📷 Galerie (<?= $n ?>)</h2>

                <?php if ($galleryStyle === 'grid'): ?>
                    <div class="gallery-grid">
                        <?php foreach ($images as $img): ?>
                            <a class="g-cell" href="<?= $src($img) ?>" target="_blank" rel="noopener">
                                <img class="g-img" src="<?= $src($img) ?>" alt="" loading="lazy">
                            </a>
                        <?php endforeach; ?>
                    </div>

                <?php elseif ($galleryStyle === 'slider'): ?>
                    <div class="gallery-slider" data-gslider>
                        <div class="gs-viewport">
                            <div class="gs-track">
                                <?php foreach ($images as $img): ?>
                                    <a class="gs-slide" href="<?= $src($img) ?>" target="_blank" rel="noopener">
                                        <img class="g-img" src="<?= $src($img) ?>" alt="" loading="lazy">
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php if ($n > 1): ?>
                            <button type="button" class="gs-nav prev" aria-label="Image précédente">‹</button>
                            <button type="button" class="gs-nav next" aria-label="Image suivante">›</button>
                            <div class="gs-dots">
                                <?php for ($i = 0; $i < $n; $i++): ?>
                                    <button type="button" class="gs-dot<?= $i === 0 ? ' active' : '' ?>" data-i="<?= $i ?>" aria-label="Aller à l'image <?= $i + 1 ?>"></button>
                                <?php endfor; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                <?php elseif ($galleryStyle === 'thumbs'): ?>
                    <div class="gallery-thumbs" data-gthumbs>
                        <a class="gt-main-link" href="<?= $src($images[0]) ?>" target="_blank" rel="noopener">
                            <img class="gt-main" data-gmain src="<?= $src($images[0]) ?>" alt="">
                        </a>
                        <div class="gt-strip">
                            <?php foreach ($images as $i => $img): ?>
                                <button type="button" class="gt-thumb<?= $i === 0 ? ' active' : '' ?>">
                                    <img class="g-img" src="<?= $src($img) ?>" alt="" loading="lazy">
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>

                <?php else: // 'auto' — carrousel défilant en boucle (le jeu est rendu 2× pour une boucle sans couture) ?>
                    <?php $duration = max(12, $n * 4.5); ?>
                    <div class="gallery-marquee">
                        <div class="gallery-marquee-track" style="animation-duration:<?= $duration ?>s">
                            <?php for ($pass = 0; $pass < 2; $pass++): ?>
                                <?php foreach ($images as $img): ?>
                                    <a href="<?= $src($img) ?>" target="_blank" rel="noopener"
                                       <?= $pass ? 'aria-hidden="true" tabindex="-1"' : '' ?>>
                                        <img class="g-img" src="<?= $src($img) ?>" alt="" loading="lazy">
                                    </a>
                                <?php endforeach; ?>
                            <?php endfor; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </section>

            <script>
            (function () {
                // SLIDER manuel : flèches + points de navigation.
                document.querySelectorAll('.gallery-slider[data-gslider]').forEach(function (sl) {
                    if (sl.__init) { return; } sl.__init = true;
                    var track = sl.querySelector('.gs-track');
                    var slides = sl.querySelectorAll('.gs-slide');
                    var dots = sl.querySelectorAll('.gs-dot');
                    var n = slides.length, i = 0;
                    function go(k) {
                        i = (k + n) % n;
                        if (track) { track.style.transform = 'translateX(' + (-i * 100) + '%)'; }
                        dots.forEach(function (d, j) { d.classList.toggle('active', j === i); });
                    }
                    var prev = sl.querySelector('.gs-nav.prev'), next = sl.querySelector('.gs-nav.next');
                    if (prev) { prev.addEventListener('click', function () { go(i - 1); }); }
                    if (next) { next.addEventListener('click', function () { go(i + 1); }); }
                    dots.forEach(function (d) {
                        d.addEventListener('click', function () { go(parseInt(d.getAttribute('data-i'), 10) || 0); });
                    });
                });
                // BANDEAU + MINIATURES : clic sur une vignette → grande image active.
                document.querySelectorAll('.gallery-thumbs[data-gthumbs]').forEach(function (gt) {
                    if (gt.__init) { return; } gt.__init = true;
                    var main = gt.querySelector('[data-gmain]'), link = gt.querySelector('.gt-main-link');
                    gt.querySelectorAll('.gt-thumb').forEach(function (btn) {
                        btn.addEventListener('click', function () {
                            var im = btn.querySelector('img');
                            if (im && main) { main.src = im.src; if (link) { link.href = im.src; } }
                            gt.querySelectorAll('.gt-thumb').forEach(function (b) { b.classList.remove('active'); });
                            btn.classList.add('active');
                        });
                    });
                });
            })();
            </script>
<?php endif; ?>
