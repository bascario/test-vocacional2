<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= APP_NAME ?> - Gestión de Preguntas</title>
    <link rel="stylesheet" href="/test-vocacional/assets/css/styles.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-brand">
                Gestión de Preguntas
            </div>
            <div class="nav-menu">
                <span><?= htmlspecialchars($_SESSION['user_name']) ?> (<?= $_SESSION['user_role'] ?>)</span>
                <a href="/test-vocacional/logout" class="btn btn-sm btn-outline">Cerrar Sesión</a>
            </div>
        </div>
    </nav>

    <div class="admin-container">
        <aside class="admin-sidebar">
            <ul class="sidebar-menu">
                <li><a href="/test-vocacional/admin">📊 Dashboard</a></li>
                <li><a href="/test-vocacional/admin/questions" class="active">❓ Gestión de Preguntas</a></li>
                <?php if ($_SESSION['user_role'] === 'administrador'): ?>
                    <li><a href="/test-vocacional/admin/questions/import">📥 Importar Preguntas</a></li>
                <?php endif; ?>
                <li><a href="/test-vocacional/admin/reports/group">📋 Reportes</a></li>
            </ul>
        </aside>

        <main class="admin-main">
            <div class="admin-header">
                <h1>Gestión de Preguntas</h1>
                <p>Administrar el banco de preguntas del test vocacional</p>
            </div>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <?= htmlspecialchars($_SESSION['success']) ?>
                    <?php unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-error">
                    <?= htmlspecialchars($_SESSION['error']) ?>
                    <?php unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>

            <!-- Search and Filter -->
            <div class="search-filter-container">
                <form method="GET" action="/test-vocacional/admin/questions" class="search-form">
                    <input type="text" name="search" placeholder="Buscar preguntas..." 
                           value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                    <select name="category">
                        <option value="">Todas las categorías</option>
                        <?php foreach (TEST_CATEGORIES as $category): ?>
                            <option value="<?= $category ?>" <?= ($_GET['category'] ?? '') === $category ? 'selected' : '' ?>>
                                <?= ucfirst($category) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <select name="type">
                        <option value="">Todos los tipos</option>
                        <?php foreach (TEST_TYPES as $type): ?>
                            <option value="<?= $type ?>" <?= ($_GET['type'] ?? '') === $type ? 'selected' : '' ?>>
                                <?= ucfirst($type) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn btn-primary">Buscar</button>
                </form>
            </div>

            <!-- Questions Table -->
            <div class="table-container">
                <form method="POST" action="/test-vocacional/admin/questions/delete" id="deleteForm">
                    <div class="table-actions">
                        <?php if ($_SESSION['user_role'] === 'administrador'): ?>
                            <button type="submit" class="btn btn-danger" 
                                    onclick="return confirm('¿Estás seguro de eliminar las preguntas seleccionadas?')">
                                Eliminar Seleccionadas
                            </button>
                        <?php endif; ?>
                        <span><?= count($questions) ?> preguntas encontradas</span>
                    </div>

                    <table class="admin-table">
                        <thead>
                            <tr>
                                <?php if ($_SESSION['user_role'] === 'administrador'): ?>
                                    <th><input type="checkbox" id="selectAll"></th>
                                <?php endif; ?>
                                <th>ID</th>
                                <th>Categoría</th>
                                <th>Tipo</th>
                                <th>Pregunta</th>
                                <th>Peso</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($questions as $question): ?>
                                <tr>
                                    <?php if ($_SESSION['user_role'] === 'administrador'): ?>
                                        <td><input type="checkbox" name="ids[]" value="<?= $question['id'] ?>"></td>
                                    <?php endif; ?>
                                    <td><?= $question['id'] ?></td>
                                    <td><?= ucfirst($question['categoria']) ?></td>
                                    <td><?= ucfirst($question['tipo']) ?></td>
                                    <td><?= htmlspecialchars(substr($question['pregunta'], 0, 100)) ?>...</td>
                                    <td><?= $question['peso'] ?></td>
                                    <td>
                                        <a href="/test-vocacional/admin/questions/edit?id=<?= $question['id'] ?>" 
                                           class="btn btn-sm btn-primary">Editar</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </form>
            </div>
        </main>
    </div>

    <script>
        // Select all checkbox functionality
        document.getElementById('selectAll')?.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('input[name="ids[]"]');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });
    </script>
</body>
</html>