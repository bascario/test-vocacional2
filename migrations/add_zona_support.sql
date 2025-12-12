-- ============================================
-- Migración: Agregar soporte para Perfil Zonal
-- ============================================

-- 1. Agregar campo 'zona' a instituciones_educativas
ALTER TABLE instituciones_educativas 
  ADD COLUMN zona VARCHAR(50) AFTER tipo;

-- 2. Agregar índice para zona
CREATE INDEX idx_zona ON instituciones_educativas(zona);

-- 3. Agregar campo 'zona_id' a usuarios
ALTER TABLE usuarios 
  ADD COLUMN zona_id INT AFTER distrito_id;

-- 4. Actualizar ENUM de rol para incluir 'zonal'
ALTER TABLE usuarios 
  MODIFY COLUMN rol ENUM('administrador', 'zonal', 'dece', 'estudiante') NOT NULL;

-- 5. Verificar cambios
SELECT COLUMN_NAME, DATA_TYPE, COLUMN_TYPE 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = 'test_vocacional' 
  AND TABLE_NAME = 'instituciones_educativas' 
  AND COLUMN_NAME = 'zona';

SELECT COLUMN_NAME, DATA_TYPE 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = 'test_vocacional' 
  AND TABLE_NAME = 'usuarios' 
  AND COLUMN_NAME = 'zona_id';

-- ============================================
-- Ejemplo: Asignar zonas a instituciones
-- ============================================
-- Descomenta y ajusta según tus zonas reales

-- UPDATE instituciones_educativas SET zona = 'Zona 1' WHERE id BETWEEN 1 AND 10;
-- UPDATE instituciones_educativas SET zona = 'Zona 2' WHERE id BETWEEN 11 AND 20;
-- UPDATE instituciones_educativas SET zona = 'Zona 9' WHERE id BETWEEN 21 AND 30;
