<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'professeur') {
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

$message  = '';
$type_msg = '';

// Ajouter un commentaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['commenter'])) {
    $contenu    = trim($_POST['contenu'] ?? '');
    $memoire_id = (int)$_POST['memoire_id'];
    if ($contenu) {
        $c = new Commentaire(0, $contenu, '', $_SESSION['user_id'], $memoire_id);
        $commentaireDAO->create($c);
        $message  = '✅ Commentaire ajouté.';
        $type_msg = 'success';
    }
}

$filtre   = $_GET['statut'] ?? 'valide';
$memoires = $filtre === 'tous' ? $memoireDAO->findAll() : $memoireDAO->findByStatut($filtre);

$memoireDetail   = null;
$commentaires    = [];
if (isset($_GET['id'])) {
    $memoireDetail = $memoireDAO->findById((int)$_GET['id']);
    $commentaires  = $commentaireDAO->findByMemoire((int)$_GET['id']);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8"/>
    <title>Mémoires — Enseignant</title>
    <link rel="stylesheet" href="../../assets/css/style.css"/>
    <link rel="icon" type="image/png" href="<?= $base_url ?>/assets/img/logo_GASA.png">
</head>
<body>
<?php include '../../includes/navbar.php'; ?>

<div class="main-content">
    <div class="topbar">
        <div class="topbar-title">
            <h1>Mémoires</h1>
            <p><?= count($memoires) ?> mémoire(s)</p>
        </div>
    </div>

    <div class="page-content">

        <?php if ($message): ?>
            <div class="alert alert-<?= $type_msg ?>"><?= $message ?></div>
        <?php endif; ?>

        <?php if ($memoireDetail): ?>
        <!-- Détail + commentaires -->
        <div class="card" style="margin-bottom:20px;">
            <div class="card-header">
                <h3>📄 <?= htmlspecialchars($memoireDetail->getTheme()) ?></h3>
                <a href="memoires.php" class="btn btn-outline btn-sm">← Retour</a>
            </div>
            <div class="card-body">
                <p><strong>Résumé :</strong> <?= htmlspecialchars($memoireDetail->getResumer()) ?></p>
                <p style="margin-top:8px;"><strong>Mots-clés :</strong> <?= htmlspecialchars($memoireDetail->getMotsCle()) ?></p>
                <p style="margin-top:8px;"><strong>Année :</strong> <?= htmlspecialchars($memoireDetail->getAnnee()) ?></p>
                <p style="margin-top:8px;">
                    <span class="badge badge-<?= $memoireDetail->getStatut() ?>">
                        <?= match($memoireDetail->getStatut()) {
                            'en_attente' => 'En attente', 'valide' => 'Validé', 'refuse' => 'Refusé', default => ''
                        } ?>
                    </span>
                </p>
                <p style="margin-top:12px;">
                    <a href="detail.php?id=<?= $m->getIdMemoir() ?>" class="btn btn-outline btn-sm">👁 Consulter</a>
                </p>

                <hr style="margin:20px 0; border-color:#F0F0F0;"/>

                <!-- Commentaires -->
                <h4 style="color:#1A237E; margin-bottom:12px;">Commentaires</h4>
                <div class="comment-list">
                    <?php if (empty($commentaires)): ?>
                        <p style="color:#888; font-size:13px;">Aucun commentaire pour l'instant.</p>
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

                <!-- Formulaire commentaire -->
                <form method="POST" style="margin-top:16px;">
                    <input type="hidden" name="memoire_id" value="<?= $memoireDetail->getIdMemoir() ?>"/>
                    <div class="form-group">
                        <label>Ajouter un commentaire</label>
                        <textarea name="contenu" class="form-control" rows="3" placeholder="Votre commentaire..." required></textarea>
                    </div>
                    <button type="submit" name="commenter" class="btn btn-primary btn-sm">Commenter</button>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <!-- Filtres -->
        <div style="display:flex; gap:10px; margin-bottom:20px;">
            <a href="?statut=tous"       class="btn <?= $filtre==='tous'       ? 'btn-primary' : 'btn-outline' ?>">Tous</a>
            <a href="?statut=valide"     class="btn <?= $filtre==='valide'     ? 'btn-primary' : 'btn-outline' ?>">Validés</a>
            <a href="?statut=en_attente" class="btn <?= $filtre==='en_attente' ? 'btn-primary' : 'btn-outline' ?>">En attente</a>
            <a href="?statut=refuse"     class="btn <?= $filtre==='refuse'     ? 'btn-primary' : 'btn-outline' ?>">Refusés</a>
        </div>

        <!-- Liste -->
        <div class="memoires-grid">
            <?php if (empty($memoires)): ?>
                <div class="alert alert-info">Aucun mémoire trouvé.</div>
            <?php else: ?>
                <?php foreach ($memoires as $m): ?>
                <div class="memoire-card">
                    <h4><?= htmlspecialchars($m->getTheme()) ?></h4>
                    <p class="meta">📅 <?= htmlspecialchars($m->getAnnee()) ?></p>
                    <p class="meta">🏷 <?= htmlspecialchars($m->getMotsCle()) ?></p>
                    <span class="badge badge-<?= $m->getStatut() ?>">
                        <?= match($m->getStatut()) {
                            'en_attente' => 'En attente', 'valide' => 'Validé', 'refuse' => 'Refusé', default => ''
                        } ?>
                    </span>
                    <div class="actions">
                        <a href="detail.php?id=<?= $m->getIdMemoir() ?>" class="btn btn-primary btn-sm">👁 Consulter</a>
                    </div>
                    <p class="meta">❤️ <?= $likeDAO->compter($m->getIdMemoir()) ?></p>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </div>
</div>
</body>
</html>
