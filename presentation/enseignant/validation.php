<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'professeur') {
    header('Location: ../auth/login.php'); exit;
}

require_once '../../config/db.php';
require_once '../../persistance/MemoireDAO.php';
require_once '../../persistance/NotificationDAO.php';
require_once '../../metier/Notification.php';

$memoireDAO = new MemoireDAO(getDb());
$notifDAO   = new NotificationDAO(getDb());

$message  = '';
$type_msg = '';

// Récupérer l'id du professeur depuis la session
$professeur_id = $_SESSION['professeur_id'];
// Valider un mémoire
if (isset($_GET['valider'])) {
    $id = (int)$_GET['valider'];
    $memoire = $memoireDAO->findById($id);
    if ($memoire) {
        $memoireDAO->valider($id, $professeur_id);
        $stmt = getDb()->prepare("SELECT personne_id FROM etudiant WHERE id = :id");
        $stmt->execute([':id' => $memoire->getEtudiantId()]);
        $row = $stmt->fetch();
        $personne_id_etudiant = $row['personne_id'];

        $notif = new Notification(0, 'Mémoire validé', 'Votre mémoire "' . $memoire->getTheme() . '" a été validé et publié.', '', false, $personne_id_etudiant);
        $notifDAO->create($notif);
        $message  = '✅ Mémoire validé et publié avec succès.';
        $type_msg = 'success';
    }
}

// Refuser un mémoire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['refuser'])) {
    $id    = (int)$_POST['memoire_id'];
    $motif = trim($_POST['motif'] ?? '');
    $memoire = $memoireDAO->findById($id);
    if ($memoire) {
        $memoireDAO->refuser($id, $professeur_id, $motif);
        // Notifier l'étudiant
        $stmt = getDb()->prepare("SELECT personne_id FROM etudiant WHERE id = :id");
        $stmt->execute([':id' => $memoire->getEtudiantId()]);
        $row = $stmt->fetch();
        $personne_id_etudiant = $row['personne_id'];
        
        $notif = new Notification(0, 'Mémoire refusé', 'Votre mémoire "' . $memoire->getTheme() . '" a été refusé. Motif :'. $motif, '', false, $personne_id_etudiant);        $notifDAO->create($notif);
        $message  = '❌ Mémoire refusé.';
        $type_msg = 'danger';
    }
}

$enAttente = $memoireDAO->findByProfesseur($_SESSION['professeur_id']);

// Voir un mémoire en détail
$memoireDetail = null;
if (isset($_GET['id'])) {
    $memoireDetail = $memoireDAO->findById((int)$_GET['id']);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8"/>
    <title>Validation des mémoires</title>
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
            <h1>Validation des mémoires</h1>
            <p><?= count($enAttente) ?> mémoire(s) en attente</p>
        </div>
    </div>

    <div class="page-content">

        <?php if ($message): ?>
            <div class="alert alert-<?= $type_msg ?>"><?= $message ?></div>
        <?php endif; ?>

        <?php if ($memoireDetail): ?>
        <!-- Détail du mémoire à examiner -->
        <div class="card" style="margin-bottom:20px;">
            <div class="card-header">
                <h3>
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-file-pdf-fill" viewBox="0 0 16 16">
  <path d="M5.523 10.424q.21-.124.459-.238a8 8 0 0 1-.45.606c-.28.337-.498.516-.635.572l-.035.012a.3.3 0 0 1-.026-.044c-.056-.11-.054-.216.04-.36.106-.165.319-.354.647-.548m2.455-1.647q-.178.037-.356.078a21 21 0 0 0 .5-1.05 12 12 0 0 0 .51.858q-.326.048-.654.114m2.525.939a4 4 0 0 1-.435-.41q.344.007.612.054c.317.057.466.147.518.209a.1.1 0 0 1 .026.064.44.44 0 0 1-.06.2.3.3 0 0 1-.094.124.1.1 0 0 1-.069.015c-.09-.003-.258-.066-.498-.256M8.278 4.97c-.04.244-.108.524-.2.829a5 5 0 0 1-.089-.346c-.076-.353-.087-.63-.046-.822.038-.177.11-.248.196-.283a.5.5 0 0 1 .145-.04c.013.03.028.092.032.198q.008.183-.038.465z"/>
  <path fill-rule="evenodd" d="M4 0h8a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V2a2 2 0 0 1 2-2m.165 11.668c.09.18.23.343.438.419.207.075.412.04.58-.03.318-.13.635-.436.926-.786.333-.401.683-.927 1.021-1.51a11.6 11.6 0 0 1 1.997-.406c.3.383.61.713.91.95.28.22.603.403.934.417a.86.86 0 0 0 .51-.138c.155-.101.27-.247.354-.416.09-.181.145-.37.138-.563a.84.84 0 0 0-.2-.518c-.226-.27-.596-.4-.96-.465a5.8 5.8 0 0 0-1.335-.05 11 11 0 0 1-.98-1.686c.25-.66.437-1.284.52-1.794.036-.218.055-.426.048-.614a1.24 1.24 0 0 0-.127-.538.7.7 0 0 0-.477-.365c-.202-.043-.41 0-.601.077-.377.15-.576.47-.651.823-.073.34-.04.736.046 1.136.088.406.238.848.43 1.295a20 20 0 0 1-1.062 2.227 7.7 7.7 0 0 0-1.482.645c-.37.22-.699.48-.897.787-.21.326-.275.714-.08 1.103"/>
</svg> Examen du mémoire</h3>
                <a href="validation.php" class="btn btn-outline btn-sm">← Retour</a>
            </div>
            <div class="card-body">
                <p><strong>Thème :</strong> <?= htmlspecialchars($memoireDetail->getTheme()) ?></p>
                <p style="margin-top:10px;"><strong>Résumé :</strong> <?= htmlspecialchars($memoireDetail->getResumer()) ?></p>
                <p style="margin-top:10px;"><strong>Mots-clés :</strong> <?= htmlspecialchars($memoireDetail->getMotsCle()) ?></p>
                <p style="margin-top:10px;"><strong>Année :</strong> <?= htmlspecialchars($memoireDetail->getAnnee()) ?></p>
                <p style="margin-top:10px;">
                    <a href="../../assets/uploads/<?= htmlspecialchars($memoireDetail->getFichierPdf()) ?>"
                       target="_blank" class="btn btn-outline btn-sm"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-file-pdf-fill" viewBox="0 0 16 16">
  <path d="M5.523 10.424q.21-.124.459-.238a8 8 0 0 1-.45.606c-.28.337-.498.516-.635.572l-.035.012a.3.3 0 0 1-.026-.044c-.056-.11-.054-.216.04-.36.106-.165.319-.354.647-.548m2.455-1.647q-.178.037-.356.078a21 21 0 0 0 .5-1.05 12 12 0 0 0 .51.858q-.326.048-.654.114m2.525.939a4 4 0 0 1-.435-.41q.344.007.612.054c.317.057.466.147.518.209a.1.1 0 0 1 .026.064.44.44 0 0 1-.06.2.3.3 0 0 1-.094.124.1.1 0 0 1-.069.015c-.09-.003-.258-.066-.498-.256M8.278 4.97c-.04.244-.108.524-.2.829a5 5 0 0 1-.089-.346c-.076-.353-.087-.63-.046-.822.038-.177.11-.248.196-.283a.5.5 0 0 1 .145-.04c.013.03.028.092.032.198q.008.183-.038.465z"/>
  <path fill-rule="evenodd" d="M4 0h8a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V2a2 2 0 0 1 2-2m.165 11.668c.09.18.23.343.438.419.207.075.412.04.58-.03.318-.13.635-.436.926-.786.333-.401.683-.927 1.021-1.51a11.6 11.6 0 0 1 1.997-.406c.3.383.61.713.91.95.28.22.603.403.934.417a.86.86 0 0 0 .51-.138c.155-.101.27-.247.354-.416.09-.181.145-.37.138-.563a.84.84 0 0 0-.2-.518c-.226-.27-.596-.4-.96-.465a5.8 5.8 0 0 0-1.335-.05 11 11 0 0 1-.98-1.686c.25-.66.437-1.284.52-1.794.036-.218.055-.426.048-.614a1.24 1.24 0 0 0-.127-.538.7.7 0 0 0-.477-.365c-.202-.043-.41 0-.601.077-.377.15-.576.47-.651.823-.073.34-.04.736.046 1.136.088.406.238.848.43 1.295a20 20 0 0 1-1.062 2.227 7.7 7.7 0 0 0-1.482.645c-.37.22-.699.48-.897.787-.21.326-.275.714-.08 1.103"/>
</svg> Voir le PDF</a>
                </p>

                <hr style="margin:20px 0; border-color:#F0F0F0;"/>

                <!-- Actions -->
                <div style="display:flex; gap:12px; flex-wrap:wrap;">
                    <!-- Valider -->
                    <a href="?valider=<?= $memoireDetail->getIdMemoir() ?>"
                       class="btn btn-success"
                       onclick="return confirm('Valider ce mémoire ?')">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-check-lg" viewBox="0 0 16 16">
                                <path d="M12.736 3.97a.733.733 0 0 1 1.047 0c.286.289.29.756.01 1.05L7.88 12.01a.733.733 0 0 1-1.065.02L3.217 8.384a.757.757 0 0 1 0-1.06.733.733 0 0 1 1.047 0l3.052 3.093 5.4-6.425z"/>
                            </svg> Valider
                    </a>

                    <!-- Refuser -->
                    <button class="btn btn-danger" onclick="document.getElementById('form-refus').style.display='block'">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-x-circle" viewBox="0 0 16 16">
                            <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16"/>
                            <path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708"/>
                        </svg> Refuser
                    </button>
                </div>

                <!-- Formulaire refus -->
                <form method="POST" id="form-refus" style="display:none; margin-top:16px;">
                    <input type="hidden" name="memoire_id" value="<?= $memoireDetail->getIdMemoir() ?>"/>
                    <div class="form-group">
                        <label>Motif du refus</label>
                        <textarea name="motif" class="form-control" rows="3" placeholder="Expliquez la raison du refus..." required></textarea>
                    </div>
                    <button type="submit" name="refuser" class="btn btn-danger">Confirmer le refus</button>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <!-- Liste des mémoires en attente -->
        <div class="card">
            <div class="card-header"><h3><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-hourglass-split" viewBox="0 0 16 16">
                            <path d="M2.5 15a.5.5 0 1 1 0-1h1v-1a4.5 4.5 0 0 1 2.557-4.06c.29-.139.443-.377.443-.59v-.7c0-.213-.154-.451-.443-.59A4.5 4.5 0 0 1 3.5 3V2h-1a.5.5 0 0 1 0-1h11a.5.5 0 0 1 0 1h-1v1a4.5 4.5 0 0 1-2.557 4.06c-.29.139-.443.377-.443.59v.7c0 .213.154.451.443.59A4.5 4.5 0 0 1 12.5 13v1h1a.5.5 0 0 1 0 1zm2-13v1c0 .537.12 1.045.337 1.5h6.326c.216-.455.337-.963.337-1.5V2zm3 6.35c0 .701-.478 1.236-1.011 1.492A3.5 3.5 0 0 0 4.5 13s.866-1.299 3-1.48zm1 0v3.17c2.134.181 3 1.48 3 1.48a3.5 3.5 0 0 0-1.989-3.158C8.978 9.586 8.5 9.052 8.5 8.351z"/>
                        </svg> Mémoires en attente</h3></div>
            <div class="card-body">
                <?php if (empty($enAttente)): ?>
                    <div class="alert alert-success"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-check-lg" viewBox="0 0 16 16">
                                <path d="M12.736 3.97a.733.733 0 0 1 1.047 0c.286.289.29.756.01 1.05L7.88 12.01a.733.733 0 0 1-1.065.02L3.217 8.384a.757.757 0 0 1 0-1.06.733.733 0 0 1 1.047 0l3.052 3.093 5.4-6.425z"/>
                            </svg> Aucun mémoire en attente.</div>
                <?php else: ?>
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr><th>Thème</th><th>Année</th><th>Date soumission</th><th>Actions</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($enAttente as $m): ?>
                                <tr>
                                    <td><?= htmlspecialchars(substr($m->getTheme(), 0, 50)) ?>...</td>
                                    <td><?= htmlspecialchars($m->getAnnee()) ?></td>
                                    <td><?= htmlspecialchars($m->getDateSoumission()) ?></td>
                                    <td style="display:flex; gap:6px;">
                                        <a href="?id=<?= $m->getIdMemoir() ?>" class="btn btn-primary btn-sm">Examiner</a>
                                        <a href="?valider=<?= $m->getIdMemoir() ?>" class="btn btn-success btn-sm"
                                           onclick="return confirm('Valider ?')"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-check-lg" viewBox="0 0 16 16">
                                <path d="M12.736 3.97a.733.733 0 0 1 1.047 0c.286.289.29.756.01 1.05L7.88 12.01a.733.733 0 0 1-1.065.02L3.217 8.384a.757.757 0 0 1 0-1.06.733.733 0 0 1 1.047 0l3.052 3.093 5.4-6.425z"/>
                            </svg></a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
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
