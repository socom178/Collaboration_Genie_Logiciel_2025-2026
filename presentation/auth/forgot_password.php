<?php
session_start();
require_once '../../config/db.php';
require_once '../../service/MailerService.php';

$message  = '';
$type_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (empty($email)) {
        $message  = '⚠️ Veuillez entrer votre email.';
        $type_msg = 'danger';
    } else {
        $db = getDb();

        // Vérifier si l'email existe dans personne
        $stmt = $db->prepare("SELECT * FROM personne WHERE email = :email");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        if ($user) {
            // Générer un token unique
            $token     = bin2hex(random_bytes(32));
            $expire_at = date('Y-m-d H:i:s', strtotime('+1 hour'));

            // Enregistrer le token
            $stmt2 = $db->prepare("
                INSERT INTO reset_password (email, token, expire_at)
                VALUES (:email, :token, :expire_at)
            ");
            $stmt2->execute([
                ':email'     => $email,
                ':token'     => $token,
                ':expire_at' => $expire_at,
            ]);

            // Envoyer le mail
            $lien = 'https://gestionmemoiresoutenu.onrender.com/presentation/auth/reset_password.php?token=' . $token;

            MailerService::sendMail(
                $email,
                "Réinitialisation de mot de passe — UATM",
                "
                    <h2>Réinitialisation de mot de passe</h2>
                    <p>Bonjour {$user['prenom']} {$user['nom']},</p>
                    <p>Vous avez demandé une réinitialisation de mot de passe.</p>
                    <p>Cliquez sur le lien ci-dessous (valable 1 heure) :</p>
                    <a href='$lien' style='background:#1A237E;color:white;padding:10px 20px;border-radius:5px;text-decoration:none;'>
                        Réinitialiser mon mot de passe
                    </a>
                    <p style='margin-top:16px;'>Si vous n'avez pas fait cette demande, ignorez ce mail.</p>
                "
            );
            try {
    
            $message  = '✅ Un lien de réinitialisation a été envoyé à votre email.';
            $type_msg = 'success';
        } else {
            $message  = '⚠️ Aucun compte trouvé avec cet email.';
            $type_msg = 'danger';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8"/>
    <title>Mot de passe oublié</title>
    <link rel="stylesheet" href="../../assets/css/loginstyle.css"/>
    <link rel="stylesheet" href="../../assets/css/style.css"/>
</head>
<body>
<div class="login-page">
    <div class="login-left">
        <img src="../../assets/img/logo_GASA.png" alt="UATM"/>
        <div class="login-sep"></div>
        <h1>Gestion des mémoires soutenus</h1>
    </div>
    <div class="login-right">
        <div class="login-card">
            <h2>Mot de passe oublié</h2>
            <p class="sub">Entrez votre email pour recevoir un lien de réinitialisation</p>

            <?php if ($message): ?>
                <div class="alert alert-<?= $type_msg ?>"><?= $message ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" class="form-control"
                           placeholder="votre@email.com" required/>
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%; justify-content:center;">
                    Envoyer le lien
                </button>
            </form>

            <p class="contact-help" style="margin-top:12px;">
                <a href="login.php" style="color:#1A237E;">← Retour à la connexion</a>
            </p>
        </div>
    </div>
</div>
</body>
</html>