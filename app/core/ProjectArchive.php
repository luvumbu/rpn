<?php
/**
 * CLASSE ProjectArchive
 * Export / import d'un « projet » sous forme d'archive .zip :
 *   - articles (texte, couverture, galerie, pièces jointes) ;
 *   - questionnaires (questions, options, image) ;
 *   - associations article ↔ questionnaire.
 *
 * Réutilisée par l'admin (TOUT le site) et par chaque membre (SON propre projet).
 * L'import recrée les contenus en BROUILLON, attribués à l'utilisateur fourni.
 */
class ProjectArchive
{
    private const IMG_EXT  = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    private const FILE_EXT = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv', 'zip', 'odt', 'ods', 'odp'];

    /**
     * Construit l'archive à partir des articles + questionnaires fournis, l'envoie
     * au navigateur (téléchargement) puis arrête le script. Les associations ne
     * sont incluses que si l'article ET le questionnaire font partie de l'export.
     */
    public static function export(array $articles, array $quizzes, string $baseName): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'rpn_export_');
        $zip = new ZipArchive();
        if ($tmp === false || $zip->open($tmp, ZipArchive::OVERWRITE) !== true) {
            exit("Impossible de créer l'archive d'export.");
        }

        $imgDir  = APP_ROOT . '/uploads/articles/';
        $fileDir = APP_ROOT . '/uploads/articles/files/';
        $quizDir = APP_ROOT . '/uploads/quizzes/';
        $added   = [];

        // --- Articles ---------------------------------------------------------
        $payload = [];
        foreach ($articles as $a) {
            $aid = (int) $a['id'];

            if (!empty($a['image'])) {
                $fn = basename($a['image']);
                if (!isset($added['media/' . $fn]) && is_file($imgDir . $fn)) {
                    $zip->addFile($imgDir . $fn, 'media/' . $fn);
                    $added['media/' . $fn] = true;
                }
            }
            $galleryNames = [];
            foreach (ArticleImage::forArticle($aid) as $g) {
                $fn = basename($g['filename']);
                $galleryNames[] = $fn;
                if (!isset($added['media/' . $fn]) && is_file($imgDir . $fn)) {
                    $zip->addFile($imgDir . $fn, 'media/' . $fn);
                    $added['media/' . $fn] = true;
                }
            }
            $fileMeta = [];
            foreach (ArticleFile::forArticle($aid) as $f) {
                $fn = basename($f['filename']);
                $fileMeta[] = ['filename' => $fn, 'original' => $f['original'] ?? '', 'mime' => $f['mime'] ?? '', 'size' => (int) ($f['size'] ?? 0)];
                if (!isset($added['media/files/' . $fn]) && is_file($fileDir . $fn)) {
                    $zip->addFile($fileDir . $fn, 'media/files/' . $fn);
                    $added['media/files/' . $fn] = true;
                }
            }
            $payload[] = [
                'ref'         => $aid,
                'title'       => $a['title'],
                'content'     => $a['content'],
                'image'       => !empty($a['image']) ? basename($a['image']) : null,
                'template'    => $a['template'] ?? 'standard',
                'active'      => (int) $a['active'],
                'parent_ref'  => !empty($a['parent_id']) ? (int) $a['parent_id'] : null,
                'author_name' => $a['author_name'] ?? '',
                'created_at'  => $a['created_at'] ?? null,
                'gallery'     => $galleryNames,
                'files'       => $fileMeta,
            ];
        }

        // --- Questionnaires ---------------------------------------------------
        $quizPayload = [];
        $quizIdSet   = [];
        foreach ($quizzes as $z) {
            $zid = (int) $z['id'];
            $quizIdSet[$zid] = true;
            if (!empty($z['image'])) {
                $fn = basename($z['image']);
                if (!isset($added['media/quizzes/' . $fn]) && is_file($quizDir . $fn)) {
                    $zip->addFile($quizDir . $fn, 'media/quizzes/' . $fn);
                    $added['media/quizzes/' . $fn] = true;
                }
            }
            $qs = [];
            foreach (Quiz::questions($zid) as $q) {
                $opts = [];
                foreach ($q['options'] as $o) {
                    $opts[] = ['label' => $o['label'], 'is_correct' => (int) $o['is_correct'], 'position' => (int) $o['position']];
                }
                $qs[] = ['body' => $q['body'], 'type' => $q['type'], 'position' => (int) $q['position'], 'options' => $opts];
            }
            $quizPayload[] = [
                'ref'          => $zid,
                'title'        => $z['title'],
                'description'  => $z['description'] ?? '',
                'image'        => !empty($z['image']) ? basename($z['image']) : null,
                'active'       => (int) $z['active'],
                'max_attempts' => (int) ($z['max_attempts'] ?? 0),
                'author_name'  => $z['author_name'] ?? '',
                'created_at'   => $z['created_at'] ?? null,
                'questions'    => $qs,
            ];
        }

        // --- Associations (seulement entre éléments tous deux exportés) -------
        $linkPayload = [];
        foreach ($articles as $a) {
            foreach (Article::quizIds((int) $a['id']) as $qref) {
                if (isset($quizIdSet[(int) $qref])) {
                    $linkPayload[] = ['article_ref' => (int) $a['id'], 'quiz_ref' => (int) $qref];
                }
            }
        }

        $manifest = [
            'format'          => 'rpm-articles',
            'version'         => 2,
            'exported_at'     => date('Y-m-d H:i:s'),
            'count'           => count($payload),
            'articles'        => $payload,
            'quizzes'         => $quizPayload,
            'article_quizzes' => $linkPayload,
        ];
        $zip->addFromString('articles.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $zip->close();

        $filename = $baseName . '-' . date('Y-m-d-His') . '.zip';
        if (!headers_sent()) {
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . filesize($tmp));
            header('Cache-Control: no-store');
        }
        readfile($tmp);
        @unlink($tmp);
        exit;
    }

    /**
     * Recrée le contenu d'une archive .zip, attribué à l'utilisateur ($authorId).
     * Tout arrive en BROUILLON. Retourne ['articles'=>n,'quizzes'=>n] ou
     * ['error'=>message].
     */
    public static function import(string $tmpZipPath, int $authorId, string $authorName): array
    {
        $zip = new ZipArchive();
        if ($zip->open($tmpZipPath) !== true) {
            return ['error' => 'Archive illisible (fichier .zip attendu).'];
        }
        $json = $zip->getFromName('articles.json');
        $data = $json !== false ? json_decode($json, true) : null;
        if (!is_array($data) || ($data['format'] ?? '') !== 'rpm-articles' || !is_array($data['articles'] ?? null)) {
            $zip->close();
            return ['error' => 'Archive non reconnue (manifeste articles.json manquant ou invalide).'];
        }

        $imgDir  = APP_ROOT . '/uploads/articles/';
        $fileDir = APP_ROOT . '/uploads/articles/files/';
        $quizDir = APP_ROOT . '/uploads/quizzes/';
        @mkdir($imgDir, 0775, true);
        @mkdir($fileDir, 0775, true);
        @mkdir($quizDir, 0775, true);

        $refToId  = [];
        $parentOf = [];
        $imported = 0;

        $skipped = 0;
        foreach ($data['articles'] as $entry) {
            if (!is_array($entry) || empty($entry['title'])) {
                continue;
            }
            // Anti-doublon : si l'utilisateur a déjà un article du même titre, on l'ignore
            // (et on fait pointer sa référence vers l'existant pour les associations).
            $dup = Article::findByTitleAuthor((string) $entry['title'], $authorId);
            if ($dup) {
                if (isset($entry['ref'])) {
                    $refToId[(int) $entry['ref']] = (int) $dup['id'];
                }
                $skipped++;
                continue;
            }
            $newImage = self::copyFromZip($zip, 'media/' . basename((string) ($entry['image'] ?? '')), $imgDir, self::IMG_EXT, !empty($entry['image']));
            $newId = Article::create([
                'title'       => (string) $entry['title'],
                'content'     => (string) ($entry['content'] ?? ''),
                'image'       => $newImage,
                'template'    => (string) ($entry['template'] ?? 'standard'),
                'active'      => 0,
                'parent_id'   => null,
                'author_id'   => $authorId,
                'author_name' => $authorName,
            ]);
            if (isset($entry['ref'])) {
                $refToId[(int) $entry['ref']] = $newId;
            }
            if (!empty($entry['parent_ref'])) {
                $parentOf[$newId] = (int) $entry['parent_ref'];
            }
            foreach ((array) ($entry['gallery'] ?? []) as $g) {
                $copied = self::copyFromZip($zip, 'media/' . basename((string) $g), $imgDir, self::IMG_EXT, true);
                if ($copied !== null) {
                    ArticleImage::add($newId, $copied);
                }
            }
            foreach ((array) ($entry['files'] ?? []) as $file) {
                if (!is_array($file) || empty($file['filename'])) {
                    continue;
                }
                $copied = self::copyFromZip($zip, 'media/files/' . basename((string) $file['filename']), $fileDir, self::FILE_EXT, true);
                if ($copied !== null) {
                    ArticleFile::add($newId, [
                        'filename' => $copied,
                        'original' => (string) ($file['original'] ?? $file['filename']),
                        'mime'     => (string) ($file['mime'] ?? ''),
                        'size'     => (int) ($file['size'] ?? 0),
                    ]);
                }
            }
            $imported++;
        }

        // Questionnaires
        $quizRefToId  = [];
        $importedQuiz = 0;
        foreach ((array) ($data['quizzes'] ?? []) as $z) {
            if (!is_array($z) || empty($z['title'])) {
                continue;
            }
            // Anti-doublon : questionnaire du même titre déjà présent → on l'ignore.
            $dupq = Quiz::findByTitleAuthor((string) $z['title'], $authorId);
            if ($dupq) {
                if (isset($z['ref'])) {
                    $quizRefToId[(int) $z['ref']] = (int) $dupq['id'];
                }
                $skipped++;
                continue;
            }
            $newImg = self::copyFromZip($zip, 'media/quizzes/' . basename((string) ($z['image'] ?? '')), $quizDir, self::IMG_EXT, !empty($z['image']));
            $nzid = Quiz::create([
                'title'        => (string) $z['title'],
                'description'  => (string) ($z['description'] ?? ''),
                'image'        => $newImg,
                'active'       => 0,
                'max_attempts' => (int) ($z['max_attempts'] ?? 0),
                'author_id'    => $authorId,
                'author_name'  => $authorName,
            ]);
            if (isset($z['ref'])) {
                $quizRefToId[(int) $z['ref']] = $nzid;
            }
            $pos = 0;
            foreach ((array) ($z['questions'] ?? []) as $q) {
                if (!is_array($q) || trim((string) ($q['body'] ?? '')) === '') {
                    continue;
                }
                $qqid = Quiz::addQuestion($nzid, (string) $q['body'], (string) ($q['type'] ?? 'single'), $pos++);
                $opos = 0;
                foreach ((array) ($q['options'] ?? []) as $o) {
                    if (!is_array($o) || trim((string) ($o['label'] ?? '')) === '') {
                        continue;
                    }
                    Quiz::addOption($qqid, (string) $o['label'], (int) ($o['is_correct'] ?? 0) === 1, $opos++);
                }
            }
            $importedQuiz++;
        }

        $zip->close();

        // Hiérarchie parent → enfant
        foreach ($parentOf as $childId => $parentRef) {
            if (isset($refToId[$parentRef])) {
                Article::setParent($childId, $refToId[$parentRef]);
            }
        }

        // Associations article ↔ questionnaire
        $links = [];
        foreach ((array) ($data['article_quizzes'] ?? []) as $lk) {
            if (!is_array($lk)) {
                continue;
            }
            $aId = $refToId[(int) ($lk['article_ref'] ?? 0)] ?? null;
            $qId = $quizRefToId[(int) ($lk['quiz_ref'] ?? 0)] ?? null;
            if ($aId && $qId) {
                $links[$aId][] = $qId;
            }
        }
        foreach ($links as $aId => $qIds) {
            Article::setQuizzes($aId, array_values(array_unique($qIds)));
        }

        return ['articles' => $imported, 'quizzes' => $importedQuiz, 'skipped' => $skipped];
    }

    /** Copie un fichier de l'archive vers $destDir (nom unique, extension validée). */
    private static function copyFromZip(ZipArchive $zip, string $zipPath, string $destDir, array $allowedExt, bool $expected): ?string
    {
        if (!$expected || $zipPath === '' || basename($zipPath) === '') {
            return null;
        }
        $content = $zip->getFromName($zipPath);
        if ($content === false) {
            return null;
        }
        $ext = strtolower(pathinfo($zipPath, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExt, true)) {
            return null;
        }
        $name = bin2hex(random_bytes(8)) . '_' . time() . '.' . $ext;
        if (@file_put_contents($destDir . $name, $content) === false) {
            return null;
        }
        return $name;
    }
}
