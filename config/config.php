<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'test_vocacional');
define('DB_USER', 'root');
define('DB_PASS', '');

// Application settings
define('APP_NAME', 'Sistema de Test Vocacional');
define('APP_URL', 'https://www.desarrollodesoftware.com.ec/test-vocacional/');
define('APP_EMAIL', 'admin@desarrollodesoftware.com.ec');

// File upload settings
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB
define('ALLOWED_EXCEL_TYPES', ['application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
define('ALLOWED_WORD_TYPES', ['application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']);

// Security settings
define('SESSION_LIFETIME', 3600); // 1 hour
define('PASSWORD_MIN_LENGTH', 6);

// Email/SMTP configuration
// Para usar SMTP, configura los siguientes valores:
// Ejemplo con Gmail:
// 1. Habilitar "Contraseñas de aplicación" en Google Account
// 2. Usar la contraseña de aplicación en SMTP_PASS

define('SMTP_HOST', 'smtp.gmail.com');        // Servidor SMTP
define('SMTP_PORT', 587);                      // Puerto SMTP (587 para TLS, 465 para SSL)
define('SMTP_USER', '');                       // Tu email SMTP
define('SMTP_PASS', '');                       // Contraseña SMTP o app password
define('SMTP_FROM_NAME', 'Test Vocacional');   // Nombre remitente
define('SMTP_FROM_EMAIL', 'noreply@test-vocacional.com');  // Email remitente

// Si los valores de SMTP están vacíos, se usará la función mail() de PHP

// Test settings - RIASEC Model (Holland)
// Categorías usando el modelo RIASEC directamente
define('TEST_CATEGORIES', ['Realista', 'Investigador', 'Artístico', 'Social', 'Emprendedor', 'Convencional']);
define('TEST_TYPES', ['intereses', 'habilidades', 'valores']);
define('LIKERT_SCALE', [1, 2, 3, 4, 5]); // 1=Totalmente en desacuerdo, 2=En desacuerdo, 3=Es difícil decidir, 4=De acuerdo, 5=Totalmente de acuerdo

// RIASEC Category Labels (Display names)
define('CATEGORY_LABELS', [
    'Realista' => 'Realista',
    'Investigador' => 'Investigador',
    'Artístico' => 'Artístico',
    'Social' => 'Social',
    'Emprendedor' => 'Emprendedor',
    'Convencional' => 'Convencional'
]);

// RIASEC Display Order for Charts
define('RIASEC_ORDER', [
    'Realista' => 'Realista',
    'Investigador' => 'Investigador',
    'Artístico' => 'Artístico',
    'Social' => 'Social',
    'Emprendedor' => 'Emprendedor',
    'Convencional' => 'Convencional'
]);

// Results thresholds (adjusted for binary scale)
define('APTO_THRESHOLD', 80);
define('POTENCIAL_THRESHOLD', 60);

// Test retake restriction
// Minimum number of months a student must wait before retaking the test
define('TEST_RETAKE_MONTHS', 6);

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Timezone
date_default_timezone_set('America/Guayaquil');

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>