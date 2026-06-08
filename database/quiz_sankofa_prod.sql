-- =====================================================================
--  Cours « La simple distributivité » (Institut Sankofa) + QCM + images
--  À coller dans phpMyAdmin de bokonzi.com (onglet SQL). Lancer UNE fois.
--  IMPORTANT : déposer aussi les 2 images sur le serveur :
--    uploads/articles/sankofa-distributivite.png   (couverture article)
--    uploads/quizzes/sankofa-qcm.png               (couverture QCM)
-- =====================================================================

-- Sécurité : colonne image du quiz (créée auto par l'app, ici par précaution)
ALTER TABLE `quizzes` ADD COLUMN IF NOT EXISTS `image` VARCHAR(255) DEFAULT NULL AFTER `description`;

-- 1) Article
INSERT INTO `articles` (`title`,`content`,`image`,`template`,`active`,`parent_id`,`author_id`,`author_name`)
VALUES ('Calcul littéral : la simple distributivité', '<p><em>Institut Sankofa — RPN.</em> Bienvenue ! Ce cours s''adresse à <strong>tout le monde</strong> : que tu sois élève, parent, ou simplement curieux, tu vas comprendre une règle de mathématiques toute simple et très utile : la <strong>simple distributivité</strong>. Pas besoin d''être un expert — on avance pas à pas, avec des exemples de la vie courante.</p>

<blockquote>« Formez-vous, armez-vous de sciences jusqu''aux dents et arrachez votre patrimoine culturel. » — Cheikh Anta Diop</blockquote>
<blockquote>« Chaque génération doit, dans une relative opacité, découvrir sa mission, la remplir ou la trahir. » — Frantz Fanon</blockquote>

<h2>1. La règle, en une ligne</h2>
<p>Quand un nombre est <strong>multiplié par une parenthèse</strong> qui contient une addition (ou une soustraction), on peut « <strong>distribuer</strong> » ce nombre sur chaque terme :</p>
<p><strong>a × ( b + c ) = a × b + a × c = ab + ac</strong></p>
<p>Autrement dit : le facteur devant la parenthèse va « visiter » <em>chacun</em> des éléments à l''intérieur, et on les multiplie un par un.</p>

<h2>2. Pourquoi ça marche ? (l''image du marché)</h2>
<p>Imagine que tu achètes <strong>3 paniers</strong> identiques. Dans chaque panier il y a <strong>2 mangues + 1 ananas</strong>.</p>
<ul>
<li>Tu peux compter panier par panier : 3 × (2 + 1) = 3 × 3 = 9 fruits.</li>
<li>Ou bien compter par type : 3 × 2 mangues + 3 × 1 ananas = 6 + 3 = 9 fruits.</li>
</ul>
<p>Le résultat est le même ! C''est exactement ça, la distributivité : <strong>3 × (2 + 1) = 3×2 + 3×1</strong>.</p>

<h2>3. Une petite histoire pour retenir</h2>
<p>Thabo a 14 ans. Il est issu de la tribu <strong>Xhosa</strong> et vit à <strong>Pretoria</strong>, en Afrique du Sud. Son papa lui fait une promesse : <em>« Si tu réussis ton QCM, tu pourras aller passer le week-end chez ton cousin à Johannesburg. »</em> Alors Thabo s''entraîne… et toi aussi, tu vas pouvoir l''aider en répondant au questionnaire à la fin de ce cours.</p>

<h2>4. On s''entraîne ensemble (développer)</h2>
<p><strong>Développer</strong>, c''est enlever la parenthèse en distribuant. Regarde :</p>
<ul>
<li><strong>5 ( x + 4 )</strong> → 5×x + 5×4 → <strong>5x + 20</strong></li>
<li><strong>8 ( 2 − y )</strong> → 8×2 − 8×y → <strong>16 − 8y</strong></li>
<li><strong>−2 ( 3 + x )</strong> → −2×3 − 2×x → <strong>−6 − 2x</strong></li>
</ul>

<h2>5. Le piège à éviter : le signe «  − »</h2>
<p>Quand le facteur devant la parenthèse est <strong>négatif</strong>, il <strong>change le signe de chaque terme</strong> qu''il distribue. C''est l''erreur la plus fréquente — prends ton temps sur ces cas-là.</p>

<h2>6. Et dans l''autre sens : factoriser</h2>
<p><strong>Factoriser</strong>, c''est l''opération inverse : on remet une parenthèse en mettant en facteur ce qui est commun. Par exemple <strong>2x − 10 = 2 ( x − 5 )</strong>, car 2 est commun à 2x et à 10. C''est très utile pour simplifier et pour le QCM ci-dessous.</p>

<h2>À retenir</h2>
<ul>
<li>Distribuer : le facteur multiplie <em>chaque</em> terme de la parenthèse.</li>
<li>Attention au signe quand le facteur est négatif.</li>
<li>Factoriser = mettre en commun (l''inverse de développer).</li>
</ul>
<p>🎯 <strong>Prêt ? Teste-toi avec le questionnaire juste en dessous, et voyage avec Thabo jusqu''à Johannesburg !</strong></p>', 'sankofa-distributivite.png', 'standard', 1, NULL, NULL, 'Institut Sankofa');
SET @art := LAST_INSERT_ID();

-- 2) Questionnaire
INSERT INTO `quizzes` (`title`,`description`,`image`,`active`,`urgent`,`required`,`pass_required`,`author_id`,`author_name`)
VALUES ('QCM : la simple distributivité (Institut Sankofa)', 'Développe, factorise et déjoue le piège du signe moins. Une seule bonne réponse par question (sauf indication).', 'sankofa-qcm.png', 1, 0, 0, 0, NULL, 'Institut Sankofa');
SET @quiz := LAST_INSERT_ID();

-- 3) Questions + options
INSERT INTO `quiz_questions` (`quiz_id`,`body`,`type`,`position`) VALUES (@quiz, 'Développe : 5 ( x + 4 )', 'single', 0);
SET @q := LAST_INSERT_ID();
INSERT INTO `quiz_options` (`question_id`,`label`,`is_correct`,`position`) VALUES
  (@q, '5x + 20', 1, 0),
  (@q, '5x + 4', 0, 1),
  (@q, '9x', 0, 2),
  (@q, 'x + 20', 0, 3);

INSERT INTO `quiz_questions` (`quiz_id`,`body`,`type`,`position`) VALUES (@quiz, 'Développe : 8 ( 2 − y )', 'single', 1);
SET @q := LAST_INSERT_ID();
INSERT INTO `quiz_options` (`question_id`,`label`,`is_correct`,`position`) VALUES
  (@q, '16 − 8y', 1, 0),
  (@q, '16 − y', 0, 1),
  (@q, '10 − 8y', 0, 2),
  (@q, '8y − 16', 0, 3);

INSERT INTO `quiz_questions` (`quiz_id`,`body`,`type`,`position`) VALUES (@quiz, 'Développe : −2 ( 3 + x )', 'single', 2);
SET @q := LAST_INSERT_ID();
INSERT INTO `quiz_options` (`question_id`,`label`,`is_correct`,`position`) VALUES
  (@q, '−6 − 2x', 1, 0),
  (@q, '−6 + 2x', 0, 1),
  (@q, '6 − 2x', 0, 2),
  (@q, '−6 − x', 0, 3);

INSERT INTO `quiz_questions` (`quiz_id`,`body`,`type`,`position`) VALUES (@quiz, 'Quelle est la forme factorisée de 2x − 10 ?', 'single', 3);
SET @q := LAST_INSERT_ID();
INSERT INTO `quiz_options` (`question_id`,`label`,`is_correct`,`position`) VALUES
  (@q, '2 ( x − 5 )', 1, 0),
  (@q, '3 ( x − 10 )', 0, 1),
  (@q, '2 ( x + 5 )', 0, 2);

INSERT INTO `quiz_questions` (`quiz_id`,`body`,`type`,`position`) VALUES (@quiz, 'Quelle est la forme factorisée de 8y + 4 ?', 'single', 4);
SET @q := LAST_INSERT_ID();
INSERT INTO `quiz_options` (`question_id`,`label`,`is_correct`,`position`) VALUES
  (@q, '2 ( 4y + 2 )', 1, 0),
  (@q, '8 ( y − 4 )', 0, 1),
  (@q, '4y ( 2y + 2 )', 0, 2);

INSERT INTO `quiz_questions` (`quiz_id`,`body`,`type`,`position`) VALUES (@quiz, 'Quelle est la forme développée de −2 ( 6 + 2x ) ?', 'single', 5);
SET @q := LAST_INSERT_ID();
INSERT INTO `quiz_options` (`question_id`,`label`,`is_correct`,`position`) VALUES
  (@q, '−12 − 4x', 1, 0),
  (@q, '6 ( x − 2 )', 0, 1),
  (@q, '3 ( 4 − 2x )', 0, 2);

INSERT INTO `quiz_questions` (`quiz_id`,`body`,`type`,`position`) VALUES (@quiz, 'Quelle est la factorisation exacte de 20x + 5 ?', 'single', 6);
SET @q := LAST_INSERT_ID();
INSERT INTO `quiz_options` (`question_id`,`label`,`is_correct`,`position`) VALUES
  (@q, '5 ( 4x + 1 )', 1, 0),
  (@q, '5 ( 4x − 1 )', 0, 1),
  (@q, '4 ( x − 5 )', 0, 2);

INSERT INTO `quiz_questions` (`quiz_id`,`body`,`type`,`position`) VALUES (@quiz, 'Quand le facteur devant la parenthèse est négatif, que se passe-t-il ?', 'single', 7);
SET @q := LAST_INSERT_ID();
INSERT INTO `quiz_options` (`question_id`,`label`,`is_correct`,`position`) VALUES
  (@q, 'Il change le signe de chaque terme distribué', 1, 0),
  (@q, 'Il ne change rien aux signes', 0, 1),
  (@q, 'Il supprime la parenthèse sans calcul', 0, 2);

INSERT INTO `quiz_questions` (`quiz_id`,`body`,`type`,`position`) VALUES (@quiz, 'Quelles écritures sont égales à a ( b + c ) ?', 'multiple', 8);
SET @q := LAST_INSERT_ID();
INSERT INTO `quiz_options` (`question_id`,`label`,`is_correct`,`position`) VALUES
  (@q, 'ab + ac', 1, 0),
  (@q, 'a×b + a×c', 1, 1),
  (@q, 'ab + c', 0, 2),
  (@q, 'abc', 0, 3),
  (@q, 'a + b + c', 0, 4);

-- 4) Association article <-> quiz (questionnaire proposé en fin de lecture)
INSERT INTO `article_quizzes` (`article_id`,`quiz_id`,`position`) VALUES (@art, @quiz, 0);
