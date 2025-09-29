<?php
    class Validator {
    
    // ... métodos anteriores ...
    
    public static function validateQuestionData($data) {
        $errors = [];
        
        // Validar campos requeridos
        if (empty($data['categoria'])) {
            $errors[] = "La categoría es obligatoria";
        }
        
        if (empty($data['tipo'])) {
            $errors[] = "El tipo es obligatorio";
        }
        
        if (empty($data['pregunta'])) {
            $errors[] = "La pregunta es obligatoria";
        }
        
        // Validar valores específicos
        $validCategories = ['ciencias', 'tecnologia', 'humanidades', 'artes', 'salud', 'negocios'];
        if (!empty($data['categoria']) && !in_array($data['categoria'], $validCategories)) {
            $errors[] = "Categoría inválida. Debe ser: " . implode(', ', $validCategories);
        }
        
        $validTypes = ['intereses', 'habilidades', 'valores'];
        if (!empty($data['tipo']) && !in_array($data['tipo'], $validTypes)) {
            $errors[] = "Tipo inválido. Debe ser: " . implode(', ', $validTypes);
        }
        
        // Validar longitud de pregunta
        if (!empty($data['pregunta']) && strlen($data['pregunta']) < 10) {
            $errors[] = "La pregunta debe tener al menos 10 caracteres";
        }
        
        if (!empty($data['pregunta']) && strlen($data['pregunta']) > 500) {
            $errors[] = "La pregunta no puede exceder 500 caracteres";
        }
        
        // Validar peso
        if (isset($data['peso']) && (!is_numeric($data['peso']) || $data['peso'] < 1 || $data['peso'] > 10)) {
            $errors[] = "El peso debe ser un número entre 1 y 10";
        }
        
        return $errors;
    }
    
    public static function validateImportFile($file) {
        $errors = [];
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            switch ($file['error']) {
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    $errors[] = "El archivo es demasiado grande";
                    break;
                case UPLOAD_ERR_PARTIAL:
                    $errors[] = "El archivo se subió parcialmente";
                    break;
                case UPLOAD_ERR_NO_FILE:
                    $errors[] = "No se seleccionó ningún archivo";
                    break;
                default:
                    $errors[] = "Error desconocido al subir el archivo";
            }
        }
        
        // Validar tamaño (máximo 10MB)
        if ($file['size'] > 10 * 1024 * 1024) {
            $errors[] = "El archivo no puede exceder 10MB";
        }
        
        // Validar tipo MIME
        $allowedMimes = [
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', // xlsx
            'application/vnd.ms-excel', // xls
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document', // docx
            'application/msword' // doc
        ];
        
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, $allowedMimes)) {
            $errors[] = "Tipo de archivo no soportado. Solo Excel (.xlsx, .xls) o Word (.docx, .doc)";
        }
        
        return $errors;
    }
}
?>