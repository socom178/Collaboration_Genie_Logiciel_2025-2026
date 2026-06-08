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
    <link rel="icon" type="image/png" href="<?= $base_url ?>../../assets/img/logo_GASA.png">
</head>
<div class="main-content">
    <button class="hamburger" onclick="ouvrirMenu()" style="display:none;" id="hamburger">
            <span></span>
            <span></span>
            <span></span>
        </button>
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
            <div class="alert alert-success"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-check-lg" viewBox="0 0 16 16">
                                <path d="M12.736 3.97a.733.733 0 0 1 1.047 0c.286.289.29.756.01 1.05L7.88 12.01a.733.733 0 0 1-1.065.02L3.217 8.384a.757.757 0 0 1 0-1.06.733.733 0 0 1 1.047 0l3.052 3.093 5.4-6.425z"/>
                            </svg> Enseignant supprimé.</div>
        <?php endif; ?>

        <!-- Formulaire ajout -->
        <div class="card" style="margin-bottom:20px;">
            <div class="card-header">
                <h3>
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-plus-circle" viewBox="0 0 16 16">
                        <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16"/>
                        <path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4"/>
                    </svg> Ajouter un enseignant
                </h3>
            </div>
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
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-plus-circle" viewBox="0 0 16 16">
                            <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16"/>
                            <path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4"/>
                            </svg> Enregistrer l'enseignant
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Liste enseignants -->
        <div class="card">
            <div class="card-header"><h3>
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-person-fill" viewBox="0 0 16 16">
                    <path d="M3 14s-1 0-1-1 1-4 6-4 6 3 6 4-1 1-1 1zm5-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6"/>
                    </svg> Liste des enseignants</h3></div>
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
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-trash3" viewBox="0 0 16 16">
                                                <path d="M6.5 1h3a.5.5 0 0 1 .5.5v1H6v-1a.5.5 0 0 1 .5-.5M11 2.5v-1A1.5 1.5 0 0 0 9.5 0h-3A1.5 1.5 0 0 0 5 1.5v1H1.5a.5.5 0 0 0 0 1h.538l.853 10.66A2 2 0 0 0 4.885 16h6.23a2 2 0 0 0 1.994-1.84l.853-10.66h.538a.5.5 0 0 0 0-1zm1.958 1-.846 10.58a1 1 0 0 1-.997.92h-6.23a1 1 0 0 1-.997-.92L3.042 3.5zm-7.487 1a.5.5 0 0 1 .528.47l.5 8.5a.5.5 0 0 1-.998.06L5 5.03a.5.5 0 0 1 .47-.53Zm5.058 0a.5.5 0 0 1 .47.53l-.5 8.5a.5.5 0 1 1-.998-.06l.5-8.5a.5.5 0 0 1 .528-.47M8 4.5a.5.5 0 0 1 .5.5v8.5a.5.5 0 0 1-1 0V5a.5.5 0 0 1 .5-.5"/>
                                            </svg> Supprimer
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
