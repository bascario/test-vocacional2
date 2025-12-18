<?php
$pageTitle = APP_NAME . ' - Gestión de Preguntas';
require 'views/layout/header.php';
?>

<div class="admin-container">
    <?php require 'views/layout/sidebar.php'; ?>

    <main class="admin-main">
        <div class="admin-header">
            <h1>Gestión de Preguntas</h1>
            <p>Administrar el banco de preguntas del test vocacional</p>
        </div>


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
    document.getElementById('selectAll')?.addEventListener('change', function () {
        const checkboxes = document.querySelectorAll('input[name="ids[]"]');
        checkboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
    });
</script>
<?php require 'views/layout/footer.php'; ?>
