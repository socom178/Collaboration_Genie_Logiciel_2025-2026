<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'etudiant') {
    header('Location: ../auth/login.php');
    exit;
}

require_once '../../config/db.php';
require_once '../../persistance/MemoireDAO.php';
require_once '../../persistance/EtudiantDAO.php';
require_once '../../persistance/NotificationDAO.php';
require_once '../../persistance/ProfesseurDAO.php';
require_once '../../metier/Memoire.php';
require_once '../../metier/Notification.php';

$memoireDAO    = new MemoireDAO(getDb());
$etudiantDAO   = new EtudiantDAO(getDb());
$notifDAO      = new NotificationDAO(getDb());
$professeurDAO = new ProfesseurDAO(getDb());

$professeurs = $professeurDAO->findAll();

$etudiant = $etudiantDAO->findByPersonneId($_SESSION['user_id']);

// Vérifier diplôme
if (!$etudiant || !$etudiant->estDiplome()) {
    header('Location: dashboard.php');
    exit;
}

$message  = '';
$type_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $theme    = trim($_POST['theme'] ?? '');
    $resumer  = trim($_POST['resumer'] ?? '');
    $mots_cle = trim($_POST['mots_cle'] ?? '');
    $annee    = trim($_POST['annee'] ?? date('Y'));
    $prof_id  = $_POST['professeur_id'] ?? null;
    
    // Vérifier que l'année correspond à l'année de diplomation
    $stmtAnnee = getDb()->prepare("
        SELECT annee FROM liste_etudiants_csv 
        WHERE matricule = :matricule AND statut = 'diplome'
    ");
    $stmtAnnee->execute([':matricule' => $etudiant->getMatricule()]);
    $rowAnnee = $stmtAnnee->fetch();

    if (!$rowAnnee) {
        $message  = '⚠️ Votre année de diplomation est introuvable. Contactez l\'administration.';
        $type_msg = 'danger';
    } elseif ($annee != $rowAnnee['annee']) {
        $message  = '⚠️ Vous ne pouvez soumettre un mémoire que pour votre année de diplomation (' . $rowAnnee['annee'] . ').';
        $type_msg = 'danger';
    } elseif ($memoireDAO->existeMemoire($etudiant->getEtudiantId(), $annee)) {
        $message  = '⚠️ Vous avez déjà soumis un mémoire pour l\'année ' . $annee . '.';
        $type_msg = 'danger';
    } else {
        // Upload PDF
        $fichier_pdf = '';

        if (isset($_FILES['fichier_pdf']) && $_FILES['fichier_pdf']['error'] === 0) {

            $ext = strtolower(pathinfo($_FILES['fichier_pdf']['name'], PATHINFO_EXTENSION));

            if ($ext !== 'pdf') {
                $message  = '⚠️ Seuls les fichiers PDF sont acceptés.';
                $type_msg = 'danger';
            } else {

                $nom_fichier = uniqid('memoire_') . '.pdf';
                $destination = __DIR__ . '/../../assets/uploads/' . $nom_fichier;

                if (move_uploaded_file($_FILES['fichier_pdf']['tmp_name'], $destination)) {
                    $fichier_pdf = $nom_fichier;
                } else {
                    $message  = '⚠️ Erreur lors de l\'upload du fichier.';
                    $type_msg = 'danger';
                }
            }

        } else {
            $message  = '⚠️ Veuillez sélectionner un fichier PDF.';
            $type_msg = 'danger';
        }

        // =========================
        // 🔥 CREATION MEMOIRE
        // =========================
        if ($fichier_pdf && !$message) {

            $memoire = new Memoire(
                0,
                $theme,
                $resumer,
                $fichier_pdf,
                date('Y-m-d'),
                'en_attente',
                $mots_cle,
                $annee,
                $etudiant->getEtudiantId()
            );

            $id = $memoireDAO->create($memoire);

            if ($id) {

                $notif = new Notification(
                    0,
                    'Dépôt réussi',
                    'Votre mémoire "' . $theme . '" a été soumis avec succès et est en attente de validation.',
                    '',
                    false,
                    $_SESSION['user_id']
                );
                $notifDAO->create($notif);

                require_once __DIR__ .'/../../service/MailerService.php';

                $email = $etudiant->getEmail();
                $nom   = $etudiant->getNom();

                MailerService::sendMail(
                    $email,
                    "Soumission de mémoire réussie",
                    "
                        <div style='font-family:Arial'>
                            <h2>Bonjour $nom 👋</h2>

                            <p>Votre mémoire a été soumis avec succès.</p>

                            <p><b>Thème :</b> $theme</p>

                            <p>Status : <b>En attente de validation</b></p>

                            <hr>
                            <small>Plateforme de gestion des mémoires</small>
                        </div>
                    "
                );

                $message  = '✅ Mémoire soumis avec succès !';
                $type_msg = 'success';

            } else {
                $message  = '⚠️ Une erreur est survenue lors de la soumission.';
                $type_msg = 'danger';
            }
        }
    }    
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8"/>
    <title>Soumettre un mémoire</title>
    <link rel="stylesheet" href="../../assets/css/style.css"/>
    <link rel="icon" type="image/png" href="<?= $base_url ?>/assets/img/logo_GASA.png">
</head>
<body>
<?php include '../../includes/navbar.php'; ?>

<div class="main-content">
    <div class="topbar">
        <div class="topbar-title">
            <h1>Soumettre un mémoire</h1>
            <p>Remplissez le formulaire ci-dessous</p>
        </div>
    </div>

    <div class="page-content">

        <?php if ($message): ?>
            <div class="alert alert-<?= $type_msg ?>"><?= $message ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header"><h3>📤 Nouveau mémoire</h3></div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">

                    <div class="form-group">
                        <label>Thème du mémoire *</label>
                        <input type="text" name="theme" class="form-control"
                               placeholder="Ex : Conception d'une application..."
                               value="<?= htmlspecialchars($_POST['theme'] ?? '') ?>"
                               required/>
                    </div>

                    <div class="form-group">
                        <label>Résumé *</label>
                        <textarea name="resumer" class="form-control" rows="5"
                                  placeholder="Résumé de votre mémoire..."
                                  required><?= htmlspecialchars($_POST['resumer'] ?? '') ?></textarea>
                    </div>

                    <div class="form-group">
                        <label>Mots-clés</label>
                        <input type="text" name="mots_cle" class="form-control"
                               placeholder="Ex : base de données, PHP, web..."
                               value="<?= htmlspecialchars($_POST['mots_cle'] ?? '') ?>"/>
                    </div>

                    <div class="form-group">
                        <label>Année de soutenance *</label>
                        <input type="number" name="annee" class="form-control"
                               min="1992" max="<?= date('Y') ?>"
                               value="<?= htmlspecialchars($_POST['annee'] ?? date('Y')) ?>"
                               required/>
                    </div>

                    <div class="form-group">
                        <label>Professeur responsable *</label>
                        <select name="professeur_id" class="form-control" required>
                            <option value="">-- Choisir un professeur --</option>
                            <?php foreach ($professeurs as $prof): ?>
                                <option value="<?= $prof->getProfesseurId() ?>">
                                    <?= htmlspecialchars($prof->getNomComplet()) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Fichier PDF du mémoire *</label>
                        <input type="file" name="fichier_pdf" class="form-control"
                               accept=".pdf" required/>
                        <small style="color:#888; font-size:12px;">Format accepté : PDF uniquement</small>
                    </div>

                    <div style="display:flex; gap:10px; margin-top:8px;">
                        <button type="submit" class="btn btn-primary">📤 Soumettre le mémoire</button>
                        <a href="dashboard.php" class="btn btn-outline">Annuler</a>
                    </div>

                </form>
            </div>
        </div>

    </div>
</div>
</body>
</html>