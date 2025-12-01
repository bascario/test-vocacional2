<?php require 'views/layout/header.php'; ?>

<div class="admin-container">
    <aside class="admin-sidebar">
        <ul class="sidebar-menu">
            <li><a href="/test-vocacional/admin">📊 Dashboard</a></li>
            <li><a href="/test-vocacional/admin/questions">❓ Gestión de Preguntas</a></li>
            <li><a href="/test-vocacional/admin/users" class="active">👥 Usuarios</a></li>
            <li><a href="/test-vocacional/admin/institutions">🏫 Instituciones</a></li>
        </ul>
    </aside>

    <main class="admin-main">
        <div class="admin-header">
            <h1>Gestión de Usuarios</h1>
            <p>Listado de usuarios y asignación de roles</p>
        </div>

        <?php
            $success = $_SESSION['success'] ?? null;
            $error = $_SESSION['error'] ?? null;
            if ($success) unset($_SESSION['success']);
            if ($error) unset($_SESSION['error']);
        ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="table-container">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Usuario</th>
                        <th>Nombre</th>
                        <th>Email</th>
                        <th>Rol</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td><?= $u['id'] ?></td>
                            <td><?= htmlspecialchars($u['username']) ?></td>
                            <td><?= htmlspecialchars($u['nombre'] . ' ' . $u['apellido']) ?></td>
                            <td><?= htmlspecialchars($u['email']) ?></td>
                            <td>
                                <form method="POST" action="/test-vocacional/admin/users" style="display:inline">
                                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                    <select name="role">
                                        <option value="estudiante" <?= $u['rol'] === 'estudiante' ? 'selected' : '' ?>>Estudiante</option>
                                        <option value="dece" <?= $u['rol'] === 'dece' ? 'selected' : '' ?>>DECE</option>
                                        <option value="administrador" <?= $u['rol'] === 'administrador' ? 'selected' : '' ?>>Administrador</option>
                                    </select>
                            </td>
                            <td>
                                    <button type="submit" class="btn btn-sm btn-primary">Guardar</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<?php require 'views/layout/footer.php'; ?>
</body>
</html>
