<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'professeur') {
    header('Location: ../auth/login.php'); exit;
}

require_once '../../config/db.php';
require_once '../../persistance/MemoireDAO.php';
require_once '../../persistance/NotificationDAO.php';

$memoireDAO  = new MemoireDAO(getDb());
$notifDAO    = new NotificationDAO(getDb());

$nbAttente   = $memoireDAO->compterEnAttente();
$nbValides   = $memoireDAO->compter();
$enAttente   = $memoireDAO->findByStatut('en_attente');
$notifs      = $notifDAO->findByPersonne($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8"/>
    <title>Dashboard Enseignant</title>
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
    </div>

    <div class="page-content">

        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="label">Mémoires en attente</div>
                <div class="value red"><?= $nbAttente ?></div>
                <div class="bar red"></div>
            </div>
            <div class="stat-card">
                <div class="label">Mémoires validés</div>
                <div class="value"><?= $nbValides ?></div>
                <div class="bar"></div>
            </div>
        </div>

        <!-- Mémoires en attente -->
        <div class="card">
            <div class="card-header">
                <h3>⏳ Mémoires en attente de validation</h3>
                <a href="validation.php" class="btn btn-primary btn-sm">Voir tout</a>
            </div>
            <div class="card-body">
                <?php if (empty($enAttente)): ?>
                    <div class="alert alert-success">✅ Aucun mémoire en attente.</div>
                <?php else: ?>
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr><th>Thème</th><th>Année</th><th>Date soumission</th><th>Action</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($enAttente as $m): ?>
                                <tr>
                                    <td><?= htmlspecialchars(substr($m->getTheme(), 0, 50)) ?>...</td>
                                    <td><?= htmlspecialchars($m->getAnnee()) ?></td>
                                    <td><?= htmlspecialchars($m->getDateSoumission()) ?></td>
                                    <td>
                                        <a href="validation.php?id=<?= $m->getIdMemoir() ?>" class="btn btn-primary btn-sm">
                                            Examiner
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>
</body>
</html>
