<?php
// Seguridad: si no hay sesión, redirigir al login
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    header('Location: /test-vocacional/login');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? APP_NAME ?></title>
    <link rel="stylesheet" href="/test-vocacional/assets/css/styles.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<nav class="navbar">
    <div class="nav-container">
        <div class="nav-brand">
            🎯 <?= APP_NAME ?>
        </div>
        <div class="nav-menu">
            <span><?= htmlspecialchars($_SESSION['user_name'] ?? 'Usuario') ?> (<?= $_SESSION['user_role'] ?? 'Rol' ?>)</span>
            <a href="/test-vocacional/logout" class="btn btn-sm btn-outline">Cerrar Sesión</a>
        </div>
    </div>
</nav>

<!-- Alertas globales -->
<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
<?php endif; ?>
<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-error"><?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
<?php endif; ?>

<div class="container">