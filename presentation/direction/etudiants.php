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
    <link rel="icon" type="image/png" href="<?= $base_url ?>../../assets/img/logo_GASA.png">
</head>
<div class="main-content">
    <div class="topbar">
        <button class="hamburger" onclick="ouvrirMenu()" style="display:none;" id="hamburger">
            <span></span>
            <span></span>
            <span></span>
        </button>
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
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-cloud-arrow-up-fill" viewBox="0 0 16 16">
                                <path d="M8 2a5.53 5.53 0 0 0-3.594 1.342c-.766.66-1.321 1.52-1.464 2.383C1.266 6.095 0 7.555 0 9.318 0 11.366 1.708 13 3.781 13h8.906C14.502 13 16 11.57 16 9.773c0-1.636-1.242-2.969-2.834-3.194C12.923 3.999 10.69 2 8 2m2.354 5.146a.5.5 0 0 1-.708.708L8.5 6.707V10.5a.5.5 0 0 1-1 0V6.707L6.354 7.854a.5.5 0 1 1-.708-.708l2-2a.5.5 0 0 1 .708 0z"/>
                            </svg> Télécharger le fichier des nouveaux mots de passe
            </a>
        <?php endif; ?>
        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'supprime'): ?>
            <div class="alert alert-success"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-check-lg" viewBox="0 0 16 16">
                                <path d="M12.736 3.97a.733.733 0 0 1 1.047 0c.286.289.29.756.01 1.05L7.88 12.01a.733.733 0 0 1-1.065.02L3.217 8.384a.757.757 0 0 1 0-1.06.733.733 0 0 1 1.047 0l3.052 3.093 5.4-6.425z"/>
                            </svg> Étudiant supprimé avec succès.</div>
        <?php endif; ?>

        <!-- Import CSV -->
        <div class="card" style="margin-bottom:20px;">
            <div class="card-header"><h3><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-folder2-open" viewBox="0 0 16 16">
                <path d="M1 3.5A1.5 1.5 0 0 1 2.5 2h2.764c.958 0 1.76.56 2.311 1.184C7.985 3.648 8.48 4 9 4h4.5A1.5 1.5 0 0 1 15 5.5v.64c.57.265.94.876.856 1.546l-.64 5.124A2.5 2.5 0 0 1 12.733 15H3.266a2.5 2.5 0 0 1-2.481-2.19l-.64-5.124A1.5 1.5 0 0 1 1 6.14zM2 6h12v-.5a.5.5 0 0 0-.5-.5H9c-.964 0-1.71-.629-2.174-1.154C6.374 3.334 5.82 3 5.264 3H2.5a.5.5 0 0 0-.5.5zm-.367 1a.5.5 0 0 0-.496.562l.64 5.124A1.5 1.5 0 0 0 3.266 14h9.468a1.5 1.5 0 0 0 1.489-1.314l.64-5.124A.5.5 0 0 0 14.367 7z"/>
            </svg> Importer une liste d'étudiants (CSV)</h3></div>
            <div class="card-body">
                <div class="alert alert-info">
                    Format attendu : <strong>matricule, nom, prenom, filiere, annee, email</strong> (avec en-tête)
                </div>
                <form method="POST" enctype="multipart/form-data" style="display:flex; gap:12px; align-items:flex-end;">
                    <div class="form-group" style="margin:0; flex:1;">
                        <label>Fichier CSV</label>
                        <input type="file" name="csv_etudiants" class="form-control" accept=".csv" required/>
                    </div>
                    <button class="btn btn-primary" type="submit" name="action" value="import_etudiants">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-cloud-arrow-up-fill" viewBox="0 0 16 16">
                                <path d="M8 2a5.53 5.53 0 0 0-3.594 1.342c-.766.66-1.321 1.52-1.464 2.383C1.266 6.095 0 7.555 0 9.318 0 11.366 1.708 13 3.781 13h8.906C14.502 13 16 11.57 16 9.773c0-1.636-1.242-2.969-2.834-3.194C12.923 3.999 10.69 2 8 2m2.354 5.146a.5.5 0 0 1-.708.708L8.5 6.707V10.5a.5.5 0 0 1-1 0V6.707L6.354 7.854a.5.5 0 1 1-.708-.708l2-2a.5.5 0 0 1 .708 0z"/>
                            </svg> Importer
                    </button>
                </form>
            </div>
        </div>
        <!-- Liste étudiants -->
        <div class="card">
            <div class="card-header">
                <h3> 
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-people-fill" viewBox="0 0 16 16">
                        <path d="M7 14s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1zm4-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6m-5.784 6A2.24 2.24 0 0 1 5 13c0-1.355.68-2.75 1.936-3.72A6.3 6.3 0 0 0 5 9c-4 0-5 3-5 4s1 1 1 1zM4.5 8a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5"/>
                    </svg>
                    Liste des étudiants
                </h3>
            </div>
            
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
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-trash3" viewBox="0 0 16 16">
                                                <path d="M6.5 1h3a.5.5 0 0 1 .5.5v1H6v-1a.5.5 0 0 1 .5-.5M11 2.5v-1A1.5 1.5 0 0 0 9.5 0h-3A1.5 1.5 0 0 0 5 1.5v1H1.5a.5.5 0 0 0 0 1h.538l.853 10.66A2 2 0 0 0 4.885 16h6.23a2 2 0 0 0 1.994-1.84l.853-10.66h.538a.5.5 0 0 0 0-1zm1.958 1-.846 10.58a1 1 0 0 1-.997.92h-6.23a1 1 0 0 1-.997-.92L3.042 3.5zm-7.487 1a.5.5 0 0 1 .528.47l.5 8.5a.5.5 0 0 1-.998.06L5 5.03a.5.5 0 0 1 .47-.53Zm5.058 0a.5.5 0 0 1 .47.53l-.5 8.5a.5.5 0 1 1-.998-.06l.5-8.5a.5.5 0 0 1 .528-.47M8 4.5a.5.5 0 0 1 .5.5v8.5a.5.5 0 0 1-1 0V5a.5.5 0 0 1 .5-.5"/>
                                            </svg> Supprimer
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
