-- ============================================
-- ACTUALIZAR BASE DE DATOS LOCAL A CATEGORÍAS RIASEC
-- Ejecutar en localhost para sincronizar con el servidor
-- ============================================

USE test_vocacional;

-- Ver las categorías actuales
SELECT DISTINCT categoria FROM preguntas ORDER BY categoria;

-- Actualizar categorías a RIASEC
UPDATE preguntas SET categoria = 'Realista' WHERE categoria = 'tecnologia';
UPDATE preguntas SET categoria = 'Investigador' WHERE categoria = 'ciencias';
UPDATE preguntas SET categoria = 'Artístico' WHERE categoria = 'artes';
UPDATE preguntas SET categoria = 'Social' WHERE categoria = 'humanidades' OR categoria = 'salud';
UPDATE preguntas SET categoria = 'Emprendedor' WHERE categoria = 'negocios';

-- Si tienes categoría 'Convencional' separada, mantenerla
-- Si no, algunas preguntas de negocios podrían ser Convencional

-- Verificar los cambios
SELECT categoria, tipo, COUNT(*) as total 
FROM preguntas 
GROUP BY categoria, tipo 
ORDER BY categoria, tipo;

-- Resultado esperado: 
-- Realista, Investigador, Artístico, Social, Emprendedor, Convencional

SELECT '✅ Base de datos local actualizada a RIASEC!' AS status;
