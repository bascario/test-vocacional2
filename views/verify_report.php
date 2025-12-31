<?php
// views/verify_report.php

require_once 'models/VocationalTest.php';
require_once 'models/User.php';
require_once 'models/Institucion.php';

$testId = $_GET['id'] ?? null;
$hash = $_GET['h'] ?? null;

$isValid = false;
$resultData = null;
$studentName = "";
$institutionName = "";
$testDate = "";

if ($testId && $hash) {
    // Re-calculate hash to verify
    $expectedHash = hash_hmac('sha256', $testId, 'secret_salt_v1');

    if ($hash === $expectedHash) {
        $testModel = new VocationalTest();
        $userModel = new User();
        $institucionModel = new Institucion();

        // Handle group reports (if $testId is institution ID or generic)
        if (is_numeric($testId)) {
            $test = $testModel->getResultsByUser(null, $testId); // Assuming we can get by test ID
            // Actually getResultsByUser takes studentId. I need a find() for VocationalTest.
            // Let's use a direct query or find if it exists.

            // Checking VocationalTest.php for find()
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("SELECT rt.*, u.nombre, u.apellido, u.institucion_id FROM resultados_test rt JOIN usuarios u ON rt.usuario_id = u.id WHERE rt.id = ?");
            $stmt->execute([$testId]);
            $test = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($test) {
                $isValid = true;
                $studentName = $test['nombre'] . ' ' . $test['apellido'];
                $testDate = date('d/m/Y', strtotime($test['fecha_test']));

                if ($test['institucion_id']) {
                    $inst = $institucionModel->find($test['institucion_id']);
                    $institutionName = $inst['nombre'] ?? 'Desconocida';
                }
            }
        } else {
            // It could be a group report verification
            $isValid = true;
            $studentName = "Informe Grupal / Institucional";
            $institutionName = "Verificación Completa";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificación de Informe - Test Vocacional</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            background-color: #f0f2f5;
        }

        .card {
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            text-align: center;
            max-width: 400px;
            width: 90%;
        }

        .icon {
            font-size: 64px;
            margin-bottom: 20px;
        }

        .valid {
            color: #27ae60;
        }

        .invalid {
            color: #e74c3c;
        }

        h1 {
            margin: 0 0 10px 0;
            font-size: 24px;
        }

        p {
            color: #666;
            margin: 5px 0;
        }

        .details {
            margin-top: 25px;
            text-align: left;
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            border: 1px dashed #ccc;
        }

        .details strong {
            color: #333;
        }

        .btn {
            display: inline-block;
            margin-top: 30px;
            padding: 10px 20px;
            background: #00aeef;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <div class="card">
        <?php if ($isValid): ?>
            <div class="icon valid">✅</div>
            <h1 class="valid">Informe Válido</h1>
            <p>Este documento ha sido generado por el Sistema de Test Vocacional oficial.</p>

            <div class="details">
                <p><strong>Estudiante:</strong> <?= htmlspecialchars($studentName) ?></p>
                <?php if ($institutionName): ?>
                    <p><strong>Institución:</strong> <?= htmlspecialchars($institutionName) ?></p>
                <?php endif; ?>
                <?php if ($testDate): ?>
                    <p><strong>Fecha del Test:</strong> <?= htmlspecialchars($testDate) ?></p>
                <?php endif; ?>
                <p><strong>ID de Verificación:</strong> <?= htmlspecialchars($testId) ?></p>
            </div>
        <?php else: ?>
            <div class="icon invalid">❌</div>
            <h1 class="invalid">Informe No Válido</h1>
            <p>No se pudo verificar la autenticidad de este documento. Podría ser una copia no autorizada o manipulada.</p>
        <?php endif; ?>

        <a href="/test-vocacional" class="btn">Ir al Inicio</a>
    </div>
</body>

</html>