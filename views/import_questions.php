<?php
$pageTitle = APP_NAME . ' - Importar Preguntas';
require 'views/layout/header.php';
?>

<div class="admin-container">
    <?php require 'views/layout/sidebar.php'; ?>

    <main class="admin-main">
        <div class="admin-header">
            <h1>Importar Preguntas</h1>
            <p>Sube preguntas desde archivos Excel o Word</p>
        </div>


        <div class="import-container">
            <!-- Upload Form -->
            <div class="upload-section">
                <form method="POST" action="/test-vocacional/admin/questions/import" enctype="multipart/form-data"
                    id="importForm">
                    <div class="drag-drop-area" id="dragDropArea">
                        <div class="upload-icon">📁</div>
                        <h3>Arrastra aquí tu archivo</h3>
                        <p>O haz clic para seleccionar un archivo</p>
                        <input type="file" name="file" id="fileInput" accept=".xlsx,.xls,.docx,.doc" required>
                        <p class="file-info">Formatos permitidos: Excel (.xlsx, .xls) o Word (.docx, .doc)</p>
                        <p class="file-info">Tamaño máximo: 10MB</p>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary btn-lg">
                            Importar Preguntas
                        </button>
                    </div>
                </form>
            </div>

            <!-- Templates Section -->
            <div class="templates-section">
                <h3>Plantillas de Ejemplo</h3>
                <p>Descarga las plantillas con el formato correcto:</p>

                <div class="template-downloads">
                    <a href="/test-vocacional/utils/download-template?type=excel" class="btn btn-secondary">
                        📊 Descargar Plantilla Excel
                    </a>
                    <a href="/test-vocacional/utils/download-template?type=word" class="btn btn-secondary">
                        📝 Descargar Plantilla Word
                    </a>
                </div>
            </div>

            <!-- Format Instructions -->
            <div class="instructions-section">
                <h3>Instrucciones de Formato</h3>

                <div class="format-instructions">
                    <h4>Formato Excel:</h4>
                    <ul>
                        <li>Columna A: Categoría (ciencias, tecnologia, humanidades, artes, salud, negocios)</li>
                        <li>Columna B: Tipo (intereses, habilidades, valores)</li>
                        <li>Columna C: Pregunta (texto completo, 10-500 caracteres)</li>
                        <li>Columna D: Peso (opcional, 1-10, por defecto 1)</li>
                    </ul>

                    <h4>Formato Word:</h4>
                    <ul>
                        <li>Use # para categorías principales</li>
                        <li>Use ## para tipos de preguntas</li>
                        <li>Use números seguidos de punto para cada pregunta</li>
                        <li>Ejemplo: "1. ¿Pregunta ejemplo?"</li>
                    </ul>
                </div>
            </div>
        </div>
    </main>
</div>

<script>
    // Drag and drop functionality
    const dragDropArea = document.getElementById('dragDropArea');
    const fileInput = document.getElementById('fileInput');
    const importForm = document.getElementById('importForm');

    dragDropArea.addEventListener('click', () => {
        fileInput.click();
    });

    dragDropArea.addEventListener('dragover', (e) => {
        e.preventDefault();
        dragDropArea.classList.add('dragover');
    });

    dragDropArea.addEventListener('dragleave', () => {
        dragDropArea.classList.remove('dragover');
    });

    dragDropArea.addEventListener('drop', (e) => {
        e.preventDefault();
        dragDropArea.classList.remove('dragover');

        const files = e.dataTransfer.files;
        if (files.length > 0) {
            fileInput.files = files;
            updateFileInfo(files[0]);
        }
    });

    fileInput.addEventListener('change', (e) => {
        if (e.target.files.length > 0) {
            updateFileInfo(e.target.files[0]);
        }
    });

    function updateFileInfo(file) {
        const fileInfo = dragDropArea.querySelector('.file-info') || document.createElement('div');
        fileInfo.className = 'file-info';
        fileInfo.innerHTML = `Archivo seleccionado: <strong>${file.name}</strong> (${formatFileSize(file.size)})`;

        if (!dragDropArea.querySelector('.file-info')) {
            dragDropArea.appendChild(fileInfo);
        }
    }

    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    // Form validation
    importForm.addEventListener('submit', (e) => {
        if (!fileInput.files.length) {
            e.preventDefault();
            alert('Por favor selecciona un archivo para importar.');
            return;
        }

        const file = fileInput.files[0];
        const maxSize = 10 * 1024 * 1024; // 10MB

        if (file.size > maxSize) {
            e.preventDefault();
            alert('El archivo excede el tamaño máximo permitido (10MB).');
            return;
        }
    });
</script>
<?php require 'views/layout/footer.php'; ?>