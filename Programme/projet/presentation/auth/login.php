<?php
session_start();

/*if (isset($_SESSION['user_id'])) {
    header('Location: ../direction/dashboard.php');
    exit;
}*/

include '../../config/db.php'; 
require_once '../../persistance/PersonneDAO.php';
require_once __DIR__ . '/../../persistance/EtudiantDAO.php';
require_once __DIR__ . '/../../persistance/ProfesseurDAO.php';

$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifiant = trim($_POST['identifiant'] ?? '');
    $password    = trim($_POST['password'] ?? '');

    if (empty($identifiant) || empty($password)) {
        $erreur = "Veuillez remplir tous les champs.";
    } else {
            // 1. Étudiant ou Professeur (table personne, par email)
    $dao  = new PersonneDAO(getDb());
    $user = $dao->authentifierPersonne($identifiant, $password);
    if ($user) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['nom']     = $user['nom'];
    $_SESSION['prenom']  = $user['prenom'];
    $_SESSION['role']    = $user['role'];

    if ($user['role'] === 'professeur') {
        // Récupérer l'id dans la table professeur
        $professeurDAO = new ProfesseurDAO(getDb());
        $prof = $professeurDAO->findByEmail($identifiant);
        if ($prof) {
            $_SESSION['professeur_id'] = $prof->getProfesseurId();
        }
        header('Location: ../enseignant/dashboard.php');
    } else {
        // Récupérer l'id dans la table etudiant
        $etudiantDAO = new EtudiantDAO(getDb());
        $etudiant = $etudiantDAO->findByPersonneId($user['id']);
        if ($etudiant) {
            $_SESSION['etudiant_id'] = $etudiant->getEtudiantId();
            $_SESSION['type']        = $etudiant->getType();
        }
        header('Location: ../etudiant/dashboard.php');
    }
    exit;
}

    $admin = $dao->authentifierDirection($identifiant, $password);
    if ($admin) {
        $_SESSION['user_id'] = $admin['id'];
        $_SESSION['role']    = 'direction';
        $_SESSION['nom']     = 'Études';
        $_SESSION['prenom']  = 'Direction';
        header('Location: ../direction/dashboard.php');
        exit;
    }
    
        $erreur = "Identifiant ou mot de passe incorrect.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Connexion — UATM Mémoires</title>
    <link rel="stylesheet" href="/Collaboration_GL_2025-2026/Programme/projet/assets/css/loginstyle.css"/>
    <link rel="stylesheet" href="/Collaboration_GL_2025-2026/Programme/projet/assets/css/style.css"/>

</head>
<body>
<div class="login-page">

    <div class="login-left">
        <img src="<?= $base_url ?>/assets/img/logo_GASA2.jpeg" alt="UATM" />
        <div class="login-sep"></div>
        <h1>Gestion des mémoires soutenus</h1>
        <p>Plateforme académique de publication et consultation des mémoires de l'UATM</p>
    </div>

    <div class="login-right">
        <div class="login-card">
            <h2>Connexion</h2>
            <p class="sub">Utilisez vos identifiants fournis par l'administration</p>

            <div class="alert alert-info">
                ℹ️ Vos identifiants ont été attribués par la Direction des études.
            </div>

            <?php if ($erreur): ?>
                <div class="alert alert-danger">⚠️ <?= htmlspecialchars($erreur) ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="identifiant">Matricule / Email / Nom d'utilisateur</label>
                    <input type="text" id="identifiant" name="identifiant"
                           class="form-control"
                           placeholder="Ex : ETU2024001"
                           value="<?= htmlspecialchars($_POST['identifiant'] ?? '') ?>"
                           required/>
                </div>

                <div class="form-group">
                    <label for="password">Mot de passe</label>
                    <input type="password" id="password" name="password"
                           class="form-control"
                           placeholder="••••••••"
                           required/>
                    <a href="#" class="forgot-link">Mot de passe oublié ?</a>
                </div>

                <button type="submit" class="btn btn-primary"
                        style="width:100%; justify-content:center; margin-top:8px;">
                    Se connecter
                </button>
            </form>

            <p class="contact-help">
                Problème de connexion ? <span>Contactez l'administration</span>
            </p>
        </div>
    </div>
</div>
</body>
</html>