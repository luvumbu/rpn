-- =====================================================================
--  MIGRATION — Questions interactives (types enrichis)
--  À exécuter une seule fois sur la base existante (local + production).
--  Tous les ajouts sont additifs : aucun impact sur les quiz existants
--  (les anciennes questions restent 'single' / 'multiple').
--
--  Nouveaux types pris en charge par quiz_questions.type (varchar 10) :
--    single   : QCM, une seule bonne réponse           (existant)
--    multiple : QCM, plusieurs bonnes réponses          (existant)
--    numeric  : réponse chiffrée à saisir (+ tolérance)  (nouveau)
--    text     : réponse texte courte à saisir            (nouveau)
--    fill     : texte à trous (plusieurs blancs)         (nouveau)
--    order    : remettre des éléments dans l'ordre       (nouveau)
--    match    : associer par paires                      (nouveau)
--    interactive : exercice manipulable intégré (non noté)(nouveau)
-- =====================================================================

-- 0. Élargir la colonne `type` pour accueillir 'interactive' (11 caractères).
ALTER TABLE `quiz_questions` MODIFY `type` VARCHAR(16) NOT NULL DEFAULT 'single';

-- 1. Réponse attendue + tolérance (numeric / text / fill).
--    answer : pour 'numeric' = le nombre ; pour 'text' = variantes acceptées
--             séparées par « | » ; pour 'fill' = réponses des trous séparées par « | »
--             (dans l'ordre des [trous] de l'énoncé).
--    tolerance : marge acceptée pour 'numeric' (ex : 0.1).
ALTER TABLE `quiz_questions`
    ADD COLUMN `answer`    VARCHAR(500) DEFAULT NULL AFTER `explanation`,
    ADD COLUMN `tolerance` FLOAT NOT NULL DEFAULT 0  AFTER `answer`;

-- 2. Cible d'appariement pour le type 'match' : le membre doit relier
--    quiz_options.label (gauche) à quiz_options.pair (droite).
ALTER TABLE `quiz_options`
    ADD COLUMN `pair` VARCHAR(300) DEFAULT NULL AFTER `label`;

-- 3. Réponse libre saisie/ordonnée/associée par le membre (numeric/text/fill/order/match).
--    Pour 'order' : suite d'option_id séparés par des virgules (ordre choisi).
--    Pour 'match' : couples « optionId:cibleChoisie » séparés par des virgules.
--    Pour numeric/text/fill : le texte saisi (les trous séparés par « | »).
ALTER TABLE `quiz_answers`
    ADD COLUMN `answer_text` VARCHAR(500) DEFAULT NULL AFTER `option_id`;
