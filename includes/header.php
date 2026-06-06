<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: /Collaboration_GL_2025-2026/auth/login.php');
    exit;
}

$page_courante = basename($_SERVER['PHP_SELF']);
$role          = $_SESSION['role'] ?? '';
$nom_complet   = ($_SESSION['prenom'] ?? '') . ' ' . ($_SESSION['nom'] ?? '');
$initiales     = strtoupper(substr($_SESSION['prenom'] ?? 'U', 0, 1) . substr($_SESSION['nom'] ?? '', 0, 1));

// Chemin relatif vers assets
$depth = substr_count(str_replace($_SERVER['DOCUMENT_ROOT'], '', $_SERVER['SCRIPT_FILENAME']), '/') - 1;
$base  = str_repeat('../', $depth);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Gestion Mémoires — UATM</title>
    <link rel="stylesheet" href="../../assets/css/style.css"/>
</head>
<body>
