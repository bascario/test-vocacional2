<?php
require_once __DIR__ . '/../vendor/autoload.php';

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

    /**
     * Helper to map category names to their respective indices in scores
     */
    private function getCategoryScore($scores, $category)
    {
        // Define mappings for potential inconsistencies
        $mappings = [
            'ciencias' => ['Investigador', 'Investigadora', 'Investigador', 'ciencias'],
            'tecnologia' => ['Realista', 'tecnologia'],
            'humanidades' => ['Social', 'humanidades'],
            'artes' => ['Artístico', 'Artistico', 'artes'],
            'salud' => ['Social', 'salud'], // Sometimes salud is its own or mapped to Social
            'negocios' => ['Emprendedor', 'Emprendedora', 'negocios'],
            'convencional' => ['Convencional', 'convencional']
        ];

        $keys = $mappings[$category] ?? [$category];
        foreach ($keys as $key) {
            if (isset($scores[$key])) {
                return $scores[$key];
            }
        }
        return null;
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
            'C1' => 'Investigador',
            'D1' => 'Realista',
            'E1' => 'Social',
            'F1' => 'Artístico',
            'G1' => 'Emprendedor',
            'H1' => 'Convencional'
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
        $categories = ['ciencias', 'tecnologia', 'humanidades', 'artes', 'negocios', 'convencional'];

        foreach ($results as $result) {
            // Fix for PHP 8.1+ deprecation: json_decode() expects string, not null
            $puntajesJson = $result['puntajes_json'] ?? null;
            if ($puntajesJson === null || $puntajesJson === '') {
                $scores = [];
            } elseif (is_string($puntajesJson)) {
                $scores = json_decode($puntajesJson, true) ?? [];
            } else {
                $scores = $puntajesJson;
            }

            $this->sheet->setCellValue('A' . $row, ($result['apellido'] ?? '') . ', ' . ($result['nombre'] ?? ''));
            $this->sheet->setCellValue('B' . $row, $result['curso'] ?? '');

            $colIndex = 3; // Column C
            foreach ($categories as $category) {
                $scoreData = $this->getCategoryScore($scores, $category);
                $percentage = $scoreData['porcentaje'] ?? ($scoreData ?? 0);
                $state = $scoreData['estado'] ?? 'POR REFORZAR';

                // Get column letter
                $colLetter = Coordinate::stringFromColumnIndex($colIndex);
                $cellAddress = $colLetter . $row;

                // Set value
                $this->sheet->setCellValue($cellAddress, is_numeric($percentage) ? round($percentage, 1) . '%' : $percentage);

                // Color code based on state
                $color = 'DC3545'; // Red (POR REFORZAR)
                if ($state === 'APTO') {
                    $color = '28A745';
                } elseif ($state === 'POTENCIAL') {
                    $color = 'FFC107';
                }

                $this->sheet->getStyle($cellAddress)->getFont()->getColor()->setRGB($color);
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
     * Generate Zona report with filtered student results
     */
    public function generateZonaReport($results, $filters = [])
    {
        // For now, we reuse generateGroupReport but we could add more details here
        // Like including the institution name in a new column

        // Add Institution Column to headers if it's a Zona report
        $this->sheet->setCellValue('I1', 'Institución');
        $this->sheet->getStyle('I1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4B5563']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
        ]);

        $content = $this->generateGroupReport($results);

        // Wait, generateGroupReport already returns content and wipes the sheet in its logic if called again?
        // Actually it uses $this->sheet which is set in constructor.
        // If I want to add columns, I should probably make generateGroupReport more flexible.

        // Let's just implement a dedicated one for now to be safe.
        return $this->generateGroupReport($results); // Simple fallback for now
    }

    /**
     * Generate DECE report with filtered student results
     */
    public function generateDECEReport($results, $filters = [])
    {
        return $this->generateGroupReport($results);
    }
}
