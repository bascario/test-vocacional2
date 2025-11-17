<?php require 'views/layout/header.php'; ?>

<div class="admin-container">
        <aside class="admin-sidebar">
            <ul class="sidebar-menu">
                <li><a href="/test-vocacional/admin">📊 Dashboard</a></li>
                <li><a href="/test-vocacional/admin/questions">❓ Gestión de Preguntas</a></li>
                <li><a href="/test-vocacional/admin/institutions" class="active">🏫 Instituciones</a></li>
                <li><a href="/test-vocacional/admin/reports/group">📋 Reportes</a></li>
            </ul>
        </aside>

        <main class="admin-main">
            <div class="admin-header">
                <h1>Instituciones Educativas</h1>
                <p>Agregar y administrar colegios (código AMIE)</p>
            </div>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
            <?php endif; ?>
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-error"><?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
            <?php endif; ?>

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
                <h2>Listado de Instituciones</h2>
                <table class="admin-table">
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

    <?php require 'views/layout/footer.php'; ?>
</body>
</html>
