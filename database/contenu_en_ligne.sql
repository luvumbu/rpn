-- =====================================================================
--  Cours « La simple distributivité » + QCM (Institut Sankofa)
--  À COLLER dans phpMyAdmin de bokonzi.com (onglet SQL) → Exécuter.
--  Crée l'article (PUBLIÉ) + le questionnaire + l'association. Aucune
--  image requise (couvertures laissées vides pour éviter tout fichier manquant).
-- =====================================================================

ALTER TABLE `quizzes` ADD COLUMN IF NOT EXISTS `image` VARCHAR(255) DEFAULT NULL AFTER `description`;
ALTER TABLE `quizzes` ADD COLUMN IF NOT EXISTS `max_attempts` INT NOT NULL DEFAULT 0 AFTER `pass_required`;

-- 1) Article (publié)
INSERT INTO `articles` (`title`,`content`,`image`,`template`,`active`,`parent_id`,`author_id`,`author_name`)
VALUES ('Calcul littéral : la simple distributivité', '<p><em>Institut Sankofa — RPN.</em> Bienvenue ! Ce cours s''adresse à <strong>tout le monde</strong> : que tu sois élève, parent ou simplement curieux, tu vas comprendre une règle de mathématiques toute simple et très utile : la <strong>simple distributivité</strong>.</p>

<blockquote>« Formez-vous, armez-vous de sciences jusqu''aux dents et arrachez votre patrimoine culturel. » — Cheikh Anta Diop</blockquote>
<blockquote>« Chaque génération doit, dans une relative opacité, découvrir sa mission, la remplir ou la trahir. » — Frantz Fanon</blockquote>

<h2>1. La règle, en une ligne</h2>
<p>Quand un nombre multiplie une parenthèse, on « distribue » ce nombre sur chaque terme :</p>
$$a\\,(b + c) = a\\times b + a\\times c = ab + ac$$
<p>Le facteur devant la parenthèse « visite » chacun des éléments à l''intérieur.</p>

<h2>2. Pourquoi ça marche ? (l''image du marché)</h2>
<p>Tu achètes <strong>3 paniers</strong> identiques contenant chacun <strong>2 mangues + 1 ananas</strong> :</p>
<ul>
<li>panier par panier : $3\\times(2+1) = 3\\times 3 = 9$ fruits ;</li>
<li>par type : $3\\times 2 + 3\\times 1 = 6 + 3 = 9$ fruits.</li>
</ul>
<p>Même résultat ! C''est la distributivité : $3\\,(2+1) = 3\\times 2 + 3\\times 1$.</p>

<h2>3. Une histoire pour retenir</h2>
<p>Thabo a 14 ans, il est Xhosa et vit à <strong>Pretoria</strong>. Son papa lui promet : <em>s''il réussit son QCM, il ira passer le week-end à Johannesburg.</em> Aide-le en répondant au questionnaire à la fin !</p>

<h2>4. On s''entraîne ensemble (développer)</h2>
<ul>
<li>$5\\,(x + 4) = 5\\times x + 5\\times 4 = 5x + 20$</li>
<li>$8\\,(2 - y) = 8\\times 2 - 8\\times y = 16 - 8y$</li>
<li>$-2\\,(3 + x) = -2\\times 3 - 2\\times x = -6 - 2x$</li>
</ul>

<h2>5. Le piège du signe «  $-$ »</h2>
<p>Quand le facteur est <strong>négatif</strong>, il <strong>change le signe de chaque terme</strong> distribué. C''est l''erreur la plus fréquente.</p>

<h2>6. Et dans l''autre sens : factoriser</h2>
<p>Factoriser, c''est l''inverse : on met en facteur ce qui est commun. Exemple : $2x - 10 = 2\\,(x - 5)$.</p>

<h2>À retenir</h2>
<ul>
<li>Distribuer : le facteur multiplie <em>chaque</em> terme : $a(b+c)=ab+ac$.</li>
<li>Attention au signe quand le facteur est négatif.</li>
<li>Factoriser = mettre en commun (l''inverse de développer).</li>
</ul>
<p>🎯 <strong>Teste-toi avec le questionnaire ci-dessous !</strong></p>', NULL, 'standard', 1, NULL, NULL, 'Institut Sankofa');
SET @art := LAST_INSERT_ID();

-- 2) Questionnaire (publié)
INSERT INTO `quizzes` (`title`,`description`,`image`,`active`,`max_attempts`,`author_id`,`author_name`)
VALUES ('QCM : la simple distributivité (Institut Sankofa)', 'Développe, factorise et déjoue le piège du signe moins. Une seule bonne réponse par question (sauf indication).', NULL, 1, 0, NULL, 'Institut Sankofa');
SET @quiz := LAST_INSERT_ID();

-- 3) Questions + réponses
INSERT INTO `quiz_questions` (`quiz_id`,`body`,`type`,`position`) VALUES (@quiz, 'Développe : $5\\,(x + 4)$', 'single', 0);
SET @q := LAST_INSERT_ID();
INSERT INTO `quiz_options` (`question_id`,`label`,`is_correct`,`position`) VALUES
  (@q, '$5x + 20$', 1, 0),
  (@q, '$5x + 4$', 0, 1),
  (@q, '$9x$', 0, 2),
  (@q, '$x + 20$', 0, 3);

INSERT INTO `quiz_questions` (`quiz_id`,`body`,`type`,`position`) VALUES (@quiz, 'Développe : $8\\,(2 - y)$', 'single', 1);
SET @q := LAST_INSERT_ID();
INSERT INTO `quiz_options` (`question_id`,`label`,`is_correct`,`position`) VALUES
  (@q, '$16 - 8y$', 1, 0),
  (@q, '$16 - y$', 0, 1),
  (@q, '$10 - 8y$', 0, 2),
  (@q, '$8y - 16$', 0, 3);

INSERT INTO `quiz_questions` (`quiz_id`,`body`,`type`,`position`) VALUES (@quiz, 'Développe : $-2\\,(3 + x)$', 'single', 2);
SET @q := LAST_INSERT_ID();
INSERT INTO `quiz_options` (`question_id`,`label`,`is_correct`,`position`) VALUES
  (@q, '$-6 - 2x$', 1, 0),
  (@q, '$-6 + 2x$', 0, 1),
  (@q, '$6 - 2x$', 0, 2),
  (@q, '$-6 - x$', 0, 3);

INSERT INTO `quiz_questions` (`quiz_id`,`body`,`type`,`position`) VALUES (@quiz, 'Forme factorisée de $2x - 10$ ?', 'single', 3);
SET @q := LAST_INSERT_ID();
INSERT INTO `quiz_options` (`question_id`,`label`,`is_correct`,`position`) VALUES
  (@q, '$2\\,(x - 5)$', 1, 0),
  (@q, '$3\\,(x - 10)$', 0, 1),
  (@q, '$2\\,(x + 5)$', 0, 2);

INSERT INTO `quiz_questions` (`quiz_id`,`body`,`type`,`position`) VALUES (@quiz, 'Forme factorisée de $8y + 4$ ?', 'single', 4);
SET @q := LAST_INSERT_ID();
INSERT INTO `quiz_options` (`question_id`,`label`,`is_correct`,`position`) VALUES
  (@q, '$2\\,(4y + 2)$', 1, 0),
  (@q, '$8\\,(y - 4)$', 0, 1),
  (@q, '$4y\\,(2y + 2)$', 0, 2);

INSERT INTO `quiz_questions` (`quiz_id`,`body`,`type`,`position`) VALUES (@quiz, 'Forme développée de $-2\\,(6 + 2x)$ ?', 'single', 5);
SET @q := LAST_INSERT_ID();
INSERT INTO `quiz_options` (`question_id`,`label`,`is_correct`,`position`) VALUES
  (@q, '$-12 - 4x$', 1, 0),
  (@q, '$6\\,(x - 2)$', 0, 1),
  (@q, '$3\\,(4 - 2x)$', 0, 2);

INSERT INTO `quiz_questions` (`quiz_id`,`body`,`type`,`position`) VALUES (@quiz, 'Factorisation de $20x + 5$ ?', 'single', 6);
SET @q := LAST_INSERT_ID();
INSERT INTO `quiz_options` (`question_id`,`label`,`is_correct`,`position`) VALUES
  (@q, '$5\\,(4x + 1)$', 1, 0),
  (@q, '$5\\,(4x - 1)$', 0, 1),
  (@q, '$4\\,(x - 5)$', 0, 2);

INSERT INTO `quiz_questions` (`quiz_id`,`body`,`type`,`position`) VALUES (@quiz, 'Quand le facteur devant la parenthèse est négatif, que se passe-t-il ?', 'single', 7);
SET @q := LAST_INSERT_ID();
INSERT INTO `quiz_options` (`question_id`,`label`,`is_correct`,`position`) VALUES
  (@q, 'Il change le signe de chaque terme distribué', 1, 0),
  (@q, 'Il ne change rien aux signes', 0, 1),
  (@q, 'Il supprime la parenthèse sans calcul', 0, 2);

INSERT INTO `quiz_questions` (`quiz_id`,`body`,`type`,`position`) VALUES (@quiz, 'Quelles écritures sont égales à $a\\,(b + c)$ ?', 'multiple', 8);
SET @q := LAST_INSERT_ID();
INSERT INTO `quiz_options` (`question_id`,`label`,`is_correct`,`position`) VALUES
  (@q, '$ab + ac$', 1, 0),
  (@q, '$a\\times b + a\\times c$', 1, 1),
  (@q, '$ab + c$', 0, 2),
  (@q, '$abc$', 0, 3),
  (@q, '$a + b + c$', 0, 4);

-- 4) Association article ↔ questionnaire
INSERT INTO `article_quizzes` (`article_id`,`quiz_id`,`position`) VALUES (@art, @quiz, 0);
