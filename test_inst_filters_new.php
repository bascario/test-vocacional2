<?php
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'models/BaseModel.php';
require_once 'models/Institucion.php';

$instModel = new Institucion();

// Test combinations of filters
$testCases = [
    ['provincia' => 'PICHINCHA'],
    ['tipo' => 'Fiscal'],
    ['zona' => '9'],
    ['distrito' => '17D08']
];

foreach ($testCases as $filters) {
    echo "Testing filters: " . json_encode($filters) . "\n";
    $total = $instModel->countAll($filters);
    $results = $instModel->getAll(5, 0, $filters);

    echo "Total found: $total\n";
    if ($total > 0) {
        echo "Sample result: " . $results[0]['nombre'] . " (" . $results[0]['codigo'] . ")\n";
        if (isset($filters['provincia']))
            echo "Province match: " . ($results[0]['provincia'] == $filters['provincia'] ? "YES" : "NO") . "\n";
        if (isset($filters['tipo']))
            echo "Type match: " . ($results[0]['tipo'] == $filters['tipo'] ? "YES" : "NO") . "\n";
    }
    echo "-------------------\n";
}
