<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'etudiant') {
    header('Location: ../auth/login.php'); exit;
}

require_once '../../config/db.php';
require_once '../../persistance/MemoireDAO.php';
require_once '../../persistance/NotificationDAO.php';
require_once '../../persistance/EtudiantDAO.php';

$memoireDAO  = new MemoireDAO(getDb());
$notifDAO    = new NotificationDAO(getDb());
$etudiantDAO = new EtudiantDAO(getDb());

$etudiant    = $etudiantDAO->findByPersonneId($_SESSION['user_id']);
$notifs      = $notifDAO->findByPersonne($_SESSION['user_id']);
$nbNotifs    = $notifDAO->compterNonLues($_SESSION['user_id']);

$mesMemoires = [];
if ($etudiant) {
    $mesMemoires = $memoireDAO->findByEtudiant($etudiant->getEtudiantId());
}

// Demande upgrade diplômé
$msg_upgrade = '';
if (isset($_GET['upgrade'])) {
    if ($etudiant) {
        $ok = $etudiantDAO->upgraderStatut($etudiant->getMatricule());
        if ($ok) {
            $_SESSION['type'] = 'diplome';
            $msg_upgrade = 'success';
        } else {
            $msg_upgrade = 'erreur';
        }
    }
    header('Location: dashboard.php?upgrade_msg=' . $msg_upgrade);
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8"/>
    <title>Dashboard Étudiant</title>
    <link rel="stylesheet" href="../../assets/css/style.css"/>
    <link rel="icon" type="image/png" href="<?= $base_url ?>/assets/img/logo_GASA.png">
</head>
<body>
<?php include '../../includes/navbar.php'; ?>

<div class="main-content">
    <div class="topbar">
        <div class="topbar-title">
            <h1>Tableau de bord</h1>
            <p>Bienvenue, <?= htmlspecialchars($_SESSION['prenom'] . ' ' . $_SESSION['nom']) ?></p>
        </div>
        <div class="topbar-actions">
            <?php if ($nbNotifs > 0): ?>
            <div class="notif-btn">
                🔔 <span class="notif-badge"><?= $nbNotifs ?></span>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="page-content">

        <?php if (isset($_GET['upgrade_msg'])): ?>
            <?php if ($_GET['upgrade_msg'] === 'success'): ?>
                <div class="alert alert-success">🎓 Félicitations ! Votre statut a été mis à jour en Diplômé. Vous pouvez maintenant soumettre votre mémoire.</div>
            <?php else: ?>
                <div class="alert alert-danger">⚠️ Votre matricule n'a pas été trouvé dans la liste des diplômés. Veuillez contacter la Direction des études.</div>
            <?php endif; ?>
        <?php endif; ?>

        <!-- Statut étudiant -->
        <?php if ($etudiant && !$etudiant->estDiplome()): ?>
        <div class="alert alert-info" style="display:flex; justify-content:space-between; align-items:center;">
            <span>ℹ️ Vous êtes étudiant simple. Si vous avez soutenu votre mémoire, demandez votre upgrade.</span>
            <a href="?upgrade=1" class="btn btn-primary btn-sm"
               onclick="return confirm('Demander le statut Diplômé ?')">
                🎓 Devenir Diplômé
            </a>
        </div>
        <?php endif; ?>

        <?php if ($etudiant && $etudiant->estDiplome()): ?>
        <div class="alert alert-success" style="display:flex; justify-content:space-between; align-items:center;">
            <span>🎓 Vous êtes étudiant diplômé. Vous pouvez soumettre votre mémoire.</span>
            <a href="soumettre.php" class="btn btn-primary btn-sm">📤 Soumettre un mémoire</a>
        </div>
        <?php endif; ?>

        <!-- Mes mémoires -->
        <div class="card" style="margin-bottom:20px;">
            <div class="card-header">
                <h3>📄 Mes mémoires soumis</h3>
            </div>
            <div class="card-body">
                <?php if (empty($mesMemoires)): ?>
                    <p style="color:#888; font-size:13px;">Vous n'avez pas encore soumis de mémoire.</p>
                <?php else: ?>
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr><th>Thème</th><th>Année</th><th>Date soumission</th><th>Statut</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($mesMemoires as $m): ?>
                                <tr>
                                    <td><?= htmlspecialchars($m->getTheme()) ?></td>
                                    <td><?= htmlspecialchars($m->getAnnee()) ?></td>
                                    <td><?= htmlspecialchars($m->getDateSoumission()) ?></td>
                                    <td>
                                        <span class="badge badge-<?= $m->getStatut() ?>">
                                            <?= match($m->getStatut()) {
                                                'en_attente' => 'En attente', 'valide' => 'Validé', 'refuse' => 'Refusé', default => ''
                                            } ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Notifications -->
        <?php if (!empty($notifs)): ?>
        <div class="card">
            <div class="card-header">
                <h3>🔔 Notifications</h3>
                <a href="?marquer_lu=1" class="btn btn-outline btn-sm">Tout marquer lu</a>
            </div>
            <div class="card-body">
                <div class="comment-list">
                    <?php foreach ($notifs as $n): ?>
                    <div class="comment-item" style="<?= !$n->isOuvert() ? 'border-left:3px solid #1A237E;' : '' ?>">
                        <span class="comment-author"><?= htmlspecialchars($n->getTitre()) ?></span>
                        <span class="comment-date"><?= htmlspecialchars($n->getDateEnvoie()) ?></span>
                        <p class="comment-text"><?= htmlspecialchars($n->getMessage()) ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

    </div>
</div>
</body>
</html>
