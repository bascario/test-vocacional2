-- Add column for storing pre-test survey data in JSON format
ALTER TABLE resultados_test ADD COLUMN encuesta_prev_json JSON DEFAULT NULL;
