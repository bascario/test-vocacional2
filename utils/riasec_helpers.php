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
        'Realista' => 'Realista',
        'Investigador' => 'Investigador',
        'Artístico' => 'Artístico',
        'Social' => 'Social',
        'Emprendedor' => 'Emprendedor',
        'Convencional' => 'Convencional'
    ];

    return $labels[$category] ?? ucfirst($category);
}

/**
 * Get RIASEC categories in display order for radar chart
 */
function getRIASECOrder()
{
    return [
        'Realista',
        'Investigador',
        'Artístico',
        'Social',
        'Emprendedor',
        'Convencional'
    ];
}

/**
 * Convert scores array to RIASEC format for charts
 * Since we're already using RIASEC categories, just format the labels
 */
function convertToRIASEC($scores)
{
    $riasec = [
        'Realista' => 0,
        'Investigador' => 0,
        'Artístico' => 0,
        'Social' => 0,
        'Emprendedor' => 0,
        'Convencional' => 0
    ];

    $mapping = [
        'Realista' => 'Realista',
        'Investigador' => 'Investigador',
        'Artístico' => 'Artístico',
        'Social' => 'Social',
        'Emprendedor' => 'Emprendedor',
        'Convencional' => 'Convencional',
        // Legacy mappings just in case
        'ciencias' => 'Investigador',
        'tecnologia' => 'Realista',
        'humanidades' => 'Social',
        'artes' => 'Artístico',
        'salud' => 'Social',
        'negocios' => 'Emprendedor'
    ];

    foreach ($scores as $category => $data) {
        if (isset($mapping[$category])) {
            $riasecType = $mapping[$category];
            $percentage = is_array($data) ? ($data['porcentaje'] ?? 0) : $data;
            // If mapping maps multiple old keys to same new key (e.g. salud->Social), take max? or average?
            // Current code just overwrites. Let's take max to be safe if multiple map to one.
            if ($percentage > $riasec[$riasecType]) {
                $riasec[$riasecType] = $percentage;
            }
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
        'Realista' => '#667EEA',
        'Investigador' => '#48BB78',
        'Artístico' => '#ED8936',
        'Social' => '#F56565',
        'Emprendedor' => '#9F7AEA',
        'Convencional' => '#38B2AC',
        // Legacy uppercase fallback
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