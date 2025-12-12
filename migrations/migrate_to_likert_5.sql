-- Migration Script: Convert Binary (0/1) to 5-Point Likert Scale (1-5)
-- WARNING: This will modify existing test data

-- Option 1: Delete all existing test data (DESTRUCTIVE)
-- Uncomment the following lines to delete all existing tests:
-- DELETE FROM respuestas_detalle;
-- DELETE FROM resultados_test;

-- Option 2: Convert existing 0/1 responses to 1/5 scale
-- This converts: 0 (No) -> 1 (Totalmente en desacuerdo)
--                1 (Sí) -> 5 (Totalmente de acuerdo)
-- Uncomment the following line to convert:
-- UPDATE respuestas_detalle SET respuesta = CASE WHEN respuesta = 0 THEN 1 WHEN respuesta = 1 THEN 5 ELSE respuesta END;

-- After choosing Option 1 or 2, run this to update the constraint:
ALTER TABLE respuestas_detalle DROP CONSTRAINT chk_respuesta;
ALTER TABLE respuestas_detalle ADD CONSTRAINT chk_respuesta CHECK (respuesta BETWEEN 1 AND 5);

-- Verify the change:
SHOW CREATE TABLE respuestas_detalle;
