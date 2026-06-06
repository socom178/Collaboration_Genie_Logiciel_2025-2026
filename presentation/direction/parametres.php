<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'direction') {
    header('Location: ../auth/login.php'); exit;
}

require_once '../../config/db.php';

$message  = '';
$type_msg = '';

// Changer mot de passe direction
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['changer_mdp'])) {
    $ancien = $_POST['ancien_mdp'] ?? '';
    $nouveau = $_POST['nouveau_mdp'] ?? '';
    $confirm = $_POST['confirm_mdp'] ?? '';

    $stmt = getDb()->prepare("SELECT * FROM direction_etude WHERE id = :id");
    $stmt->execute([':id' => $_SESSION['user_id']]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($admin && $ancien === $admin['password']) {
        if ($nouveau === $confirm && strlen($nouveau) >= 4) {
            $stmt2 = getDb()->prepare("UPDATE direction_etude SET password = :mdp WHERE id = :id");
            $stmt2->execute([':mdp' => $nouveau, ':id' => $_SESSION['user_id']]);
            $message  = '✅ Mot de passe mis à jour.';
            $type_msg = 'success';
        } else {
            $message  = '⚠️ Les mots de passe ne correspondent pas ou sont trop courts.';
            $type_msg = 'danger';
        }
    } else {
        $message  = '⚠️ Ancien mot de passe incorrect.';
        $type_msg = 'danger';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8"/>
    <title>Paramètres</title>
    <link rel="stylesheet" href="../../assets/css/style.css"/>
    <link rel="icon" type="image/png" href="<?= $base_url ?>/assets/img/logo_GASA.png">
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
            <h1>Paramètres</h1>
            <p>Configuration du système</p>
        </div>
    </div>

    <div class="page-content">

        <?php if ($message): ?>
            <div class="alert alert-<?= $type_msg ?>"><?= $message ?></div>
        <?php endif; ?>

        <!-- Changer mot de passe -->
        <div class="card">
            <div class="card-header"><h3>🔒 Changer le mot de passe</h3></div>
            <div class="card-body">
                <form method="POST" style="max-width:400px;">
                    <div class="form-group">
                        <label>Ancien mot de passe</label>
                        <input type="password" name="ancien_mdp" class="form-control" required/>
                    </div>
                    <div class="form-group">
                        <label>Nouveau mot de passe</label>
                        <input type="password" name="nouveau_mdp" class="form-control" required/>
                    </div>
                    <div class="form-group">
                        <label>Confirmer le nouveau mot de passe</label>
                        <input type="password" name="confirm_mdp" class="form-control" required/>
                    </div>
                    <button type="submit" name="changer_mdp" class="btn btn-primary">
                        🔒 Mettre à jour
                    </button>
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
