<?php

session_start();
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'direction') {
        header('Location: ../auth/login.php');
        exit;
    }

    include __DIR__ . '/../../config/db.php';
    require_once __DIR__ . '/../../persistance/EtudiantDAO.php';
    require_once __DIR__ . '/../../persistance/ProfesseurDAO.php';
    require_once __DIR__ . '/../../persistance/MemoireDAO.php';

    $etudiantDAO  = new EtudiantDAO(getDb());
    $professeurDAO = new ProfesseurDAO(getDb());
    $memoireDAO   = new MemoireDAO(getDb());

    $nbEtudiants  = $etudiantDAO->compter();
    $nbDiplomes   = $etudiantDAO->compterDiplomes();
    $nbMemoires   = $memoireDAO->compter();
    $nbAttente    = $memoireDAO->compterEnAttente();
    $enAttente    = $memoireDAO->findByStatut('en_attente');
?>
<?php include __DIR__ . '/../../includes/header.php'; ?>
<?php include __DIR__ . '/../../includes/navbar.php'; ?>

<head>
    <meta charset="UTF-8"/>
    <title>Dashboard</title>
    <link rel="stylesheet" href="../../assets/css/style.css"/>
    <link rel="icon" type="image/png" href="<?= $base_url ?>/assets/img/logo_GASA.png">
</head>
 <?php if (!empty($message)): ?>
    <div class="alert alert-danger">
        <?= $message ?>
    </div>
<?php endif; ?>
<div class="main-content">
    <!-- Topbar -->
    <div class="topbar">
        <div class="topbar-title">
            <h1>Tableau de bord</h1>
            <p>Direction des études — Vue d'ensemble</p>
        </div>
        <div class="topbar-actions">
            <div class="user-badge"><?= $initiales ?></div>
        </div>
    </div>

    <div class="page-content">

        <!-- Statistiques -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="label">Étudiants inscrits</div>
                <div class="value"><?= $nbEtudiants ?></div>
                <div class="bar"></div>
            </div>
            <div class="stat-card">
                <div class="label">Étudiants diplômés</div>
                <div class="value"><?= $nbDiplomes ?></div>
                <div class="bar red"></div>
            </div>
            <div class="stat-card">
                <div class="label">Mémoires publiés</div>
                <div class="value"><?= $nbMemoires ?></div>
                <div class="bar"></div>
            </div>
            <div class="stat-card">
                <div class="label">En attente</div>
                <div class="value red"><?= $nbAttente ?></div>
                <div class="bar red"></div>
            </div>
        </div>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px;">

            <!-- Import CSV -->
            <div class="card">
                <div class="card-header"><h3>📁 Imports CSV</h3></div>
                <div class="card-body">
                    <form method="POST" action="etudiants.php" enctype="multipart/form-data">
                        <div class="form-group">
                            <label>Liste des étudiants (CSV)</label>
                            <input type="file" name="csv_etudiants" class="form-control" accept=".csv"/>
                        </div>
                        <button class="btn btn-primary btn-sm" type="submit" name="action" value="import_etudiants">
                            📤 Importer étudiants
                        </button>
                    </form>
                    <hr style="margin:16px 0; border-color:#F0F0F0"/>
                    <form method="POST" action="diplomes.php" enctype="multipart/form-data">
                        <div class="form-group">
                            <label>Liste des diplômés (CSV)</label>
                            <input type="file" name="csv_diplomes" class="form-control" accept=".csv"/>
                        </div>
                        <button class="btn btn-outline btn-sm" type="submit" name="action" value="import_diplomes">
                            📤 Importer diplômés
                        </button>
                    </form>
                </div>
            </div>

            <!-- Mémoires en attente -->
            <div class="card">
                <div class="card-header">
                    <h3>⏳ Mémoires en attente (<?= $nbAttente ?>)</h3>
                    <a href="memoires.php" class="btn btn-outline btn-sm">Voir tout</a>
                </div>
                <div class="card-body">
                    <?php if (empty($enAttente)): ?>
                        <div class="alert alert-success">✅ Aucun mémoire en attente.</div>
                    <?php else: ?>
                        <div class="table-wrap">
                            <table>
                                <thead>
                                    <tr><th>Thème</th><th>Année</th><th>Statut</th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach (array_slice($enAttente, 0, 5) as $m): ?>
                                    <tr>
                                        <td><?= htmlspecialchars(substr($m->getTheme(), 0, 40)) ?>...</td>
                                        <td><?= htmlspecialchars($m->getAnnee()) ?></td>
                                        <td><span class="badge badge-attente">En attente</span></td>
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
</div>
</body>
</html>
