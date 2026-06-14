<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RPN — Membres</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <?= Theme::css() ?>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Poppins', sans-serif; min-height: 100vh; color: var(--text); padding: 40px 20px;
            background: radial-gradient(circle at 90% 0%, var(--glow2), transparent 45%), var(--bg-base);
        }
        body::before { content:""; position:fixed; top:0; left:0; right:0; height:6px; background: var(--bar); }
        .wrap { max-width: 1000px; margin: 0 auto; }
        .top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 28px; flex-wrap: wrap; gap: 12px; }
        h1 { font-size: 22px; } h1 span { color: var(--accent); }
        .nav a { color:var(--text); text-decoration:none; font-size:14px; padding:8px 16px; border-radius:10px; border:1px solid var(--card-border); }
        .nav a:hover { border-color: var(--accent); color: var(--accent); }
        .table { width: 100%; border-collapse: collapse; background: var(--card-bg); border-radius: 14px; overflow: hidden; box-shadow: var(--card-shadow); }
        th, td { padding: 14px 16px; text-align: left; font-size: 14px; border-bottom: 1px solid var(--card-border); }
        th { color: var(--accent); font-weight: 600; background: rgba(127,127,127,.08); }
        td.user { display: flex; align-items: center; gap: 10px; }
        .av { width: 36px; height: 36px; border-radius: 50%; object-fit: cover; }
        .tag { padding: 3px 10px; border-radius: 999px; font-size: 12px; font-weight: 600; }
        .tag.admin { background: rgba(244,193,75,.18); color: var(--accent); border: 1px solid var(--accent); }
        .tag.membre { background: rgba(42,157,74,.18); color: #2a9d4a; border: 1px solid rgba(42,157,74,.4); }
        .tag.block { background: rgba(230,57,70,.18); color: #e0566a; border: 1px solid rgba(230,57,70,.4); }
        .actions { display: flex; gap: 8px; }
        .actions button {
            border: none; cursor: pointer; padding: 7px 12px; border-radius: 8px; font-family: inherit;
            font-size: 12px; font-weight: 600; color: #fff;
        }
        .b-block  { background: #b97a12; }
        .b-unblock{ background: var(--vert); }
        .b-del    { background: var(--rouge); }
        .b-admin  { background: #6d28d9; }
        .b-unadmin{ background: #475569; }
        .actions button:hover { filter: brightness(1.1); }
        .me { color: var(--muted); font-size: 12px; }
        .super { color: var(--accent); font-size: 12px; font-weight: 600; }
        .empty { text-align: center; padding: 40px; color: var(--muted); }
    </style>
</head>
<body>
    <div class="wrap">
        <?php view('admin/_nav', ['active' => 'members']); ?>
        <div class="top">
            <h1>Membres <span>(<?= count($users) ?>)</span></h1>
        </div>

        <table class="table">
            <tr>
                <th>Utilisateur</th><th>Email</th><th>Rôle</th><th>Statut</th><th>Actions</th>
            </tr>
            <?php if (empty($users)): ?>
                <tr><td colspan="5" class="empty">Aucun membre pour l'instant.</td></tr>
            <?php endif; ?>
            <?php foreach ($users as $u): ?>
                <?php
                    $isMe    = ((int) $u['id'] === (int) $me['id']);
                    $isSuper = in_array(strtolower($u['email'] ?? ''), $superEmails, true);
                    $pic     = avatar_url($u['picture'] ?? '', $u['name'] ?: 'RPN');
                ?>
                <tr>
                    <td class="user">
                        <img class="av" src="<?= htmlspecialchars($pic) ?>" alt="" referrerpolicy="no-referrer">
                        <span><?= htmlspecialchars($u['name'] ?: '—') ?></span>
                    </td>
                    <td><?= htmlspecialchars($u['email']) ?></td>
                    <td><span class="tag <?= $u['role'] === 'admin' ? 'admin' : 'membre' ?>"><?= htmlspecialchars($u['role']) ?></span></td>
                    <td>
                        <?php if (!empty($u['blocked'])): ?>
                            <span class="tag block">bloqué</span>
                        <?php else: ?>
                            <span class="tag membre">actif</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($isSuper): ?>
                            <span class="super">🛡️ Super-admin (protégé)</span>
                        <?php else: ?>
                            <?php
                                // l'admin connecté peut-il agir sur la ligne pour bloquer ?
                                $canBlockThis = $canManage || $u['role'] === 'membre';
                            ?>
                            <div class="actions">
                                <?php if ($canManage): // super-admin uniquement : gérer les rôles ?>
                                    <?php if ($u['role'] === 'admin'): ?>
                                        <form method="post" action="<?= url('admin/demote') ?>">
                                            <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                                            <button class="b-unadmin" type="submit">Retirer admin</button>
                                        </form>
                                    <?php else: ?>
                                        <form method="post" action="<?= url('admin/promote') ?>">
                                            <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                                            <button class="b-admin" type="submit">Rendre admin</button>
                                        </form>
                                    <?php endif; ?>
                                <?php endif; ?>

                                <?php if ($canBlockThis): ?>
                                    <?php if (!empty($u['blocked'])): ?>
                                        <form method="post" action="<?= url('admin/unblock') ?>">
                                            <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                                            <button class="b-unblock" type="submit">Débloquer</button>
                                        </form>
                                    <?php else: ?>
                                        <form method="post" action="<?= url('admin/block') ?>">
                                            <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                                            <button class="b-block" type="submit">Bloquer</button>
                                        </form>
                                    <?php endif; ?>
                                <?php endif; ?>

                                <?php if ($canManage): // super-admin uniquement : supprimer ?>
                                    <form method="post" action="<?= url('admin/delete') ?>"
                                          onsubmit="return confirm('Supprimer définitivement ce membre ?');">
                                        <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                                        <button class="b-del" type="submit">Supprimer</button>
                                    </form>
                                <?php endif; ?>

                                <?php if (!$canManage && $u['role'] === 'admin'): ?>
                                    <span class="me">— (admin)</span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
</body>
</html>
