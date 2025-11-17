<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'test_vocacional');
define('DB_USER', 'root');
define('DB_PASS', '');

// Application settings
define('APP_NAME', 'Sistema de Test Vocacional');
define('APP_URL', 'https://www.desarrollodesoftware.com.ec/test-vocacional/');
define('APP_EMAIL', 'admin@colegio.edu');

// File upload settings
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB
define('ALLOWED_EXCEL_TYPES', ['application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
define('ALLOWED_WORD_TYPES', ['application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']);

// Security settings
define('SESSION_LIFETIME', 3600); // 1 hour
define('PASSWORD_MIN_LENGTH', 6);

// Test settings
define('TEST_CATEGORIES', ['ciencias', 'tecnologia', 'humanidades', 'artes', 'salud', 'negocios']);
define('TEST_TYPES', ['intereses', 'habilidades', 'valores']);
define('LIKERT_SCALE', [0, 1]); // Binary scale: 0 = No, 1 = Sí

// Results thresholds (adjusted for binary scale)
define('APTO_THRESHOLD', 80);
define('POTENCIAL_THRESHOLD', 60);

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