<?php
/**
 * Liste des pièces jointes (documents) d'un article.
 * PARTAGÉ entre la page publique (show.view.php) et l'aperçu (preview.view.php).
 * Variable attendue : $files = [ ['url','name','ext','size'(int|null)], ... ].
 *
 * Les PDF disposent d'un bouton « Voir » qui déplie une visionneuse intégrée
 * (l'iframe n'est chargée qu'au clic, pour ne pas alourdir la page).
 */
?>
        <?php if (!empty($files)): ?>
            <section class="attachments">
                <h2 class="attach-title">📎 Documents (<?= count($files) ?>)</h2>
                <div class="attach-list">
                    <?php foreach ($files as $f): ?>
                        <?php
                            $ext    = strtolower($f['ext'] ?? '');
                            $url    = $f['url'] ?? '#';
                            $isPdf  = $ext === 'pdf';
                            $canView = $isPdf && $url !== '#';
                        ?>
                        <div class="attach-wrap">
                            <div class="attach">
                                <span class="attach-ico"><?= file_type_icon($ext) ?></span>
                                <a class="attach-info" href="<?= htmlspecialchars($url) ?>" target="_blank" rel="noopener">
                                    <span class="attach-name"><?= htmlspecialchars($f['name'] ?? 'Document') ?></span>
                                    <span class="attach-meta"><?= strtoupper(htmlspecialchars($ext)) ?><?= isset($f['size']) && $f['size'] ? ' · ' . human_filesize((int) $f['size']) : '' ?></span>
                                </a>
                                <?php if ($canView): ?>
                                    <button type="button" class="attach-view" aria-expanded="false">👁 Voir</button>
                                <?php endif; ?>
                                <a class="attach-dl" href="<?= htmlspecialchars($url) ?>" download title="Télécharger">⬇</a>
                            </div>
                            <?php if ($canView): ?>
                                <div class="pdf-box" hidden>
                                    <iframe class="pdf-frame" data-src="<?= htmlspecialchars($url) ?>"
                                            title="<?= htmlspecialchars($f['name'] ?? 'Document PDF') ?>" loading="lazy"></iframe>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
            <script>
            // Bascule de la visionneuse PDF (chargement différé de l'iframe).
            (function () {
                document.querySelectorAll('.attach-view').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        var wrap = btn.closest('.attach-wrap');
                        var box  = wrap ? wrap.querySelector('.pdf-box') : null;
                        if (!box) { return; }
                        var iframe = box.querySelector('iframe');
                        var open = box.hasAttribute('hidden');
                        if (open) {
                            if (iframe && !iframe.src) { iframe.src = iframe.getAttribute('data-src'); }
                            box.removeAttribute('hidden');
                            btn.textContent = '✕ Masquer';
                            btn.setAttribute('aria-expanded', 'true');
                        } else {
                            box.setAttribute('hidden', '');
                            btn.textContent = '👁 Voir';
                            btn.setAttribute('aria-expanded', 'false');
                        }
                    });
                });
            })();
            </script>
        <?php endif; ?>
