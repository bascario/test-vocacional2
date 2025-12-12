<?php
require_once 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class ExcelGenerator
{
    private $spreadsheet;
    private $sheet;

    public function __construct()
    {
        $this->spreadsheet = new Spreadsheet();
        $this->sheet = $this->spreadsheet->getActiveSheet();
    }

    public function generateGroupReport($results)
    {
        // Set document properties
        $this->spreadsheet->getProperties()
            ->setCreator('Sistema de Test Vocacional')
            ->setLastModifiedBy('Sistema de Test Vocacional')
            ->setTitle('Reporte Grupal de Test Vocacional')
            ->setSubject('Resultados del Test Vocacional')
            ->setDescription('Reporte generado automáticamente por el sistema.');

        // Set header
        $headers = [
            'A1' => 'Estudiante',
            'B1' => 'Curso',
            'C1' => 'Ciencias',
            'D1' => 'Tecnología',
            'E1' => 'Humanidades',
            'F1' => 'Artes',
            'G1' => 'Salud',
            'H1' => 'Negocios'
        ];

        foreach ($headers as $cell => $value) {
            $this->sheet->setCellValue($cell, $value);
        }

        // Style header
        $headerStyle = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4B5563'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
        ];

        $this->sheet->getStyle('A1:H1')->applyFromArray($headerStyle);

        // Add data
        $row = 2;
        $categories = ['ciencias', 'tecnologia', 'humanidades', 'artes', 'salud', 'negocios'];

        foreach ($results as $result) {
            $scores = json_decode($result['puntajes_json'], true);

            $this->sheet->setCellValue('A' . $row, $result['apellido'] . ', ' . $result['nombre']);
            // Ensure curso is present, default to empty string if not
            $this->sheet->setCellValue('B' . $row, $result['curso'] ?? '');

            $colIndex = 3; // Column C
            foreach ($categories as $category) {
                $percentage = $scores[$category]['porcentaje'] ?? 0;
                $state = $scores[$category]['estado'] ?? 'POR REFORZAR';

                // Get column letter
                $colLetter = Coordinate::stringFromColumnIndex($colIndex);
                $cellAddress = $colLetter . $row;

                // Set value
                $this->sheet->setCellValue($cellAddress, $percentage . '%');

                // Color code based on state
                $color = 'DC3545'; // Red (POR REFORZAR)
                if ($state === 'APTO') {
                    $color = '28A745'; // Green
                } elseif ($state === 'POTENCIAL') {
                    $color = 'FFC107'; // Yellow
                }

                $this->sheet->getStyle($cellAddress)->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color($color));
                $this->sheet->getStyle($cellAddress)->getFont()->setBold(true);

                $colIndex++;
            }

            $row++;
        }

        // Auto-size columns
        foreach (range('A', 'H') as $col) {
            $this->sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Create writer and return content
        $writer = new Xlsx($this->spreadsheet);

        ob_start();
        $writer->save('php://output');
        $content = ob_get_clean();

        return $content;
    }

    /**
     * Generate DECE report with filtered student results
     */
    public function generateDECEReport($results, $filters = [])
    {
        // Set document properties
        $this->spreadsheet->getProperties()
            ->setCreator('Sistema de Test Vocacional - DECE')
            ->setLastModifiedBy('Sistema de Test Vocacional')
            ->setTitle('Reporte DECE')
            ->setSubject('Resultados del Test Vocacional - DECE')
            ->setDescription('Reporte generado por DECE con filtros aplicados.');

        // Set header
        $headers = [
            'A1' => 'Estudiante',
            'B1' => 'Curso',
            'C1' => 'Paralelo',
            'D1' => 'Bachillerato',
            'E1' => 'Fecha Test',
            'F1' => 'Ciencias',
            'G1' => 'Tecnología',
            'H1' => 'Humanidades',
            'I1' => 'Artes',
            'J1' => 'Salud',
            'K1' => 'Negocios',
            'L1' => 'Área Principal'
        ];

        foreach ($headers as $cell => $value) {
            $this->sheet->setCellValue($cell, $value);
        }

        // Style header
        $headerStyle = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '667EEA'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
        ];

        $this->sheet->getStyle('A1:L1')->applyFromArray($headerStyle);

        // Add filter info if present
        if (!empty($filters['curso']) || !empty($filters['paralelo'])) {
            $filterText = 'Filtros: ';
            if (!empty($filters['curso']))
                $filterText .= 'Curso: ' . $filters['curso'] . ' ';
            if (!empty($filters['paralelo']))
                $filterText .= 'Paralelo: ' . $filters['paralelo'];

            $this->sheet->mergeCells('A2:L2');
            $this->sheet->setCellValue('A2', $filterText);
            $this->sheet->getStyle('A2')->getFont()->setItalic(true);
            $row = 3;
        } else {
            $row = 2;
        }

        // Add data
        $categories = ['ciencias', 'tecnologia', 'humanidades', 'artes', 'salud', 'negocios'];

        foreach ($results as $result) {
            $studentName = trim($result['nombre'] . ' ' . $result['apellido']);

            $this->sheet->setCellValue('A' . $row, $studentName);
            $this->sheet->setCellValue('B' . $row, $result['curso'] ?? '—');
            $this->sheet->setCellValue('C' . $row, $result['paralelo'] ?? '—');
            $this->sheet->setCellValue('D' . $row, $result['bachillerato'] ?? '—');

            // Check if test exists
            if (!empty($result['puntajes_json'])) {
                $scores = json_decode($result['puntajes_json'], true);
                $testDate = date('d/m/Y', strtotime($result['fecha_test']));
                $this->sheet->setCellValue('E' . $row, $testDate);

                // Add scores
                $colIndex = 6; // Column F
                $maxPct = -INF;
                $mainArea = 'N/A';

                foreach ($categories as $category) {
                    $percentage = $scores[$category]['porcentaje'] ?? 0;
                    $state = $scores[$category]['estado'] ?? 'POR REFORZAR';

                    // Get column letter
                    $colLetter = Coordinate::stringFromColumnIndex($colIndex);
                    $cellAddress = $colLetter . $row;

                    // Set value
                    $this->sheet->setCellValue($cellAddress, round($percentage, 1) . '%');

                    // Color code based on state
                    $color = 'DC3545'; // Red (POR REFORZAR)
                    if ($state === 'APTO') {
                        $color = '28A745'; // Green
                    } elseif ($state === 'POTENCIAL') {
                        $color = 'FFC107'; // Yellow
                    }

                    $this->sheet->getStyle($cellAddress)->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color($color));
                    $this->sheet->getStyle($cellAddress)->getFont()->setBold(true);

                    // Track main area
                    if ($percentage > $maxPct) {
                        $maxPct = $percentage;
                        $mainArea = ucfirst($category);
                    }

                    $colIndex++;
                }

                // Set main area
                $this->sheet->setCellValue('L' . $row, $mainArea);
                $this->sheet->getStyle('L' . $row)->getFont()->setBold(true);
            } else {
                $this->sheet->setCellValue('E' . $row, 'Pendiente');
                $this->sheet->setCellValue('L' . $row, 'Pendiente');
            }

            $row++;
        }

        // Auto-size columns
        foreach (range('A', 'L') as $col) {
            $this->sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Create writer and return content
        $writer = new Xlsx($this->spreadsheet);

        ob_start();
        $writer->save('php://output');
        $content = ob_get_clean();

        return $content;
    }
}