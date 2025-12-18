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
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/test-vocacional/assets/img/logoTUVN.ico">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Choices.js -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
</head>

<body>

    <nav class="navbar">
        <div class="nav-container">
            <div style="display:flex; align-items:center; gap:12px;">
                <button id="mobileMenuBtn" class="sidebar-toggle-btn" aria-label="Abrir menú" title="Abrir menú" style="display:none;">☰</button>
                <div class="nav-brand">
                    <img src="/test-vocacional/assets/img/logoTUVN.png" alt="Logo TUVN"
                        style="height: 40px; margin-right: 10px; vertical-align: middle;">
                    <?= APP_NAME ?>
                </div>
            </div>
            <div class="nav-menu">
                <span><?= htmlspecialchars($_SESSION['user_name'] ?? 'Usuario') ?>
                    (<?= $_SESSION['user_role'] ?? 'Rol' ?>)</span>
                <a href="/test-vocacional/logout" class="btn btn-sm btn-outline">Cerrar Sesión</a>
            </div>
        </div>
    </nav>

    <script>
        // Script para alternar la visibilidad del sidebar en pantallas móviles
        document.addEventListener('DOMContentLoaded', function () {
            const btn = document.getElementById('mobileMenuBtn');
            const sidebar = document.querySelector('.admin-sidebar');
            function updateBtnVisibility() {
                if (window.innerWidth <= 768) {
                    btn.style.display = 'inline-flex';
                } else {
                    btn.style.display = 'none';
                    if (sidebar) sidebar.classList.remove('hidden-mobile');
                }
            }

            updateBtnVisibility();
            window.addEventListener('resize', updateBtnVisibility);

            if (btn && sidebar) {
                btn.addEventListener('click', function () {
                    sidebar.classList.toggle('hidden-mobile');
                });
            }
        });
    </script>

    <!-- Alertas globales -->
    <div class="alerts-container">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']);
            unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error"><?= htmlspecialchars($_SESSION['error']);
            unset($_SESSION['error']); ?></div>
        <?php endif; ?>
    </div>