<?php
class QuestionController {
    private $questionModel;
    
    public function __construct() {
        $this->questionModel = new Question();
    }
    
    public function index() {
        $search = $_GET['search'] ?? '';
        $category = $_GET['category'] ?? '';
        $type = $_GET['type'] ?? '';
        
        if ($search) {
            $questions = $this->questionModel->search($search);
        } else {
            $conditions = [];
            if ($category) $conditions['categoria'] = $category;
            if ($type) $conditions['tipo'] = $type;
            
            $questions = $this->questionModel->findAll($conditions, 'categoria, tipo, id');
        }
        
        require_once 'views/admin_questions.php';
    }
    
    public function import() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!isset($_FILES['file'])) {
                $_SESSION['error'] = "No se seleccionó ningún archivo";
                header('Location: /test-vocacional/admin/questions/import');
                exit;
            }
            
            $file = $_FILES['file'];
            
            try {
                // Validate file
                $this->validateImportFile($file);
                
                // Process import
                require_once 'utils/QuestionImporter.php';
                $importer = new QuestionImporter();
                $result = $importer->import($file['tmp_name'], $file['type']);
                
                $_SESSION['success'] = sprintf(
                    "Importación exitosa: %d preguntas importadas, %d duplicadas",
                    $result['imported'],
                    $result['duplicates']
                );
                
                header('Location: /test-vocacional/admin/questions');
                exit;
                
            } catch (Exception $e) {
                $_SESSION['error'] = "Error al importar: " . $e->getMessage();
                header('Location: /test-vocacional/admin/questions/import');
                exit;
            }
        } else {
            require_once 'views/import_questions.php';
        }
    }
    
    public function delete() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /test-vocacional/admin/questions');
            exit;
        }
        
        $ids = $_POST['ids'] ?? [];
        
        if (empty($ids)) {
            $_SESSION['error'] = "No se seleccionaron preguntas para eliminar";
            header('Location: /test-vocacional/admin/questions');
            exit;
        }
        
        try {
            $deleted = $this->questionModel->deleteMultiple($ids);
            $_SESSION['success'] = "$deleted preguntas eliminadas exitosamente";
        } catch (Exception $e) {
            $_SESSION['error'] = "Error al eliminar preguntas: " . $e->getMessage();
        }
        
        header('Location: /test-vocacional/admin/questions');
        exit;
    }
    
    private function validateImportFile($file) {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("Error al subir el archivo");
        }
        
        if ($file['size'] > MAX_FILE_SIZE) {
            throw new Exception("El archivo excede el tamaño máximo permitido (10MB)");
        }
        
        $allowedTypes = array_merge(ALLOWED_EXCEL_TYPES, ALLOWED_WORD_TYPES);
        if (!in_array($file['type'], $allowedTypes)) {
            throw new Exception("Tipo de archivo no permitido. Use Excel o Word");
        }
    }
}
?>