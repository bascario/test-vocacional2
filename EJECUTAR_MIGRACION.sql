-- ============================================
-- EJECUTAR ESTA MIGRACIÓN EN TU BASE DE DATOS
-- ============================================

-- Agregar las columnas faltantes a la tabla usuarios
-- Si alguna columna ya existe, simplemente ignora el error de esa línea

ALTER TABLE usuarios ADD COLUMN paralelo VARCHAR(10) AFTER curso;
ALTER TABLE usuarios ADD COLUMN bachillerato VARCHAR(100) AFTER paralelo;
ALTER TABLE usuarios ADD COLUMN telefono VARCHAR(20) AFTER bachillerato;

-- Agregar índices para mejorar el rendimiento
CREATE INDEX idx_usuarios_paralelo ON usuarios(paralelo);
CREATE INDEX idx_usuarios_bachillerato ON usuarios(bachillerato);

-- Verificar que las columnas se agregaron correctamente
SELECT COLUMN_NAME, DATA_TYPE, CHARACTER_MAXIMUM_LENGTH 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = 'test_vocacional' 
  AND TABLE_NAME = 'usuarios' 
  AND COLUMN_NAME IN ('paralelo', 'bachillerato', 'telefono');
