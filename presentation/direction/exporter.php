<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'direction') {
    header('Location: ../auth/login.php');
    exit;
}


require_once '../../config/db.php';
require_once '../../persistance/PersonneDAO.php';

$dao = new PersonneDAO(getDb());


if (isset($_POST['export_etudiants'])) {

    $donnees = $dao->getEtudiants();

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=etudiants.csv');

    $fp = fopen('php://output', 'w');

    fputcsv($fp,
        ['Matricule','Nom','Prenom','Email'],
        ';'
    );

    foreach ($donnees as $ligne) {
        fputcsv($fp, $ligne, ';');
    }

    fclose($fp);
    exit;
}


if (isset($_POST['export_professeurs'])) {

    $donnees = $dao->getProfesseurs();

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="liste_professeur.csv"');

    $fp = fopen('php://output', 'w');

    fputcsv(
        $fp,
        ['Matricule','Nom','Prenom','Email'],
        ';'
    );

    foreach ($donnees as $ligne) {
        fputcsv($fp, $ligne, ';');
    }

    fclose($fp);
    exit;
}
include '../../includes/header.php'; 
include '../../includes/navbar.php'; 
?>
<head>
    <meta charset="UTF-8"/>
    <title>Dashboard</title>
    <link rel="stylesheet" href="../../assets/css/style.css"/>
    <link rel="icon" type="image/png" href="<?= $base_url ?>/assets/img/logo_GASA.png">
</head>
<div class="main-content">

    <div class="page-content">

        <div class="card">
            <div class="card-header">
                <h3>📥 Exportation des comptes</h3>
            </div>

            <div class="card-body">

                <p>Télécharger la liste des identifiants.</p>

                <form method="post">
                    <button
                        type="submit"
                        name="export_etudiants"
                        class="btn btn-primary">
                        Exporter les étudiants
                    </button>

                    <button
                        type="submit"
                        name="export_professeurs"
                        class="btn btn-primary">
                        Exporter les professeurs
                    </button>
                </form>

            </div>
        </div>

    </div>
</div>