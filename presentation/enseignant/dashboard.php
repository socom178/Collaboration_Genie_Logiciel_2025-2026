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
    <link rel="icon" type="image/png" href="<?= $base_url ?>../../assets/img/logo_GASA.png">
</head>
<body>
<?php include '../../includes/navbar.php'; ?>

<div class="main-content">
    <div class="topbar">
        <button class="hamburger" onclick="ouvrirMenu()" style="display:none;" id="hamburger">
            <span></span>
            <span></span>
            <span></span>
        </button>
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
                <h3>
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-hourglass-split" viewBox="0 0 16 16">
                        <path d="M2.5 15a.5.5 0 1 1 0-1h1v-1a4.5 4.5 0 0 1 2.557-4.06c.29-.139.443-.377.443-.59v-.7c0-.213-.154-.451-.443-.59A4.5 4.5 0 0 1 3.5 3V2h-1a.5.5 0 0 1 0-1h11a.5.5 0 0 1 0 1h-1v1a4.5 4.5 0 0 1-2.557 4.06c-.29.139-.443.377-.443.59v.7c0 .213.154.451.443.59A4.5 4.5 0 0 1 12.5 13v1h1a.5.5 0 0 1 0 1zm2-13v1c0 .537.12 1.045.337 1.5h6.326c.216-.455.337-.963.337-1.5V2zm3 6.35c0 .701-.478 1.236-1.011 1.492A3.5 3.5 0 0 0 4.5 13s.866-1.299 3-1.48zm1 0v3.17c2.134.181 3 1.48 3 1.48a3.5 3.5 0 0 0-1.989-3.158C8.978 9.586 8.5 9.052 8.5 8.351z"/>
                    </svg> Mémoires en attente de validation</h3>
                <a href="validation.php" class="btn btn-primary btn-sm">Voir tout</a>
            </div>
            <div class="card-body">
                <?php if (empty($enAttente)): ?>
                    <div class="alert alert-success">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-check-lg" viewBox="0 0 16 16">
                                <path d="M12.736 3.97a.733.733 0 0 1 1.047 0c.286.289.29.756.01 1.05L7.88 12.01a.733.733 0 0 1-1.065.02L3.217 8.384a.757.757 0 0 1 0-1.06.733.733 0 0 1 1.047 0l3.052 3.093 5.4-6.425z"/>
                            </svg> Aucun mémoire en attente.</div>
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
    <script>
        function ouvrirMenu() {
            document.getElementById('sidebar').classList.add('open');
            document.getElementById('overlay').classList.add('open');
        }

        function fermerMenu() {
            document.getElementById('sidebar').classList.remove('open');
            document.getElementById('overlay').classList.remove('open');
        }

        function checkTaille() {
            const hamburger = document.getElementById('hamburger');
            if (hamburger) {
                hamburger.style.display = window.innerWidth <= 768 ? 'flex' : 'none';
            }
        }

        window.addEventListener('resize', checkTaille);
        checkTaille();
    </script>
</body>
</html>
