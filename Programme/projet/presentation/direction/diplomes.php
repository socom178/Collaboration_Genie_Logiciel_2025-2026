<?php

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'direction') {
    header('Location: ../auth/login.php'); exit;
}

require_once __DIR__ . '/../../persistance/EtudiantDAO.php';
include __DIR__ . '/../../config/db.php';
$dao = new EtudiantDAO(getDb());

$message = '';

// Import CSV diplômés
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_diplomes'])) {
    if ($_FILES['csv_diplomes']['error'] === 0) {
        $tmp    = $_FILES['csv_diplomes']['tmp_name'];
        $handle = fopen($tmp, 'r');
        fgetcsv($handle); 
        $nb = 0;
        $pdo = getDb();
        while (($ligne = fgetcsv($handle, 1000, ';')) !== false) {
            [$matricule, $nom, $prenom, $filiere, $annee] = array_map('trim', $ligne);
            $stmt = $pdo->prepare("
                INSERT INTO liste_etudiants_csv (matricule, nom, prenom, filiere, annee, statut)
                VALUES (:m,:n,:p,:f,:a,'diplome')
                ON DUPLICATE KEY UPDATE statut='diplome'
            ");
            $stmt->execute([':m'=>$matricule,':n'=>$nom,':p'=>$prenom,':f'=>$filiere,':a'=>$annee]);
            $nb++;
        }
        fclose($handle);
        $message = "✔ $nb diplômé(s) importé(s) avec succès.";
    }
}

// Upgrade manuel par la direction
if (isset($_GET['upgrade'])) {
    $matricule = $_GET['upgrade'];
    $ok = $dao->upgraderStatut($matricule);
    header('Location: diplomes.php?msg=' . ($ok ? 'upgraded' : 'erreur'));
    exit;
}

$diplomes = $dao->findDiplomes();
?>
<?php
require_once __DIR__ . '/../../config/db.php';
include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/navbar.php';
?>
<head>
    <meta charset="UTF-8"/>
    <title>Dashboard</title>
    <link rel="stylesheet" href="../../assets/css/style.css"/>
    <link rel="icon" type="image/png" href="<?= $base_url ?>/assets/img/logo_GASA.png">
</head>
<div class="main-content">
    <div class="topbar">
        <div class="topbar-title">
            <h1>Gestion des diplômés</h1>
            <p><?= count($diplomes) ?> diplômé(s) enregistré(s)</p>
        </div>
    </div>

    <div class="page-content">

        <?php if ($message): ?>
            <div class="alert alert-success"><?= $message ?></div>
        <?php endif; ?>
        <?php if (isset($_GET['msg'])): ?>
            <div class="alert alert-<?= $_GET['msg'] === 'upgraded' ? 'success' : 'danger' ?>">
                <?= $_GET['msg'] === 'upgraded' ? '✅ Statut mis à jour avec succès.' : '⚠️ Matricule non trouvé dans la liste des diplômés.' ?>
            </div>
        <?php endif; ?>

        <!-- Import CSV diplômés -->
        <div class="card" style="margin-bottom:20px;">
            <div class="card-header"><h3>📁 Importer la liste des diplômés (CSV)</h3></div>
            <div class="card-body">
                <div class="alert alert-info">
                    Format attendu : <strong>matricule, nom, prenom, filiere, annee</strong>
                </div>
                <form method="POST" enctype="multipart/form-data" style="display:flex; gap:12px; align-items:flex-end;">
                    <div class="form-group" style="margin:0; flex:1;">
                        <label>Fichier CSV des diplômés</label>
                        <input type="file" name="csv_diplomes" class="form-control" accept=".csv" required/>
                    </div>
                    <button class="btn btn-primary" type="submit">📤 Importer</button>
                </form>
            </div>
        </div>

        <!-- Liste diplômés -->
        <div class="card">
            <div class="card-header"><h3>🎓 Liste des étudiants diplômés</h3></div>
            <div class="card-body">
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Matricule</th>
                                <th>Nom complet</th>
                                <th>Email</th>
                                <th>Mémoires</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($diplomes)): ?>
                                <tr><td colspan="4" style="text-align:center; color:#888;">Aucun diplômé enregistré.</td></tr>
                            <?php else: ?>
                                <?php foreach ($diplomes as $d): ?>
                                <tr>
                                    <td><?= htmlspecialchars($d->getMatricule()) ?></td>
                                    <td><?= htmlspecialchars($d->getNomComplet()) ?></td>
                                    <td><?= htmlspecialchars($d->getEmail()) ?></td>
                                    <td>
                                        <a href="../direction/memoires.php?etudiant=<?= $d->getEtudiantId() ?>" class="btn btn-outline btn-sm">
                                            Voir mémoires
                                        </a>
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
