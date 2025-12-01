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
}