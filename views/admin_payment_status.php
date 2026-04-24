<?php require 'views/layout/header.php'; ?>

<div class="admin-container">
    <?php require 'views/layout/sidebar.php'; ?>
    <main class="admin-main">
        <div class="admin-header">
            <h1>Administrar Estado de Pago</h1>
            <p>Actualiza el estado de pago de los usuarios desde una cuenta de acceso oculto.</p>
        </div>

        <div class="table-container" style="margin-bottom: 20px;">
            <form method="GET" action="/test-vocacional/admin/payment-status" class="filters-row" style="display: flex; gap: 15px; align-items: flex-end; flex-wrap: wrap;">
                <div class="form-group" style="margin-bottom: 0;">
                    <label>Rol</label>
                    <select name="rol" class="form-control" onchange="this.form.submit()">
                        <option value="">Todos los roles</option>
                        <option value="estudiante" <?= ($filters['rol'] ?? '') === 'estudiante' ? 'selected' : '' ?>>Estudiante</option>
                        <option value="dece" <?= ($filters['rol'] ?? '') === 'dece' ? 'selected' : '' ?>>DECE</option>
                        <option value="directivo" <?= ($filters['rol'] ?? '') === 'directivo' ? 'selected' : '' ?>>Directivo</option>
                        <option value="zonal" <?= ($filters['rol'] ?? '') === 'zonal' ? 'selected' : '' ?>>Zonal</option>
                        <option value="administrador" <?= ($filters['rol'] ?? '') === 'administrador' ? 'selected' : '' ?>>Administrador</option>
                    </select>
                </div>

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

                <div class="form-group" style="margin-bottom: 0; flex-grow: 1;">
                    <label>Buscar</label>
                    <input type="text" name="search" class="form-control" placeholder="Nombre, usuario o email..." value="<?= htmlspecialchars($filters['search'] ?? '') ?>">
                </div>

                <button type="submit" class="btn btn-primary">Filtrar</button>
                <a href="/test-vocacional/admin/payment-status" class="btn btn-outline">Limpiar</a>
            </form>
        </div>

        <div class="table-container">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Usuario</th>
                        <th>Nombre</th>
                        <th>Rol</th>
                        <th>Institución</th>
                        <th>Pago</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td><?= $u['id'] ?></td>
                            <td><?= htmlspecialchars($u['username']) ?></td>
                            <td><?= htmlspecialchars(trim($u['nombre'] . ' ' . $u['apellido'])) ?></td>
                            <td><?= htmlspecialchars($u['rol']) ?></td>
                            <td><?= htmlspecialchars($u['institucion_nombre'] ?? '—') ?></td>
                            <td style="font-weight: 600; color: <?= ($u['payment_status'] ?? 'unpaid') === 'paid' ? '#2d8a4d' : '#c0392b' ?>;">
                                <?= strtoupper($u['payment_status'] ?? 'unpaid') ?>
                            </td>
                            <td>
                                <form method="POST" action="/test-vocacional/admin/payment-status/update" style="display:inline-flex; gap: 6px; align-items: center;">
                                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                    <select name="payment_status" style="padding: 6px; border: 1px solid #ddd; border-radius: 4px;">
                                        <option value="paid" <?= ($u['payment_status'] ?? '') === 'paid' ? 'selected' : '' ?>>Paid</option>
                                        <option value="unpaid" <?= ($u['payment_status'] ?? '') === 'unpaid' ? 'selected' : '' ?>>Unpaid</option>
                                    </select>
                                    <button type="submit" class="btn btn-sm btn-primary">Guardar</button>
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
                if ($queryString) {
                    $queryString = '&' . $queryString;
                }
                ?>
                <div class="pagination" style="margin-top: 20px; display: flex; justify-content: center; gap: 10px;">
                    <?php if ($currentPage > 1): ?>
                        <a href="?page=1<?= $queryString ?>" class="btn btn-sm btn-outline-secondary">&laquo; Primera</a>
                        <a href="?page=<?= $currentPage - 1 ?><?= $queryString ?>" class="btn btn-sm btn-outline-secondary">Anterior</a>
                    <?php endif; ?>
                    <span style="align-self: center;">Página <?= $currentPage ?> de <?= $totalPages ?></span>
                    <?php if ($currentPage < $totalPages): ?>
                        <a href="?page=<?= $currentPage + 1 ?><?= $queryString ?>" class="btn btn-sm btn-outline-secondary">Siguiente</a>
                        <a href="?page=<?= $totalPages ?><?= $queryString ?>" class="btn btn-sm btn-outline-secondary">Última &raquo;</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<script>
    document.querySelectorAll('.searchable-filter').forEach(el => {
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