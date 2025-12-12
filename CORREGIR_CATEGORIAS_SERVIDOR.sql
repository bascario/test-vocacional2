-- ============================================
-- CORREGIR CATEGORÍAS DE PREGUNTAS EN EL SERVIDOR
-- Ejecutar este script en phpMyAdmin del servidor
-- ============================================

USE desarrollo_test_vocacional;

-- Ver las categorías actuales
SELECT DISTINCT categoria FROM preguntas ORDER BY categoria;

-- Actualizar categorías de RIASEC a formato del código
UPDATE preguntas SET categoria = 'tecnologia' WHERE categoria = 'Realista';
UPDATE preguntas SET categoria = 'ciencias' WHERE categoria = 'Investigador';
UPDATE preguntas SET categoria = 'artes' WHERE categoria = 'Artístico' OR categoria = 'Artistica';
UPDATE preguntas SET categoria = 'humanidades' WHERE categoria = 'Social';
UPDATE preguntas SET categoria = 'negocios' WHERE categoria = 'Emprendedor' OR categoria = 'Emprendedora';
UPDATE preguntas SET categoria = 'negocios' WHERE categoria = 'Convencional';

-- Si hay categoría "salud" que no aparece en la imagen, mantenerla
-- UPDATE preguntas SET categoria = 'salud' WHERE categoria = 'Salud';

-- Verificar los cambios
SELECT categoria, tipo, COUNT(*) as total 
FROM preguntas 
GROUP BY categoria, tipo 
ORDER BY categoria, tipo;

-- Resultado esperado: categorías en minúsculas
-- ciencias, tecnologia, humanidades, artes, salud, negocios

SELECT '✅ Categorías actualizadas correctamente!' AS status;
