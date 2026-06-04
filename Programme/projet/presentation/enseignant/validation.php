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
    <link rel="icon" type="image/png" href="<?= $base_url ?>/assets/img/logo_GASA.png">
</head>
<body>
<?php include '../../includes/navbar.php'; ?>

<div class="main-content">
    <div class="topbar">
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
                <h3>📄 Examen du mémoire</h3>
                <a href="validation.php" class="btn btn-outline btn-sm">← Retour</a>
            </div>
            <div class="card-body">
                <p><strong>Thème :</strong> <?= htmlspecialchars($memoireDetail->getTheme()) ?></p>
                <p style="margin-top:10px;"><strong>Résumé :</strong> <?= htmlspecialchars($memoireDetail->getResumer()) ?></p>
                <p style="margin-top:10px;"><strong>Mots-clés :</strong> <?= htmlspecialchars($memoireDetail->getMotsCle()) ?></p>
                <p style="margin-top:10px;"><strong>Année :</strong> <?= htmlspecialchars($memoireDetail->getAnnee()) ?></p>
                <p style="margin-top:10px;">
                    <a href="../../assets/uploads/<?= htmlspecialchars($memoireDetail->getFichierPdf()) ?>"
                       target="_blank" class="btn btn-outline btn-sm">📄 Voir le PDF</a>
                </p>

                <hr style="margin:20px 0; border-color:#F0F0F0;"/>

                <!-- Actions -->
                <div style="display:flex; gap:12px; flex-wrap:wrap;">
                    <!-- Valider -->
                    <a href="?valider=<?= $memoireDetail->getIdMemoir() ?>"
                       class="btn btn-success"
                       onclick="return confirm('Valider ce mémoire ?')">
                        ✅ Valider
                    </a>

                    <!-- Refuser -->
                    <button class="btn btn-danger" onclick="document.getElementById('form-refus').style.display='block'">
                        ❌ Refuser
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
            <div class="card-header"><h3>⏳ Mémoires en attente</h3></div>
            <div class="card-body">
                <?php if (empty($enAttente)): ?>
                    <div class="alert alert-success">✅ Aucun mémoire en attente.</div>
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
                                           onclick="return confirm('Valider ?')">✅</a>
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
</body>
</html>
