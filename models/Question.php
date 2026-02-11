<?php
class Question extends BaseModel
{
    protected $table = 'preguntas';

    // Obtener preguntas aleatorias por categoría y tipo
    /**
     * Obtener preguntas aleatorias por categoría y tipo.
     *
     * @param string $category Categoría (ej. Realista).
     * @param string $type Tipo (ej. Interés).
     * @return array Lista de preguntas.
     */
    public function getByCategoryAndType($category, $type)
    {
        $stmt = $this->db->prepare("
            SELECT * FROM {$this->table} 
            WHERE categoria = ? AND tipo = ? 
            ORDER BY RAND()
        ");
        $stmt->execute([$category, $type]);
        return $stmt->fetchAll();
    }

    /**
     * Obtener todas las preguntas agrupadas por categoría y tipo.
     *
     * @return array Preguntas agrupadas.
     */
    public function getAllGrouped()
    {
        $questions = [];
        foreach (TEST_CATEGORIES as $category) {
            foreach (TEST_TYPES as $type) {
                $questions[$category][$type] = $this->getByCategoryAndType($category, $type);
            }
        }
        return $questions;
    }

    /**
     * Buscar preguntas por texto.
     *
     * @param string $keyword Palabra clave.
     * @return array Lista de preguntas coincidentes.
     */
    public function search($keyword)
    {
        $stmt = $this->db->prepare("
            SELECT * FROM {$this->table} 
            WHERE pregunta LIKE ? 
            ORDER BY categoria, tipo
        ");
        $stmt->execute(['%' . $keyword . '%']);
        return $stmt->fetchAll();
    }

    /**
     * Importar preguntas desde un arreglo (usa transacción).
     *
     * @param array $questions Lista de preguntas a importar.
     * @return array Resultado de la importación (éxito, importados, duplicados).
     * @throws Exception Si ocurre un error durante la transacción.
     */
    public function importFromArray($questions)
    {
        $this->db->beginTransaction();

        try {
            $imported = 0;
            $duplicates = 0;

            foreach ($questions as $question) {
                // Verificar duplicados
                $stmt = $this->db->prepare("
                    SELECT id FROM {$this->table} 
                    WHERE pregunta = ? AND categoria = ? AND tipo = ?
                ");
                $stmt->execute([$question['pregunta'], $question['categoria'], $question['tipo']]);

                if ($stmt->fetch()) {
                    $duplicates++;
                    continue;
                }

                // Insertar pregunta
                $this->create([
                    'categoria' => $question['categoria'],
                    'tipo' => $question['tipo'],
                    'pregunta' => $question['pregunta'],
                    'peso' => $question['peso'] ?? 1
                ]);

                $imported++;
            }

            $this->db->commit();

            return [
                'success' => true,
                'imported' => $imported,
                'duplicates' => $duplicates
            ];

        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Eliminar múltiples preguntas por IDs.
     *
     * @param array $ids IDs de las preguntas a eliminar.
     * @return int Número de preguntas eliminadas.
     */
    public function deleteMultiple($ids)
    {
        if (empty($ids)) {
            return 0;
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id IN ($placeholders)");
        $stmt->execute($ids);

        return $stmt->rowCount();
    }
}
?>