<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'etudiant') {
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
        header('Location: memoires.php?id=' . $memoire_id . '&msg=commente');
        exit;
    }
}

// Recherche
$recherche = trim($_GET['q'] ?? '');
if ($recherche) {
    $memoires = $memoireDAO->rechercher($recherche);
} else {
    $memoires = $memoireDAO->findByStatut('valide');
}

// Détail d'un mémoire
$memoireDetail = null;
$commentaires  = [];
if (isset($_GET['id'])) {
    $memoireDetail = $memoireDAO->findById((int)$_GET['id']);
    $commentaires  = $commentaireDAO->findByMemoire((int)$_GET['id']);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8"/>
    <title>Mémoires — Étudiant</title>
    <link rel="stylesheet" href="../../assets/css/style.css"/>
    <link rel="icon" type="image/png" href="<?= $base_url ?>/assets/img/logo_GASA.png">
</head>
<body>
<?php include '../../includes/navbar.php'; ?>

<div class="main-content">
    <div class="topbar">
        <div class="topbar-title">
            <h1>Mémoires publiés</h1>
            <p><?= count($memoires) ?> mémoire(s) trouvé(s)</p>
        </div>
    </div>

    <div class="page-content">

        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'commente'): ?>
            <div class="alert alert-success">✅ Commentaire ajouté.</div>
        <?php endif; ?>

        <?php if ($memoireDetail): ?>
        <!-- Détail mémoire -->
        <div class="card" style="margin-bottom:20px;">
            <div class="card-header">
                <h3>📄 <?= htmlspecialchars($memoireDetail->getTheme()) ?></h3>
                <a href="memoires.php" class="btn btn-outline btn-sm">← Retour</a>
            </div>
            <div class="card-body">
                <p><strong>Résumé :</strong> <?= htmlspecialchars($memoireDetail->getResumer()) ?></p>
                <p style="margin-top:8px;"><strong>Mots-clés :</strong> <?= htmlspecialchars($memoireDetail->getMotsCle()) ?></p>
                <p style="margin-top:8px;"><strong>Année :</strong> <?= htmlspecialchars($memoireDetail->getAnnee()) ?></p>
                <p style="margin-top:12px;">
                    <a href="detail.php?id=<?= $m->getIdMemoir() ?>" class="btn btn-outline btn-sm">👁 Consulter</a>
                </p>

                <hr style="margin:20px 0; border-color:#F0F0F0;"/>

                <!-- Commentaires -->
                <h4 style="color:#1A237E; margin-bottom:12px;">
                    💬 Commentaires (<?= count($commentaires) ?>)
                </h4>
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

                <!-- Formulaire commentaire -->
                <form method="POST" style="margin-top:16px;">
                    <input type="hidden" name="memoire_id" value="<?= $memoireDetail->getIdMemoir() ?>"/>
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

        <?php else: ?>

        <!-- Barre de recherche -->
        <form method="GET" class="search-bar">
            <input type="text" name="q" class="search-input"
                   placeholder="Rechercher par thème, mots-clés..."
                   value="<?= htmlspecialchars($recherche) ?>"/>
            <button type="submit" class="btn btn-primary">🔍 Rechercher</button>
            <?php if ($recherche): ?>
                <a href="memoires.php" class="btn btn-outline">✕ Effacer</a>
            <?php endif; ?>
        </form>

        <!-- Liste des mémoires -->
        <?php if (empty($memoires)): ?>
            <div class="alert alert-info">Aucun mémoire trouvé.</div>
        <?php else: ?>
            <div class="memoires-grid">
                <?php foreach ($memoires as $m): ?>
                <div class="memoire-card">
                    <h4><?= htmlspecialchars($m->getTheme()) ?></h4>
                    <p class="meta">📅 <?= htmlspecialchars($m->getAnnee()) ?></p>
                    <p class="meta">🏷 <?= htmlspecialchars($m->getMotsCle()) ?></p>
                    <p style="font-size:12px; color:#666; margin-top:4px;">
                        <?= htmlspecialchars(substr($m->getResumer(), 0, 100)) ?>...
                    </p>
                    <div class="actions">
                        <a href="detail.php?id=<?= $m->getIdMemoir() ?>" class="btn btn-primary btn-sm">👁 Consulter</a>
                    </div>
                    <p class="meta">❤️ <?= $likeDAO->compter($m->getIdMemoir()) ?></p>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php endif; ?>

    </div>
</div>
</body>
</html>
