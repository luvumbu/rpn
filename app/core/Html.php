<?php
/**
 * CLASSE Html
 * Nettoie le HTML envoyé par l'éditeur d'articles (sécurité anti-XSS).
 * Seules quelques balises de mise en forme sont autorisées ; tout le reste
 * (scripts, styles, attributs « onclick », liens javascript:…) est supprimé.
 *
 * Indispensable : les articles peuvent être rédigés par n'importe quel membre,
 * on ne fait donc JAMAIS confiance au HTML reçu.
 */
class Html
{
    /**
     * Balises autorisées. Tous les attributs sont retirés (sauf href sur <a>),
     * donc div/span/p sans attribut sont sûrs et préservent la structure que
     * l'éditeur contenteditable génère pour les sauts de ligne.
     */
    private const ALLOWED = [
        'p', 'div', 'span', 'br', 'b', 'strong', 'i', 'em', 'u',
        'h2', 'h3', 'ul', 'ol', 'li', 'a', 'blockquote',
    ];

    /** Balises supprimées AVEC leur contenu (et non « déballées »). */
    private const DROP = [
        'script', 'style', 'iframe', 'svg', 'math', 'object',
        'embed', 'noscript', 'template', 'link', 'meta',
    ];

    /** Nettoie un fragment HTML et renvoie une version sûre. */
    public static function clean(string $html): string
    {
        $html = trim($html);
        if ($html === '') {
            return '';
        }

        $doc = new DOMDocument();
        libxml_use_internal_errors(true);
        // L'en-tête XML force l'UTF-8 ; NOIMPLIED/NODEFDTD évitent <html><body>.
        $doc->loadHTML(
            '<?xml encoding="UTF-8"><div>' . $html . '</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();

        // Récupère le <div> conteneur (premier élément du document).
        $root = null;
        foreach ($doc->childNodes as $n) {
            if ($n instanceof DOMElement) {
                $root = $n;
                break;
            }
        }
        if ($root === null) {
            return '';
        }

        self::cleanNode($root);

        $out = '';
        foreach ($root->childNodes as $child) {
            $out .= $doc->saveHTML($child);
        }
        return trim($out);
    }

    /** Nettoie récursivement les enfants d'un nœud. */
    private static function cleanNode(DOMNode $node): void
    {
        // Copie de la liste : on modifie l'arbre pendant le parcours.
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child instanceof DOMElement) {
                $tag = strtolower($child->tagName);

                if (in_array($tag, self::DROP, true)) {
                    $child->parentNode->removeChild($child); // balise + contenu supprimés
                    continue;
                }

                self::cleanNode($child); // d'abord les enfants

                if (!in_array($tag, self::ALLOWED, true)) {
                    self::unwrap($child);          // balise interdite → on garde le texte
                } else {
                    self::stripAttributes($child, $tag);
                }
            } elseif ($child instanceof DOMComment) {
                $child->parentNode->removeChild($child); // pas de commentaires
            }
            // Les nœuds texte (DOMText) sont conservés tels quels.
        }
    }

    /** Retire tous les attributs sauf un href sûr sur <a>. */
    private static function stripAttributes(DOMElement $el, string $tag): void
    {
        foreach (iterator_to_array($el->attributes) as $attr) {
            $name = strtolower($attr->name);

            if ($tag === 'a' && $name === 'href') {
                $href = trim($attr->value);
                // Uniquement http(s), mailto: ou lien interne — jamais javascript:
                if (!preg_match('#^(https?:|mailto:|/)#i', $href)) {
                    $el->removeAttribute($attr->name);
                }
                continue;
            }

            if ($name === 'style') {
                $clean = self::cleanStyle($attr->value);
                if ($clean !== '') {
                    $el->setAttribute('style', $clean);
                } else {
                    $el->removeAttribute($attr->name);
                }
                continue;
            }

            $el->removeAttribute($attr->name);
        }

        if ($tag === 'a' && $el->hasAttribute('href')) {
            // Ouverture sûre des liens externes
            $el->setAttribute('rel', 'noopener nofollow');
            $el->setAttribute('target', '_blank');
        }
    }

    /**
     * Ne garde que des styles inline SÛRS : alignement, taille, police.
     * Tout le reste (position, url(), expression, javascript:…) est jeté.
     */
    private static function cleanStyle(string $style): string
    {
        $out = [];
        foreach (explode(';', $style) as $decl) {
            if (strpos($decl, ':') === false) {
                continue;
            }
            [$prop, $val] = explode(':', $decl, 2);
            $prop = strtolower(trim($prop));
            $val  = trim($val);
            if ($val === '' || preg_match('/url\s*\(|expression|javascript:|\\\\/i', $val)) {
                continue;
            }
            switch ($prop) {
                case 'text-align':
                    if (in_array(strtolower($val), ['left', 'right', 'center', 'justify'], true)) {
                        $out[] = 'text-align:' . strtolower($val);
                    }
                    break;
                case 'font-size':
                    if (preg_match('/^\d{1,3}(px|%|em|rem)$/i', $val)) {
                        $out[] = 'font-size:' . $val;
                    }
                    break;
                case 'font-family':
                    if (preg_match('/^[\w\s,\'"-]+$/u', $val)) {
                        $out[] = 'font-family:' . $val;
                    }
                    break;
            }
        }
        return implode(';', $out);
    }

    /** « Déballe » un élément : remplace la balise par son contenu. */
    private static function unwrap(DOMElement $el): void
    {
        $parent = $el->parentNode;
        while ($el->firstChild) {
            $parent->insertBefore($el->firstChild, $el);
        }
        $parent->removeChild($el);
    }
}
