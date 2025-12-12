-- ============================================
-- MIGRACIÓN COMPLETA PARA SOPORTE DE ROL ZONAL
-- Ejecutar este script en phpMyAdmin o MySQL
-- ============================================

USE test_vocacional;

-- 1. Actualizar ENUM de rol para incluir 'zonal'
ALTER TABLE usuarios 
MODIFY COLUMN rol ENUM('administrador', 'zonal', 'dece', 'estudiante') 
NOT NULL DEFAULT 'estudiante';

-- 2. Agregar columna zona_id si no existe (para usuarios zonales)
ALTER TABLE usuarios 
ADD COLUMN IF NOT EXISTS zona_id VARCHAR(50) NULL 
COMMENT 'Zona asignada para usuarios con rol zonal'
AFTER institucion_id;

-- 3. Agregar columna paralelo si no existe
ALTER TABLE usuarios 
ADD COLUMN IF NOT EXISTS paralelo VARCHAR(10) NULL 
AFTER curso;

-- 4. Agregar columna bachillerato si no existe
ALTER TABLE usuarios 
ADD COLUMN IF NOT EXISTS bachillerato VARCHAR(100) NULL 
AFTER paralelo;

-- 5. Agregar columna telefono si no existe
ALTER TABLE usuarios 
ADD COLUMN IF NOT EXISTS telefono VARCHAR(20) NULL 
AFTER bachillerato;

-- 6. Agregar índices para mejorar rendimiento
ALTER TABLE usuarios 
ADD INDEX IF NOT EXISTS idx_usuarios_paralelo (paralelo);

ALTER TABLE usuarios 
ADD INDEX IF NOT EXISTS idx_usuarios_zona (zona_id);

-- 7. Agregar índice de zona en instituciones_educativas si no existe
ALTER TABLE instituciones_educativas 
ADD INDEX IF NOT EXISTS idx_zona (zona);

-- ============================================
-- VERIFICACIÓN
-- ============================================

-- Verificar estructura de la tabla usuarios
DESCRIBE usuarios;

-- Verificar que el rol 'zonal' esté disponible
SELECT COLUMN_TYPE 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = 'test_vocacional' 
  AND TABLE_NAME = 'usuarios' 
  AND COLUMN_NAME = 'rol';

-- Resultado esperado: enum('administrador','zonal','dece','estudiante')

SELECT '✅ Migración completada exitosamente!' AS status;
