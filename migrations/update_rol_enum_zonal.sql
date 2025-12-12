-- ============================================
-- Migración: Actualizar ENUM de rol para incluir 'zonal'
-- ============================================

-- Actualizar el ENUM de rol en la tabla usuarios
ALTER TABLE usuarios 
MODIFY COLUMN rol ENUM('administrador', 'zonal', 'dece', 'estudiante') NOT NULL DEFAULT 'estudiante';

-- Verificar el cambio
SELECT COLUMN_NAME, COLUMN_TYPE 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = 'test_vocacional' 
  AND TABLE_NAME = 'usuarios' 
  AND COLUMN_NAME = 'rol';

-- Resultado esperado: 
-- rol | enum('administrador','zonal','dece','estudiante')
