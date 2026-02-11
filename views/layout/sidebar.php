<?php
// Sidebar partial - included in admin/dece/zona views to keep layout consistent
$current = $_SERVER['REQUEST_URI'] ?? '';
// Normalize path without base
$current = str_replace('/test-vocacional', '', $current);
function active($path)
{
    global $current;
    if ($path === '/admin') {
        return ($current === '/admin' || $current === '/admin/') ? 'active' : '';
    }
    return strpos($current, $path) === 0 ? 'active' : '';
}
?>
<aside class="admin-sidebar" id="adminSidebar">
    <div class="sidebar-header">
        <button id="sidebarToggle" class="sidebar-toggle-btn" title="Minimizar Sidebar">
            ☰
        </button>
        <span class="sidebar-brand-text">Menú</span>
    </div>

    <ul class="sidebar-menu">
        <li><a href="/test-vocacional/admin" class="<?= active('/admin') ?>" title="Dashboard">
                <span class="menu-icon">📊</span>
                <span class="menu-text">Dashboard</span>
            </a></li>

        <li><a href="/test-vocacional/admin/questions" class="<?= active('/admin/questions') ?>"
                title="Gestión de Preguntas">
                <span class="menu-icon">❓</span>
                <span class="menu-text">Gestión de Preguntas</span>
            </a></li>

        <?php if (in_array($_SESSION['user_role'], ['administrador', 'dece'])): ?>
            <?php if ($_SESSION['user_role'] === 'administrador'): ?>
                <li><a href="/test-vocacional/admin/questions/import" class="<?= active('/admin/questions/import') ?>"
                        title="Importar Preguntas">
                        <span class="menu-icon">📥</span>
                        <span class="menu-text">Importar Preguntas</span>
                    </a></li>
            <?php endif; ?>

            <li><a href="/test-vocacional/admin/users" class="<?= active('/admin/users') ?>" title="Gestión de Usuarios">
                    <span class="menu-icon">👥</span>
                    <span class="menu-text">Gestión de Usuarios</span>
                </a></li>

            <?php if ($_SESSION['user_role'] === 'administrador'): ?>
                <li><a href="/test-vocacional/admin/institutions" class="<?= active('/admin/institutions') ?>"
                        title="Instituciones">
                        <span class="menu-icon">🏫</span>
                        <span class="menu-text">Instituciones</span>
                    </a></li>
            <?php endif; ?>
        <?php endif; ?>

        <li><a href="/test-vocacional/admin/reports/group" class="<?= active('/admin/reports/group') ?>"
                title="Reportes Grupales">
                <span class="menu-icon">📋</span>
                <span class="menu-text">Reportes Grupales</span>
            </a></li>
    </ul>

    <script>
        // Immediately apply state to prevent flickering
        (function () {
            const sidebar = document.getElementById('adminSidebar');
            if (localStorage.getItem('sidebarCollapsed') === 'true') {
                sidebar.classList.add('collapsed');
            }
        })();

        document.getElementById('sidebarToggle').addEventListener('click', function () {
            const sidebar = document.getElementById('adminSidebar');
            sidebar.classList.toggle('collapsed');
            localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
        });
    </script>
    <p style="color: #FFFFFF;"><small><span style="font-size: 0.4em;">desarrollado por Rubén Jaramillo</small></span>
    </p>
</aside>