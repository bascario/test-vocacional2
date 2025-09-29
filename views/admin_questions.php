<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Preguntas - Test Vocacional</title>
    <style>
        /* Estilos base del sistema anterior */
        .import-container {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin: 20px 0;
        }
        
        .file-upload-area {
            border: 3px dashed #667eea;
            border-radius: 15px;
            padding: 40px;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);
        }
        
        .file-upload-area:hover {
            border-color: #764ba2;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
            transform: translateY(-2px);
        }
        
        .file-upload-area.dragover {
            border-color: #28a745;
            background: linear-gradient(135deg, rgba(40, 167, 69, 0.1) 0%, rgba(40, 167, 69, 0.05) 100%);
        }
        
        .upload-icon {
            font-size: 4em;
            color: #667eea;
            margin-bottom: 20px;
        }
        
        .upload-text {
            font-size: 1.2em;
            color: #555;
            margin-bottom: 15px;
        }
        
        .upload-hint {
            color: #888;
            font-size: 0.9em;
        }
        
        .progress-container {
            display: none;
            margin: 20px 0;
        }
        
        .progress-bar {
            width: 100%;
            height: 20px;
            background: #f1f1f1;
            border-radius: 10px;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(45deg, #667eea, #764ba2);
            width: 0%;
            transition: width 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 12px;
        }
        
        .result-container {
            margin-top: 30px;
            padding: 20px;
            border-radius: 10px;
            display: none;
        }
        
        .result-success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        
        .result-error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        
        .question-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .question-table th {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: bold;
        }
        
        .question-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #e1e1e1;
            vertical-align: top;
        }
        
        .question-table tr:hover {
            background: #f8f9fa;
        }
        
        .question-text {
            max-width: 400px;
            word-wrap: break-word;
        }
        
        .category-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            color: white;
            text-transform: uppercase;
        }
        
        .category-ciencias { background: #007bff; }
        .category-tecnologia { background: #28a745; }
        .category-humanidades { background: #ffc107; color: #333; }
        .category-artes { background: #e83e8c; }
        .category-salud { background: #20c997; }
        .category-negocios { background: #fd7e14; }
        
        .type-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 15px;
            font-size: 11px;
            background: #6c757d;
            color: white;
            margin-top: 5px;
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .btn-small {
            padding: 6px 12px;
            font-size: 12px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-edit {
            background: #ffc107;
            color: #333;
        }
        
        .btn-delete {
            background: #dc3545;
            color: white;
        }
        
        .btn-small:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 15px;
            text-align: center;
        }
        
        .stat-number {
            font-size: 2.5em;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .stat-label {
            font-size: 0.9em;
            opacity: 0.9;
        }
        
        .search-container {
            margin-bottom: 20px;
            display: flex;
            gap: 15px;
            align-items: center;
        }
        
        .search-input {
            flex: 1;
            padding: 12px;
            border: 2px solid #e1e1e1;
            border-radius: 8px;
            font-size: 16px;
        }
        
        .filter-select {
            padding: 12px;
            border: 2px solid #e1e1e1;
            border-radius: 8px;
            background: white;
            min-width: 150px;
        }
        
        .bulk-actions {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: none;
        }
        
        .template-buttons {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
            justify-content: center;
        }
        
        .btn-template {
            background: linear-gradient(45deg, #28a745, #20c997);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 10px;
            font-size: 16px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
        }
        
        .btn-template:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.3);
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
        }
        
        .modal-content {
            background: white;
            margin: 5% auto;
            padding: 30px;
            border-radius: 15px;
            width: 90%;
            max-width: 600px;
            max-height: 80vh;
            overflow-y: auto;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e1e1e1;
        }
        
        .modal-close {
            background: none;
            border: none;
            font-size: 2em;
            cursor: pointer;
            color: #999;
        }
        
        .error-list {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 15px;
            margin: 15px 0;
        }
        
        .error-list ul {
            margin: 0;
            padding-left: 20px;
        }
        
        .error-list li {
            color: #856404;
            margin-bottom: 5px;
        }
    </style>
</head>
<body>

<?php if (basename($_SERVER['PHP_SELF']) == 'admin_questions.php'): ?>
<div class="navigation">
    <div class="nav-container">
        <div class="nav-logo">Gestión de Preguntas</div>
        <ul class="nav-menu">
            <li><a href="/admin/questions">Preguntas</a></li>
            <li><a href="/admin/questions/import">Importar</a></li>
            <li><a href="/admin">Dashboard</a></li>
            <li><a href="/logout">Cerrar Sesión</a></li>
        </ul>
    </div>
</div>

<div class="container" style="max-width: 1400px;">
    <div class="header">
        <h1>Banco de Preguntas</h1>
        <p>Gestiona las preguntas del test vocacional</p>
    </div>
    
    <!-- Estadísticas -->
    <div class="stats-container">
        <div class="stat-card">
            <div class="stat-number"><?= $totalQuestions ?></div>
            <div class="stat-label">Total de Preguntas</div>
        </div>
        
        <?php 
        $categoryCount = array_count_values(array_column($questions, 'categoria'));
        $mostPopularCategory = array_keys($categoryCount, max($categoryCount))[0] ?? 'N/A';
        ?>
        
        <div class="stat-card">
            <div class="stat-number"><?= count($categoryCount) ?></div>
            <div class="stat-label">Categorías Activas</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-number"><?= max($categoryCount) ?? 0 ?></div>
            <div class="stat-label">Preguntas en <?= ucfirst($mostPopularCategory) ?></div>
        </div>
    </div>
    
    <!-- Botones de acción -->
    <div class="action-buttons" style="margin-bottom: 30px;">
        <a href="/admin/questions/import" class="btn">📁 Importar Preguntas</a>
        <button class="btn btn-secondary" onclick="showBulkActions()">✅ Acciones en Lote</button>
        <a href="/admin/questions/template/excel" class="btn-template">📊 Descargar Plantilla Excel</a>
        <a href="/admin/questions/template/word" class="btn-template">📄 Descargar Plantilla Word</a>
    </div>
    
    <!-- Búsqueda y filtros -->
    <div class="search-container">
        <input type="text" class="search-input" placeholder="Buscar preguntas..." id="searchInput">
        
        <select class="filter-select" id="categoryFilter">
            <option value="">Todas las categorías</option>
            <option value="ciencias">Ciencias</option>
            <option value="tecnologia">Tecnología</option>
            <option value="humanidades">Humanidades</option>
            <option value="artes">Artes</option>
            <option value="salud">Salud</option>
            <option value="negocios">Negocios</option>
        </select>
        
        <select class="filter-select" id="typeFilter">
            <option value="">Todos los tipos</option>
            <option value="intereses">Intereses</option>
            <option value="habilidades">Habilidades</option>
            <option value="valores">Valores</option>
        </select>
        
        <button class="btn" onclick="applyFilters()">🔍 Filtrar</button>
    </div>
    
    <!-- Acciones en lote -->
    <div class="bulk-actions" id="bulkActions">
        <h4>Acciones en Lote</h4>
        <div style="display: flex; gap: 15px; align-items: center;">
            <button class="btn btn-secondary" onclick="selectAll()">Seleccionar Todo</button>
            <button class="btn btn-secondary" onclick="deselectAll()">Deseleccionar Todo</button>
            <button class="btn" style="background: #dc3545;" onclick="bulkDelete()">🗑️ Eliminar Seleccionadas</button>
            <span id="selectedCount">0 preguntas seleccionadas</span>
        </div>
    </div>
    
    <!-- Tabla de preguntas -->
    <table class="question-table" id="questionsTable">
        <thead>
            <tr>
                <th width="40px">
                    <input type="checkbox" id="selectAllCheckbox" onchange="toggleAllSelection()">
                </th>
                <th width="100px">Categoría</th>
                <th width="80px">Tipo</th>
                <th>Pregunta</th>
                <th width="60px">Peso</th>
                <th width="120px">Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($questions as $question): ?>
            <tr data-category="<?= $question['categoria'] ?>" data-type="<?= $question['tipo'] ?>">
                <td>
                    <input type="checkbox" name="question_ids[]" value="<?= $question['id'] ?>" 
                           class="question-checkbox" onchange="updateSelectedCount()">
                </td>
                <td>
                    <span class="category-badge category-<?= $question['categoria'] ?>">
                        <?= ucfirst($question['categoria']) ?>
                    </span>
                </td>
                <td>
                    <span class="type-badge">
                        <?= ucfirst($question['tipo']) ?>
                    </span>
                </td>
                <td class="question-text">
                    <?= htmlspecialchars($question['pregunta']) ?>
                </td>
                <td style="text-align: center;">
                    <?= $question['peso'] ?? 1 ?>
                </td>
                <td>
                    <div class="action-buttons">
                        <button class="btn-small btn-edit" onclick="editQuestion(<?= $question['id'] ?>)">
                            ✏️ Editar
                        </button>
                        <button class="btn-small btn-delete" onclick="deleteQuestion(<?= $question['id'] ?>)">
                            🗑️ Eliminar
                        </button>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
let selectedQuestions = new Set();

function showBulkActions() {
    const bulkActions = document.getElementById('bulkActions');
    bulkActions.style.display = bulkActions.style.display === 'none' ? 'block' : 'none';
}

function applyFilters() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    const categoryFilter = document.getElementById('categoryFilter').value;
    const typeFilter = document.getElementById('typeFilter').value;
    
    const rows = document.querySelectorAll('#questionsTable tbody tr');
    
    rows.forEach(row => {
        const questionText = row.querySelector('.question-text').textContent.toLowerCase();
        const category = row.dataset.category;
        const type = row.dataset.type;
        
        const matchesSearch = questionText.includes(searchTerm);
        const matchesCategory = !categoryFilter || category === categoryFilter;
        const matchesType = !typeFilter || type === typeFilter;
        
        if (matchesSearch && matchesCategory && matchesType) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

function toggleAllSelection() {
    const selectAll = document.getElementById('selectAllCheckbox');
    const checkboxes = document.querySelectorAll('.question-checkbox');
    
    checkboxes.forEach(checkbox => {
        if (checkbox.closest('tr').style.display !== 'none') {
            checkbox.checked = selectAll.checked;
            if (selectAll.checked) {
                selectedQuestions.add(checkbox.value);
            } else {
                selectedQuestions.delete(checkbox.value);
            }
        }
    });
    
    updateSelectedCount();
}

function selectAll() {
    const checkboxes = document.querySelectorAll('.question-checkbox');
    checkboxes.forEach(checkbox => {
        if (checkbox.closest('tr').style.display !== 'none') {
            checkbox.checked = true;
            selectedQuestions.add(checkbox.value);
        }
    });
    updateSelectedCount();
}

function deselectAll() {
    const checkboxes = document.querySelectorAll('.question-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.checked = false;
        selectedQuestions.delete(checkbox.value);
    });
    document.getElementById('selectAllCheckbox').checked = false;
    updateSelectedCount();
}

function updateSelectedCount() {
    const selectedCount = document.querySelectorAll('.question-checkbox:checked').length;
    document.getElementById('selectedCount').textContent = `${selectedCount} preguntas seleccionadas`;
}

function editQuestion(id) {
    // Implementar modal de edición
    alert('Función de edición en desarrollo. ID: ' + id);
}

function deleteQuestion(id) {
    if (confirm('¿Estás seguro de que quieres eliminar esta pregunta?')) {
        window.location.href = `/admin/questions/delete/${id}`;
    }
}

function bulkDelete() {
    const selected = Array.from(selectedQuestions);
    if (selected.length === 0) {
        alert('Por favor selecciona al menos una pregunta para eliminar.');
        return;
    }
    
    if (confirm(`¿Estás seguro de que quieres eliminar ${selected.length} preguntas seleccionadas?`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/admin/questions/bulk-delete';
        
        selected.forEach(id => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'question_ids[]';
            input.value = id;
            form.appendChild(input);
        });
        
        document.body.appendChild(form);
        form.submit();
    }
}

// Aplicar filtros en tiempo real
document.getElementById('searchInput').addEventListener('input', applyFilters);
document.getElementById('categoryFilter').addEventListener('change', applyFilters);
document.getElementById('typeFilter').addEventListener('change', applyFilters);
</script>
<?php endif; ?>
<?php if (basename($_SERVER['PHP_SELF']) == 'import_questions.php'): ?>
<div class="navigation">
    <div class="nav-container">
        <div class="nav-logo">Importar Preguntas</div>
        <ul class="nav-menu">
            <li><a href="/admin/questions">Preguntas</a></li>
            <li><a href="/admin/questions/import">Importar</a></li>
            <li><a href="/admin">Dashboard</a></li>
            <li><a href="/logout">Cerrar Sesión</a></li>
        </ul>
    </div>
</div>

<div class="container" style="max-width: 900px;">
    <div class="header">
        <h1>Importar Preguntas desde Excel o Word</h1>
        <p>Carga masivamente preguntas para el test vocacional</p>
    </div>
    
    <!-- Botones de plantillas -->
    <div class="template-buttons">
        <a href="/admin/questions/template/excel" class="btn-template">
            📊 Descargar Plantilla Excel
        </a>
        <a href="/admin/questions/template/word" class="btn-template">
            📄 Descargar Plantilla Word
        </a>
    </div>
    
    <div class="import-container">
        <form id="importForm" enctype="multipart/form-data">
            <!-- Área de subida de archivos -->
            <div class="file-upload-area" id="uploadArea">
                <div class="upload-icon">📁</div>
                <div class="upload-text">
                    <strong>Arrastra tu archivo aquí o haz clic para seleccionar</strong>
                </div>
                <div class="upload-hint">
                    Formatos soportados: .xlsx, .xls, .docx, .doc (máximo 10MB)
                </div>
                <input type="file" name="question_file" id="fileInput" 
                       accept=".xlsx,.xls,.docx,.doc" style="display: none;">
            </div>
            
            <!-- Información del archivo seleccionado -->
            <div id="fileInfo" style="display: none; margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 10px;">
                <h4>Archivo seleccionado:</h4>
                <div id="fileName"></div>
                <div id="fileSize"></div>
                <div id="fileType"></div>
            </div>
            
            <!-- Barra de progreso -->
            <div class="progress-container" id="progressContainer">
                <div class="progress-bar">
                    <div class="progress-fill" id="progressFill">0%</div>
                </div>
                <div style="text-align: center; margin-top: 10px; color: #666;" id="progressText">
                    Procesando archivo...
                </div>
            </div>
            
            <!-- Botones de acción -->
            <div style="text-align: center; margin-top: 30px;">
                <button type="submit" class="btn" id="importBtn" disabled>
                    📤 Importar Preguntas
                </button>
                <button type="button" class="btn btn-secondary" onclick="resetForm()">
                    🔄 Limpiar
                </button>
            </div>
        </form>
        
        <!-- Contenedor de resultados -->
        <div class="result-container" id="resultContainer">
            <div id="resultContent"></div>
        </div>
    </div>
    
    <!-- Instrucciones -->
    <div class="import-container">
        <h3>📋 Instrucciones de Uso</h3>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-top: 20px;">
            <div style="padding: 20px; background: #e3f2fd; border-radius: 10px;">
                <h4 style="color: #1976d2; margin-bottom: 15px;">📊 Para Excel</h4>
                <ul style="margin: 0; padding-left: 20px;">
                    <li>Descarga la plantilla Excel</li>
                    <li>Completa las columnas: Categoría, Tipo, Pregunta, Peso</li>
                    <li>Usa las listas desplegables proporcionadas</li>
                    <li>Guarda como .xlsx o .xls</li>
                </ul>
            </div>
            
            <div style="padding: 20px; background: #f3e5f5; border-radius: 10px;">
                <h4 style="color: #7b1fa2; margin-bottom: 15px;">📄 Para Word</h4>
                <ul style="margin: 0; padding-left: 20px;">
                    <li>Descarga la plantilla Word</li>
                    <li>Organiza por categorías usando #</li>
                    <li>Organiza por tipos usando ##</li>
                    <li>Numera las preguntas: 1. 2. 3...</li>
                </ul>
            </div>
        </div>
        
        <div style="margin-top: 20px; padding: 20px; background: #fff3cd; border-radius: 10px; border-left: 4px solid #ffc107;">
            <h4 style="color: #856404; margin-bottom: 15px;">⚠️ Importante</h4>
            <ul style="margin: 0; padding-left: 20px; color: #856404;">
                <li><strong>Categorías válidas:</strong> ciencias, tecnologia, humanidades, artes, salud, negocios</li>
                <li><strong>Tipos válidos:</strong> intereses, habilidades, valores</li>
                <li><strong>Preguntas duplicadas:</strong> Se omitirán automáticamente</li>
                <li><strong>Formato de preguntas:</strong> Mínimo 10 caracteres, máximo 500</li>
            </ul>
        </div>
    </div>
</div>

<script>
const uploadArea = document.getElementById('uploadArea');
const fileInput = document.getElementById('fileInput');
const importForm = document.getElementById('importForm');
const importBtn = document.getElementById('importBtn');
const progressContainer = document.getElementById('progressContainer');
const progressFill = document.getElementById('progressFill');
const progressText = document.getElementById('progressText');
const resultContainer = document.getElementById('resultContainer');
const fileInfo = document.getElementById('fileInfo');

// Eventos de drag & drop
uploadArea.addEventListener('click', () => fileInput.click());
uploadArea.addEventListener('dragover', handleDragOver);
uploadArea.addEventListener('dragleave', handleDragLeave);
uploadArea.addEventListener('drop', handleDrop);
fileInput.addEventListener('change', handleFileSelect);

function handleDragOver(e) {
    e.preventDefault();
    uploadArea.classList.add('dragover');
}

function handleDragLeave(e) {
    e.preventDefault();
    uploadArea.classList.remove('dragover');
}

function handleDrop(e) {
    e.preventDefault();
    uploadArea.classList.remove('dragover');
    
    const files = e.dataTransfer.files;
    if (files.length > 0) {
        fileInput.files = files;
        handleFileSelect();
    }
}

function handleFileSelect() {
    const file = fileInput.files[0];
    
    if (file) {
        // Validar archivo
        const allowedTypes = [
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/msword'
        ];
        
        const allowedExtensions = ['xlsx', 'xls', 'docx', 'doc'];
        const fileExtension = file.name.split('.').pop().toLowerCase();
        
        if (!allowedExtensions.includes(fileExtension)) {
            alert('Formato de archivo no soportado. Solo se permiten archivos Excel (.xlsx, .xls) o Word (.docx, .doc)');
            resetForm();
            return;
        }
        
        if (file.size > 10 * 1024 * 1024) {
            alert('El archivo no puede exceder 10MB');
            resetForm();
            return;
        }
        
        // Mostrar información del archivo
        showFileInfo(file);
        importBtn.disabled = false;
    }
}

function showFileInfo(file) {
    const fileName = document.getElementById('fileName');
    const fileSize = document.getElementById('fileSize');
    const fileType = document.getElementById('fileType');
    
    fileName.innerHTML = `<strong>Nombre:</strong> ${file.name}`;
    fileSize.innerHTML = `<strong>Tamaño:</strong> ${formatFileSize(file.size)}`;
    fileType.innerHTML = `<strong>Tipo:</strong> ${getFileTypeDescription(file.name)}`;
    
    fileInfo.style.display = 'block';
    
    // Cambiar apariencia del área de upload
    uploadArea.innerHTML = `
        <div class="upload-icon">✅</div>
        <div class="upload-text">
            <strong>Archivo seleccionado: ${file.name}</strong>
        </div>
        <div class="upload-hint">
            Haz clic en "Importar Preguntas" para procesar el archivo
        </div>
    `;
}

function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

function getFileTypeDescription(filename) {
    const ext = filename.split('.').pop().toLowerCase();
    const descriptions = {
        'xlsx': 'Excel (Moderno)',
        'xls': 'Excel (Clásico)',
        'docx': 'Word (Moderno)',
        'doc': 'Word (Clásico)'
    };
    return descriptions[ext] || 'Desconocido';
}

function resetForm() {
    fileInput.value = '';
    importBtn.disabled = true;
    fileInfo.style.display = 'none';
    progressContainer.style.display = 'none';
    resultContainer.style.display = 'none';
    
    uploadArea.innerHTML = `
        <div class="upload-icon">📁</div>
        <div class="upload-text">
            <strong>Arrastra tu archivo aquí o haz clic para seleccionar</strong>
        </div>
        <div class="upload-hint">
            Formatos soportados: .xlsx, .xls, .docx, .doc (máximo 10MB)
        </div>
    `;
}

// Manejar envío del formulario
importForm.addEventListener('submit', function(e) {
    e.preventDefault();
    
    if (!fileInput.files[0]) {
        alert('Por favor selecciona un archivo');
        return;
    }
    
    const formData = new FormData();
    formData.append('question_file', fileInput.files[0]);
    
    // Mostrar progreso
    showProgress();
    
    // Simular progreso mientras se procesa
    simulateProgress();
    
    fetch('/admin/questions/import/process', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        hideProgress();
        showResult(data);
    })
    .catch(error => {
        hideProgress();
        showResult({
            success: false,
            message: 'Error de conexión: ' + error.message,
            data: { errors: ['No se pudo conectar con el servidor'] }
        });
    });
});

function showProgress() {
    progressContainer.style.display = 'block';
    importBtn.disabled = true;
    importBtn.innerHTML = '⏳ Procesando...';
}

function hideProgress() {
    progressContainer.style.display = 'none';
    importBtn.disabled = false;
    importBtn.innerHTML = '📤 Importar Preguntas';
}

function simulateProgress() {
    let progress = 0;
    const interval = setInterval(() => {
        progress += Math.random() * 15;
        if (progress > 90) progress = 90;
        
        progressFill.style.width = progress + '%';
        progressFill.textContent = Math.round(progress) + '%';
        
        if (progress >= 90) {
            clearInterval(interval);
        }
    }, 200);
}

function showResult(data) {
    const resultContent = document.getElementById('resultContent');
    resultContainer.style.display = 'block';
    
    if (data.success) {
        resultContainer.className = 'result-container result-success';
        
        let html = `
            <h4>✅ Importación Exitosa</h4>
            <p><strong>${data.message}</strong></p>
        `;
        
        if (data.data) {
            html += `
                <div style="margin-top: 15px;">
                    <p><strong>Resumen:</strong></p>
                    <ul>
                        <li>Total de preguntas en archivo: ${data.data.total}</li>
                        <li>Preguntas importadas exitosamente: ${data.data.saved}</li>
                        <li>Preguntas omitidas: ${data.data.total - data.data.saved}</li>
                    </ul>
                </div>
            `;
            
            if (data.data.errors && data.data.errors.length > 0) {
                html += `
                    <div class="error-list">
                        <h5>⚠️ Advertencias y errores:</h5>
                        <ul>
                            ${data.data.errors.map(error => `<li>${error}</li>`).join('')}
                        </ul>
                    </div>
                `;
            }
        }
        
        html += `
            <div style="margin-top: 20px; text-align: center;">
                <a href="/admin/questions" class="btn">Ver Preguntas Importadas</a>
                <button class="btn btn-secondary" onclick="resetForm()">Importar Otro Archivo</button>
            </div>
        `;
        
        resultContent.innerHTML = html;
        
    } else {
        resultContainer.className = 'result-container result-error';
        
        let html = `
            <h4>❌ Error en la Importación</h4>
            <p><strong>${data.message}</strong></p>
        `;
        
        if (data.data && data.data.errors && data.data.errors.length > 0) {
            html += `
                <div class="error-list">
                    <h5>Detalles de los errores:</h5>
                    <ul>
                        ${data.data.errors.map(error => `<li>${error}</li>`).join('')}
                    </ul>
                </div>
            `;
        }
        
        html += `
            <div style="margin-top: 20px; text-align: center;">
                <button class="btn" onclick="resetForm()">Intentar de Nuevo</button>
                <a href="/admin/questions/template/excel" class="btn btn-secondary">Descargar Plantilla</a>
            </div>
        `;
        
        resultContent.innerHTML = html;
    }
    
    // Scroll al resultado
    resultContainer.scrollIntoView({ behavior: 'smooth' });
}
</script>
<?php endif; ?>

<!-- ================================ -->
<!-- ACTUALIZACIÓN DEL ROUTER -->
<!-- ================================ -->
<?php
// Agregar estas rutas al router principal

class Router {
    public function route($uri) {
        switch (true) {
            // ... rutas existentes ...
            
            case '/admin/questions':
                $controller = new QuestionController();
                $controller->index();
                break;
            
            case '/admin/questions/import':
                $controller = new QuestionController();
                $controller->importForm();
                break;
            
            case '/admin/questions/import/process':
                $controller = new QuestionController();
                $controller->processImport();
                break;
            
            case (preg_match('/\/admin\/questions\/delete\/(\d+)/', $uri, $matches) ? true : false):
                $controller = new QuestionController();
                $controller->delete($matches[1]);
                break;
            
            case '/admin/questions/bulk-delete':
                $controller = new QuestionController();
                $controller->bulkDelete();
                break;
            
            case '/admin/questions/template/excel':
                $controller = new QuestionController();
                $controller->downloadTemplate();
                break;
            
            case '/admin/questions/template/word':
                $templateGenerator = new QuestionTemplateGenerator();
                $templateGenerator->generateWordTemplate();
                break;
            
            // ... otras rutas ...
        }
    }
}