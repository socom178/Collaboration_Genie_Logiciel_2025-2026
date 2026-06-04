<?php

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'direction') {
    header('Location: ../auth/login.php'); exit;
}

require_once __DIR__ . '/../../persistance/MemoireDAO.php';
include __DIR__ . '/../../config/db.php';
$dao = new MemoireDAO(getDb());

// Suppression
if (isset($_GET['supprimer'])) {
    $dao->delete((int)$_GET['supprimer']);
    header('Location: memoires.php?msg=supprime');
    exit;
}

$filtre   = $_GET['statut'] ?? 'tous';
$memoires = $filtre === 'tous' ? $dao->findAll() : $dao->findByStatut($filtre);
?>
<?php include __DIR__ . '/../../includes/header.php'; ?>
<?php include __DIR__ . '/../../includes/navbar.php'; ?>
<head>
    <meta charset="UTF-8"/>
    <title>Dashboard</title>
    <link rel="stylesheet" href="../../assets/css/style.css"/>
    <link rel="icon" type="image/png" href="<?= $base_url ?>/assets/img/logo_GASA.png">
</head>
<div class="main-content">
    <div class="topbar">
        <div class="topbar-title">
            <h1>Gestion des mémoires</h1>
            <p><?= count($memoires) ?> mémoire(s) trouvé(s)</p>
        </div>
    </div>

    <div class="page-content">

        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'supprime'): ?>
            <div class="alert alert-success">✅ Mémoire supprimé.</div>
        <?php endif; ?>

        <!-- Filtres -->
        <div style="display:flex; gap:10px; margin-bottom:20px;">
            <a href="?statut=tous"       class="btn <?= $filtre==='tous'       ? 'btn-primary' : 'btn-outline' ?>">Tous</a>
            <a href="?statut=en_attente" class="btn <?= $filtre==='en_attente' ? 'btn-primary' : 'btn-outline' ?>">En attente</a>
            <a href="?statut=valide"     class="btn <?= $filtre==='valide'     ? 'btn-primary' : 'btn-outline' ?>">Validés</a>
            <a href="?statut=refuse"     class="btn <?= $filtre==='refuse'     ? 'btn-primary' : 'btn-outline' ?>">Refusés</a>
        </div>

        <div class="card">
            <div class="card-header"><h3>📄 Liste des mémoires</h3></div>
            <div class="card-body">
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr><th>Thème</th><th>Année</th><th>Date soumission</th><th>Statut</th><th>Actions</th></tr>
                        </thead>
                        <tbody>
                            <?php if (empty($memoires)): ?>
                                <tr><td colspan="5" style="text-align:center; color:#888;">Aucun mémoire trouvé.</td></tr>
                            <?php else: ?>
                                <?php foreach ($memoires as $m): ?>
                                <tr>
                                    <td><?= htmlspecialchars(substr($m->getTheme(), 0, 50)) ?>...</td>
                                    <td><?= htmlspecialchars($m->getAnnee()) ?></td>
                                    <td><?= htmlspecialchars($m->getDateSoumission()) ?></td>
                                    <td>
                                        <span class="badge badge-<?= $m->getStatut() ?>">
                                            <?= match($m->getStatut()) {
                                                'en_attente' => 'En attente',
                                                'valide'     => 'Validé',
                                                'refuse'     => 'Refusé',
                                                default      => $m->getStatut()
                                            } ?>
                                        </span>
                                    </td>
                                    <td style="display:flex; gap:6px;">
                                        <a href="../../assets/uploads/<?= htmlspecialchars($m->getFichierPdf()) ?>"
                                           target="_blank" class="btn btn-outline btn-sm">📄 PDF</a>
                                        <a href="?supprimer=<?= $m->getIdMemoir() ?>"
                                           class="btn btn-danger btn-sm"
                                           onclick="return confirm('Supprimer ce mémoire ?')">🗑</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>
</body>
</html>
