<?php require 'views/layout/header.php'; ?>

<div class="admin-container">
    <?php require 'views/layout/sidebar.php'; ?>
    <main class="admin-main">
        <div class="admin-header">
            <h1>Instituciones Educativas</h1>
            <p>Agregar y administrar colegios (código AMIE)</p>
        </div>


        <div class="table-container" id="formContainer">
            <h2 id="formTitle">Agregar Institución</h2>
            <form method="POST" action="/test-vocacional/admin/institutions" class="institution-form" id="instForm">
                <input type="hidden" name="id" id="instId">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label>Nombre</label>
                        <input type="text" name="nombre" id="instNombre" required>
                    </div>
                    <div class="form-group">
                        <label>Código AMIE</label>
                        <input type="text" name="codigo" id="instCodigo" required>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label>Tipo</label>
                        <select name="tipo" id="instTipo" required>
                            <option value="Fiscal">Fiscal</option>
                            <option value="Particular">Particular</option>
                            <option value="Fiscomisional">Fiscomisional</option>
                            <option value="Municipal">Municipal</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Provincia</label>
                        <select name="provincia" id="instProvincia" required>
                            <option value="">Seleccione...</option>
                            <?php
                            $provincias = ["Azuay", "Bolívar", "Cañar", "Carchi", "Chimborazo", "Cotopaxi", "El Oro", "Esmeraldas", "Galápagos", "Guayas", "Imbabura", "Loja", "Los Ríos", "Manabí", "Morona Santiago", "Napo", "Orellana", "Pastaza", "Pichincha", "Santa Elena", "Santo Domingo de los Tsáchilas", "Sucumbíos", "Tungurahua", "Zamora Chinchipe"];
                            foreach ($provincias as $prov): ?>
                                <option value="<?= $prov ?>"><?= $prov ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label>Zona</label>
                        <select name="zona" id="instZona" required>
                            <option value="">Seleccione...</option>
                            <?php for ($i = 1; $i <= 12; $i++): ?>
                                <option value="<?= $i ?>"><?= $i ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Cantón</label>
                        <input type="text" name="canton" id="instCanton" required>
                    </div>
                    <div class="form-group">
                        <label>Distrito</label>
                        <select name="distrito" id="instDistrito" required>
                            <option value="">Seleccione...</option>
                            <?php
                            $distritos = ["04D02", "04D03", "04D01", "08D05", "08D03", "08D02", "08D01", "08D04", "08D06", "10D02", "10D03", "10D01", "21D01", "21D03", "21D02", "21D04", "15D01", "15D02", "22D02", "22D03", "22D01", "17D10", "17D11", "17D12", "06D02", "06D01", "06D04", "06D03", "06D05", "05D02", "05D01", "05D04", "05D03", "05D05", "05D06", "16D02", "16D01", "18D01", "18D02", "18D06", "18D04", "18D03", "18D05", "23D03", "13D07", "13D04", "23D01", "13D03", "13D06", "13D12", "13D08", "13D05", "13D10", "13D02", "13D01", "13D09", "13D11", "23D02", "02D03", "02D04", "12D01", "12D02", "02D02", "09D16", "02D01", "09D12", "09D18", "20D01", "09D11", "09D13", "09D19", "09D15", "12D06", "09D22", "09D17", "09D14", "09D20", "09D21", "12D03", "12D05", "12D04", "24D02", "24D01", "01D07", "01D04", "01D02", "01D01", "01D06", "01D03", "01D05", "01D08", "03D01", "03D02", "03D03", "14D04", "14D02", "14D06", "14D03", "14D01", "14D05", "07D05", "07D03", "07D04", "07D01", "07D02", "07D06", "11D06", "11D02", "11D04", "11D05", "11D01", "11D07", "11D03", "11D08", "11D09", "19D02", "19D03", "19D04", "19D01", "09D05", "09D23", "09D24", "09D03", "09D02", "09D04", "09D09", "09D07", "09D08", "09D06", "09D01", "09D10", "17D07", "17D08", "17D03", "17D05", "17D04", "17D02", "17D09", "17D06", "17D01"];
                            sort($distritos);
                            foreach ($distritos as $dist): ?>
                                <option value="<?= $dist ?>"><?= $dist ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary" id="submitBtn">Agregar Institución</button>
                    <button type="button" class="btn btn-secondary" id="cancelBtn"
                        style="display: none;">Cancelar</button>
                </div>
            </form>
        </div>

        <div class="table-container">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2 style="margin-bottom: 0;">Listado de Instituciones</h2>
                <div class="search-box" style="width: 300px;">
                    <input type="text" id="institutionSearch"
                        placeholder="Buscar institución por nombre, código o tipo..." class="form-control"
                        style="width: 100%;">
                </div>
            </div>
            <table class="admin-table" id="institutionsTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Provincia</th>
                        <th>Cantón</th>
                        <th>Zona</th>
                        <th>Distrito</th>
                        <th>AMIE</th>
                        <th>Tipo</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($institutions as $inst): ?>
                        <tr>
                            <td><?= $inst['id'] ?></td>
                            <td><?= htmlspecialchars($inst['nombre']) ?></td>
                            <td><?= htmlspecialchars($inst['provincia'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($inst['canton'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($inst['zona'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($inst['distrito'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($inst['codigo']) ?></td>
                            <td><?= htmlspecialchars($inst['tipo']) ?></td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary edit-inst" data-id="<?= $inst['id'] ?>"
                                    data-nombre="<?= htmlspecialchars($inst['nombre']) ?>"
                                    data-codigo="<?= htmlspecialchars($inst['codigo']) ?>"
                                    data-tipo="<?= htmlspecialchars($inst['tipo']) ?>"
                                    data-provincia="<?= htmlspecialchars($inst['provincia'] ?? '') ?>"
                                    data-canton="<?= htmlspecialchars($inst['canton'] ?? '') ?>"
                                    data-zona="<?= htmlspecialchars($inst['zona'] ?? '') ?>"
                                    data-distrito="<?= htmlspecialchars($inst['distrito'] ?? '') ?>">
                                    Editar
                                </button>
                                <?php if ($_SESSION['user_role'] === 'administrador'): ?>
                                    <a href="/test-vocacional/admin/institutions?delete=<?= $inst['id'] ?>"
                                        class="btn btn-sm btn-outline-danger"
                                        onclick="return confirm('¿Está seguro de eliminar esta institución? Esta acción no se puede deshacer y podría afectar a los usuarios vinculados.')">
                                        Eliminar
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?php if ($totalPages > 1): ?>
                <div class="pagination" style="margin-top: 20px; display: flex; justify-content: center; gap: 10px;">
                    <?php if ($currentPage > 1): ?>
                        <a href="?page=1" class="btn btn-sm btn-outline-secondary">&laquo; Primera</a>
                        <a href="?page=<?= $currentPage - 1 ?>" class="btn btn-sm btn-outline-secondary">Anterior</a>
                    <?php endif; ?>

                    <span style="align-self: center;">Página <?= $currentPage ?> de <?= $totalPages ?></span>

                    <?php if ($currentPage < $totalPages): ?>
                        <a href="?page=<?= $currentPage + 1 ?>" class="btn btn-sm btn-outline-secondary">Siguiente</a>
                        <a href="?page=<?= $totalPages ?>" class="btn btn-sm btn-outline-secondary">Última &raquo;</a>
                    <?php endif; ?>
                </div>
                <div style="text-align: center; margin-top: 10px; color: #666; font-size: 0.9em;">
                    Total: <?= $totalRecords ?> instituciones
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<script>
    const instForm = document.getElementById('instForm');
    const instId = document.getElementById('instId');
    const formTitle = document.getElementById('formTitle');
    const submitBtn = document.getElementById('submitBtn');
    const cancelBtn = document.getElementById('cancelBtn');

    // Edit button click handler
    document.querySelectorAll('.edit-inst').forEach(btn => {
        btn.addEventListener('click', function () {
            const data = this.dataset;

            instId.value = data.id;
            document.getElementById('instNombre').value = data.nombre;
            document.getElementById('instCodigo').value = data.codigo;
            document.getElementById('instTipo').value = data.tipo;
            document.getElementById('instProvincia').value = data.provincia;
            document.getElementById('instCanton').value = data.canton;
            document.getElementById('instZona').value = data.zona;
            document.getElementById('instDistrito').value = data.distrito;

            formTitle.textContent = 'Editar Institución';
            submitBtn.textContent = 'Guardar Cambios';
            cancelBtn.style.display = 'inline-block';

            document.getElementById('formContainer').scrollIntoView({ behavior: 'smooth' });
        });
    });

    // Cancel button click handler
    cancelBtn.addEventListener('click', function () {
        instForm.reset();
        instId.value = '';
        formTitle.textContent = 'Agregar Institución';
        submitBtn.textContent = 'Agregar Institución';
        this.style.display = 'none';
    });

    // Search filter
    document.getElementById('institutionSearch').addEventListener('keyup', function () {
        const value = this.value.toLowerCase();
        const rows = document.querySelectorAll('#institutionsTable tbody tr');

        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(value) ? '' : 'none';
        });
    });
</script>

<?php require 'views/layout/footer.php'; ?>