<?php
/**
 * Script para crear tabla password_reset_tokens
 * 
 * Uso: php create_tokens_table.php
 */

require_once 'vendor/autoload.php';
require_once 'config/config.php';
require_once 'models/BaseModel.php';

echo "=========================================\n";
echo "Creando tabla password_reset_tokens\n";
echo "=========================================\n\n";

try {
    $db = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
        DB_USER,
        DB_PASS
    );

    $sql = <<<SQL
CREATE TABLE IF NOT EXISTS `password_reset_tokens` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `token` VARCHAR(64) NOT NULL UNIQUE,
  `token_hash` VARCHAR(255) NOT NULL UNIQUE,
  `email` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `expires_at` TIMESTAMP NOT NULL,
  `used_at` TIMESTAMP NULL DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE,
  KEY `idx_token_hash` (`token_hash`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_email` (`email`),
  KEY `idx_expires_at` (`expires_at`),
  INDEX `idx_unused_tokens` (`used_at`, `expires_at`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='Tokens para recuperación de contraseña'
SQL;

    $db->exec($sql);

    echo "✓ Tabla creada exitosamente\n";
    echo "\nVerificando tabla...\n";

    $stmt = $db->prepare("DESCRIBE password_reset_tokens");
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Columnas:\n";
    foreach ($columns as $col) {
        echo "  - " . $col['Field'] . " (" . $col['Type'] . ")\n";
    }

    echo "\n✓ Migración completada correctamente\n";

} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
