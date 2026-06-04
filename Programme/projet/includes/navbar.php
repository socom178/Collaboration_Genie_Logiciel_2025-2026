<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$page_courante = basename($_SERVER['PHP_SELF']);
$role          = $_SESSION['role'] ?? '';
$nom_complet   = ($_SESSION['prenom'] ?? '') . ' ' . ($_SESSION['nom'] ?? '');

$base_url = '/Collaboration_GL_2025-2026/Programme/projet';

$routes = [
    'direction' => [
        'dashboard' => $base_url . '/presentation/direction/dashboard.php',
        'etudiants' => $base_url . '/presentation/direction/etudiants.php',
        'diplomes'   => $base_url . '/presentation/direction/diplomes.php',
        'enseignants' => $base_url . '/presentation/direction/enseignants.php',
        'memoires'    => $base_url . '/presentation/direction/memoires.php',
        'anciens memoires'    => $base_url . '/presentation/direction/memoires_anciens.php',
        'parametres'  => $base_url . '/presentation/direction/parametres.php',
    ],
    'professeur' => [
        'dashboard'  => $base_url . '/presentation/enseignant/dashboard.php',
        'validation' => $base_url . '/presentation/enseignant/validation.php',
        'memoires'   => $base_url . '/presentation/enseignant/memoires.php',
    ],
    'etudiant' => [
        'dashboard' => $base_url . '/presentation/etudiant/dashboard.php',
        'explorer'  => $base_url . '/presentation/etudiant/memoires.php',
        'soumettre' => $base_url . '/presentation/etudiant/soumettre.php',
    ],
];
$r = $routes[$role] ?? [];

function isActive(string $page): string {
    return basename($_SERVER['PHP_SELF']) === $page ? 'active' : '';
}
?>

<aside class="sidebar">
    <div class="sidebar-logo">
        <img src="<?= $base_url ?>/assets/img/logo_GASA.png" alt="UATM">
        <!--<img src="<?= $base ?>assets/img/logo.png" alt="UATM" onerror="this.style.display='none'"/>-->
        <h2>Gestion des<br/>mémoires soutenus</h2>
    </div>

    <nav class="sidebar-nav">

        <?php if ($role === 'direction'): ?>
            <a href="<?= $r['dashboard'] ?>" class="nav-item <?= isActive('dashboard.php') ?>">
                <span class="icon">🏠</span><span>Tableau de bord</span>
            </a>

            <!-- Utilisateurs avec sous-menu -->
            <div class="nav-item-dropdown <?= in_array($page_courante, ['etudiants.php','diplomes.php','enseignants.php']) ? 'open' : '' ?>">
                <div class="nav-item dropdown-toggle" onclick="toggleDropdown(this)">
                    <span class="icon">👥</span>
                    <span>Utilisateurs</span>
                    <span class="arrow">▾</span>
                </div>
                <div class="dropdown-menu <?= in_array($page_courante, ['etudiants.php','diplomes.php','enseignants.php']) ? 'open' : '' ?>">
                    <a href="<?= $r['etudiants'] ?>" class="dropdown-item <?= isActive('etudiants.php') ?>">
                        <span>🎒</span> Étudiants
                    </a>
                    <a href="<?= $r['diplomes'] ?>" class="dropdown-item <?= isActive('diplomes.php') ?>">
                        <span>🎓</span> Diplômés
                    </a>
                    <a href="<?= $r['enseignants'] ?>" class="dropdown-item <?= isActive('enseignants.php') ?>">
                        <span>👨‍🏫</span> Enseignants
                    </a>
                </div>
            </div>

            <a href="<?= $r['memoires'] ?>" class="nav-item <?= isActive('memoires.php') ?>">
                <span class="icon">📄</span><span>Mémoires</span>
            </a>
            <a href="<?= $base_url ?>/presentation/direction/memoires_anciens.php" class="nav-item <?= isActive('memoires_anciens.php') ?>">
                <span class="icon">📥</span><span>Anciens mémoires</span>
            </a>
            <a href="<?= $r['parametres'] ?>" class="nav-item <?= isActive('parametres.php') ?>">
                <span class="icon">⚙️</span><span>Paramètres</span>
            </a>

        <?php elseif ($role === 'professeur'): ?>
            <a href="<?= $r['dashboard'] ?>" class="nav-item <?= isActive('dashboard.php') ?>">
                <span class="icon">🏠</span><span>Tableau de bord</span>
            </a>
            <a href="<?= $r['validation'] ?>" class="nav-item <?= isActive('validation.php') ?>">
                <span class="icon">✅</span><span>Validation</span>
            </a>
            <a href="<?= $r['memoires'] ?>" class="nav-item <?= isActive('memoires.php') ?>">
                <span class="icon">📄</span><span>Mémoires</span>
            </a>

        <?php elseif ($role === 'etudiant'): ?>
            <a href="<?= $r['dashboard'] ?>" class="nav-item <?= isActive('dashboard.php') ?>">
                <span class="icon">🏠</span><span>Tableau de bord</span>
            </a>
            <a href="<?= $r['explorer'] ?>" class="nav-item <?= isActive('memoires.php') ?>">
                <span class="icon">🔍</span><span>Mémoires</span>
            </a>
            <?php if (($_SESSION['type'] ?? '') === 'diplome'): ?>
            <a href="<?= $r['soumettre'] ?>" class="nav-item <?= isActive('soumettre.php') ?>">
                <span class="icon">📤</span><span>Soumettre</span>
            </a>
            <?php endif; ?>
        <?php endif; ?>

    </nav>

    <div class="sidebar-footer">
        Connecté : <strong style="color:white"><?= htmlspecialchars($nom_complet) ?></strong><br/>
        <a href="<?= $base_url ?>/presentation/auth/logout.php">Se déconnecter</a>
    </div>
</aside>

<script>
function toggleDropdown(el) {
    const parent = el.closest('.nav-item-dropdown');
    parent.classList.toggle('open');
}
</script>
