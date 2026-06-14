<?php /** Résultat de paiement. Variables : $ok (retour réussi/annulé), $paid. */ ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(Settings::get('main_title', 'RPN')) ?> — Paiement</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <?= Theme::css() ?>
    <style>
        * { box-sizing:border-box; margin:0; padding:0; }
        body { font-family:'Poppins',sans-serif; min-height:100vh; display:flex; align-items:center; justify-content:center;
            padding:40px 20px; color:var(--text);
            background:radial-gradient(circle at 15% 12%, var(--glow1), transparent 42%), var(--bg-base); }
        body::before { content:""; position:fixed; top:0; left:0; right:0; height:6px; background:var(--bar); }
        .card { width:100%; max-width:440px; text-align:center; background:var(--card-bg); border:1px solid var(--card-border);
            border-radius:22px; padding:42px 30px; box-shadow:var(--card-shadow); }
        .ico { font-size:56px; line-height:1; margin-bottom:12px; }
        h1 { font-size:23px; margin-bottom:8px; }
        p { color:var(--muted); font-size:14px; line-height:1.55; margin-bottom:22px; }
        .btn { display:inline-block; text-decoration:none; font-weight:700; font-size:15px; border-radius:12px;
            padding:13px 26px; background:var(--accent); color:var(--accent-ink); }
        .btn2 { display:inline-block; text-decoration:none; font-weight:700; font-size:14px; border-radius:12px;
            padding:12px 22px; border:1px solid var(--card-border); color:var(--text); margin-left:8px; }
    </style>
</head>
<body>
    <div class="card">
        <?php if (!empty($ok)): ?>
            <div class="ico">🎉</div>
            <h1>Merci !</h1>
            <p><?= !empty($paid)
                ? 'Ton paiement a bien été confirmé. Un reçu t\'a été envoyé par Stripe.'
                : 'Ton paiement est en cours de confirmation. Il apparaîtra dans « Mes paiements » dès qu\'il sera validé.' ?></p>
        <?php else: ?>
            <div class="ico">↩️</div>
            <h1>Paiement annulé</h1>
            <p>Aucun montant n'a été débité. Tu peux réessayer quand tu veux.</p>
        <?php endif; ?>
        <a class="btn" href="<?= url('paiement') ?>">Retour au paiement</a>
        <a class="btn2" href="<?= url('dashboard') ?>">Tableau de bord</a>
    </div>
</body>
</html>
