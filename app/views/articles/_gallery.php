<?php
/**
 * Galerie de photos d'un article — CARROUSEL AUTO (défilement continu).
 * PARTAGÉ entre la page publique (show.view.php) et l'aperçu (preview.view.php).
 * Variable attendue dans la portée : $images (liste de ['filename' => ...]).
 *
 * Effet : les vignettes défilent horizontalement en boucle, pause au survol.
 * Pour une boucle « sans couture », le jeu d'images est rendu DEUX FOIS dans la
 * piste (.gallery-marquee-track) ; le CSS l'anime de 0 à -50% (donc d'exactement
 * un jeu) puis recommence. Le 2ᵉ jeu est aria-hidden (décoratif).
 * Vitesse constante : durée proportionnelle au nombre d'images (~4,5 s/image).
 *
 * En aperçu (form.view.php), les <img> portent un placeholder transparent et
 * leur vraie source est injectée côté client par index (modulo => les doublons
 * reçoivent la même image que l'original). Voir form.view.php → inject().
 */
?>
<?php if (!empty($images)): ?>
            <?php $duration = max(12, count($images) * 4.5); // secondes pour parcourir un jeu ?>
            <section class="gallery">
                <h2 class="gallery-title">📷 Galerie (<?= count($images) ?>)</h2>
                <div class="gallery-marquee">
                    <div class="gallery-marquee-track" style="animation-duration:<?= $duration ?>s">
                        <?php for ($pass = 0; $pass < 2; $pass++): ?>
                            <?php foreach ($images as $img): ?>
                                <a href="<?= url('uploads/articles/' . rawurlencode($img['filename'])) ?>"
                                   target="_blank" rel="noopener"
                                   <?= $pass ? 'aria-hidden="true" tabindex="-1"' : '' ?>>
                                    <img src="<?= url('uploads/articles/' . rawurlencode($img['filename'])) ?>" alt="" loading="lazy">
                                </a>
                            <?php endforeach; ?>
                        <?php endfor; ?>
                    </div>
                </div>
            </section>
<?php endif; ?>
