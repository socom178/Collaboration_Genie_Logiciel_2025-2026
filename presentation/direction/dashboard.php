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
    <div class="topbar">
        <button class="hamburger" onclick="ouvrirMenu()" style="display:none;" id="hamburger">
            <span></span>
            <span></span>
            <span></span>
        </button>
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
                <div class="card-header">
                    <h3>
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-folder2-open" viewBox="0 0 16 16">
                            <path d="M1 3.5A1.5 1.5 0 0 1 2.5 2h2.764c.958 0 1.76.56 2.311 1.184C7.985 3.648 8.48 4 9 4h4.5A1.5 1.5 0 0 1 15 5.5v.64c.57.265.94.876.856 1.546l-.64 5.124A2.5 2.5 0 0 1 12.733 15H3.266a2.5 2.5 0 0 1-2.481-2.19l-.64-5.124A1.5 1.5 0 0 1 1 6.14zM2 6h12v-.5a.5.5 0 0 0-.5-.5H9c-.964 0-1.71-.629-2.174-1.154C6.374 3.334 5.82 3 5.264 3H2.5a.5.5 0 0 0-.5.5zm-.367 1a.5.5 0 0 0-.496.562l.64 5.124A1.5 1.5 0 0 0 3.266 14h9.468a1.5 1.5 0 0 0 1.489-1.314l.64-5.124A.5.5 0 0 0 14.367 7z"/>
                        </svg> Imports CSV
                    </h3></div>
                <div class="card-body">
                    <form method="POST" action="etudiants.php" enctype="multipart/form-data">
                        <div class="form-group">
                            <label>Liste des étudiants (CSV)</label>
                            <input type="file" name="csv_etudiants" class="form-control" accept=".csv"/>
                        </div>
                        <button class="btn btn-primary btn-sm" type="submit" name="action" value="import_etudiants">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-download" viewBox="0 0 16 16">
                                <path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5"/>
                                <path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708z"/>
                            </svg> Importer étudiants
                        </button>
                    </form>
                    <hr style="margin:16px 0; border-color:#F0F0F0"/>
                    <form method="POST" action="diplomes.php" enctype="multipart/form-data">
                        <div class="form-group">
                            <label>Liste des diplômés (CSV)</label>
                            <input type="file" name="csv_diplomes" class="form-control" accept=".csv"/>
                        </div>
                        <button class="btn btn-outline btn-sm" type="submit" name="action" value="import_diplomes">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-download" viewBox="0 0 16 16">
                                <path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5"/>
                                <path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708z"/>
                            </svg> Importer diplômés
                        </button>
                    </form>
                </div>
            </div>

            <!-- Mémoires en attente -->
            <div class="card">
                <div class="card-header">
                    <h3>
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-hourglass-split" viewBox="0 0 16 16">
                            <path d="M2.5 15a.5.5 0 1 1 0-1h1v-1a4.5 4.5 0 0 1 2.557-4.06c.29-.139.443-.377.443-.59v-.7c0-.213-.154-.451-.443-.59A4.5 4.5 0 0 1 3.5 3V2h-1a.5.5 0 0 1 0-1h11a.5.5 0 0 1 0 1h-1v1a4.5 4.5 0 0 1-2.557 4.06c-.29.139-.443.377-.443.59v.7c0 .213.154.451.443.59A4.5 4.5 0 0 1 12.5 13v1h1a.5.5 0 0 1 0 1zm2-13v1c0 .537.12 1.045.337 1.5h6.326c.216-.455.337-.963.337-1.5V2zm3 6.35c0 .701-.478 1.236-1.011 1.492A3.5 3.5 0 0 0 4.5 13s.866-1.299 3-1.48zm1 0v3.17c2.134.181 3 1.48 3 1.48a3.5 3.5 0 0 0-1.989-3.158C8.978 9.586 8.5 9.052 8.5 8.351z"/>
                        </svg> Mémoires en attente (<?= $nbAttente ?>)</h3>
                    <a href="memoires.php" class="btn btn-outline btn-sm">Voir tout</a>
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
</div>
</body>
</html>
