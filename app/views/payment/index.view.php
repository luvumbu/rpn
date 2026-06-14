<?php
/**
 * Espace paiement (Stripe). Variables : $configured, $currency, $donationLabel,
 * $donationAmounts, $plans, $payments, $notice, $error, $isAdmin.
 */
$cur = function ($cents) use ($currency) { return number_format($cents / 100, 2, ',', ' ') . ' ' . $currency; };
?>
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
        body { font-family:'Poppins',sans-serif; min-height:100vh; color:var(--text); padding:34px 20px 70px;
            background:radial-gradient(circle at 12% 0%, var(--glow1), transparent 42%), var(--bg-base); }
        body::before { content:""; position:fixed; top:0; left:0; right:0; height:6px; background:var(--bar); }
        .wrap { max-width:760px; margin:0 auto; }
        .top { display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; margin-bottom:8px; }
        h1 { font-size:24px; } h1 span { color:var(--accent); }
        .back { font-size:14px; color:var(--muted); text-decoration:none; border:1px solid var(--card-border); padding:8px 14px; border-radius:10px; }
        .subtitle { color:var(--muted); font-size:13px; margin-bottom:22px; }
        .card { background:var(--card-bg); border:1px solid var(--card-border); border-radius:16px; padding:20px 22px; margin-bottom:18px; box-shadow:var(--card-shadow); }
        .card h2 { font-size:17px; color:var(--accent); margin-bottom:6px; }
        .card .lead { font-size:13px; color:var(--muted); margin-bottom:14px; }
        .msg { padding:12px 16px; border-radius:12px; font-size:14px; margin-bottom:16px; }
        .msg.ok { background:rgba(42,157,74,.14); border:1px solid var(--vert,#2a9d4a); }
        .msg.err { background:rgba(230,57,70,.12); border:1px solid var(--rouge,#e63946); }
        .amounts { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:12px; }
        .amt { font:inherit; font-weight:700; cursor:pointer; border:1px solid var(--card-border); background:var(--card-bg);
            color:var(--text); border-radius:10px; padding:9px 15px; }
        .amt:hover, .amt.on { border-color:var(--accent); color:var(--accent); }
        .pay-row { display:flex; gap:10px; flex-wrap:wrap; align-items:center; }
        .pay-row input { flex:1; min-width:120px; padding:12px 14px; border-radius:11px; border:1px solid var(--card-border);
            background:rgba(127,127,127,.08); color:var(--text); font-family:inherit; font-size:15px; }
        .btn { font:inherit; font-weight:700; font-size:15px; cursor:pointer; border:none; border-radius:11px;
            padding:12px 22px; background:var(--accent); color:var(--accent-ink); }
        .plan { display:flex; align-items:center; gap:14px; justify-content:space-between; flex-wrap:wrap;
            border:1px solid var(--card-border); border-radius:14px; padding:14px 16px; margin-bottom:10px; }
        .plan .pn { font-weight:700; font-size:15.5px; }
        .plan .pp { font-size:13px; color:var(--muted); }
        .hist { width:100%; border-collapse:collapse; font-size:13.5px; }
        .hist th, .hist td { text-align:left; padding:9px 10px; border-bottom:1px solid var(--card-border); }
        .hist th { color:var(--muted); font-size:12px; text-transform:uppercase; letter-spacing:.4px; }
        .tag { font-size:11.5px; font-weight:700; padding:2px 9px; border-radius:999px; }
        .tag.paid { background:rgba(42,157,74,.16); color:#1d7a37; }
        .tag.pending { background:rgba(244,193,75,.22); color:#8a6d1f; }
        .tag.other { background:rgba(127,127,127,.18); color:var(--muted); }
        .stripe-note { font-size:12px; color:var(--muted); margin-top:10px; }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="top">
            <h1>💳 <span>Paiement</span></h1>
            <a class="back" href="<?= url('dashboard') ?>">← Tableau de bord</a>
        </div>
        <p class="subtitle">Paiement 100 % sécurisé via <b>Stripe</b> : aucune donnée de carte ne transite par ce site.</p>

        <?php if (!empty($notice)): ?><div class="msg ok"><?= htmlspecialchars($notice) ?></div><?php endif; ?>
        <?php if (!empty($error)): ?><div class="msg err"><?= htmlspecialchars($error) ?></div><?php endif; ?>

        <?php if (empty($configured)): ?>
            <div class="card">
                <h2>⏳ Paiements bientôt disponibles</h2>
                <p class="lead">Le paiement en ligne n'est pas encore activé.</p>
                <?php if (!empty($isAdmin)): ?>
                    <p class="lead">👑 En tant qu'admin : active-le dans <a href="<?= url('admin/settings') ?>#p-paiements" style="color:var(--accent);font-weight:700;">Paramètres → Paiements</a> (clés Stripe).</p>
                <?php endif; ?>
            </div>
        <?php else: ?>

            <!-- Don / cotisation ponctuel -->
            <div class="card">
                <h2>❤️ <?= htmlspecialchars($donationLabel) ?></h2>
                <p class="lead">Choisis un montant (paiement unique).</p>
                <form method="post" action="<?= url('paiement/checkout') ?>">
                    <input type="hidden" name="type" value="payment">
                    <div class="amounts">
                        <?php foreach ($donationAmounts as $a): ?>
                            <button type="button" class="amt" onclick="rpmSetAmount(this,<?= (int) $a ?>)"><?= (int) $a ?> <?= htmlspecialchars($currency) ?></button>
                        <?php endforeach; ?>
                    </div>
                    <div class="pay-row">
                        <input type="number" name="amount" id="amount" min="1" step="1" placeholder="Autre montant (<?= htmlspecialchars($currency) ?>)" required>
                        <button class="btn" type="submit">Payer</button>
                    </div>
                </form>
            </div>

            <!-- Abonnements -->
            <?php if (!empty($plans)): ?>
            <div class="card">
                <h2>🔄 Abonnements</h2>
                <p class="lead">Adhésion récurrente, renouvelée automatiquement. Résiliable à tout moment.</p>
                <?php foreach ($plans as $i => $p): ?>
                    <div class="plan">
                        <div>
                            <div class="pn"><?= htmlspecialchars($p['name']) ?></div>
                            <div class="pp"><?= number_format($p['amount'], 2, ',', ' ') ?> <?= htmlspecialchars($currency) ?> / <?= $p['interval'] === 'year' ? 'an' : 'mois' ?></div>
                        </div>
                        <form method="post" action="<?= url('paiement/checkout') ?>" style="margin:0;">
                            <input type="hidden" name="type" value="subscription">
                            <input type="hidden" name="plan" value="<?= (int) $i ?>">
                            <button class="btn" type="submit">S'abonner</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Historique -->
            <?php if (!empty($payments)): ?>
            <div class="card">
                <h2>🧾 Mes paiements</h2>
                <table class="hist">
                    <tr><th>Date</th><th>Objet</th><th>Montant</th><th>Statut</th></tr>
                    <?php foreach ($payments as $p): ?>
                        <?php $st = $p['status']; $cls = $st === 'paid' ? 'paid' : ($st === 'pending' ? 'pending' : 'other'); ?>
                        <tr>
                            <td><?= date('d/m/Y', strtotime($p['created_at'])) ?></td>
                            <td><?= htmlspecialchars($p['description'] ?: ($p['type'] === 'subscription' ? 'Abonnement' : 'Paiement')) ?></td>
                            <td><?= number_format($p['amount'] / 100, 2, ',', ' ') ?> <?= strtoupper(htmlspecialchars($p['currency'])) ?></td>
                            <td><span class="tag <?= $cls ?>"><?= $st === 'paid' ? 'Payé' : ($st === 'pending' ? 'En attente' : ucfirst($st)) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
            <?php endif; ?>

        <?php endif; ?>
    </div>
    <script>
    function rpmSetAmount(btn, v) {
        document.getElementById('amount').value = v;
        document.querySelectorAll('.amt').forEach(function (b) { b.classList.remove('on'); });
        btn.classList.add('on');
    }
    </script>
</body>
</html>
