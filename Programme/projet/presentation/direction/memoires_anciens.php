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

    // 1. Extraire le ZIP
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
        fgetcsv($handle, 0, ';'); // ignorer en-tête

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
            <div class="card-header"><h3>📥 Import en masse</h3></div>
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
                        📥 Importer les mémoires
                    </button>

                </form>
            </div>
        </div>

    </div>
</div>
</body>
</html>