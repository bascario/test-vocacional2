<?php require 'views/layout/header.php'; ?>
<!-- Choices.js is now global in header.php -->

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
/* Choices.js custom styling to fit in table */
.choices {
    margin-bottom: 0;
    font-size: 13px;
}
.choices__inner {
    min-height: 35px;
    padding: 2px 5px;
    background-color: white;
}
.choices__list--dropdown {
    z-index: 1000;
}
</style>

<div class="admin-container">
    <?php require 'views/layout/sidebar.php'; ?>
    <main class="admin-main">
        <div class="admin-header">
            <h1>Gestión de Usuarios</h1>
            <p>Listado de usuarios y asignación de roles</p>
        </div>

        

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
                        <form id="user-form-<?= $u['id'] ?>" method="POST" action="/test-vocacional/admin/users" style="display:none;"></form>
                        <tr>
                            <td><?= $u['id'] ?></td>
                            <td><?= htmlspecialchars($u['username']) ?></td>
                            <td><?= htmlspecialchars($u['nombre'] . ' ' . $u['apellido']) ?></td>
                            <td><?= htmlspecialchars($u['email']) ?></td>
                            <td>
                                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>" form="user-form-<?= $u['id'] ?>">
                                    <select name="role" class="role-select" data-user-id="<?= $u['id'] ?>" form="user-form-<?= $u['id'] ?>">
                                        <option value="estudiante" <?= $u['rol'] === 'estudiante' ? 'selected' : '' ?>>Estudiante</option>
                                        <option value="dece" <?= $u['rol'] === 'dece' ? 'selected' : '' ?>>DECE</option>
                                        <option value="zonal" <?= $u['rol'] === 'zonal' ? 'selected' : '' ?>>Zonal</option>
                                        <option value="administrador" <?= $u['rol'] === 'administrador' ? 'selected' : '' ?>>Administrador</option>
                                    </select>
                            </td>
                            <td>
                                    <!-- DECE Institution Assignment -->
                                    <div class="assignment-fields dece-assignment-<?= $u['id'] ?> <?= $u['rol'] === 'dece' ? 'show' : '' ?>">
                                        <select name="institucion_id" class="searchable-select" form="user-form-<?= $u['id'] ?>">
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
                                        <select name="zona_id" form="user-form-<?= $u['id'] ?>">
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
                                    <button type="submit" class="btn btn-sm btn-primary" form="user-form-<?= $u['id'] ?>">Guardar</button>
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
// Initialize Choices.js for institution dropdowns
document.querySelectorAll('.searchable-select').forEach(el => {
    new Choices(el, {
        searchEnabled: true,
        itemSelectText: '',
        noResultsText: 'No se encontraron resultados',
        noChoicesText: 'No hay opciones disponibles',
        placeholder: true,
        placeholderValue: 'Buscar institución...'
    });
});
</script>

<?php require 'views/layout/footer.php'; ?>

