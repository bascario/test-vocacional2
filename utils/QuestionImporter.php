<?php
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpWord\IOFactory as WordIOFactory;

class QuestionImporter {
    
    public function import($filePath, $fileType) {
        if (in_array($fileType, ALLOWED_EXCEL_TYPES)) {
            return $this->importFromExcel($filePath);
        } elseif (in_array($fileType, ALLOWED_WORD_TYPES)) {
            return $this->importFromWord($filePath);
        } else {
            throw new Exception("Tipo de archivo no soportado");
        }
    }
    
    private function importFromExcel($filePath) {
        try {
            $spreadsheet = IOFactory::load($filePath);
            $worksheet = $spreadsheet->getActiveSheet();
            
            $questions = [];
            $row = 2; // Skip header row
            
            while ($worksheet->getCell('A' . $row)->getValue() !== null) {
                $categoria = strtolower(trim($worksheet->getCell('A' . $row)->getValue()));
                $tipo = strtolower(trim($worksheet->getCell('B' . $row)->getValue()));
                $pregunta = trim($worksheet->getCell('C' . $row)->getValue());
                $peso = (int)($worksheet->getCell('D' . $row)->getValue() ?: 1);
                
                // Validate data
                if (!$this->validateQuestionData($categoria, $tipo, $pregunta)) {
                    $row++;
                    continue;
                }
                
                $questions[] = [
                    'categoria' => $categoria,
                    'tipo' => $tipo,
                    'pregunta' => $pregunta,
                    'peso' => $peso
                ];
                
                $row++;
            }
            
            if (empty($questions)) {
                throw new Exception("No se encontraron preguntas válidas en el archivo");
            }
            
            // Import to database
            $questionModel = new Question();
            return $questionModel->importFromArray($questions);
            
        } catch (Exception $e) {
            throw new Exception("Error al procesar Excel: " . $e->getMessage());
        }
    }
    
    private function importFromWord($filePath) {
        try {
            $phpWord = WordIOFactory::load($filePath);
            $questions = [];
            
            foreach ($phpWord->getSections() as $section) {
                $elements = $section->getElements();
                
                $currentCategory = '';
                $currentType = '';
                
                foreach ($elements as $element) {
                    if ($element instanceof \PhpOffice\PhpWord\Element\Title) {
                        $text = $element->getText();
                        
                        if (strpos($text, '#') === 0) {
                            // Category
                            $currentCategory = $this->normalizeCategory(trim(str_replace('#', '', $text)));
                        } elseif (strpos($text, '##') === 0) {
                            // Type
                            $currentType = $this->normalizeType(trim(str_replace('##', '', $text)));
                        }
                    } elseif ($element instanceof \PhpOffice\PhpWord\Element\TextRun) {
                        $text = '';
                        foreach ($element->getElements() as $textElement) {
                            if ($textElement instanceof \PhpOffice\PhpWord\Element\Text) {
                                $text .= $textElement->getText();
                            }
                        }
                        
                        // Extract question number and text
                        if (preg_match('/^\d+\.\s*(.+)$/', $text, $matches)) {
                            $questionText = trim($matches[1]);
                            
                            if ($currentCategory && $currentType && $questionText) {
                                $questions[] = [
                                    'categoria' => $currentCategory,
                                    'tipo' => $currentType,
                                    'pregunta' => $questionText,
                                    'peso' => 1
                                ];
                            }
                        }
                    }
                }
            }
            
            if (empty($questions)) {
                throw new Exception("No se encontraron preguntas válidas en el archivo Word");
            }
            
            // Import to database
            $questionModel = new Question();
            return $questionModel->importFromArray($questions);
            
        } catch (Exception $e) {
            throw new Exception("Error al procesar Word: " . $e->getMessage());
        }
    }
    
    private function validateQuestionData($categoria, $tipo, $pregunta) {
        // Validate category
        if (!in_array($categoria, TEST_CATEGORIES)) {
            return false;
        }
        
        // Validate type
        if (!in_array($tipo, TEST_TYPES)) {
            return false;
        }
        
        // Validate question text
        if (empty($pregunta) || strlen($pregunta) < 10 || strlen($pregunta) > 500) {
            return false;
        }
        
        return true;
    }
    
    private function normalizeCategory($category) {
        $category = strtolower(trim($category));
        
        // Synonyms mapping
        $synonyms = [
            'ciencias' => ['ciencias', 'science', 'ciencia'],
            'tecnologia' => ['tecnologia', 'tecnología', 'technology', 'tech'],
            'humanidades' => ['humanidades', 'humanities', 'humanidad'],
            'artes' => ['artes', 'arts', 'arte'],
            'salud' => ['salud', 'health', 'medicina'],
            'negocios' => ['negocios', 'business', 'empresas', 'comercio']
        ];
        
        foreach ($synonyms as $standard => $variants) {
            if (in_array($category, $variants)) {
                return $standard;
            }
        }
        
        return $category;
    }
    
    private function normalizeType($type) {
        $type = strtolower(trim($type));
        
        $synonyms = [
            'intereses' => ['intereses', 'interests', 'gustos'],
            'habilidades' => ['habilidades', 'skills', 'capacidades'],
            'valores' => ['valores', 'values', 'principios']
        ];
        
        foreach ($synonyms as $standard => $variants) {
            if (in_array($type, $variants)) {
                return $standard;
            }
        }
        
        return $type;
    }
}
?>