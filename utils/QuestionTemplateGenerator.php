<?php
    class QuestionTemplateGenerator {
    
    public function generateExcelTemplate() {
        require_once 'vendor/autoload.php';
        
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $worksheet = $spreadsheet->getActiveSheet();
        
        // Configurar headers
        $worksheet->setCellValue('A1', 'Categoría');
        $worksheet->setCellValue('B1', 'Tipo');
        $worksheet->setCellValue('C1', 'Pregunta');
        $worksheet->setCellValue('D1', 'Peso');
        
        // Estilo del header
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'color' => ['rgb' => '4472C4']],
            'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]]
        ];
        
        $worksheet->getStyle('A1:D1')->applyFromArray($headerStyle);
        
        // Ajustar ancho de columnas
        $worksheet->getColumnDimension('A')->setWidth(15);
        $worksheet->getColumnDimension('B')->setWidth(15);
        $worksheet->getColumnDimension('C')->setWidth(60);
        $worksheet->getColumnDimension('D')->setWidth(10);
        
        // Agregar datos de ejemplo
        $examples = [
            ['ciencias', 'intereses', '¿Te gusta realizar experimentos científicos?', 1],
            ['tecnologia', 'habilidades', '¿Tienes facilidad para usar computadoras?', 1],
            ['humanidades', 'valores', '¿Consideras importante la justicia social?', 1],
            ['artes', 'intereses', '¿Disfrutas creando obras artísticas?', 1],
            ['salud', 'valores', '¿Te motiva ayudar a personas enfermas?', 1],
            ['negocios', 'habilidades', '¿Tienes habilidades de liderazgo?', 1]
        ];
        
        $row = 2;
        foreach ($examples as $example) {
            $worksheet->setCellValue('A' . $row, $example[0]);
            $worksheet->setCellValue('B' . $row, $example[1]);
            $worksheet->setCellValue('C' . $row, $example[2]);
            $worksheet->setCellValue('D' . $row, $example[3]);
            $row++;
        }
        
        // Agregar validación de datos
        $categoryValidation = $worksheet->getCell('A2')->getDataValidation();
        $categoryValidation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
        $categoryValidation->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_INFORMATION);
        $categoryValidation->setAllowBlank(false);
        $categoryValidation->setShowInputMessage(true);
        $categoryValidation->setShowErrorMessage(true);
        $categoryValidation->setShowDropDown(true);
        $categoryValidation->setErrorTitle('Error de entrada');
        $categoryValidation->setError('Valor no válido');
        $categoryValidation->setPromptTitle('Categorías disponibles');
        $categoryValidation->setPrompt('Selecciona una categoría válida');
        $categoryValidation->setFormula1('"ciencias,tecnologia,humanidades,artes,salud,negocios"');
        
        $typeValidation = $worksheet->getCell('B2')->getDataValidation();
        $typeValidation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
        $typeValidation->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_INFORMATION);
        $typeValidation->setAllowBlank(false);
        $typeValidation->setShowInputMessage(true);
        $typeValidation->setShowErrorMessage(true);
        $typeValidation->setShowDropDown(true);
        $typeValidation->setErrorTitle('Error de entrada');
        $typeValidation->setError('Valor no válido');
        $typeValidation->setPromptTitle('Tipos disponibles');
        $typeValidation->setPrompt('Selecciona un tipo válido');
        $typeValidation->setFormula1('"intereses,habilidades,valores"');
        
        // Copiar validación a más filas
        for ($i = 3; $i <= 100; $i++) {
            $worksheet->getCell('A' . $i)->setDataValidation(clone $categoryValidation);
            $worksheet->getCell('B' . $i)->setDataValidation(clone $typeValidation);
        }
        
        // Agregar instrucciones en una hoja separada
        $instructionSheet = $spreadsheet->createSheet();
        $instructionSheet->setTitle('Instrucciones');
        
        $instructions = [
            'INSTRUCCIONES PARA IMPORTAR PREGUNTAS',
            '',
            '1. FORMATO DEL ARCHIVO:',
            '   - Use la plantilla proporcionada en la hoja "Hoja1"',
            '   - Mantenga los headers en la fila 1',
            '   - Complete los datos desde la fila 2 en adelante',
            '',
            '2. COLUMNAS:',
            '   A) Categoría: ciencias, tecnologia, humanidades, artes, salud, negocios',
            '   B) Tipo: intereses, habilidades, valores',
            '   C) Pregunta: Texto completo de la pregunta',
            '   D) Peso: Número entero (opcional, por defecto 1)',
            '',
            '3. RECOMENDACIONES:',
            '   - Escriba preguntas claras y específicas',
            '   - Use un lenguaje apropiado para estudiantes',
            '   - Distribuya las preguntas equitativamente entre categorías',
            '   - Revise la ortografía antes de importar',
            '',
            '4. EJEMPLO DE PREGUNTAS POR CATEGORÍA:',
            '',
            'CIENCIAS:',
            '- ¿Te interesa descubrir cómo funcionan las cosas?',
            '- ¿Disfrutas resolviendo problemas matemáticos?',
            '- ¿Te gusta observar fenómenos naturales?',
            '',
            'TECNOLOGÍA:',
            '- ¿Te sientes cómodo usando nuevas aplicaciones?',
            '- ¿Te interesa la programación de computadoras?',
            '- ¿Disfrutas armando y desarmando dispositivos?',
            '',
            'HUMANIDADES:',
            '- ¿Te gusta leer libros de historia?',
            '- ¿Disfrutas debatiendo temas sociales?',
            '- ¿Te interesa conocer diferentes culturas?',
            '',
            'ARTES:',
            '- ¿Te gusta dibujar o pintar?',
            '- ¿Disfrutas escuchando música?',
            '- ¿Te consideras una persona creativa?',
            '',
            'SALUD:',
            '- ¿Te interesa ayudar a personas enfermas?',
            '- ¿Valoras el bienestar de otros?',
            '- ¿Te gustaría trabajar en un hospital?',
            '',
            'NEGOCIOS:',
            '- ¿Te consideras una persona emprendedora?',
            '- ¿Te gusta liderar grupos de trabajo?',
            '- ¿Te interesa la economía y las finanzas?'
        ];
        
        $row = 1;
        foreach ($instructions as $instruction) {
            $instructionSheet->setCellValue('A' . $row, $instruction);
            if (strpos($instruction, 'INSTRUCCIONES') !== false || strpos($instruction, ':') !== false) {
                $instructionSheet->getStyle('A' . $row)->getFont()->setBold(true);
            }
            $row++;
        }
        
        $instructionSheet->getColumnDimension('A')->setWidth(80);
        
        // Configurar como hoja activa la primera
        $spreadsheet->setActiveSheetIndex(0);
        
        // Descargar archivo
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="plantilla_preguntas_test_vocacional.xlsx"');
        header('Cache-Control: max-age=0');
        
        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save('php://output');
        exit;
    }
    
    public function generateWordTemplate() {
        require_once 'vendor/autoload.php';
        
        $phpWord = new \PhpOffice\PhpWord\PhpWord();
        $phpWord->setDefaultFontName('Arial');
        $phpWord->setDefaultFontSize(12);
        
        $section = $phpWord->addSection();
        
        // Título
        $section->addTitle('PLANTILLA PARA IMPORTAR PREGUNTAS - TEST VOCACIONAL', 1);
        $section->addTextBreak(1);
        
        // Instrucciones
        $section->addTitle('INSTRUCCIONES:', 2);
        $section->addText('1. Use el formato mostrado a continuación');
        $section->addText('2. Las categorías disponibles son: ciencias, tecnologia, humanidades, artes, salud, negocios');
        $section->addText('3. Los tipos disponibles son: intereses, habilidades, valores');
        $section->addText('4. Numere las preguntas secuencialmente');
        $section->addTextBreak(1);
        
        // Formato de ejemplo
        $section->addTitle('FORMATO RECOMENDADO:', 2);
        $section->addTextBreak(1);
        
        $categories = [
            'ciencias' => [
                'intereses' => [
                    '¿Te gusta realizar experimentos científicos?',
                    '¿Disfrutas resolviendo problemas de matemáticas?',
                    '¿Te interesa conocer cómo funciona el universo?'
                ],
                'habilidades' => [
                    '¿Tienes facilidad para el cálculo mental?',
                    '¿Eres bueno observando detalles?'
                ]
            ],
            'tecnologia' => [
                'intereses' => [
                    '¿Te gusta usar nuevas aplicaciones y programas?',
                    '¿Disfrutas programando computadoras?'
                ],
                'habilidades' => [
                    '¿Tienes facilidad para aprender nuevas tecnologías?',
                    '¿Eres bueno resolviendo problemas técnicos?'
                ]
            ]
        ];
        
        foreach ($categories as $categoryName => $types) {
            $section->addTitle('# ' . strtoupper($categoryName), 3);
            
            foreach ($types as $typeName => $questions) {
                $section->addTitle('## ' . ucfirst($typeName), 4);
                
                $counter = 1;
                foreach ($questions as $question) {
                    $section->addText($counter . '. ' . $question);
                    $counter++;
                }
                $section->addTextBreak(1);
            }
        }
        
        // Descargar archivo
        header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        header('Content-Disposition: attachment;filename="plantilla_preguntas_test_vocacional.docx"');
        header('Cache-Control: max-age=0');
        
        $writer = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save('php://output');
        exit;
    }
}
?>