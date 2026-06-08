-- =====================================================================
--  QUIZ pour l'article #4 — « Calcul littéral : la simple distributivité »
--  À exécuter dans phpMyAdmin (onglet SQL) sur la base de bokonzi.com.
--  Idempotence : si tu le relances, un 2e quiz sera créé. Ne lance qu'UNE fois.
-- =====================================================================

-- 1) Le questionnaire (publié)
INSERT INTO `quizzes` (`title`, `description`, `active`, `urgent`, `required`, `pass_required`, `author_id`, `author_name`)
VALUES ('QCM : la simple distributivité',
        'Teste-toi sur la regle a(b + c) = ab + ac : developpements et regle des signes.',
        1, 0, 0, 0, NULL, 'RPN');
SET @quiz := LAST_INSERT_ID();

-- 2) Questions + options (is_correct = 1 pour la bonne reponse)

-- Q1
INSERT INTO `quiz_questions` (`quiz_id`, `body`, `type`, `position`)
VALUES (@quiz, 'Que permet la simple distributivite ?', 'single', 0);
SET @q := LAST_INSERT_ID();
INSERT INTO `quiz_options` (`question_id`, `label`, `is_correct`, `position`) VALUES
(@q, 'Multiplier un facteur par une somme (ou une difference)', 1, 0),
(@q, 'Additionner deux fractions', 0, 1),
(@q, 'Resoudre une equation du second degre', 0, 2),
(@q, 'Calculer une racine carree', 0, 3);

-- Q2
INSERT INTO `quiz_questions` (`quiz_id`, `body`, `type`, `position`)
VALUES (@quiz, 'Developpe : 5(x + 4)', 'single', 1);
SET @q := LAST_INSERT_ID();
INSERT INTO `quiz_options` (`question_id`, `label`, `is_correct`, `position`) VALUES
(@q, '5x + 20', 1, 0),
(@q, '5x + 4', 0, 1),
(@q, '9x', 0, 2),
(@q, 'x + 20', 0, 3);

-- Q3
INSERT INTO `quiz_questions` (`quiz_id`, `body`, `type`, `position`)
VALUES (@quiz, 'Developpe : 8(2 - y)', 'single', 2);
SET @q := LAST_INSERT_ID();
INSERT INTO `quiz_options` (`question_id`, `label`, `is_correct`, `position`) VALUES
(@q, '16 - 8y', 1, 0),
(@q, '16 - y', 0, 1),
(@q, '10 - 8y', 0, 2),
(@q, '8y - 16', 0, 3);

-- Q4
INSERT INTO `quiz_questions` (`quiz_id`, `body`, `type`, `position`)
VALUES (@quiz, 'Developpe : -2(3 + x)', 'single', 3);
SET @q := LAST_INSERT_ID();
INSERT INTO `quiz_options` (`question_id`, `label`, `is_correct`, `position`) VALUES
(@q, '-6 - 2x', 1, 0),
(@q, '-6 + 2x', 0, 1),
(@q, '6 - 2x', 0, 2),
(@q, '-6 - x', 0, 3);

-- Q5 (regle des signes)
INSERT INTO `quiz_questions` (`quiz_id`, `body`, `type`, `position`)
VALUES (@quiz, 'Quand le facteur devant la parenthese est NEGATIF, que se passe-t-il ?', 'single', 4);
SET @q := LAST_INSERT_ID();
INSERT INTO `quiz_options` (`question_id`, `label`, `is_correct`, `position`) VALUES
(@q, 'Il change le signe de chaque terme distribue', 1, 0),
(@q, 'Il ne change rien aux signes', 0, 1),
(@q, 'Il supprime la parenthese sans calcul', 0, 2),
(@q, 'Il transforme l''addition en multiplication', 0, 3);

-- Q6 (plusieurs bonnes reponses)
INSERT INTO `quiz_questions` (`quiz_id`, `body`, `type`, `position`)
VALUES (@quiz, 'Quelles ecritures sont egales a a(b + c) ?', 'multiple', 5);
SET @q := LAST_INSERT_ID();
INSERT INTO `quiz_options` (`question_id`, `label`, `is_correct`, `position`) VALUES
(@q, 'ab + ac', 1, 0),
(@q, 'a x b + a x c', 1, 1),
(@q, 'ab + c', 0, 2),
(@q, 'a + b + c', 0, 3),
(@q, 'abc', 0, 4);

-- 3) Association du quiz a l'article #4 (propose a la fin de la lecture)
INSERT INTO `article_quizzes` (`article_id`, `quiz_id`, `position`) VALUES (4, @quiz, 0);
