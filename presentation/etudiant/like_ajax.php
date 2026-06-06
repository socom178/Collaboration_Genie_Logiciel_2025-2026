<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['erreur' => 'Non connecté']);
    exit;
}

require_once '../../config/db.php';
require_once '../../persistance/LikeDAO.php';

$likeDAO    = new LikeDAO(getDb());
$memoire_id = (int)($_POST['memoire_id'] ?? 0);

if (!$memoire_id) {
    echo json_encode(['erreur' => 'ID invalide']);
    exit;
}

$action  = $likeDAO->toggleLike($_SESSION['user_id'], $memoire_id);
$nbLikes = $likeDAO->compter($memoire_id);

echo json_encode([
    'action'   => $action,
    'nb_likes' => $nbLikes
]);