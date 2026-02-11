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

        <div class="table-container" style="margin-bottom: 20px;">
            <form method="GET" action="/test-vocacional/admin/users" class="filters-row" style="display: flex; gap: 15px; align-items: flex-end; flex-wrap: wrap;">
                <div class="form-group" style="margin-bottom: 0;">
                    <label>Rol</label>
                    <select name="rol" class="form-control" onchange="this.form.submit()">
                        <option value="">Todos los roles</option>
                        <option value="estudiante" <?= ($filters['rol'] ?? '') === 'estudiante' ? 'selected' : '' ?>>Estudiante</option>
                        <option value="dece" <?= ($filters['rol'] ?? '') === 'dece' ? 'selected' : '' ?>>DECE</option>
                        <option value="directivo" <?= ($filters['rol'] ?? '') === 'directivo' ? 'selected' : '' ?>>Directivo</option>
                        <?php if ($_SESSION['user_role'] === 'administrador'): ?>
                            <option value="zonal" <?= ($filters['rol'] ?? '') === 'zonal' ? 'selected' : '' ?>>Zonal</option>
                            <option value="administrador" <?= ($filters['rol'] ?? '') === 'administrador' ? 'selected' : '' ?>>Administrador</option>
                        <?php endif; ?>
                    </select>
                </div>
                
                <?php if ($_SESSION['user_role'] === 'administrador'): ?>
                    <div class="form-group" style="margin-bottom: 0; min-width: 250px;">
                        <label>Institución</label>
                        <select name="institucion_id" class="searchable-filter" onchange="this.form.submit()">
                            <option value="">Todas las instituciones</option>
                            <?php foreach ($institutions as $inst): ?>
                                <option value="<?= $inst['id'] ?>" <?= ($filters['institucion_id'] ?? '') == $inst['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($inst['nombre']) ?> (<?= htmlspecialchars($inst['codigo']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group" style="margin-bottom: 0;">
                        <label>Zona</label>
                        <select name="zona" class="form-control" onchange="this.form.submit()">
                            <option value="">Todas</option>
                            <?php foreach ($zonasList as $z): ?>
                                <option value="<?= $z ?>" <?= ($filters['zona'] ?? '') === $z ? 'selected' : '' ?>><?= htmlspecialchars($z) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group" style="margin-bottom: 0;">
                        <label>Distrito</label>
                        <select name="distrito" class="form-control" onchange="this.form.submit()">
                            <option value="">Todos</option>
                            <?php foreach ($distritosList as $d): ?>
                                <option value="<?= $d ?>" <?= ($filters['distrito'] ?? '') === $d ? 'selected' : '' ?>><?= htmlspecialchars($d) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>

                <div class="form-group" style="margin-bottom: 0;">
                    <label>Curso</label>
                    <select name="curso" class="form-control" onchange="this.form.submit()">
                        <option value="">Todos</option>
                        <?php foreach ($cursosList as $c): ?>
                            <option value="<?= $c ?>" <?= ($filters['curso'] ?? '') === $c ? 'selected' : '' ?>><?= htmlspecialchars($c) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <label>Paralelo</label>
                    <select name="paralelo" class="form-control" onchange="this.form.submit()">
                        <option value="">Todos</option>
                        <?php foreach ($paralelosList as $p): ?>
                            <option value="<?= $p ?>" <?= ($filters['paralelo'] ?? '') === $p ? 'selected' : '' ?>><?= htmlspecialchars($p) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group" style="margin-bottom: 0; flex-grow: 1;">
                    <label>Buscar</label>
                    <input type="text" name="search" class="form-control" placeholder="Nombre, usuario o email..." value="<?= htmlspecialchars($filters['search'] ?? '') ?>">
                </div>
                <button type="submit" class="btn btn-primary">Filtrar</button>
                <a href="/test-vocacional/admin/users" class="btn btn-outline">Limpiar</a>
            </form>
        </div>

        <div class="table-container">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Usuario</th>
                        <th>Nombre / Representante</th>
                        <th>Institución / Ubicación</th>
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
                            <td>
                                <div><strong>Est:</strong> <?= htmlspecialchars($u['nombre']) ?></div>
                                <div style="font-size: 0.85em; color: #666;"><strong>Rep:</strong> <?= htmlspecialchars($u['apellido']) ?></div>
                                <?php if (in_array($u['rol'], ['estudiante', 'directivo'])): ?>
                                    <div style="font-size: 0.85em; color: #444; margin-top: 2px;">
                                        <strong>Cur:</strong> <?= htmlspecialchars($u['curso'] ?? '-') ?> | 
                                        <strong>Par:</strong> <?= htmlspecialchars($u['paralelo'] ?? '-') ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($u['institucion_nombre'])): ?>
                                    <div style="font-weight: 500;"><?= htmlspecialchars($u['institucion_nombre']) ?></div>
                                    <?php if ($_SESSION['user_role'] === 'administrador'): ?>
                                        <div style="font-size: 0.85em; color: #666;">
                                            Zona: <?= htmlspecialchars($u['zona'] ?? '-') ?> | 
                                            Dist: <?= htmlspecialchars($u['distrito'] ?? '-') ?>
                                        </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span style="color: #999;">Sin institución</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>" form="user-form-<?= $u['id'] ?>">
                                    <select name="role" class="role-select" data-user-id="<?= $u['id'] ?>" form="user-form-<?= $u['id'] ?>">
                                        <option value="estudiante" <?= $u['rol'] === 'estudiante' ? 'selected' : '' ?>>Estudiante</option>
                                        <option value="dece" <?= $u['rol'] === 'dece' ? 'selected' : '' ?>>DECE</option>
                                        <option value="directivo" <?= $u['rol'] === 'directivo' ? 'selected' : '' ?>>Directivo</option>
                                        <?php if ($_SESSION['user_role'] === 'administrador'): ?>
                                            <option value="zonal" <?= $u['rol'] === 'zonal' ? 'selected' : '' ?>>Zonal</option>
                                            <option value="administrador" <?= $u['rol'] === 'administrador' ? 'selected' : '' ?>>Administrador</option>
                                        <?php endif; ?>
                                    </select>
                            </td>
                            <td>
                                    <!-- DECE Institution Assignment -->
                                    <div class="assignment-fields dece-assignment-<?= $u['id'] ?> <?= $u['rol'] === 'dece' ? 'show' : '' ?>">
                                        <?php if ($_SESSION['user_role'] === 'administrador'): ?>
                                            <select name="institucion_id" class="searchable-select" form="user-form-<?= $u['id'] ?>">
                                                <option value="">Seleccionar Institución</option>
                                                <?php foreach ($institutions as $inst): ?>
                                                    <option value="<?= $inst['id'] ?>" 
                                                        <?= ($u['institucion_id'] ?? '') == $inst['id'] ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($inst['nombre']) ?> (<?= htmlspecialchars($inst['codigo']) ?>)
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        <?php else: ?>
                                            <span style="font-size: 0.9em; color: #666;"><?= htmlspecialchars($u['institucion_nombre'] ?? '—') ?></span>
                                            <input type="hidden" name="institucion_id" value="<?= $u['institucion_id'] ?>" form="user-form-<?= $u['id'] ?>">
                                        <?php endif; ?>
                                    </div>

                                    <!-- Zonal Zone Assignment -->
                                    <?php if ($_SESSION['user_role'] === 'administrador'): ?>
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
                                    <?php endif; ?>

                                    <?php if ($u['rol'] === 'estudiante' || $u['rol'] === 'directivo' || $u['rol'] === 'administrador'): ?>
                                        <span style="color: #999;">—</span>
                                    <?php endif; ?>
                            </td>
                            <td>
                                    <button type="submit" class="btn btn-sm btn-primary" form="user-form-<?= $u['id'] ?>">Guardar</button>
                                    <!-- Change password inline form -->
                                    <button type="button" class="btn btn-sm btn-outline change-pass-btn" data-user-id="<?= $u['id'] ?>">Cambiar contraseña</button>

                                    <form method="POST" action="/test-vocacional/admin/users/change-password" class="change-pass-form" id="change-pass-form-<?= $u['id'] ?>" style="display:none; margin-top:8px;">
                                        <input type="hidden" name="target_user_id" value="<?= $u['id'] ?>">
                                        <input type="password" name="new_password" placeholder="Nueva contraseña" required style="padding:6px; margin-right:6px;">
                                        <input type="password" name="confirm_password" placeholder="Confirmar" required style="padding:6px; margin-right:6px;">
                                        <button type="submit" class="btn btn-sm btn-danger">Actualizar</button>
                                    </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?php if ($totalPages > 1): ?>
                <?php
                $queryParams = $_GET;
                unset($queryParams['page']);
                $queryString = http_build_query($queryParams);
                if ($queryString)
                    $queryString = '&' . $queryString;
                ?>
                <div class="pagination" style="margin-top: 20px; display: flex; justify-content: center; gap: 10px;">
                    <?php if ($currentPage > 1): ?>
                        <a href="?page=1<?= $queryString ?>" class="btn btn-sm btn-outline-secondary">&laquo; Primera</a>
                        <a href="?page=<?= $currentPage - 1 ?><?= $queryString ?>"
                            class="btn btn-sm btn-outline-secondary">Anterior</a>
                    <?php endif; ?>

                    <span style="align-self: center;">Página <?= $currentPage ?> de <?= $totalPages ?></span>

                    <?php if ($currentPage < $totalPages): ?>
                        <a href="?page=<?= $currentPage + 1 ?><?= $queryString ?>"
                            class="btn btn-sm btn-outline-secondary">Siguiente</a>
                        <a href="?page=<?= $totalPages ?><?= $queryString ?>" class="btn btn-sm btn-outline-secondary">Última
                            &raquo;</a>
                    <?php endif; ?>
                </div>
                <div style="text-align: center; margin-top: 10px; color: #666; font-size: 0.9em;">
                    Total: <?= $totalRecords ?> usuarios
                </div>
            <?php endif; ?>
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
document.querySelectorAll('.searchable-select, .searchable-filter').forEach(el => {
    new Choices(el, {
        searchEnabled: true,
        itemSelectText: '',
        noResultsText: 'No se encontraron resultados',
        noChoicesText: 'No hay opciones disponibles',
        placeholder: true,
        placeholderValue: 'Buscar institución...'
    });
});

// Toggle inline change-password form
document.querySelectorAll('.change-pass-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const userId = this.dataset.userId;
        const form = document.getElementById('change-pass-form-' + userId);
        if (form.style.display === 'none' || form.style.display === '') {
            form.style.display = 'block';
        } else {
            form.style.display = 'none';
        }
    });
});
</script>

<?php require 'views/layout/footer.php'; ?>

