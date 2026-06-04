<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'direction') {
    header('Location: ../auth/login.php'); exit;
}

require_once __DIR__ . '/../../persistance/EtudiantDAO.php';
include __DIR__ . '/../../config/db.php';
$dao = new EtudiantDAO(getDb());

$message = '';
$type_msg = 'info';
if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['action'])
    && $_POST['action'] === 'import_etudiants') {

    if (isset($_FILES['csv_etudiants'])
        && $_FILES['csv_etudiants']['error'] === 0) {

        $tmp      = $_FILES['csv_etudiants']['tmp_name'];
        $resultats = $dao->importerCSV($tmp);

        // Génération du fichier récapitulatif
        if (!empty($resultats['comptes'])) {
            $fichier = __DIR__ . '/../../assets/uploads/comptes_etudiants_' . date('Ymd_His') . '.csv';
            $fp = fopen($fichier, 'w');
            if ($fp) {
                fputcsv($fp, ['Matricule', 'Nom', 'Prenom', 'Email', 'Mot de passe'], ';');
                foreach ($resultats['comptes'] as $c) {
                    fputcsv($fp, [
                        $c['matricule'],
                        $c['nom'],
                        $c['prenom'],
                        $c['email'],
                        $c['password']
                    ], ';');
                }
                fclose($fp);
                $lienCSV = '../../assets/uploads/' . basename($fichier);
            }
        }

        $message  = "✔ {$resultats['succes']} compte(s) créé(s). ✘ {$resultats['erreurs']} ignoré(s).";
        $type_msg = 'success';
        $details  = $resultats['messages'];

    } else {
        $message  = "Erreur lors de l'upload du fichier.";
        $type_msg = 'danger';
    }
}

    // Suppression
    if (isset($_GET['supprimer'])) {
        $dao->delete((int)$_GET['supprimer']);
        header('Location: etudiants.php?msg=supprime');
        exit;
    }


    if (
        $_SERVER['REQUEST_METHOD'] === 'POST'
        && isset($_POST['action'])
        && $_POST['action'] === 'reset_passwords'
    ) {
        
}

$etudiants = $dao->findAll();
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
            <h1>Gestion des étudiants</h1>
            <p><?= count($etudiants) ?> étudiant(s) enregistré(s)</p>
        </div>
    </div>
    
    <div class="page-content">

        <?php if ($message): ?>
            <div class="alert alert-<?= $type_msg ?>"><?= $message ?></div>
            <?php if (!empty($details)): ?>
                <div class="card"><div class="card-body">
                    <?php foreach ($details as $d): ?>
                        <p style="font-size:12px; margin:4px 0;"><?= htmlspecialchars($d) ?></p>
                    <?php endforeach; ?>
                </div></div>
            <?php endif; ?>
        <?php endif; ?>
        <?php if (!empty($lienCSV)): ?>
            <a href="<?= $lienCSV ?>" download class="btn btn-success">
                📥 Télécharger le fichier des nouveaux mots de passe
            </a>
        <?php endif; ?>
        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'supprime'): ?>
            <div class="alert alert-success">✅ Étudiant supprimé avec succès.</div>
        <?php endif; ?>

        <!-- Import CSV -->
        <div class="card" style="margin-bottom:20px;">
            <div class="card-header"><h3>📁 Importer une liste d'étudiants (CSV)</h3></div>
            <div class="card-body">
                <div class="alert alert-info">
                    Format attendu : <strong>matricule, nom, prenom, filiere, annee</strong> (avec en-tête)
                </div>
                <form method="POST" enctype="multipart/form-data" style="display:flex; gap:12px; align-items:flex-end;">
                    <div class="form-group" style="margin:0; flex:1;">
                        <label>Fichier CSV</label>
                        <input type="file" name="csv_etudiants" class="form-control" accept=".csv" required/>
                    </div>
                    <button class="btn btn-primary" type="submit" name="action" value="import_etudiants">
                        📤 Importer
                    </button>
                </form>
            </div>
        </div>
        <form method="POST">
            <button
                type="submit"
                name="action"
                value="reset_passwords"
                class="btn btn-warning">
                🔄 Réinitialiser tous les mots de passe
            </button>
        </form>
        <!-- Liste étudiants -->
        <div class="card">
            <div class="card-header"><h3>👥 Liste des étudiants</h3></div>
            
            <div class="card-body">
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Matricule</th>
                                <th>Nom complet</th>
                                <th>Email</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($etudiants)): ?>
                                <tr><td colspan="5" style="text-align:center; color:#888;">Aucun étudiant enregistré.</td></tr>
                            <?php else: ?>
                                <?php foreach ($etudiants as $e): ?>
                                <tr>
                                    <td><?= htmlspecialchars($e->getMatricule()) ?></td>
                                    <td><?= htmlspecialchars($e->getNomComplet()) ?></td>
                                    <td><?= htmlspecialchars($e->getEmail()) ?></td>
                                    <td>
                                        <span class="badge badge-<?= $e->getType() ?>">
                                            <?= $e->getType() === 'diplome' ? 'Diplômé' : 'Simple' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="?supprimer=<?= $e->getId() ?>"
                                           class="btn btn-danger btn-sm"
                                           onclick="return confirm('Supprimer cet étudiant ?')">
                                            🗑 Supprimer
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
