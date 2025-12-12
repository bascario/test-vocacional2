<?php
/**
 * Helper functions for RIASEC category conversion
 */

/**
 * Convert internal category name to RIASEC label
 */
function getCategoryLabel($category)
{
    $labels = [
        'Realista' => 'REALISTA',
        'Investigador' => 'INVESTIGADORA',
        'Artístico' => 'ARTÍSTICA',
        'Social' => 'SOCIAL',
        'Emprendedor' => 'EMPRENDEDORA',
        'Convencional' => 'CONVENCIONAL'
    ];

    return $labels[$category] ?? strtoupper($category);
}

/**
 * Get RIASEC categories in display order for radar chart
 */
function getRIASECOrder()
{
    return [
        'REALISTA',
        'INVESTIGADORA',
        'ARTÍSTICA',
        'SOCIAL',
        'EMPRENDEDORA',
        'CONVENCIONAL'
    ];
}

/**
 * Convert scores array to RIASEC format for charts
 * Since we're already using RIASEC categories, just format the labels
 */
function convertToRIASEC($scores)
{
    $riasec = [
        'REALISTA' => 0,
        'INVESTIGADORA' => 0,
        'ARTÍSTICA' => 0,
        'SOCIAL' => 0,
        'EMPRENDEDORA' => 0,
        'CONVENCIONAL' => 0
    ];

    $mapping = [
        'Realista' => 'REALISTA',
        'Investigador' => 'INVESTIGADORA',
        'Artístico' => 'ARTÍSTICA',
        'Social' => 'SOCIAL',
        'Emprendedor' => 'EMPRENDEDORA',
        'Convencional' => 'CONVENCIONAL'
    ];

    foreach ($scores as $category => $data) {
        if (isset($mapping[$category])) {
            $riasecType = $mapping[$category];
            $percentage = is_array($data) ? ($data['porcentaje'] ?? 0) : $data;
            $riasec[$riasecType] = $percentage;
        }
    }

    return $riasec;
}

/**
 * Get the dominant RIASEC type from scores
 */
function getDominantRIASEC($scores)
{
    $riasec = convertToRIASEC($scores);
    arsort($riasec);
    return array_key_first($riasec);
}

/**
 * Get color for RIASEC type
 */
function getRIASECColor($type)
{
    $colors = [
        'REALISTA' => '#667EEA',
        'INVESTIGADORA' => '#48BB78',
        'ARTÍSTICA' => '#ED8936',
        'SOCIAL' => '#F56565',
        'EMPRENDEDORA' => '#9F7AEA',
        'CONVENCIONAL' => '#38B2AC'
    ];

    return $colors[$type] ?? '#718096';
}
?>