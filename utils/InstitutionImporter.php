<?php
require_once __DIR__ . '/../models/Institucion.php';

class InstitutionImporter {
    /**
     * Import institutions from a CSV file with headers: nombre,codigo,tipo
     */
    public function importFromCsv($filePath) {
        if (!file_exists($filePath)) {
            throw new Exception('Archivo no encontrado: ' . $filePath);
        }

        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            throw new Exception('No se pudo abrir el archivo');
        }

        $header = fgetcsv($handle);
        if ($header === false) {
            fclose($handle);
            throw new Exception('Archivo CSV vacío');
        }

        // normalize header
        $cols = array_map(function($c){ return trim(strtolower($c)); }, $header);

        $required = ['nombre','codigo','tipo'];
        foreach ($required as $r) {
            if (!in_array($r, $cols)) {
                fclose($handle);
                throw new Exception('CSV debe contener las columnas: nombre,codigo,tipo');
            }
        }

        $institucion = new Institucion();
        $imported = 0;
        $skipped = 0;

        while (($row = fgetcsv($handle)) !== false) {
            $data = array_combine($cols, $row);
            $data = array_map('trim', $data);

            try {
                // Validate tipo to allowed values
                $tipo = ucfirst(strtolower($data['tipo']));
                if (!in_array($tipo, ['Fiscal','Fiscomisional'])) {
                    // try to infer
                    if (strpos(strtolower($data['tipo']), 'fiscom') !== false) $tipo = 'Fiscomisional';
                    else $tipo = 'Fiscal';
                }

                $institucion->createInstitution([
                    'nombre' => $data['nombre'],
                    'codigo' => $data['codigo'],
                    'tipo' => $tipo
                ]);
                $imported++;
            } catch (Exception $e) {
                $skipped++;
                continue;
            }
        }

        fclose($handle);
        return ['imported' => $imported, 'skipped' => $skipped];
    }
}
