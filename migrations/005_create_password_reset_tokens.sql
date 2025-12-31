-- =====================================================
-- Migración: Crear tabla password_reset_tokens
-- Fecha: 2025-12-18
-- Descripción: Tabla para almacenar tokens de reset de contraseña
-- =====================================================

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
COMMENT='Tokens para recuperación de contraseña';

-- Limpiar tokens expirados automáticamente (opcional, ejecutar periódicamente):
-- DELETE FROM password_reset_tokens WHERE expires_at < NOW() AND used_at IS NULL;
