-- =====================================================================
--  CONTENU À PUBLIER EN LIGNE (bokonzi.com) — Institut Sankofa
--  Maths (distributivité) + Lingala (module 1) + Compte-rendu protégé.
--  À COLLER dans phpMyAdmin (onglet SQL) puis « Exécuter ».
--  Tout est PUBLIÉ. Couvertures laissées vides (aucune image à téléverser).
-- =====================================================================

ALTER TABLE `articles` ADD COLUMN IF NOT EXISTS `access_password` VARCHAR(255) DEFAULT NULL;
ALTER TABLE `quizzes`  ADD COLUMN IF NOT EXISTS `image` VARCHAR(255) DEFAULT NULL AFTER `description`;
ALTER TABLE `quizzes`  ADD COLUMN IF NOT EXISTS `max_attempts` INT NOT NULL DEFAULT 0 AFTER `pass_required`;

-- Article : Calcul littéral : la simple distributivité
INSERT INTO `articles` (`title`,`content`,`image`,`template`,`active`,`parent_id`,`author_id`,`author_name`,`access_password`)
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
<p>🎯 <strong>Teste-toi avec le questionnaire ci-dessous !</strong></p>', NULL, 'standard', 1, NULL, NULL, 'ND LVB', NULL);
SET @art_math := LAST_INSERT_ID();

-- Questionnaire : QCM : la simple distributivité (Institut Sankofa)
INSERT INTO `quizzes` (`title`,`description`,`image`,`active`,`max_attempts`,`author_id`,`author_name`)
VALUES ('QCM : la simple distributivité (Institut Sankofa)', 'Développe, factorise et déjoue le piège du signe moins. Une seule bonne réponse par question (sauf indication).', NULL, 1, 0, NULL, 'ND LVB');
SET @qz_math := LAST_INSERT_ID();
INSERT INTO `quiz_questions` (`quiz_id`,`body`,`type`,`position`) VALUES (@qz_math, 'Développe : $5\\,(x + 4)$', 'single', 0);
SET @qq := LAST_INSERT_ID();
INSERT INTO `quiz_options` (`question_id`,`label`,`is_correct`,`position`) VALUES
  (@qq, '$5x + 20$', 1, 0),
  (@qq, '$5x + 4$', 0, 1),
  (@qq, '$9x$', 0, 2),
  (@qq, '$x + 20$', 0, 3);
INSERT INTO `quiz_questions` (`quiz_id`,`body`,`type`,`position`) VALUES (@qz_math, 'Développe : $8\\,(2 - y)$', 'single', 1);
SET @qq := LAST_INSERT_ID();
INSERT INTO `quiz_options` (`question_id`,`label`,`is_correct`,`position`) VALUES
  (@qq, '$16 - 8y$', 1, 0),
  (@qq, '$16 - y$', 0, 1),
  (@qq, '$10 - 8y$', 0, 2),
  (@qq, '$8y - 16$', 0, 3);
INSERT INTO `quiz_questions` (`quiz_id`,`body`,`type`,`position`) VALUES (@qz_math, 'Développe : $-2\\,(3 + x)$', 'single', 2);
SET @qq := LAST_INSERT_ID();
INSERT INTO `quiz_options` (`question_id`,`label`,`is_correct`,`position`) VALUES
  (@qq, '$-6 - 2x$', 1, 0),
  (@qq, '$-6 + 2x$', 0, 1),
  (@qq, '$6 - 2x$', 0, 2),
  (@qq, '$-6 - x$', 0, 3);
INSERT INTO `quiz_questions` (`quiz_id`,`body`,`type`,`position`) VALUES (@qz_math, 'Forme factorisée de $2x - 10$ ?', 'single', 3);
SET @qq := LAST_INSERT_ID();
INSERT INTO `quiz_options` (`question_id`,`label`,`is_correct`,`position`) VALUES
  (@qq, '$2\\,(x - 5)$', 1, 0),
  (@qq, '$3\\,(x - 10)$', 0, 1),
  (@qq, '$2\\,(x + 5)$', 0, 2);
INSERT INTO `quiz_questions` (`quiz_id`,`body`,`type`,`position`) VALUES (@qz_math, 'Forme factorisée de $8y + 4$ ?', 'single', 4);
SET @qq := LAST_INSERT_ID();
INSERT INTO `quiz_options` (`question_id`,`label`,`is_correct`,`position`) VALUES
  (@qq, '$2\\,(4y + 2)$', 1, 0),
  (@qq, '$8\\,(y - 4)$', 0, 1),
  (@qq, '$4y\\,(2y + 2)$', 0, 2);
INSERT INTO `quiz_questions` (`quiz_id`,`body`,`type`,`position`) VALUES (@qz_math, 'Forme développée de $-2\\,(6 + 2x)$ ?', 'single', 5);
SET @qq := LAST_INSERT_ID();
INSERT INTO `quiz_options` (`question_id`,`label`,`is_correct`,`position`) VALUES
  (@qq, '$-12 - 4x$', 1, 0),
  (@qq, '$6\\,(x - 2)$', 0, 1),
  (@qq, '$3\\,(4 - 2x)$', 0, 2);
INSERT INTO `quiz_questions` (`quiz_id`,`body`,`type`,`position`) VALUES (@qz_math, 'Factorisation de $20x + 5$ ?', 'single', 6);
SET @qq := LAST_INSERT_ID();
INSERT INTO `quiz_options` (`question_id`,`label`,`is_correct`,`position`) VALUES
  (@qq, '$5\\,(4x + 1)$', 1, 0),
  (@qq, '$5\\,(4x - 1)$', 0, 1),
  (@qq, '$4\\,(x - 5)$', 0, 2);
INSERT INTO `quiz_questions` (`quiz_id`,`body`,`type`,`position`) VALUES (@qz_math, 'Quand le facteur devant la parenthèse est négatif, que se passe-t-il ?', 'single', 7);
SET @qq := LAST_INSERT_ID();
INSERT INTO `quiz_options` (`question_id`,`label`,`is_correct`,`position`) VALUES
  (@qq, 'Il change le signe de chaque terme distribué', 1, 0),
  (@qq, 'Il ne change rien aux signes', 0, 1),
  (@qq, 'Il supprime la parenthèse sans calcul', 0, 2);
INSERT INTO `quiz_questions` (`quiz_id`,`body`,`type`,`position`) VALUES (@qz_math, 'Quelles écritures sont égales à $a\\,(b + c)$ ?', 'multiple', 8);
SET @qq := LAST_INSERT_ID();
INSERT INTO `quiz_options` (`question_id`,`label`,`is_correct`,`position`) VALUES
  (@qq, '$ab + ac$', 1, 0),
  (@qq, '$a\\times b + a\\times c$', 1, 1),
  (@qq, '$ab + c$', 0, 2),
  (@qq, '$abc$', 0, 3),
  (@qq, '$a + b + c$', 0, 4);

INSERT INTO `article_quizzes` (`article_id`,`quiz_id`,`position`) VALUES (@art_math, @qz_math, 0);

-- Article : Lingala — Se saluer et converser (Module 1)
INSERT INTO `articles` (`title`,`content`,`image`,`template`,`active`,`parent_id`,`author_id`,`author_name`,`access_password`)
VALUES ('Lingala — Se saluer et converser (Module 1)', '<p><em>Institut Sankofa — Filière langue · Module 1.</em> Le <strong>lingala</strong> est une langue bantoue parlée dans les deux Congo (capitales <strong>Kinshasa</strong> et <strong>Brazzaville</strong>) et par une large diaspora. À l''Institut Sankofa, nous souhaitons faire aimer cette langue et la transmettre à la communauté afro-descendante, pour enrichir la connaissance de toutes nos langues.</p>
<blockquote>« Formez-vous, armez-vous de science jusqu''aux dents et arrachez votre patrimoine culturel. » — Cheikh Anta Diop</blockquote>

<h3>🔎 Le savais-tu ?</h3>
<p>Le lingala est l''une des grandes langues véhiculaires d''Afrique centrale (des dizaines de millions de locuteurs). C''est aussi la langue de la célèbre <strong>rumba congolaise</strong>, inscrite au patrimoine culturel immatériel de l''UNESCO. L''apprendre, c''est ouvrir une porte sur toute une culture.</p>

<h3>🗣️ Bien prononcer (avant de commencer)</h3>
<ul>
<li>Les voyelles se prononcent clairement, comme en français : <strong>a, é, i, o, ou</strong>.</li>
<li><strong>« é »</strong> se dit comme dans « éléphant » ; <strong>« ô »</strong> est un o ouvert.</li>
<li><strong>« ng »</strong> est un son nasal doux (comme dans « parking »).</li>
<li>Bonne nouvelle : pour un francophone, presque tous les sons existent déjà. 🙂</li>
</ul>

<h2>1. Dire bonjour</h2>
<p>Une rencontre commence par un « bonjour ». En lingala, la formule change selon <strong>à qui</strong> l''on parle :</p>
<ul>
<li><strong>Mboté</strong> — bonjour (formule générale).</li>
<li><strong>Mboté na yo</strong> — bonjour à <strong>toi</strong> : une seule personne (ou quelqu''un en particulier dans un groupe).</li>
<li><strong>Mboté na bino</strong> — bonjour à <strong>vous</strong> : les personnes déjà présentes quand on arrive.</li>
<li><strong>Mboté na biso</strong> — bonjour à <strong>nous</strong> : un groupe que l''on intègre, ou se saluer entre soi.</li>
</ul>
<p><em>Exemples :</em> en entrant dans une pièce où l''on retrouve des amis, ou pour un joueur qui rejoint son équipe au vestiaire → <strong>Mboté na biso</strong>.</p>

<h2>2. « Comment vas-tu ? »</h2>
<ul>
<li><strong>O zali malamu ?</strong> — Comment vas-tu ? (littéralement : « tu vas bien ? »)</li>
<li><strong>Bo zali malamu ?</strong> — Comment allez-vous ? (à plusieurs)</li>
<li><strong>Ndenge nini ?</strong> — « Comment vas-tu ? » (langage populaire)</li>
</ul>
<p>Pour répondre :</p>
<ul>
<li><strong>Na zali malamu</strong> <em>(populaire : naza malamu)</em> — Je vais bien.</li>
<li><strong>To zali malamu</strong> <em>(populaire : to za malamu)</em> — Nous allons bien.</li>
<li><strong>Na zali malamu te</strong> — Je ne vais pas bien.</li>
<li><strong>To zali malamu te</strong> — Nous n''allons pas bien.</li>
<li><strong>Ezo simba te</strong> <em>(populaire)</em> — « Ça ne va pas » (litt. « ça ne tient pas »).</li>
</ul>
<p>💡 <strong>Astuce :</strong> la négation se forme simplement en ajoutant <strong>« te »</strong> à la fin de la phrase.</p>

<h2>3. Se présenter</h2>
<ul>
<li><strong>Kombo na yo ezali nani ?</strong> — Comment t''appelles-tu ? (litt. « quel est ton nom ? »)</li>
<li><strong>Kombo n''o nani ?</strong> — version populaire (<em>n''o</em> = contraction de <em>na yo</em> ; le verbe <em>ezali</em> est sous-entendu).</li>
<li><strong>Kombo na ngai eza Audrey</strong> <em>(populaire : kombo na nga Audrey)</em> — Je m''appelle Audrey.</li>
</ul>

<h2>4. Dire au revoir</h2>
<ul>
<li><strong>To monani</strong> — au revoir (litt. « nous nous sommes vus »).</li>
<li><strong>To ko monana</strong> — au revoir (litt. « nous nous reverrons »).</li>
<li><strong>To kutani</strong> — au revoir (litt. « nous nous sommes rencontrés »).</li>
<li><strong>To ko kutana</strong> — au revoir (litt. « nous nous rencontrerons »).</li>
<li><strong>Bo tikala malamu</strong> — au revoir (litt. « restez bien »).</li>
<li><strong>Na zo kende</strong> / <strong>Na komi kokende</strong> — je pars.</li>
</ul>
<p>La personne qui reste répond : <strong>Bozo kende malamu</strong> <em>(populaire : Bo kende malamu)</em> ou <strong>Kende malamu</strong> — « pars/partez bien ».</p>

<h2>5. ✨ Politesse &amp; mots utiles <span style="font-size:13px">(pour aller plus loin)</span></h2>
<p>Ces mots n''étaient pas dans le module de départ, mais ils te serviront tous les jours :</p>
<ul>
<li><strong>Ee</strong> (ou <em>Iyo</em>) — oui  ·  <strong>Te</strong> — non</li>
<li><strong>Matondi</strong> — merci  ·  <strong>Matondi mingi</strong> — merci beaucoup</li>
<li><strong>Limbisa ngai</strong> — pardonne-moi / excuse-moi</li>
<li><strong>Malamu</strong> — bien / bon  ·  <strong>Mingi</strong> — beaucoup</li>
</ul>

<h2>6. 🔢 Compter de 1 à 10 <span style="font-size:13px">(ajout)</span></h2>
<ul>
<li>1 — <strong>moko</strong>  ·  2 — <strong>mibale</strong>  ·  3 — <strong>misato</strong>  ·  4 — <strong>minei</strong>  ·  5 — <strong>mitano</strong></li>
<li>6 — <strong>motoba</strong>  ·  7 — <strong>sambo</strong>  ·  8 — <strong>mwambe</strong>  ·  9 — <strong>libwa</strong>  ·  10 — <strong>zomi</strong></li>
</ul>

<h2>7. Les pronoms personnels</h2>
<ul>
<li><strong>Na</strong> <em>(ou Nazo)</em> — je  ·  <strong>O</strong> <em>(ou Ozo)</em> — tu  ·  <strong>A</strong> <em>(ou Azo)</em> — il / elle</li>
<li><strong>To</strong> <em>(ou Tozo)</em> — nous  ·  <strong>Bo</strong> <em>(ou Bozo)</em> — vous  ·  <strong>Ba</strong> <em>(ou Bazo)</em> — ils / elles / eux</li>
</ul>

<h2>8. Les verbes</h2>
<p>À l''infinitif, les verbes se construisent avec <strong>KO + le mot d''action</strong> :</p>
<ul>
<li><strong>KOZALA</strong> — être  ·  <strong>KOSIMBA</strong> — tenir  ·  <strong>KOMONANA</strong> — se voir</li>
<li><strong>KOKUTANA</strong> — se rencontrer  ·  <strong>KOKENDE</strong> — aller / partir  ·  <strong>KOTIKALA</strong> — rester</li>
</ul>
<h3>Conjugaison au présent</h3>
<p><strong>KOZALA (être)</strong> : Na zali · O zali · A zali · To zali · Bo zali · Ba zali</p>
<p><strong>KOSIMBA (tenir)</strong> : Nazo simba · Ozo simba · Azo simba · Tozo simba · Bozo simba · Bazo simba</p>
<p><strong>KOKENDE (aller)</strong> : Nazo kende · Ozo kende · Azo kende · Tozo kende · Bozo kende · Bazo kende</p>
<p><strong>KOTIKALA (rester)</strong> : Nazo tikala · Ozo tikala · Azo tikala · Tozo tikala · Bozo tikala · Bazo tikala</p>

<h2>9. 💬 Petit dialogue <span style="font-size:13px">(mets tout en pratique)</span></h2>
<blockquote>
— <strong>Mboté na yo !</strong> <em>(Bonjour à toi !)</em><br>
— <strong>Mboté ! O zali malamu ?</strong> <em>(Bonjour ! Comment vas-tu ?)</em><br>
— <strong>Ee, na zali malamu. Kombo na yo ezali nani ?</strong> <em>(Oui, je vais bien. Comment t''appelles-tu ?)</em><br>
— <strong>Kombo na ngai eza Audrey.</strong> <em>(Je m''appelle Audrey.)</em><br>
— <strong>Matondi ! To monani !</strong> <em>(Merci ! Au revoir !)</em><br>
— <strong>Kende malamu !</strong> <em>(Pars bien !)</em>
</blockquote>

<h2>📌 À retenir</h2>
<ul>
<li><strong>Mboté</strong> = bonjour ; <em>na yo / na bino / na biso</em> précisent à qui l''on parle.</li>
<li>« Je vais bien » = <strong>Na zali malamu</strong> ; la négation ajoute <strong>te</strong> à la fin.</li>
<li>Les verbes à l''infinitif commencent par <strong>KO-</strong>.</li>
<li>Mots clés du quotidien : <strong>Ee</strong> (oui), <strong>Te</strong> (non), <strong>Matondi</strong> (merci).</li>
</ul>

<h3>🎓 Conseils pour apprendre</h3>
<ul>
<li>Répète à voix haute : la prononciation fait la différence aux oreilles d''un natif.</li>
<li>Utilise une phrase par jour dans une vraie conversation.</li>
<li>Écoute de la musique en lingala : c''est la meilleure immersion !</li>
</ul>
<p>🎯 <strong>Teste tes connaissances avec le QCM ci-dessous !</strong></p>', NULL, 'standard', 1, NULL, NULL, 'Institut Sankofa', NULL);
SET @art_lin := LAST_INSERT_ID();

-- Questionnaire : QCM : Lingala — salutations & conversation (Module 1)
INSERT INTO `quizzes` (`title`,`description`,`image`,`active`,`max_attempts`,`author_id`,`author_name`)
VALUES ('QCM : Lingala — salutations & conversation (Module 1)', 'Vérifie ce que tu as retenu du module 1 de lingala (salutations, conversation, pronoms, verbes).', NULL, 1, 0, NULL, 'Institut Sankofa');
SET @qz_lin := LAST_INSERT_ID();
INSERT INTO `quiz_questions` (`quiz_id`,`body`,`type`,`position`) VALUES (@qz_lin, 'Que signifie « Mboté » ?', 'single', 0);
SET @qq := LAST_INSERT_ID();
INSERT INTO `quiz_options` (`question_id`,`label`,`is_correct`,`position`) VALUES
  (@qq, 'Bonjour (formule générale)', 1, 0),
  (@qq, 'Au revoir', 0, 1),
  (@qq, 'Merci', 0, 2),
  (@qq, 'Oui', 0, 3);
INSERT INTO `quiz_questions` (`quiz_id`,`body`,`type`,`position`) VALUES (@qz_lin, '« Mboté na yo » sert à saluer…', 'single', 1);
SET @qq := LAST_INSERT_ID();
INSERT INTO `quiz_options` (`question_id`,`label`,`is_correct`,`position`) VALUES
  (@qq, 'une seule personne', 1, 0),
  (@qq, 'un groupe que l''on intègre', 0, 1),
  (@qq, 'toutes les personnes présentes', 0, 2),
  (@qq, 'personne', 0, 3);
INSERT INTO `quiz_questions` (`quiz_id`,`body`,`type`,`position`) VALUES (@qz_lin, '« Mboté na bino » signifie…', 'single', 2);
SET @qq := LAST_INSERT_ID();
INSERT INTO `quiz_options` (`question_id`,`label`,`is_correct`,`position`) VALUES
  (@qq, 'bonjour à vous', 1, 0),
  (@qq, 'bonjour à nous', 0, 1),
  (@qq, 'bonjour à toi', 0, 2),
  (@qq, 'au revoir', 0, 3);
INSERT INTO `quiz_questions` (`quiz_id`,`body`,`type`,`position`) VALUES (@qz_lin, '« Mboté na biso » s''emploie pour…', 'single', 3);
SET @qq := LAST_INSERT_ID();
INSERT INTO `quiz_options` (`question_id`,`label`,`is_correct`,`position`) VALUES
  (@qq, 'saluer un groupe que l''on intègre / se saluer entre nous', 1, 0),
  (@qq, 'saluer une seule personne', 0, 1),
  (@qq, 'dire au revoir', 0, 2),
  (@qq, 'se présenter', 0, 3);
INSERT INTO `quiz_questions` (`quiz_id`,`body`,`type`,`position`) VALUES (@qz_lin, '« O zali malamu ? » veut dire…', 'single', 4);
SET @qq := LAST_INSERT_ID();
INSERT INTO `quiz_options` (`question_id`,`label`,`is_correct`,`position`) VALUES
  (@qq, 'Comment vas-tu ?', 1, 0),
  (@qq, 'Comment t''appelles-tu ?', 0, 1),
  (@qq, 'Au revoir', 0, 2),
  (@qq, 'Je vais bien', 0, 3);
INSERT INTO `quiz_questions` (`quiz_id`,`body`,`type`,`position`) VALUES (@qz_lin, '« Bo zali malamu ? » s''adresse à…', 'single', 5);
SET @qq := LAST_INSERT_ID();
INSERT INTO `quiz_options` (`question_id`,`label`,`is_correct`,`position`) VALUES
  (@qq, 'vous (plusieurs personnes)', 1, 0),
  (@qq, 'toi seul', 0, 1),
  (@qq, 'nous', 0, 2),
  (@qq, 'lui', 0, 3);
INSERT INTO `quiz_questions` (`quiz_id`,`body`,`type`,`position`) VALUES (@qz_lin, '« Ndenge nini ? » est…', 'single', 6);
SET @qq := LAST_INSERT_ID();
INSERT INTO `quiz_options` (`question_id`,`label`,`is_correct`,`position`) VALUES
  (@qq, 'une façon populaire de dire « comment vas-tu ? »', 1, 0),
  (@qq, 'un au revoir', 0, 1),
  (@qq, 'un prénom', 0, 2),
  (@qq, 'un remerciement', 0, 3);
INSERT INTO `quiz_questions` (`quiz_id`,`body`,`type`,`position`) VALUES (@qz_lin, 'Comment dit-on « je vais bien » ?', 'single', 7);
SET @qq := LAST_INSERT_ID();
INSERT INTO `quiz_options` (`question_id`,`label`,`is_correct`,`position`) VALUES
  (@qq, 'Na zali malamu', 1, 0),
  (@qq, 'Na zali malamu te', 0, 1),
  (@qq, 'O zali malamu', 0, 2),
  (@qq, 'To zali malamu', 0, 3);
INSERT INTO `quiz_questions` (`quiz_id`,`body`,`type`,`position`) VALUES (@qz_lin, '« Na zali malamu te » signifie…', 'single', 8);
SET @qq := LAST_INSERT_ID();
INSERT INTO `quiz_options` (`question_id`,`label`,`is_correct`,`position`) VALUES
  (@qq, 'je ne vais pas bien', 1, 0),
  (@qq, 'je vais bien', 0, 1),
  (@qq, 'nous allons bien', 0, 2),
  (@qq, 'tu vas bien', 0, 3);
INSERT INTO `quiz_questions` (`quiz_id`,`body`,`type`,`position`) VALUES (@qz_lin, '« To zali malamu » veut dire…', 'single', 9);
SET @qq := LAST_INSERT_ID();
INSERT INTO `quiz_options` (`question_id`,`label`,`is_correct`,`position`) VALUES
  (@qq, 'nous allons bien', 1, 0),
  (@qq, 'je vais bien', 0, 1),
  (@qq, 'vous allez bien', 0, 2),
  (@qq, 'ils vont bien', 0, 3);
INSERT INTO `quiz_questions` (`quiz_id`,`body`,`type`,`position`) VALUES (@qz_lin, 'Que veut dire l''ajout de « te » à la fin d''une phrase ?', 'single', 10);
SET @qq := LAST_INSERT_ID();
INSERT INTO `quiz_options` (`question_id`,`label`,`is_correct`,`position`) VALUES
  (@qq, 'la négation (ne… pas)', 1, 0),
  (@qq, 'le pluriel', 0, 1),
  (@qq, 'le passé', 0, 2),
  (@qq, 'une question', 0, 3);
INSERT INTO `quiz_questions` (`quiz_id`,`body`,`type`,`position`) VALUES (@qz_lin, '« Ezo simba te » (populaire) signifie…', 'single', 11);
SET @qq := LAST_INSERT_ID();
INSERT INTO `quiz_options` (`question_id`,`label`,`is_correct`,`position`) VALUES
  (@qq, 'ça ne va pas', 1, 0),
  (@qq, 'ça va très bien', 0, 1),
  (@qq, 'au revoir', 0, 2),
  (@qq, 'bonjour', 0, 3);
INSERT INTO `quiz_questions` (`quiz_id`,`body`,`type`,`position`) VALUES (@qz_lin, '« Kombo na yo ezali nani ? » demande…', 'single', 12);
SET @qq := LAST_INSERT_ID();
INSERT INTO `quiz_options` (`question_id`,`label`,`is_correct`,`position`) VALUES
  (@qq, 'comment t''appelles-tu ?', 1, 0),
  (@qq, 'comment vas-tu ?', 0, 1),
  (@qq, 'où vas-tu ?', 0, 2),
  (@qq, 'quel âge as-tu ?', 0, 3);
INSERT INTO `quiz_questions` (`quiz_id`,`body`,`type`,`position`) VALUES (@qz_lin, 'Pour dire « je m''appelle Audrey », on dit…', 'single', 13);
SET @qq := LAST_INSERT_ID();
INSERT INTO `quiz_options` (`question_id`,`label`,`is_correct`,`position`) VALUES
  (@qq, 'Kombo na ngai eza Audrey', 1, 0),
  (@qq, 'Kombo na yo eza Audrey', 0, 1),
  (@qq, 'Mboté na yo Audrey', 0, 2),
  (@qq, 'Na zali Audrey te', 0, 3);
INSERT INTO `quiz_questions` (`quiz_id`,`body`,`type`,`position`) VALUES (@qz_lin, '« To monani » signifie…', 'single', 14);
SET @qq := LAST_INSERT_ID();
INSERT INTO `quiz_options` (`question_id`,`label`,`is_correct`,`position`) VALUES
  (@qq, 'au revoir (nous nous sommes vus)', 1, 0),
  (@qq, 'bonjour', 0, 1),
  (@qq, 'je vais bien', 0, 2),
  (@qq, 'comment vas-tu ?', 0, 3);
INSERT INTO `quiz_questions` (`quiz_id`,`body`,`type`,`position`) VALUES (@qz_lin, '« Bo tikala malamu » se dit…', 'single', 15);
SET @qq := LAST_INSERT_ID();
INSERT INTO `quiz_options` (`question_id`,`label`,`is_correct`,`position`) VALUES
  (@qq, 'à ceux qui restent, pour dire au revoir (restez bien)', 1, 0),
  (@qq, 'pour saluer en arrivant', 0, 1),
  (@qq, 'pour se présenter', 0, 2),
  (@qq, 'pour demander un nom', 0, 3);
INSERT INTO `quiz_questions` (`quiz_id`,`body`,`type`,`position`) VALUES (@qz_lin, '« Na zo kende » veut dire…', 'single', 16);
SET @qq := LAST_INSERT_ID();
INSERT INTO `quiz_options` (`question_id`,`label`,`is_correct`,`position`) VALUES
  (@qq, 'je pars', 1, 0),
  (@qq, 'je reste', 0, 1),
  (@qq, 'je vais bien', 0, 2),
  (@qq, 'au revoir à toi', 0, 3);
INSERT INTO `quiz_questions` (`quiz_id`,`body`,`type`,`position`) VALUES (@qz_lin, 'La personne qui reste peut répondre…', 'single', 17);
SET @qq := LAST_INSERT_ID();
INSERT INTO `quiz_options` (`question_id`,`label`,`is_correct`,`position`) VALUES
  (@qq, 'Kende malamu (pars bien)', 1, 0),
  (@qq, 'Mboté', 0, 1),
  (@qq, 'Kombo na yo ?', 0, 2),
  (@qq, 'Na zali malamu', 0, 3);
INSERT INTO `quiz_questions` (`quiz_id`,`body`,`type`,`position`) VALUES (@qz_lin, 'Le pronom « je » se dit…', 'single', 18);
SET @qq := LAST_INSERT_ID();
INSERT INTO `quiz_options` (`question_id`,`label`,`is_correct`,`position`) VALUES
  (@qq, 'Na (ou Nazo)', 1, 0),
  (@qq, 'O (ou Ozo)', 0, 1),
  (@qq, 'A (ou Azo)', 0, 2),
  (@qq, 'To (ou Tozo)', 0, 3);
INSERT INTO `quiz_questions` (`quiz_id`,`body`,`type`,`position`) VALUES (@qz_lin, 'Le pronom « nous » se dit…', 'single', 19);
SET @qq := LAST_INSERT_ID();
INSERT INTO `quiz_options` (`question_id`,`label`,`is_correct`,`position`) VALUES
  (@qq, 'To (ou Tozo)', 1, 0),
  (@qq, 'Bo (ou Bozo)', 0, 1),
  (@qq, 'Ba (ou Bazo)', 0, 2),
  (@qq, 'A (ou Azo)', 0, 3);
INSERT INTO `quiz_questions` (`quiz_id`,`body`,`type`,`position`) VALUES (@qz_lin, '« Ba » (ou Bazo) signifie…', 'single', 20);
SET @qq := LAST_INSERT_ID();
INSERT INTO `quiz_options` (`question_id`,`label`,`is_correct`,`position`) VALUES
  (@qq, 'ils / elles / eux', 1, 0),
  (@qq, 'vous', 0, 1),
  (@qq, 'tu', 0, 2),
  (@qq, 'nous', 0, 3);
INSERT INTO `quiz_questions` (`quiz_id`,`body`,`type`,`position`) VALUES (@qz_lin, 'À l''infinitif, les verbes se construisent avec…', 'single', 21);
SET @qq := LAST_INSERT_ID();
INSERT INTO `quiz_options` (`question_id`,`label`,`is_correct`,`position`) VALUES
  (@qq, 'KO + le mot d''action', 1, 0),
  (@qq, 'NA + le mot d''action', 0, 1),
  (@qq, 'ZO + le mot d''action', 0, 2),
  (@qq, 'MA + le mot d''action', 0, 3);
INSERT INTO `quiz_questions` (`quiz_id`,`body`,`type`,`position`) VALUES (@qz_lin, '« KOKENDE » signifie…', 'single', 22);
SET @qq := LAST_INSERT_ID();
INSERT INTO `quiz_options` (`question_id`,`label`,`is_correct`,`position`) VALUES
  (@qq, 'aller / partir', 1, 0),
  (@qq, 'rester', 0, 1),
  (@qq, 'tenir', 0, 2),
  (@qq, 'se voir', 0, 3);
INSERT INTO `quiz_questions` (`quiz_id`,`body`,`type`,`position`) VALUES (@qz_lin, '« KOTIKALA » signifie…', 'single', 23);
SET @qq := LAST_INSERT_ID();
INSERT INTO `quiz_options` (`question_id`,`label`,`is_correct`,`position`) VALUES
  (@qq, 'rester', 1, 0),
  (@qq, 'partir', 0, 1),
  (@qq, 'être', 0, 2),
  (@qq, 'se rencontrer', 0, 3);
INSERT INTO `quiz_questions` (`quiz_id`,`body`,`type`,`position`) VALUES (@qz_lin, '« Na zali » est la conjugaison (je) de quel verbe ?', 'single', 24);
SET @qq := LAST_INSERT_ID();
INSERT INTO `quiz_options` (`question_id`,`label`,`is_correct`,`position`) VALUES
  (@qq, 'KOZALA (être)', 1, 0),
  (@qq, 'KOSIMBA (tenir)', 0, 1),
  (@qq, 'KOKENDE (aller)', 0, 2),
  (@qq, 'KOTIKALA (rester)', 0, 3);
INSERT INTO `quiz_questions` (`quiz_id`,`body`,`type`,`position`) VALUES (@qz_lin, 'Parmi ces formules, lesquelles veulent dire « au revoir » ?', 'multiple', 25);
SET @qq := LAST_INSERT_ID();
INSERT INTO `quiz_options` (`question_id`,`label`,`is_correct`,`position`) VALUES
  (@qq, 'To monani', 1, 0),
  (@qq, 'To kutani', 1, 1),
  (@qq, 'Bo tikala malamu', 1, 2),
  (@qq, 'Mboté', 0, 3),
  (@qq, 'Kombo na yo', 0, 4);

INSERT INTO `article_quizzes` (`article_id`,`quiz_id`,`position`) VALUES (@art_lin, @qz_lin, 0);

-- Compte-rendu protégé : code d'accès = C271 (modifiable ensuite côté admin)
-- Article : Compte-rendu — Application Institut Sankofa (réunion du 2
INSERT INTO `articles` (`title`,`content`,`image`,`template`,`active`,`parent_id`,`author_id`,`author_name`,`access_password`)
VALUES ('Compte-rendu — Application Institut Sankofa (réunion du 29 mai)', '<p><em>Document interne — Institut Sankofa.</em></p>
<h2>Objectif général</h2>
<p>Créer une application éducative et culturelle afro-descendante permettant la mise en relation entre professeurs et élèves, tout en favorisant la transmission des savoirs, des langues, de l''histoire et des cultures afro-descendantes.</p>

<h2>1. Profils des professeurs</h2>
<p>Chaque professeur dispose d''un profil comprenant : photo, biographie, pays d''origine, langues parlées, matières enseignées, spécialités, disponibilités, présentation personnelle.</p>
<p><strong>Exemples de matières et spécialités :</strong> mathématiques, physique, français, informatique, philosophie, histoire africaine, lingala, lari, wolof, xhosa, créole martiniquais, créole guyanais, et autres langues africaines et afro-descendantes.</p>

<h2>2. Fonctionnalités pour les élèves</h2>
<ul>
<li>Rechercher un professeur</li>
<li>Réserver un cours</li>
<li>Envoyer des messages</li>
<li>Suivre leur progression</li>
<li>Consulter leur historique d''apprentissage</li>
</ul>

<h2>3. Gestion des cours</h2>
<p>Le professeur peut choisir un cours individuel (1 élève) ou en petit groupe (<strong>5 élèves maximum</strong>), afin de conserver une qualité pédagogique élevée et un suivi personnalisé.</p>

<h2>4. Identité afro-descendante</h2>
<p>L''application doit posséder une véritable identité culturelle afro-descendante. <strong>Proposition :</strong> une carte interactive de la diaspora (États-Unis, Canada, Caraïbes, Guyane, Brésil, Afrique, Europe) mettant en valeur les origines des professeurs et des membres.</p>

<h2>5. Fil d''actualité communautaire</h2>
<p>Un espace où les professeurs partagent : réflexions, livres, articles, débats, événements, projets, actualités — pour bâtir une véritable communauté de transmission du savoir.</p>

<h2>6. Progression des élèves</h2>
<p>Un système de niveaux : <strong>1 Héritier</strong>, <strong>2 Transmetteur</strong>, <strong>3 Gardien</strong>, <strong>4 Bâtisseur</strong>, <strong>5 Sage</strong>. Les élèves gagnent des points selon leur assiduité, leur ancienneté, leur progression et leur participation.</p>

<h2>7. Bibliothèque Sankofa</h2>
<p>Une bibliothèque numérique où publier : PDF, articles, travaux de recherche, livres recommandés, conférences, podcasts, archives afro-descendantes, documentaires.</p>

<h2>8. IA Sankofa (proposition)</h2>
<p>Un assistant capable d''orienter les élèves, de recommander des contenus, de répondre aux questions pédagogiques simples et d''aider à découvrir les ressources de la plateforme.</p>

<h2>9. Citations fondatrices (page d''accueil)</h2>
<blockquote>« Formez-vous, armez-vous de science jusqu''aux dents et arrachez votre patrimoine culturel. » — Cheikh Anta Diop</blockquote>
<blockquote>« Chaque génération doit, dans une relative opacité, découvrir sa mission, la remplir ou la trahir. » — Frantz Fanon</blockquote>
<p><strong>Affichage proposé :</strong> bandeau défilant en page d''accueil, rotation automatique, nom et photo de l''auteur, lien vers une courte biographie.</p>
<p><strong>Valeurs :</strong> éducation, transmission, excellence, responsabilité, héritage culturel, élévation intellectuelle.</p>', NULL, 'standard', 1, NULL, NULL, 'ND LVB', '$2y$10$gvDSIAVxNPL2eL/qgzP8quxE4/Ej9gHOOGJPupaQVIDUJSJqvo7I.');
SET @art_cr := LAST_INSERT_ID();

