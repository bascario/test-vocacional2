<?php require 'views/layout/header.php'; ?>

<style>
.assignment-fields {
    display: none;
    margin-top: 5px;
}
.assignment-fields select {
    width: 100%;
    padding: 5px;
    margin-top: 3px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 13px;
}
.assignment-fields.show {
    display: block;
}
</style>

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
                        <th>Asignación</th>
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
                                <form method="POST" action="/test-vocacional/admin/users" style="display:inline" class="user-form">
                                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                    <select name="role" class="role-select" data-user-id="<?= $u['id'] ?>">
                                        <option value="estudiante" <?= $u['rol'] === 'estudiante' ? 'selected' : '' ?>>Estudiante</option>
                                        <option value="dece" <?= $u['rol'] === 'dece' ? 'selected' : '' ?>>DECE</option>
                                        <option value="zonal" <?= $u['rol'] === 'zonal' ? 'selected' : '' ?>>Zonal</option>
                                        <option value="administrador" <?= $u['rol'] === 'administrador' ? 'selected' : '' ?>>Administrador</option>
                                    </select>
                            </td>
                            <td>
                                    <!-- DECE Institution Assignment -->
                                    <div class="assignment-fields dece-assignment-<?= $u['id'] ?> <?= $u['rol'] === 'dece' ? 'show' : '' ?>">
                                        <select name="institucion_id">
                                            <option value="">Seleccionar Institución</option>
                                            <?php foreach ($institutions as $inst): ?>
                                                <option value="<?= $inst['id'] ?>" 
                                                    <?= ($u['institucion_id'] ?? '') == $inst['id'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($inst['nombre']) ?> (<?= htmlspecialchars($inst['codigo']) ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <!-- Zonal Zone Assignment -->
                                    <div class="assignment-fields zonal-assignment-<?= $u['id'] ?> <?= $u['rol'] === 'zonal' ? 'show' : '' ?>">
                                        <select name="zona_id">
                                            <option value="">Seleccionar Zona</option>
                                            <?php foreach ($zonas as $zona): ?>
                                                <option value="<?= htmlspecialchars($zona) ?>" 
                                                    <?= ($u['zona_id'] ?? '') == $zona ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($zona) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <?php if ($u['rol'] === 'estudiante' || $u['rol'] === 'administrador'): ?>
                                        <span style="color: #999;">—</span>
                                    <?php endif; ?>
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

<script>
// Show/hide assignment fields based on role selection
document.querySelectorAll('.role-select').forEach(select => {
    select.addEventListener('change', function() {
        const userId = this.dataset.userId;
        const role = this.value;
        
        // Hide all assignment fields for this user
        document.querySelectorAll(`.dece-assignment-${userId}, .zonal-assignment-${userId}`).forEach(field => {
            field.classList.remove('show');
        });
        
        // Show relevant assignment field
        if (role === 'dece') {
            document.querySelector(`.dece-assignment-${userId}`).classList.add('show');
        } else if (role === 'zonal') {
            document.querySelector(`.zonal-assignment-${userId}`).classList.add('show');
        }
    });
});
</script>

<?php require 'views/layout/footer.php'; ?>
</body>
</html>
