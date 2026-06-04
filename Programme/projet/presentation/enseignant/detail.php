<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php'); exit;
}

require_once '../../config/db.php';
require_once '../../persistance/MemoireDAO.php';
require_once '../../persistance/CommentaireDAO.php';
require_once '../../metier/Commentaire.php';
require_once '../../persistance/LikeDAO.php';

$likeDAO = new LikeDAO(getDb());



$memoireDAO     = new MemoireDAO(getDb());
$commentaireDAO = new CommentaireDAO(getDb());

if (!isset($_GET['id'])) {
    header('Location: memoires.php'); exit;
}

$memoire = $memoireDAO->findById((int)$_GET['id']);

if (!$memoire || $memoire->getStatut() !== 'valide') {
    header('Location: memoires.php'); exit;
}
// Traitement du like
if (isset($_GET['like'])) {
    $likeDAO->toggleLike($_SESSION['user_id'], $memoire->getIdMemoir());
    header('Location: detail.php?id=' . $memoire->getIdMemoir());
    exit;
}
$nbLikes = $likeDAO->compter($memoire->getIdMemoir());
$aLike   = $likeDAO->aLike($_SESSION['user_id'], $memoire->getIdMemoir());

// Ajouter un commentaire
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['commenter'])) {
    $contenu = trim($_POST['contenu'] ?? '');
    if ($contenu) {
        $c = new Commentaire(0, $contenu, '', $_SESSION['user_id'], $memoire->getIdMemoir());
        $commentaireDAO->create($c);
        header('Location: detail.php?id=' . $memoire->getIdMemoir());
        exit;
    }
}

$commentaires = $commentaireDAO->findByMemoire($memoire->getIdMemoir());
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8"/>
    <title><?= htmlspecialchars($memoire->getTheme()) ?></title>
    <link rel="stylesheet" href="../../assets/css/style.css"/>
</head>
<body>
<?php include '../../includes/navbar.php'; ?>

<div class="main-content">
    <div class="topbar">
        <div class="topbar-title">
            <h1>Détail du mémoire</h1>
        </div>
    </div>

    <div class="page-content">

        <div class="card" style="margin-bottom:20px;">
            <div class="card-header">
                <h3>📄 <?= htmlspecialchars($memoire->getTheme()) ?></h3>
                <a href="memoires.php" class="btn btn-outline btn-sm">← Retour</a>
            </div>
            <div class="card-body">
                <p><strong>Résumé :</strong> <?= htmlspecialchars($memoire->getResumer()) ?></p>
                <p style="margin-top:8px;"><strong>Mots-clés :</strong> <?= htmlspecialchars($memoire->getMotsCle()) ?></p>
                <p style="margin-top:8px;"><strong>Année :</strong> <?= htmlspecialchars($memoire->getAnnee()) ?></p>
                <div style="margin-top:16px; display:flex; align-items:center; gap:12px;">
                    <button 
                        id="btn-like"
                        onclick="toggleLike(<?= $memoire->getIdMemoir() ?>)"
                        class="btn <?= $aLike ? 'btn-danger' : 'btn-outline' ?>">
                        <span id="like-icon"><?= $aLike ? '❤️' : '🤍' ?></span>
                        <span id="like-count"><?= $nbLikes ?></span> like(s)
                    </button>
                </div>

                <!-- Viewer PDF intégré — pas de téléchargement -->
                <div style="margin-top:16px;">
                    <strong>Contenu du mémoire :</strong>
                    <iframe
                        src="../../assets/uploads/<?= htmlspecialchars($memoire->getFichierPdf()) ?>#toolbar=0&navpanes=0&scrollbar=0"
                        width="100%"
                        height="600px"
                        style="border:1px solid #E0E0E0; border-radius:8px; margin-top:8px;"
                        type="application/pdf">
                        <p>Votre navigateur ne supporte pas l'affichage PDF.</p>
                    </iframe>
                </div>
            </div>
        </div>

        <!-- Commentaires -->
        <div class="card">
            <div class="card-header">
                <h3>💬 Commentaires (<?= count($commentaires) ?>)</h3>
            </div>
            <div class="card-body">
                <div class="comment-list">
                    <?php if (empty($commentaires)): ?>
                        <p style="color:#888; font-size:13px;">Aucun commentaire. Soyez le premier !</p>
                    <?php else: ?>
                        <?php foreach ($commentaires as $c): ?>
                        <div class="comment-item">
                            <span class="comment-author"><?= htmlspecialchars($c->auteur) ?></span>
                            <span class="comment-date"><?= htmlspecialchars($c->getDateEnvoie()) ?></span>
                            <p class="comment-text"><?= htmlspecialchars($c->getContenu()) ?></p>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <form method="POST" style="margin-top:16px;">
                    <div class="form-group">
                        <label>Laisser un commentaire</label>
                        <textarea name="contenu" class="form-control" rows="3"
                                  placeholder="Votre commentaire..." required></textarea>
                    </div>
                    <button type="submit" name="commenter" class="btn btn-primary btn-sm">
                        💬 Commenter
                    </button>
                </form>
            </div>
        </div>

    </div>
</div>
<script src="detail.js"></script>
</body>
</html>