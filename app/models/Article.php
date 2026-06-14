<?php
/**
 * MODÈLE Article
 * Représente un article (titre, contenu, image) et gère toutes les requêtes
 * en base de données associées.
 */
class Article
{
    /**
     * Styles d'affichage de la galerie (images multiples), choisis par article.
     * clé => libellé montré dans le formulaire.
     */
    public static function galleryStyles(): array
    {
        return [
            'auto'   => 'Carrousel automatique (défilement continu)',
            'slider' => 'Slider manuel (flèches ‹ › + points)',
            'grid'   => 'Grille / mosaïque',
            'thumbs' => 'Bandeau + miniatures',
        ];
    }

    /** Valide une clé de style de galerie (repli : 'auto'). */
    public static function galleryStyleKey(?string $k): string
    {
        $k = (string) $k;
        return array_key_exists($k, self::galleryStyles()) ? $k : 'auto';
    }

    /** Transforme une chaîne « a, b , c » en liste propre ['a','b','c'] (dédupliquée). */
    public static function tagsToList(?string $raw): array
    {
        $out = [];
        foreach (explode(',', (string) $raw) as $t) {
            $t = trim($t);
            if ($t !== '' && !in_array($t, $out, true)) {
                $out[] = $t;
            }
        }
        return $out;
    }

    /** Normalise une saisie de tags en chaîne stockable (max 8 tags, 30 car. chacun). */
    public static function normalizeTags(?string $raw): string
    {
        $list = array_slice(self::tagsToList($raw), 0, 8);
        $list = array_map(fn ($t) => mb_substr($t, 0, 30), $list);
        return implode(', ', $list);
    }

    /** Liste de tous les tags utilisés par des articles PUBLIÉS (uniques, triés). */
    public static function allPublicTags(): array
    {
        $rows = Database::pdo()
            ->query("SELECT tags FROM articles WHERE active = 1 AND tags IS NOT NULL AND tags <> ''")
            ->fetchAll(PDO::FETCH_COLUMN);
        $all = [];
        foreach ($rows as $r) {
            foreach (self::tagsToList($r) as $t) {
                $key = mb_strtolower($t);
                if (!isset($all[$key])) { $all[$key] = $t; }
            }
        }
        $vals = array_values($all);
        usort($vals, fn ($a, $b) => strcasecmp($a, $b));
        return $vals;
    }

    /**
     * Recherche d'articles PUBLIÉS par texte (titre/contenu/tags) et/ou tag exact.
     * Le masquage par signalements est filtré par l'appelant (isFlagHidden).
     */
    public static function searchPublic(string $q, string $tag): array
    {
        $q   = trim($q);
        $tag = trim($tag);
        $where = ['active = 1'];
        $args  = [];
        if ($q !== '') {
            $where[] = '(title LIKE ? OR content LIKE ? OR tags LIKE ?)';
            $like = '%' . $q . '%';
            $args[] = $like; $args[] = $like; $args[] = $like;
        }
        if ($tag !== '') {
            $where[] = 'tags LIKE ?';
            $args[] = '%' . $tag . '%';
        }
        $sql = 'SELECT * FROM articles WHERE ' . implode(' AND ', $where) . ' ORDER BY created_at DESC LIMIT 100';
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($args);
        $rows = $stmt->fetchAll();
        // Filtrage TAG exact (le LIKE est large : on revérifie l'appartenance réelle).
        if ($tag !== '') {
            $needle = mb_strtolower($tag);
            $rows = array_values(array_filter($rows, function ($a) use ($needle) {
                foreach (self::tagsToList($a['tags'] ?? '') as $t) {
                    if (mb_strtolower($t) === $needle) { return true; }
                }
                return false;
            }));
        }
        return $rows;
    }

    /** Tous les articles, du plus récent au plus ancien. */
    public static function all(): array
    {
        return Database::pdo()
            ->query('SELECT * FROM articles ORDER BY created_at DESC')
            ->fetchAll();
    }

    /**
     * Articles de premier niveau (sans parent).
     * Ordre : position manuelle croissante (petit = en premier), puis du plus
     * récent au plus ancien pour ceux qui partagent la même position (0 = défaut).
     */
    public static function roots(): array
    {
        return Database::pdo()
            ->query('SELECT * FROM articles WHERE parent_id IS NULL ORDER BY position ASC, created_at DESC')
            ->fetchAll();
    }

    /** Sous-articles (enfants directs) d'un article, du plus ancien au plus récent. */
    public static function children(int $parentId): array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM articles WHERE parent_id = ? ORDER BY created_at ASC');
        $stmt->execute([$parentId]);
        return $stmt->fetchAll();
    }

    /** Détache les sous-articles d'un parent (ils redeviennent de premier niveau). */
    public static function orphanChildren(int $parentId): void
    {
        $stmt = Database::pdo()->prepare('UPDATE articles SET parent_id = NULL WHERE parent_id = ?');
        $stmt->execute([$parentId]);
    }

    /** Un article par son id (ou null). */
    public static function find(int $id): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM articles WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    /** Nombre total d'articles. */
    public static function count(): int
    {
        return (int) Database::pdo()->query('SELECT COUNT(*) FROM articles')->fetchColumn();
    }

    /** Nombre d'articles publiés (active = 1). */
    public static function countActive(): int
    {
        return (int) Database::pdo()->query('SELECT COUNT(*) FROM articles WHERE active = 1')->fetchColumn();
    }

    /** Un article du même TITRE appartenant à cet auteur (anti-doublon à l'import). */
    public static function findByTitleAuthor(string $title, int $authorId): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM articles WHERE title = ? AND author_id = ? LIMIT 1');
        $stmt->execute([trim($title), $authorId]);
        return $stmt->fetch() ?: null;
    }

    /** Tous les articles d'un auteur (pour l'export « mon projet »). */
    public static function byAuthor(int $authorId): array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM articles WHERE author_id = ? ORDER BY created_at DESC');
        $stmt->execute([$authorId]);
        return $stmt->fetchAll();
    }

    /** Nombre d'articles d'un auteur donné. */
    public static function countByAuthor(int $authorId): int
    {
        $stmt = Database::pdo()->prepare('SELECT COUNT(*) FROM articles WHERE author_id = ?');
        $stmt->execute([$authorId]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Crée un article. $data = title, content, image, author_id, author_name.
     * Retourne l'id du nouvel article.
     */
    public static function create(array $data): int
    {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO articles (title, content, image, template, active, parent_id, author_id, author_name, access_password, position, gallery_style, tags)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['title'],
            $data['content'],
            $data['image']       ?? null,
            $data['template']    ?? 'standard',
            isset($data['active']) ? (int) $data['active'] : 1,
            !empty($data['parent_id']) ? (int) $data['parent_id'] : null,
            $data['author_id']   ?? null,
            $data['author_name'] ?? '',
            $data['access_password'] ?? null,
            isset($data['position']) ? (int) $data['position'] : 0,
            self::galleryStyleKey($data['gallery_style'] ?? 'auto'),
            self::normalizeTags($data['tags'] ?? '') ?: null,
        ]);
        return (int) Database::pdo()->lastInsertId();
    }

    /**
     * Définit (hash) ou retire (null) le mot de passe d'accès d'un article.
     * Le hachage est fait par l'appelant (password_hash) ; null = accès libre.
     */
    public static function setAccessPassword(int $id, ?string $hash): void
    {
        Database::pdo()->prepare('UPDATE articles SET access_password = ? WHERE id = ?')->execute([$hash, $id]);
    }

    /** L'article est-il protégé par un mot de passe d'accès ? */
    public static function hasPassword(array $article): bool
    {
        return trim((string) ($article['access_password'] ?? '')) !== '';
    }

    /** Vérifie le mot de passe d'accès saisi contre le hachage stocké. */
    public static function checkAccess(array $article, string $plain): bool
    {
        $hash = (string) ($article['access_password'] ?? '');
        return $hash !== '' && password_verify($plain, $hash);
    }

    /**
     * Met à jour un article. Si 'image' est présent dans $data, on la remplace ;
     * sinon on garde l'image existante.
     */
    public static function update(int $id, array $data): void
    {
        $template = $data['template'] ?? 'standard';
        $active   = isset($data['active']) ? (int) $data['active'] : 1;
        $parent   = !empty($data['parent_id']) ? (int) $data['parent_id'] : null;
        $position = isset($data['position']) ? (int) $data['position'] : 0;
        $gallery  = self::galleryStyleKey($data['gallery_style'] ?? 'auto');
        $tags     = self::normalizeTags($data['tags'] ?? '') ?: null;
        if (array_key_exists('image', $data)) {
            $stmt = Database::pdo()->prepare(
                'UPDATE articles SET title = ?, content = ?, template = ?, active = ?, parent_id = ?, position = ?, gallery_style = ?, tags = ?, image = ?, updated_at = NOW() WHERE id = ?'
            );
            $stmt->execute([$data['title'], $data['content'], $template, $active, $parent, $position, $gallery, $tags, $data['image'], $id]);
        } else {
            $stmt = Database::pdo()->prepare(
                'UPDATE articles SET title = ?, content = ?, template = ?, active = ?, parent_id = ?, position = ?, gallery_style = ?, tags = ?, updated_at = NOW() WHERE id = ?'
            );
            $stmt->execute([$data['title'], $data['content'], $template, $active, $parent, $position, $gallery, $tags, $id]);
        }
    }

    /**
     * Applique une même mise en page (template) à TOUS les articles existants.
     * Action ponctuelle « style général » : écrase le modèle individuel de chaque
     * article. Retourne le nombre d'articles modifiés.
     */
    public static function setTemplateForAll(string $template): int
    {
        $stmt = Database::pdo()->prepare('UPDATE articles SET template = ?, updated_at = NOW()');
        $stmt->execute([$template]);
        return $stmt->rowCount();
    }

    /** Définit l'état publié (active) sans toucher au reste de l'article. */
    public static function setActive(int $id, int $active): void
    {
        $stmt = Database::pdo()->prepare('UPDATE articles SET active = ?, updated_at = NOW() WHERE id = ?');
        $stmt->execute([$active ? 1 : 0, $id]);
    }

    /** Réattribue un article à un autre auteur (utilisé après un import). */
    public static function setAuthor(int $id, ?int $authorId, string $authorName): void
    {
        $stmt = Database::pdo()->prepare(
            'UPDATE articles SET author_id = ?, author_name = ?, updated_at = NOW() WHERE id = ?'
        );
        $stmt->execute([$authorId, $authorName, $id]);
    }

    /** Définit le parent d'un article (rattachement après import, sans toucher au contenu). */
    public static function setParent(int $id, ?int $parentId): void
    {
        $stmt = Database::pdo()->prepare('UPDATE articles SET parent_id = ? WHERE id = ?');
        $stmt->execute([$parentId, $id]);
    }

    /** Supprime un article. */
    public static function delete(int $id): void
    {
        self::deleteFlagsForArticle($id);
        self::deleteQuizLinks($id);
        $stmt = Database::pdo()->prepare('DELETE FROM articles WHERE id = ?');
        $stmt->execute([$id]);
    }

    /* ---- Questionnaires associés (proposés à la fin de la lecture) ---------- */

    /** IDs des questionnaires associés à un article (dans l'ordre). */
    public static function quizIds(int $articleId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT quiz_id FROM article_quizzes WHERE article_id = ? ORDER BY position ASC, id ASC'
        );
        $stmt->execute([$articleId]);
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    /**
     * Questionnaires (lignes complètes) associés à un article, dans l'ordre.
     * Par défaut, seuls les questionnaires PUBLIÉS (active=1) sont renvoyés.
     */
    public static function quizzesFor(int $articleId, bool $onlyActive = true): array
    {
        $sql = 'SELECT q.* FROM article_quizzes aq
                JOIN quizzes q ON q.id = aq.quiz_id
                WHERE aq.article_id = ?' . ($onlyActive ? ' AND q.active = 1' : '') . '
                ORDER BY aq.position ASC, aq.id ASC';
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute([$articleId]);
        $rows = $stmt->fetchAll();

        // ANTI-DOUBLON D'AFFICHAGE : on ne montre qu'UN seul quiz par titre, même
        // si plusieurs quiz du même intitulé sont liés à l'article. En cas de
        // doublon, on garde celui qui a une image ; à défaut, le plus récent (id).
        $byTitle = [];
        foreach ($rows as $q) {
            $key = mb_strtolower(trim((string) ($q['title'] ?? '')));
            if (!isset($byTitle[$key])) {
                $byTitle[$key] = $q;
                continue;
            }
            $cur       = $byTitle[$key];
            $qHasImg   = !empty($q['image']);
            $curHasImg = !empty($cur['image']);
            if ($qHasImg !== $curHasImg) {
                if ($qHasImg) { $byTitle[$key] = $q; }            // l'image l'emporte
            } elseif ((int) $q['id'] > (int) $cur['id']) {
                $byTitle[$key] = $q;                               // sinon, le plus récent
            }
        }
        return array_values($byTitle);
    }

    /** Remplace la liste des questionnaires associés à un article. */
    public static function setQuizzes(int $articleId, array $quizIds): void
    {
        $pdo = Database::pdo();
        $pdo->prepare('DELETE FROM article_quizzes WHERE article_id = ?')->execute([$articleId]);

        $pos = 0;
        $seen = [];
        $ins = $pdo->prepare('INSERT IGNORE INTO article_quizzes (article_id, quiz_id, position) VALUES (?, ?, ?)');
        foreach ($quizIds as $qid) {
            $qid = (int) $qid;
            if ($qid <= 0 || isset($seen[$qid])) {
                continue;
            }
            // On n'associe que des questionnaires réellement existants.
            $chk = $pdo->prepare('SELECT 1 FROM quizzes WHERE id = ? LIMIT 1');
            $chk->execute([$qid]);
            if (!$chk->fetch()) {
                continue;
            }
            $seen[$qid] = true;
            $ins->execute([$articleId, $qid, $pos++]);
        }
    }

    /** Supprime les liaisons questionnaires d'un article (à sa suppression). */
    public static function deleteQuizLinks(int $articleId): void
    {
        Database::pdo()->prepare('DELETE FROM article_quizzes WHERE article_id = ?')->execute([$articleId]);
    }

    /* ---- Signalements d'articles (masquage automatique au public) ---------- */

    /** Nombre de signalements distincts à partir duquel un article est masqué. */
    public const FLAG_LIMIT = 3;

    /** Un membre signale un article (un seul signalement par membre). */
    public static function flag(int $articleId, int $userId): void
    {
        Database::pdo()
            ->prepare('INSERT IGNORE INTO article_flags (article_id, user_id) VALUES (?, ?)')
            ->execute([$articleId, $userId]);
    }

    /** Nombre de signalements (membres distincts) d'un article. */
    public static function flagCount(int $articleId): int
    {
        $stmt = Database::pdo()->prepare('SELECT COUNT(*) FROM article_flags WHERE article_id = ?');
        $stmt->execute([$articleId]);
        return (int) $stmt->fetchColumn();
    }

    /** Signalements de plusieurs articles d'un coup : [article_id => nombre]. */
    public static function flagCountsFor(array $ids): array
    {
        $ids = array_values(array_filter(array_map('intval', $ids)));
        if (!$ids) {
            return [];
        }
        $in   = implode(',', array_fill(0, count($ids), '?'));
        $stmt = Database::pdo()->prepare(
            "SELECT article_id, COUNT(*) AS cnt FROM article_flags WHERE article_id IN ($in) GROUP BY article_id"
        );
        $stmt->execute($ids);
        $out = [];
        foreach ($stmt->fetchAll() as $r) {
            $out[(int) $r['article_id']] = (int) $r['cnt'];
        }
        return $out;
    }

    /** Ce membre a-t-il déjà signalé cet article ? */
    public static function userFlagged(int $articleId, int $userId): bool
    {
        if ($userId <= 0) {
            return false;
        }
        $stmt = Database::pdo()->prepare('SELECT 1 FROM article_flags WHERE article_id = ? AND user_id = ? LIMIT 1');
        $stmt->execute([$articleId, $userId]);
        return (bool) $stmt->fetch();
    }

    /** Réinitialise les signalements d'un article (modération admin → rétablit l'article). */
    public static function clearFlags(int $articleId): void
    {
        Database::pdo()->prepare('DELETE FROM article_flags WHERE article_id = ?')->execute([$articleId]);
    }

    /** Supprime les signalements d'un article (à sa suppression). */
    public static function deleteFlagsForArticle(int $id): void
    {
        Database::pdo()->prepare('DELETE FROM article_flags WHERE article_id = ?')->execute([$id]);
    }

    /**
     * L'article est-il masqué au public à cause des signalements ?
     * CONDITIONS (exceptions) : un article PROTÉGÉ ou en ANNONCE n'est jamais
     * masqué automatiquement (l'admin garde le contrôle). $count peut être fourni
     * (listes) pour éviter une requête par article.
     */
    public static function isFlagHidden(array $article, ?int $count = null): bool
    {
        if ((int) ($article['protected'] ?? 0) === 1 || (int) ($article['announcement'] ?? 0) === 1) {
            return false;
        }
        $count = $count ?? self::flagCount((int) $article['id']);
        return $count >= self::FLAG_LIMIT;
    }

    /** Protège (true) ou non (false) un article contre l'effacement global. */
    public static function setProtected(int $id, bool $on): void
    {
        Database::pdo()->prepare('UPDATE articles SET protected = ? WHERE id = ?')->execute([$on ? 1 : 0, $id]);
    }

    /** IDs des articles NON protégés (ceux qu'un « tout effacer » supprimera). */
    public static function unprotectedIds(): array
    {
        return array_map('intval', Database::pdo()
            ->query('SELECT id FROM articles WHERE protected = 0')
            ->fetchAll(PDO::FETCH_COLUMN));
    }

    /** Nombre d'articles protégés. */
    public static function protectedCount(): int
    {
        return (int) Database::pdo()->query('SELECT COUNT(*) FROM articles WHERE protected = 1')->fetchColumn();
    }

    /** Marque (true) ou non (false) un article comme « URGENT » (alerte carré rouge). */
    public static function setUrgent(int $id, bool $on): void
    {
        $pdo  = Database::pdo();
        $stmt = $pdo->prepare('SELECT urgent FROM articles WHERE id = ?');
        $stmt->execute([$id]);
        $was = (int) $stmt->fetchColumn() === 1;

        $pdo->prepare('UPDATE articles SET urgent = ? WHERE id = ?')->execute([$on ? 1 : 0, $id]);

        // Passage normal → urgent : on rouvre l'alerte pour tout le monde.
        if ($on && !$was) {
            Urgent::reset('article', $id);
        }
    }

    /** Marque (true) ou non (false) un article comme « annonce » (mis en avant sur l'accueil). */
    public static function setAnnouncement(int $id, bool $on): void
    {
        Database::pdo()->prepare('UPDATE articles SET announcement = ? WHERE id = ?')->execute([$on ? 1 : 0, $id]);
    }

    /** Annonces actives à mettre en avant sur la page d'accueil (les plus récentes). */
    public static function announcements(int $limit = 5): array
    {
        $limit = max(1, min(20, $limit));
        return Database::pdo()
            ->query("SELECT * FROM articles WHERE announcement = 1 AND active = 1 ORDER BY created_at DESC LIMIT $limit")
            ->fetchAll();
    }
}
