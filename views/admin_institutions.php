<?php require 'views/layout/header.php'; ?>

<div class="admin-container">
    <?php require 'views/layout/sidebar.php'; ?>
    <main class="admin-main">
        <div class="admin-header">
            <h1>Instituciones Educativas</h1>
            <p>Agregar y administrar colegios (código AMIE)</p>
        </div>


        <div class="table-container">
            <h2>Agregar Institución</h2>
            <form method="POST" action="/test-vocacional/admin/institutions" class="institution-form">
                <div class="form-group">
                    <label>Nombre</label>
                    <input type="text" name="nombre" required>
                </div>
                <div class="form-group">
                    <label>Código AMIE</label>
                    <input type="text" name="codigo" required>
                </div>
                <div class="form-group">
                    <label>Tipo</label>
                    <select name="tipo" required>
                        <option value="Fiscal">Fiscal</option>
                        <option value="Particular">Particular</option>
                        <option value="Fiscomisional">Fiscomisional</option>
                    </select>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Agregar Institución</button>
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
                        <th>Código AMIE</th>
                        <th>Tipo</th>
                        <th>Creado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($institutions as $inst): ?>
                        <tr>
                            <td><?= $inst['id'] ?></td>
                            <td><?= htmlspecialchars($inst['nombre']) ?></td>
                            <td><?= htmlspecialchars($inst['codigo']) ?></td>
                            <td><?= htmlspecialchars($inst['tipo']) ?></td>
                            <td><?= $inst['created_at'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<script>
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