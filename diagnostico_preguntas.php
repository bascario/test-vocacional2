<?php
/**
 * Script de diagnóstico para verificar preguntas en el servidor
 * Acceder a: https://tu-servidor.com/test-vocacional/diagnostico_preguntas.php
 */

// Configuración
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'models/Question.php';

// Iniciar output
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico de Preguntas</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background: #f5f5f5;
        }

        .section {
            background: white;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .success {
            color: #28a745;
            font-weight: bold;
        }

        .error {
            color: #dc3545;
            font-weight: bold;
        }

        .warning {
            color: #ffc107;
            font-weight: bold;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th,
        td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }

        th {
            background: #f8f9fa;
            font-weight: bold;
        }

        pre {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 4px;
            overflow-x: auto;
        }

        .info-box {
            background: #e7f3ff;
            border-left: 4px solid #2196F3;
            padding: 15px;
            margin: 10px 0;
        }
    </style>
</head>

<body>
    <h1>🔍 Diagnóstico de Preguntas del Test Vocacional</h1>

    <?php
    // 1. Verificar conexión a base de datos
    echo '<div class="section">';
    echo '<h2>1. Conexión a Base de Datos</h2>';
    try {
        $db = Database::getInstance()->getConnection();
        echo '<p class="success">✅ Conexión exitosa a la base de datos</p>';

        // Mostrar información de la base de datos
        $stmt = $db->query("SELECT DATABASE() as db_name");
        $dbInfo = $stmt->fetch(PDO::FETCH_ASSOC);
        echo '<p><strong>Base de datos actual:</strong> ' . htmlspecialchars($dbInfo['db_name']) . '</p>';

    } catch (Exception $e) {
        echo '<p class="error">❌ Error de conexión: ' . htmlspecialchars($e->getMessage()) . '</p>';
        echo '</div></body></html>';
        exit;
    }
    echo '</div>';

    // 2. Verificar tabla de preguntas
    echo '<div class="section">';
    echo '<h2>2. Verificación de Tabla "preguntas"</h2>';
    try {
        $stmt = $db->query("SHOW TABLES LIKE 'preguntas'");
        $tableExists = $stmt->fetch();

        if ($tableExists) {
            echo '<p class="success">✅ La tabla "preguntas" existe</p>';

            // Mostrar estructura de la tabla
            $stmt = $db->query("DESCRIBE preguntas");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo '<h3>Estructura de la tabla:</h3>';
            echo '<table>';
            echo '<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th></tr>';
            foreach ($columns as $col) {
                echo '<tr>';
                echo '<td>' . htmlspecialchars($col['Field']) . '</td>';
                echo '<td>' . htmlspecialchars($col['Type']) . '</td>';
                echo '<td>' . htmlspecialchars($col['Null']) . '</td>';
                echo '<td>' . htmlspecialchars($col['Key']) . '</td>';
                echo '<td>' . htmlspecialchars($col['Default'] ?? 'NULL') . '</td>';
                echo '</tr>';
            }
            echo '</table>';
        } else {
            echo '<p class="error">❌ La tabla "preguntas" NO existe</p>';
            echo '<div class="info-box">';
            echo '<strong>Solución:</strong> Necesitas ejecutar el script de creación de la base de datos (setup_database.sql)';
            echo '</div>';
        }
    } catch (Exception $e) {
        echo '<p class="error">❌ Error al verificar tabla: ' . htmlspecialchars($e->getMessage()) . '</p>';
    }
    echo '</div>';

    // 3. Contar preguntas
    echo '<div class="section">';
    echo '<h2>3. Conteo de Preguntas</h2>';
    try {
        $stmt = $db->query("SELECT COUNT(*) as total FROM preguntas");
        $count = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($count['total'] > 0) {
            echo '<p class="success">✅ Total de preguntas: <strong>' . $count['total'] . '</strong></p>';

            // Contar por categoría
            $stmt = $db->query("SELECT categoria, COUNT(*) as total FROM preguntas GROUP BY categoria ORDER BY categoria");
            $byCategory = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo '<h3>Preguntas por categoría:</h3>';
            echo '<table>';
            echo '<tr><th>Categoría</th><th>Total</th></tr>';
            foreach ($byCategory as $cat) {
                echo '<tr>';
                echo '<td>' . htmlspecialchars($cat['categoria']) . '</td>';
                echo '<td>' . htmlspecialchars($cat['total']) . '</td>';
                echo '</tr>';
            }
            echo '</table>';

            // Contar por tipo
            $stmt = $db->query("SELECT tipo, COUNT(*) as total FROM preguntas GROUP BY tipo ORDER BY tipo");
            $byType = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo '<h3>Preguntas por tipo:</h3>';
            echo '<table>';
            echo '<tr><th>Tipo</th><th>Total</th></tr>';
            foreach ($byType as $type) {
                echo '<tr>';
                echo '<td>' . htmlspecialchars($type['tipo']) . '</td>';
                echo '<td>' . htmlspecialchars($type['total']) . '</td>';
                echo '</tr>';
            }
            echo '</table>';

        } else {
            echo '<p class="error">❌ No hay preguntas en la base de datos</p>';
            echo '<div class="info-box">';
            echo '<strong>Solución:</strong> Necesitas importar las preguntas desde el archivo setup_database.sql';
            echo '</div>';
        }
    } catch (Exception $e) {
        echo '<p class="error">❌ Error al contar preguntas: ' . htmlspecialchars($e->getMessage()) . '</p>';
    }
    echo '</div>';

    // 4. Probar modelo Question
    echo '<div class="section">';
    echo '<h2>4. Prueba del Modelo Question</h2>';
    try {
        $questionModel = new Question();
        $grouped = $questionModel->getAllGrouped();

        if (!empty($grouped)) {
            echo '<p class="success">✅ El modelo Question funciona correctamente</p>';

            $totalQuestions = 0;
            foreach ($grouped as $category => $types) {
                foreach ($types as $type => $questions) {
                    $totalQuestions += count($questions);
                }
            }

            echo '<p><strong>Preguntas obtenidas por el modelo:</strong> ' . $totalQuestions . '</p>';

            // Mostrar estructura
            echo '<h3>Estructura de datos:</h3>';
            echo '<pre>' . htmlspecialchars(print_r(array_keys($grouped), true)) . '</pre>';

        } else {
            echo '<p class="error">❌ El modelo Question no devuelve preguntas</p>';
        }
    } catch (Exception $e) {
        echo '<p class="error">❌ Error en el modelo Question: ' . htmlspecialchars($e->getMessage()) . '</p>';
        echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
    }
    echo '</div>';

    // 5. Verificar archivos necesarios
    echo '<div class="section">';
    echo '<h2>5. Verificación de Archivos</h2>';

    $files = [
        'config/config.php' => 'Configuración principal',
        'config/database.php' => 'Configuración de base de datos',
        'models/Question.php' => 'Modelo de preguntas',
        'controllers/TestController.php' => 'Controlador de test',
        'views/test_form.php' => 'Vista del formulario'
    ];

    echo '<table>';
    echo '<tr><th>Archivo</th><th>Descripción</th><th>Estado</th></tr>';
    foreach ($files as $file => $desc) {
        $exists = file_exists($file);
        $status = $exists ? '<span class="success">✅ Existe</span>' : '<span class="error">❌ No existe</span>';
        echo '<tr>';
        echo '<td>' . htmlspecialchars($file) . '</td>';
        echo '<td>' . htmlspecialchars($desc) . '</td>';
        echo '<td>' . $status . '</td>';
        echo '</tr>';
    }
    echo '</table>';
    echo '</div>';

    // 6. Información del servidor
    echo '<div class="section">';
    echo '<h2>6. Información del Servidor</h2>';
    echo '<table>';
    echo '<tr><th>Variable</th><th>Valor</th></tr>';
    echo '<tr><td>PHP Version</td><td>' . phpversion() . '</td></tr>';
    echo '<tr><td>Server Software</td><td>' . htmlspecialchars($_SERVER['SERVER_SOFTWARE'] ?? 'N/A') . '</td></tr>';
    echo '<tr><td>Document Root</td><td>' . htmlspecialchars($_SERVER['DOCUMENT_ROOT'] ?? 'N/A') . '</td></tr>';
    echo '<tr><td>Script Filename</td><td>' . htmlspecialchars(__FILE__) . '</td></tr>';
    echo '</table>';
    echo '</div>';

    ?>

    <div class="section">
        <h2>📋 Resumen y Recomendaciones</h2>
        <div class="info-box">
            <p><strong>Si las preguntas no aparecen en el servidor:</strong></p>
            <ol>
                <li>Verifica que la tabla "preguntas" exista y tenga datos</li>
                <li>Asegúrate de que el archivo setup_database.sql se haya ejecutado en el servidor</li>
                <li>Verifica que la configuración de la base de datos en config/database.php sea correcta</li>
                <li>Revisa los permisos de archivos en el servidor</li>
                <li>Verifica los logs de errores de PHP en el servidor</li>
            </ol>
        </div>
    </div>

    <p style="text-align: center; color: #999; margin-top: 40px;">
        <small>Diagnóstico generado el <?= date('Y-m-d H:i:s') ?></small>
    </p>
</body>

</html>