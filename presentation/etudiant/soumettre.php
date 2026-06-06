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
        $message  = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-exclamation-triangle" viewBox="0 0 16 16">
  <path d="M7.938 2.016A.13.13 0 0 1 8.002 2a.13.13 0 0 1 .063.016.15.15 0 0 1 .054.057l6.857 11.667c.036.06.035.124.002.183a.2.2 0 0 1-.054.06.1.1 0 0 1-.066.017H1.146a.1.1 0 0 1-.066-.017.2.2 0 0 1-.054-.06.18.18 0 0 1 .002-.183L7.884 2.073a.15.15 0 0 1 .054-.057m1.044-.45a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767z"/>
  <path d="M7.002 12a1 1 0 1 1 2 0 1 1 0 0 1-2 0M7.1 5.995a.905.905 0 1 1 1.8 0l-.35 3.507a.552.552 0 0 1-1.1 0z"/>
</svg> Votre année de diplomation est introuvable. Contactez l\'administration.';
        $type_msg = 'danger';
    } elseif ($annee != $rowAnnee['annee']) {
        $message  = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-exclamation-triangle" viewBox="0 0 16 16">
  <path d="M7.938 2.016A.13.13 0 0 1 8.002 2a.13.13 0 0 1 .063.016.15.15 0 0 1 .054.057l6.857 11.667c.036.06.035.124.002.183a.2.2 0 0 1-.054.06.1.1 0 0 1-.066.017H1.146a.1.1 0 0 1-.066-.017.2.2 0 0 1-.054-.06.18.18 0 0 1 .002-.183L7.884 2.073a.15.15 0 0 1 .054-.057m1.044-.45a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767z"/>
  <path d="M7.002 12a1 1 0 1 1 2 0 1 1 0 0 1-2 0M7.1 5.995a.905.905 0 1 1 1.8 0l-.35 3.507a.552.552 0 0 1-1.1 0z"/>
</svg> Vous ne pouvez soumettre un mémoire que pour votre année de diplomation (' . $rowAnnee['annee'] . ').';
        $type_msg = 'danger';
    } elseif ($memoireDAO->existeMemoire($etudiant->getEtudiantId(), $annee)) {
        $message  = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-exclamation-triangle" viewBox="0 0 16 16">
  <path d="M7.938 2.016A.13.13 0 0 1 8.002 2a.13.13 0 0 1 .063.016.15.15 0 0 1 .054.057l6.857 11.667c.036.06.035.124.002.183a.2.2 0 0 1-.054.06.1.1 0 0 1-.066.017H1.146a.1.1 0 0 1-.066-.017.2.2 0 0 1-.054-.06.18.18 0 0 1 .002-.183L7.884 2.073a.15.15 0 0 1 .054-.057m1.044-.45a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767z"/>
  <path d="M7.002 12a1 1 0 1 1 2 0 1 1 0 0 1-2 0M7.1 5.995a.905.905 0 1 1 1.8 0l-.35 3.507a.552.552 0 0 1-1.1 0z"/>
</svg> Vous avez déjà soumis un mémoire pour l\'année ' . $annee . '.';
        $type_msg = 'danger';
    } else {
        // Upload PDF
        $fichier_pdf = '';

        if (isset($_FILES['fichier_pdf']) && $_FILES['fichier_pdf']['error'] === 0) {

            $ext = strtolower(pathinfo($_FILES['fichier_pdf']['name'], PATHINFO_EXTENSION));

            if ($ext !== 'pdf') {
                $message  = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-exclamation-triangle" viewBox="0 0 16 16">
  <path d="M7.938 2.016A.13.13 0 0 1 8.002 2a.13.13 0 0 1 .063.016.15.15 0 0 1 .054.057l6.857 11.667c.036.06.035.124.002.183a.2.2 0 0 1-.054.06.1.1 0 0 1-.066.017H1.146a.1.1 0 0 1-.066-.017.2.2 0 0 1-.054-.06.18.18 0 0 1 .002-.183L7.884 2.073a.15.15 0 0 1 .054-.057m1.044-.45a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767z"/>
  <path d="M7.002 12a1 1 0 1 1 2 0 1 1 0 0 1-2 0M7.1 5.995a.905.905 0 1 1 1.8 0l-.35 3.507a.552.552 0 0 1-1.1 0z"/>
</svg> Seuls les fichiers PDF sont acceptés.';
                $type_msg = 'danger';
            } else {

                $nom_fichier = uniqid('memoire_') . '.pdf';
                $destination = __DIR__ . '/../../assets/uploads/' . $nom_fichier;

                if (move_uploaded_file($_FILES['fichier_pdf']['tmp_name'], $destination)) {
                    $fichier_pdf = $nom_fichier;
                } else {
                    $message  = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-exclamation-triangle" viewBox="0 0 16 16">
  <path d="M7.938 2.016A.13.13 0 0 1 8.002 2a.13.13 0 0 1 .063.016.15.15 0 0 1 .054.057l6.857 11.667c.036.06.035.124.002.183a.2.2 0 0 1-.054.06.1.1 0 0 1-.066.017H1.146a.1.1 0 0 1-.066-.017.2.2 0 0 1-.054-.06.18.18 0 0 1 .002-.183L7.884 2.073a.15.15 0 0 1 .054-.057m1.044-.45a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767z"/>
  <path d="M7.002 12a1 1 0 1 1 2 0 1 1 0 0 1-2 0M7.1 5.995a.905.905 0 1 1 1.8 0l-.35 3.507a.552.552 0 0 1-1.1 0z"/>
</svg> Erreur lors de l\'upload du fichier.';
                    $type_msg = 'danger';
                }
            }

        } else {
            $message  = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-exclamation-triangle" viewBox="0 0 16 16">
  <path d="M7.938 2.016A.13.13 0 0 1 8.002 2a.13.13 0 0 1 .063.016.15.15 0 0 1 .054.057l6.857 11.667c.036.06.035.124.002.183a.2.2 0 0 1-.054.06.1.1 0 0 1-.066.017H1.146a.1.1 0 0 1-.066-.017.2.2 0 0 1-.054-.06.18.18 0 0 1 .002-.183L7.884 2.073a.15.15 0 0 1 .054-.057m1.044-.45a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767z"/>
  <path d="M7.002 12a1 1 0 1 1 2 0 1 1 0 0 1-2 0M7.1 5.995a.905.905 0 1 1 1.8 0l-.35 3.507a.552.552 0 0 1-1.1 0z"/>
</svg> Veuillez sélectionner un fichier PDF.';
            $type_msg = 'danger';
        }

        
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
                $message  = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-exclamation-triangle" viewBox="0 0 16 16">
  <path d="M7.938 2.016A.13.13 0 0 1 8.002 2a.13.13 0 0 1 .063.016.15.15 0 0 1 .054.057l6.857 11.667c.036.06.035.124.002.183a.2.2 0 0 1-.054.06.1.1 0 0 1-.066.017H1.146a.1.1 0 0 1-.066-.017.2.2 0 0 1-.054-.06.18.18 0 0 1 .002-.183L7.884 2.073a.15.15 0 0 1 .054-.057m1.044-.45a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767z"/>
  <path d="M7.002 12a1 1 0 1 1 2 0 1 1 0 0 1-2 0M7.1 5.995a.905.905 0 1 1 1.8 0l-.35 3.507a.552.552 0 0 1-1.1 0z"/>
</svg> Une erreur est survenue lors de la soumission.';
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
    <link rel="icon" type="image/png" href="<?= $base_url ?>../../assets/img/logo_GASA.png">
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
            <h1>Soumettre un mémoire</h1>
            <p>Remplissez le formulaire ci-dessous</p>
        </div>
    </div>

    <div class="page-content">

        <?php if ($message): ?>
            <div class="alert alert-<?= $type_msg ?>"><?= $message ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header"><h3><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-cloud-arrow-up-fill" viewBox="0 0 16 16">
                                <path d="M8 2a5.53 5.53 0 0 0-3.594 1.342c-.766.66-1.321 1.52-1.464 2.383C1.266 6.095 0 7.555 0 9.318 0 11.366 1.708 13 3.781 13h8.906C14.502 13 16 11.57 16 9.773c0-1.636-1.242-2.969-2.834-3.194C12.923 3.999 10.69 2 8 2m2.354 5.146a.5.5 0 0 1-.708.708L8.5 6.707V10.5a.5.5 0 0 1-1 0V6.707L6.354 7.854a.5.5 0 1 1-.708-.708l2-2a.5.5 0 0 1 .708 0z"/>
                            </svg> Nouveau mémoire</h3></div>
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
                        <button type="submit" class="btn btn-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-cloud-arrow-up-fill" viewBox="0 0 16 16">
                                <path d="M8 2a5.53 5.53 0 0 0-3.594 1.342c-.766.66-1.321 1.52-1.464 2.383C1.266 6.095 0 7.555 0 9.318 0 11.366 1.708 13 3.781 13h8.906C14.502 13 16 11.57 16 9.773c0-1.636-1.242-2.969-2.834-3.194C12.923 3.999 10.69 2 8 2m2.354 5.146a.5.5 0 0 1-.708.708L8.5 6.707V10.5a.5.5 0 0 1-1 0V6.707L6.354 7.854a.5.5 0 1 1-.708-.708l2-2a.5.5 0 0 1 .708 0z"/>
                            </svg> Soumettre le mémoire
                        </button>
                        <a href="dashboard.php" class="btn btn-outline">Annuler</a>
                    </div>

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