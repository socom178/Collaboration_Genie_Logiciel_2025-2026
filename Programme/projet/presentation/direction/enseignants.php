<?php

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'direction') {
    header('Location: ../auth/login.php'); exit;
}

require_once __DIR__ . '/../../persistance/ProfesseurDAO.php';
require_once __DIR__ . '/../../metier/Professeur.php';
include __DIR__ . '/../../config/db.php';
$dao = new ProfesseurDAO(getDb());

$message  = '';
$type_msg = 'info';

// Ajout d'un enseignant
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'ajouter') {
    $password = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 8);
    $prof = new Professeur(
        0, $_POST['nom'], $_POST['prenom'], $_POST['date_nais'],
        $_POST['email'], $_POST['tel'], $_POST['sexe'], $password, '',
        0, $_POST['grade'], $_POST['date_embauche']
    );
    $ok = $dao->create($prof);
    $message  = $ok ? "✔ Enseignant créé. Mot de passe temporaire : <strong>$password</strong>" : "⚠️ Erreur — email déjà utilisé ?";
    $type_msg = $ok ? 'success' : 'danger';
}

// Suppression
if (isset($_GET['supprimer'])) {
    $dao->delete((int)$_GET['supprimer']);
    header('Location: enseignants.php?msg=supprime');
    exit;
}

$enseignants = $dao->findAll();
?>
<?php include __DIR__ . '/../../includes/header.php'; ?>
<?php include __DIR__ . '/../../includes/navbar.php'; ?>
<head>
    <meta charset="UTF-8"/>
    <title>Dashboard</title>
    <link rel="stylesheet" href="../../assets/css/style.css"/>
    <link rel="icon" type="image/png" href="<?= $base_url ?>/assets/img/logo_GASA.png">
</head>
<div class="main-content">
    <div class="topbar">
        <div class="topbar-title">
            <h1>Gestion des enseignants</h1>
            <p><?= count($enseignants) ?> enseignant(s) enregistré(s)</p>
        </div>
    </div>

    <div class="page-content">

        <?php if ($message): ?>
            <div class="alert alert-<?= $type_msg ?>"><?= $message ?></div>
        <?php endif; ?>
        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'supprime'): ?>
            <div class="alert alert-success">✅ Enseignant supprimé.</div>
        <?php endif; ?>

        <!-- Formulaire ajout -->
        <div class="card" style="margin-bottom:20px;">
            <div class="card-header"><h3>➕ Ajouter un enseignant</h3></div>
            <div class="card-body">
                <form method="POST" style="display:grid; grid-template-columns:1fr 1fr; gap:14px;">
                    <div class="form-group">
                        <label>Nom</label>
                        <input type="text" name="nom" class="form-control" required/>
                    </div>
                    <div class="form-group">
                        <label>Prénom</label>
                        <input type="text" name="prenom" class="form-control" required/>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control" required/>
                    </div>
                    <div class="form-group">
                        <label>Téléphone</label>
                        <input type="text" name="tel" class="form-control"/>
                    </div>
                    <div class="form-group">
                        <label>Grade</label>
                        <input type="text" name="grade" class="form-control" placeholder="Ex : Maître de conférences"/>
                    </div>
                    <div class="form-group">
                        <label>Date d'embauche</label>
                        <input type="date" name="date_embauche" class="form-control"/>
                    </div>
                    <div class="form-group">
                        <label>Date de naissance</label>
                        <input type="date" name="date_nais" class="form-control" required/>
                    </div>
                    <div class="form-group">
                        <label>Sexe</label>
                        <select name="sexe" class="form-control">
                            <option value="M">Masculin</option>
                            <option value="F">Féminin</option>
                        </select>
                    </div>
                    <div style="grid-column:1/-1;">
                        <button type="submit" name="action" value="ajouter" class="btn btn-primary">
                            ➕ Enregistrer l'enseignant
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Liste enseignants -->
        <div class="card">
            <div class="card-header"><h3>👨‍🏫 Liste des enseignants</h3></div>
            <div class="card-body">
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr><th>Nom complet</th><th>Email</th><th>Grade</th><th>Date embauche</th><th>Actions</th></tr>
                        </thead>
                        <tbody>
                            <?php if (empty($enseignants)): ?>
                                <tr><td colspan="5" style="text-align:center; color:#888;">Aucun enseignant enregistré.</td></tr>
                            <?php else: ?>
                                <?php foreach ($enseignants as $p): ?>
                                <tr>
                                    <td><?= htmlspecialchars($p->getNomComplet()) ?></td>
                                    <td><?= htmlspecialchars($p->getEmail()) ?></td>
                                    <td><?= htmlspecialchars($p->getGrade()) ?></td>
                                    <td><?= htmlspecialchars($p->getDateEmbauche()) ?></td>
                                    <td>
                                        <a href="?supprimer=<?= $p->getId() ?>"
                                           class="btn btn-danger btn-sm"
                                           onclick="return confirm('Supprimer cet enseignant ?')">
                                            🗑 Supprimer
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>
</body>
</html>
