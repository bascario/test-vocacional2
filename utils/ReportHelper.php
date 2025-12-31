<?php

class ReportHelper
{

    // Categorías RIASEC ordenadas según el modelo de reporte
    public const CATEGORIES = [
        'Realista',
        'Investigadora',
        'Artistica',
        'Social',
        'Emprendedora',
        'Convencional'
    ];

    /**
     * Mapea claves internas a etiquetas específicas del reporte
     */
    public static function getCategoryLabelMap()
    {
        return [
            'tecnologia' => 'Realista',
            'ciencias' => 'Investigadora',
            'artes' => 'Artistica',
            'humanidades' => 'Social', // Or 'salud' depending on logic, but social is generic
            'negocios' => 'Emprendedora',
            // Default mapping for missing ones or duplicates
            'Social' => 'Social',
            'Convencional' => 'Convencional'
        ];
    }

    /**
     * Normaliza los puntajes para adaptarlos al formato del reporte
     * (convierte claves a Realista, Investigadora, Artística, etc.)
     */
    public static function normalizeScores($scores)
    {
        $normalized = [];
        $map = [
            'tecnologia' => 'Realista',
            'ciencias' => 'Investigadora',
            'artes' => 'Artistica',
            'humanidades' => 'Social',
            'salud' => 'Social', // Conflict management: will take max or specific logic if needed. 
            // Actually, typical RIASEC puts Health often in Social or Investigative. 
            // Let's treat 'humanidades' & 'salud' -> 'Social' by default OR check config.
            'negocios' => 'Emprendedora',
            // Convencional usually missing in 5-factor tests, we might need to fake it or map 'admin' if exists
            // But config.php has 6 categories now. Let's trust the input $scores keys if they match RIASEC.
        ];

        // Note: The input `scores` comes from `puntajes_json`.
        // If the test was just taken, it uses the keys from TEST_CATEGORIES in config.
        // If config has: Realista, Investigador, Artístico, Social, Emprendedor, Convencional
        // We need to map them to the Report specific gendered names: 
        // Realista, Investigadora, Artistica, Social, Emprendedora, Convencional

        foreach ($scores as $key => $data) {
            // Fuzzy match or direct map
            $keyLower = mb_strtolower($key, 'UTF-8');
            $targetKey = '';

            if (stripos($key, 'realist') !== false)
                $targetKey = 'Realista';
            elseif (stripos($key, 'investiga') !== false || stripos($key, 'ciencia') !== false)
                $targetKey = 'Investigadora';
            elseif (stripos($key, 'artist') !== false || stripos($key, 'arte') !== false)
                $targetKey = 'Artistica';
            elseif (stripos($key, 'social') !== false || stripos($key, 'humanidad') !== false)
                $targetKey = 'Social';
            elseif (stripos($key, 'emprende') !== false || stripos($key, 'negocio') !== false)
                $targetKey = 'Emprendedora';
            elseif (stripos($key, 'convencional') !== false)
                $targetKey = 'Convencional';

            if ($targetKey) {
                // Keep the highest if conflict (e.g. salud vs humanidades both mapping to social)
                if (!isset($normalized[$targetKey]) || $data['puntaje'] > $normalized[$targetKey]['puntaje']) {
                    $normalized[$targetKey] = $data;
                }
            }
        }

        // Fill missing with 0
        foreach (self::CATEGORIES as $cat) {
            if (!isset($normalized[$cat])) {
                $normalized[$cat] = ['puntaje' => 0, 'promedio' => 0, 'porcentaje' => 0, 'estado' => 'N/A'];
            }
        }

        return $normalized;
    }

    /**
     * Obtener las N áreas/carreras principales
     */
    public static function getTopAreas($normalizedScores, $n = 3)
    {
        uasort($normalizedScores, function ($a, $b) {
            return $b['puntaje'] <=> $a['puntaje'];
        });
        return array_slice($normalizedScores, 0, $n, true);
    }

    /**
     * Calcular la matriz de diferenciación
     * Devuelve una matriz [FilaCategoria][ColCategoria] = Nivel (Bajo, Media, Alto o '-')
     */
    public static function calculateDifferentiation($normalizedScores)
    {
        $matrix = [];
        // Thresholds for difference (arbitrary based on 0-4 scale or 0-100 scale? 
        // Report shows "Puntaje" as integer (3, 2, 4, 1, 5). 
        // Assuming 'promedio' (0-5) is the comparable metric.)

        foreach (self::CATEGORIES as $rowCat) {
            foreach (self::CATEGORIES as $colCat) {
                if ($rowCat === $colCat) {
                    $matrix[$rowCat][$colCat] = '-';
                    continue;
                }

                $diff = abs($normalizedScores[$rowCat]['promedio'] - $normalizedScores[$colCat]['promedio']);

                // Define thresholds for 1-5 scale differences
                if ($diff < 0.5) {
                    $level = 'Bajo';
                } elseif ($diff < 1.5) {
                    $level = 'Media';
                } else {
                    $level = 'Alto';
                }

                $matrix[$rowCat][$colCat] = $level;
            }
        }
        return $matrix;
    }

    /**
     * Calcular "Competencia" entre pares (lógica simple basada en promedios)
     */
    public static function calculateCompetence($normalizedScores)
    {
        $competence = [];
        $pairs = [
            ['Realista', 'Investigadora'],
            ['Investigadora', 'Artistica'],
            ['Artistica', 'Social'],
            ['Social', 'Emprendedora'],
            ['Emprendedora', 'Convencional'],
            ['Convencional', 'Realista']
        ];

        foreach ($pairs as $pair) {
            $cat1 = $pair[0];
            $cat2 = $pair[1];

            $score1 = $normalizedScores[$cat1]['promedio'];
            $score2 = $normalizedScores[$cat2]['promedio'];

            // Average of the pair determines "Competence" level? 
            // Or consistency? "Competencia" in vocational context usually means developed ability.
            // Let's assume average score of the pair.
            $avg = ($score1 + $score2) / 2;

            if ($avg >= 3.5)
                $level = 'Alto';
            elseif ($avg >= 2.5)
                $level = 'Medio';
            else
                $level = 'Bajo';

            $competence["$cat1 - $cat2"] = $level;
        }

        return $competence;
    }

    /**
     * Calcular "Congruencia"
     * Comparación entre preferencia implícita (test) y preferencia explícita.
     * Actualmente sin datos explícitos, se deja como marcador.
     */
    public static function calculateCongruence($normalizedScores)
    {
        // Marcador: no contamos con preferencia explícita del estudiante
        return "N/A";
    }

    /**
     * Obtener texto de evaluación basado en el área principal (usa definiciones CINE)
     */
    public static function getEvaluationText($topCat)
    {
        // CINE Definitions
        $definitions = [
            'Realista' => [
                'def' => 'Persona que prefieren las actividades manuales, con herramientas, máquinas y cosas; personas prácticas, realistas y les gusta la naturaleza y plantas.',
                'fields' => 'Ingeniería y profesiones afines (Electricidad, electrónica, mecánica, automotriz, ingeniería civil, topografía, telecomunicaciones, transporte energético y químico, mantenimiento de vehículos, transporte, industrias y producción). Arquitectura y construcción (arquitectura y urbanismo, construcción). Agricultura, silvicultura y pesca.'
            ],
            'Investigadora' => [
                'def' => 'Persona que valora mucho la teoría y la información; analíticos, intelectuales y científicos.',
                'fields' => 'Educación (programas básicos). Artes y Humanidades (lenguas, literatura, historia, filosofía). Ciencias sociales, periodismo e información (economía, sociología, psicología). Ciencias naturales, matemáticas y estadística. TIC (desarrollo de software, redes).'
            ],
            'Artistica' => [
                'def' => 'Persona que prefiere las relaciones espaciales; es original e independiente.',
                'fields' => 'Bellas artes (música, teatro, danza, dibujo, pintura). Artes visuales (fotografía, producción de medios, diseño gráfico). Diseño industrial, de modas e interiores.'
            ],
            'Social' => [
                'def' => 'Persona que desea trabajar en sociedad, le gusta ayudar a personas; cooperativos, educadores y comprensión.',
                'fields' => 'Educación (docencia, ciencias de la educación). Servicios personales (hotelería, turismo, deportes). Salud y bienestar (medicina, enfermería, psicología clínica, terapia). Servicios de seguridad.'
            ],
            'Emprendedora' => [
                'def' => 'Persona que trabaja en entornos competitivos, le gusta liderar, persuadir, vender, gestionar proyectos.',
                'fields' => 'Administración de empresas y derecho (contabilidad, auditoría, finanzas, marketing, publicidad). Gestión y administración.'
            ],
            'Convencional' => [
                'def' => 'Persona muy ordenada, precisa, que valida con atención a los detalles. Se enfocan en el orden y la organización.',
                'fields' => 'Administración de empresas y derecho (secretaría, trabajo de oficina, contabilidad). Bibliotecología, información y archivos.'
            ]
        ];

        $key = ucfirst($topCat); // Ensure capitalized match
        // Map typical variants if needed
        if ($key === 'Artistico')
            $key = 'Artistica';
        if ($key === 'Investigador')
            $key = 'Investigadora';
        if ($key === 'Emprendedor')
            $key = 'Emprendedora';

        if (isset($definitions[$key])) {
            $d = $definitions[$key];
            return "<strong>DEFINICIÓN:</strong> " . $d['def'] . "<br><br><strong>CAMPOS DE EDUCACIÓN SUGERIDOS:</strong> " . $d['fields'];
        }

        return "Perfil en desarrollo. Te recomendamos explorar diferentes áreas de interés.";
    }

    /**
     * Genera un código QR en base64 para incrustar en HTML
     */
    public static function generateQRCodeBase64($data)
    {
        try {
            if (!class_exists('TCPDF2DBarcode')) {
                $barcodeFile = __DIR__ . '/../vendor/tecnickcom/tcpdf/tcpdf_barcodes_2d.php';
                if (file_exists($barcodeFile)) {
                    require_once $barcodeFile;
                }
            }

            if (!class_exists('TCPDF2DBarcode')) {
                return null;
            }
            $barcode = new TCPDF2DBarcode($data, 'QRCODE,H');
            $pngData = $barcode->getBarcodePngData(4, 4);
            return 'data:image/png;base64,' . base64_encode($pngData);
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Genera la URL de validación para un test específico
     */
    public static function getValidationUrl($testId)
    {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
        $host = $_SERVER['HTTP_HOST'];
        $base = "/test-vocacional"; // Adjust if necessary

        // Generate a secure hash
        $hash = hash_hmac('sha256', $testId, 'secret_salt_v1');

        return $protocol . $host . $base . "/verify-report?id=" . $testId . "&h=" . $hash;
    }
}
?>