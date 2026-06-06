<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'direction') {
    header('Location: ../auth/login.php'); exit;
}

require_once '../../config/db.php';
require_once '../../persistance/MemoireDAO.php';
require_once '../../persistance/EtudiantDAO.php';
require_once '../../metier/Memoire.php';

$memoireDAO  = new MemoireDAO(getDb());
$etudiantDAO = new EtudiantDAO(getDb());

$message  = '';
$type_msg = '';
$details  = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $dossier_pdf = __DIR__ . '/../../assets/uploads/anciens/';
   
    if (!is_dir($dossier_pdf)) {
        mkdir($dossier_pdf, 0777, true);
    }

    if (isset($_FILES['pdf'])) {

        foreach ($_FILES['pdf']['tmp_name'] as $key => $tmpName) {

            if ($_FILES['pdf']['error'][$key] === 0) {

                $nomFichier = basename($_FILES['pdf']['name'][$key]);

                move_uploaded_file(
                    $tmpName,
                    $dossier_pdf . $nomFichier
                );
            }
        }
    }

    // 2. Lire le CSV
    if (!$message && isset($_FILES['csv_memoires']) && $_FILES['csv_memoires']['error'] === 0) {
        $handle = fopen($_FILES['csv_memoires']['tmp_name'], 'r');
        fgetcsv($handle, 0, ';'); 

        $succes  = 0;
        $erreurs = 0;

        while (($ligne = fgetcsv($handle, 0, ';')) !== false) {
            if (count($ligne) < 6) {
                $erreurs++;
                $details[] = "✘ Ligne invalide ignorée.";
                continue;
            }

            [$theme, $resumer, $mots_cle, $annee, $matricule, $fichier_pdf] = array_map('trim', $ligne);
            $fichier_pdf = basename($fichier_pdf);
            // Trouver l'étudiant par matricule
            $etudiant = $etudiantDAO->findByMatricule($matricule);
            if (!$etudiant) {
                $erreurs++;
                $details[] = "✘ Matricule $matricule introuvable.";
                continue;
            }
            
            // Vérifier que le PDF existe dans le dossier extrait
            if (!file_exists($dossier_pdf . $fichier_pdf)) {
                $erreurs++;
                $details[] = "✘ PDF $fichier_pdf introuvable dans le ZIP.";
                continue;
            }

            // Créer le mémoire directement publié
            $memoire = new Memoire(
                0, $theme, $resumer, 'anciens/' . $fichier_pdf,
                date('Y-m-d'), 'valide', $mots_cle,
                $annee, $etudiant->getEtudiantId()
            );

            $id = $memoireDAO->createDirect($memoire);

            if ($id) {
                $succes++;
                $details[] = "✔ \"$theme\" — $matricule publié.";
            } else {
                $erreurs++;
                $details[] = "✘ Erreur pour \"$theme\".";
            }
        }

        fclose($handle);
        $message  = "✔ $succes mémoire(s) importé(s). ✘ $erreurs erreur(s).";
        $type_msg = $erreurs > 0 ? 'warning' : 'success';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8"/>
    <title>Importer anciens mémoires</title>
    <link rel="stylesheet" href="../../assets/css/style.css"/>
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
            <h1>Importer anciens mémoires</h1>
            <p>Publication directe sans validation</p>
        </div>
    </div>

    <div class="page-content">

        <?php if ($message): ?>
            <div class="alert alert-<?= $type_msg ?>"><?= $message ?></div>
        <?php endif; ?>

        <?php if (!empty($details)): ?>
            <div class="card" style="margin-bottom:20px;">
                <div class="card-header"><h3>📋 Détails de l'import</h3></div>
                <div class="card-body">
                    <?php foreach ($details as $d): ?>
                        <p style="font-size:12px; margin:4px 0;"><?= htmlspecialchars($d) ?></p>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header"><h3><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-cloud-arrow-up-fill" viewBox="0 0 16 16">
                                <path d="M8 2a5.53 5.53 0 0 0-3.594 1.342c-.766.66-1.321 1.52-1.464 2.383C1.266 6.095 0 7.555 0 9.318 0 11.366 1.708 13 3.781 13h8.906C14.502 13 16 11.57 16 9.773c0-1.636-1.242-2.969-2.834-3.194C12.923 3.999 10.69 2 8 2m2.354 5.146a.5.5 0 0 1-.708.708L8.5 6.707V10.5a.5.5 0 0 1-1 0V6.707L6.354 7.854a.5.5 0 1 1-.708-.708l2-2a.5.5 0 0 1 .708 0z"/>
                            </svg> Import en masse</h3></div>
            <div class="card-body">

                <div class="alert alert-info">
                    <strong>Instructions :</strong><br/>
                    1. Préparez un fichier CSV avec les colonnes : <strong>theme, resumer, mots_cle, annee, matricule, fichier_pdf</strong><br/>
                    2. Mettez tous les PDF dans un fichier ZIP<br/>
                    3. Les noms des PDF dans le CSV doivent correspondre exactement aux noms dans le dossuer<br/>
                    4. Les mémoires seront publiés directement sans validation
                </div>

                <form method="POST" enctype="multipart/form-data">

                    <div class="form-group">
                        <label>Fichier CSV des mémoires *</label>
                        <input type="file" name="csv_memoires" class="form-control" accept=".csv" required/>
                        <small style="color:#888;">Format : theme;resumer;mots_cle;annee;matricule;fichier_pdf</small>
                    </div>

                    <div class="form-group">
                        <label>Fichier ZIP contenant les PDF *</label>
                        <input type="file" name="pdf[]" multiple accept=".pdf" class="form-control" required>
                        <small style="color:#888;">Tous les fichiers PDF doivent être à la racine du ZIP</small>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-cloud-arrow-up-fill" viewBox="0 0 16 16">
                                <path d="M8 2a5.53 5.53 0 0 0-3.594 1.342c-.766.66-1.321 1.52-1.464 2.383C1.266 6.095 0 7.555 0 9.318 0 11.366 1.708 13 3.781 13h8.906C14.502 13 16 11.57 16 9.773c0-1.636-1.242-2.969-2.834-3.194C12.923 3.999 10.69 2 8 2m2.354 5.146a.5.5 0 0 1-.708.708L8.5 6.707V10.5a.5.5 0 0 1-1 0V6.707L6.354 7.854a.5.5 0 1 1-.708-.708l2-2a.5.5 0 0 1 .708 0z"/>
                            </svg> Importer les mémoires
                    </button>

                </form>
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