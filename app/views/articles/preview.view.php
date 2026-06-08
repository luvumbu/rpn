<?php
/**
 * Aperçu en temps réel d'un article — document autonome chargé dans l'<iframe>
 * du formulaire (articles/new & articles/edit). Réutilise EXACTEMENT les mêmes
 * styles et le même markup que la page publique (show.view.php) via les partials
 * partagés, pour un rendu fidèle. La navigation/modération n'est pas reprise.
 *
 * Les vraies sources d'images (couverture choisie mais pas encore envoyée,
 * photos de galerie) sont injectées côté client après chargement : ici on ne
 * dessine que la STRUCTURE (avec placeholders), donc les payloads restent légers.
 */
$tpl    = ArticleTemplate::key($template ?? 'standard');
// Placeholder transparent : la vraie image est injectée par le formulaire parent.
$ph     = 'data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==';
$imgUrl = !empty($hasCover) ? $ph : '';
$images = array_fill(0, max(0, (int) ($galleryCount ?? 0)), ['filename' => '']);

ob_start(); ?>Publié le <?= date('d/m/Y', strtotime($article['created_at'])) ?><?php if (!empty($article['author_name'])): ?> · par <?= htmlspecialchars($article['author_name']) ?><?php endif; ?><?php $metaHtml = trim(ob_get_clean()); ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <?= Theme::css() ?>
    <?php include __DIR__ . '/_article.css.php'; ?>
    <style>
        /* L'aperçu vit dans un iframe étroit : on retire la marge plein écran. */
        body { padding:0; min-height:0; background:transparent; }
        body::before { display:none; }
        .wrap { max-width:none; margin:0; padding:18px; }
    </style>
    <?= math_assets() ?>
</head>
<body class="tpl-<?= $tpl ?>">
    <div class="wrap">
        <?php include __DIR__ . '/_card.php'; ?>
        <?php if (!in_array($tpl, ArticleTemplate::carouselKeys(), true)): ?>
            <?php include __DIR__ . '/_gallery.php'; ?>
        <?php endif; ?>
        <?php
            // Pièces jointes : liste envoyée par le formulaire (nom + type + taille).
            $files = array_map(function ($d) {
                return [
                    'url'  => $d['url'] ?? '#',
                    'name' => $d['name'] ?? 'Document',
                    'ext'  => $d['ext'] ?? '',
                    'size' => isset($d['size']) ? (int) $d['size'] : null,
                ];
            }, is_array($docs ?? null) ? $docs : []);
            include __DIR__ . '/_files.php';
        ?>
    </div>
</body>
</html>
