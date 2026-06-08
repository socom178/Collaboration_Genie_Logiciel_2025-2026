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
                <?= $_GET['msg'] === 'upgraded' ? '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-check-lg" viewBox="0 0 16 16">
                                <path d="M12.736 3.97a.733.733 0 0 1 1.047 0c.286.289.29.756.01 1.05L7.88 12.01a.733.733 0 0 1-1.065.02L3.217 8.384a.757.757 0 0 1 0-1.06.733.733 0 0 1 1.047 0l3.052 3.093 5.4-6.425z"/>
                            </svg> Statut mis à jour avec succès.' : '⚠️ Matricule non trouvé dans la liste des diplômés.' ?>
            </div>
        <?php endif; ?>

        <!-- Import CSV diplômés -->
        <div class="card" style="margin-bottom:20px;">
            <div class="card-header"><h3><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-folder2-open" viewBox="0 0 16 16">
                <path d="M1 3.5A1.5 1.5 0 0 1 2.5 2h2.764c.958 0 1.76.56 2.311 1.184C7.985 3.648 8.48 4 9 4h4.5A1.5 1.5 0 0 1 15 5.5v.64c.57.265.94.876.856 1.546l-.64 5.124A2.5 2.5 0 0 1 12.733 15H3.266a2.5 2.5 0 0 1-2.481-2.19l-.64-5.124A1.5 1.5 0 0 1 1 6.14zM2 6h12v-.5a.5.5 0 0 0-.5-.5H9c-.964 0-1.71-.629-2.174-1.154C6.374 3.334 5.82 3 5.264 3H2.5a.5.5 0 0 0-.5.5zm-.367 1a.5.5 0 0 0-.496.562l.64 5.124A1.5 1.5 0 0 0 3.266 14h9.468a1.5 1.5 0 0 0 1.489-1.314l.64-5.124A.5.5 0 0 0 14.367 7z"/>
            </svg> Importer la liste des diplômés (CSV)</h3></div>
            <div class="card-body">
                <div class="alert alert-info">
                    Format attendu : <strong>matricule, nom, prenom, filiere, annee</strong>
                </div>
                <form method="POST" enctype="multipart/form-data" style="display:flex; gap:12px; align-items:flex-end;">
                    <div class="form-group" style="margin:0; flex:1;">
                        <label>Fichier CSV des diplômés</label>
                        <input type="file" name="csv_diplomes" class="form-control" accept=".csv" required/>
                    </div>
                    <button class="btn btn-primary" type="submit"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-cloud-arrow-up-fill" viewBox="0 0 16 16">
                                <path d="M8 2a5.53 5.53 0 0 0-3.594 1.342c-.766.66-1.321 1.52-1.464 2.383C1.266 6.095 0 7.555 0 9.318 0 11.366 1.708 13 3.781 13h8.906C14.502 13 16 11.57 16 9.773c0-1.636-1.242-2.969-2.834-3.194C12.923 3.999 10.69 2 8 2m2.354 5.146a.5.5 0 0 1-.708.708L8.5 6.707V10.5a.5.5 0 0 1-1 0V6.707L6.354 7.854a.5.5 0 1 1-.708-.708l2-2a.5.5 0 0 1 .708 0z"/>
                            </svg> Importer</button>
                </form>
            </div>
        </div>

        <!-- Liste diplômés -->
        <div class="card">
            <div class="card-header">
                <h3>
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-mortarboard-fill" viewBox="0 0 16 16">
                        <path d="M8.211 2.047a.5.5 0 0 0-.422 0l-7.5 3.5a.5.5 0 0 0 .025.917l7.5 3a.5.5 0 0 0 .372 0L14 7.14V13a1 1 0 0 0-1 1v2h3v-2a1 1 0 0 0-1-1V6.739l.686-.275a.5.5 0 0 0 .025-.917z"/>
                        <path d="M4.176 9.032a.5.5 0 0 0-.656.327l-.5 1.7a.5.5 0 0 0 .294.605l4.5 1.8a.5.5 0 0 0 .372 0l4.5-1.8a.5.5 0 0 0 .294-.605l-.5-1.7a.5.5 0 0 0-.656-.327L8 10.466z"/>
                    </svg> 
                    Liste des étudiants diplômés
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
