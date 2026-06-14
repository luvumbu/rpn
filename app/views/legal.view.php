<?php /** Mentions légales & confidentialité (publique). */ ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(Settings::get('main_title', 'RPN')) ?> — Mentions légales & confidentialité</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <?= Theme::css() ?>
    <style>
        * { box-sizing:border-box; margin:0; padding:0; }
        body { font-family:'Poppins',sans-serif; min-height:100vh; color:var(--text); padding:34px 20px 70px;
            background:radial-gradient(circle at 12% 0%, var(--glow1), transparent 42%), var(--bg-base); }
        body::before { content:""; position:fixed; top:0; left:0; right:0; height:6px; background:var(--bar); }
        .wrap { max-width:740px; margin:0 auto; }
        .top { display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; margin-bottom:16px; }
        h1 { font-size:24px; } h1 span { color:var(--accent); }
        .back { font-size:14px; color:var(--muted); text-decoration:none; border:1px solid var(--card-border); padding:8px 14px; border-radius:10px; }
        .card { background:var(--card-bg); border:1px solid var(--card-border); border-radius:16px; padding:22px 24px; margin-bottom:18px; box-shadow:var(--card-shadow); }
        h2 { font-size:18px; color:var(--accent); margin:6px 0 10px; }
        p, li { font-size:14px; color:var(--text); line-height:1.6; margin-bottom:10px; }
        ul { margin:0 0 10px 22px; }
        .muted { color:var(--muted); font-size:13px; }
        .ph { background:rgba(244,193,75,.18); border:1px dashed #d9b85a; border-radius:6px; padding:1px 7px; }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="top">
            <h1>📄 <span>Mentions légales</span> & confidentialité</h1>
            <a class="back" href="<?= url('') ?>">← Accueil</a>
        </div>

        <div class="card">
            <h2>Éditeur du site</h2>
            <p>Ce site (<?= htmlspecialchars(Settings::get('main_title', 'RPN')) ?>) est édité par <span class="ph">[Nom de l'éditeur / association]</span>, <span class="ph">[adresse]</span>.<br>
            Contact : <span class="ph">[email de contact]</span>.</p>
            <h2>Hébergement</h2>
            <p>Le site est hébergé par <span class="ph">[nom de l'hébergeur, ex. Hostinger]</span>, <span class="ph">[adresse de l'hébergeur]</span>.</p>
        </div>

        <div class="card">
            <h2>Données personnelles (RGPD)</h2>
            <p><b>Données collectées :</b> nom, adresse e-mail, photo de profil (facultative), et les contenus que tu publies (articles, questionnaires, messages, réservations). Pour les paiements, Stripe traite les données de carte ; le site ne conserve que le montant, la date et le statut.</p>
            <p><b>Finalité :</b> faire fonctionner la communauté (compte, contenus, échanges, agenda) et, le cas échéant, encaisser des dons/cotisations.</p>
            <p><b>Conservation :</b> tes données sont conservées tant que ton compte existe. Les justificatifs de paiement sont conservés selon les obligations légales.</p>
            <p><b>Tes droits :</b> tu peux à tout moment :</p>
            <ul>
                <li>consulter et <b>télécharger tes données</b> (depuis « Confidentialité & mes données ») ;</li>
                <li><b>supprimer ton compte</b> (tes contenus publics sont alors anonymisés) ;</li>
                <li>demander une rectification en nous contactant.</li>
            </ul>
            <p class="muted">Pour exercer tes droits ou pour toute question : <span class="ph">[email de contact]</span>.</p>
        </div>

        <div class="card">
            <h2>Cookies</h2>
            <p>Ce site utilise uniquement un <b>cookie de session</b> nécessaire à la connexion et au bon fonctionnement (pas de cookie publicitaire ni de pistage). Aucun consentement n'est requis pour les cookies strictement nécessaires ; en continuant, tu acceptes ce fonctionnement.</p>
        </div>

        <p class="muted">⚠️ Modèle à compléter : remplace les champs <span class="ph">[entre crochets]</span> par tes informations réelles (éditeur, contact, hébergeur). Pour un usage professionnel, fais valider ces mentions par un juriste.</p>
    </div>
</body>
</html>
