<?php
class Question extends BaseModel {
    protected $table = 'preguntas';
    
    public function getByCategoryAndType($category, $type) {
        $stmt = $this->db->prepare("
            SELECT * FROM {$this->table} 
            WHERE categoria = ? AND tipo = ? 
            ORDER BY RAND()
        ");
        $stmt->execute([$category, $type]);
        return $stmt->fetchAll();
    }
    
    public function getAllGrouped() {
        $questions = [];
        foreach (TEST_CATEGORIES as $category) {
            foreach (TEST_TYPES as $type) {
                $questions[$category][$type] = $this->getByCategoryAndType($category, $type);
            }
        }
        return $questions;
    }
    
    public function search($keyword) {
        $stmt = $this->db->prepare("
            SELECT * FROM {$this->table} 
            WHERE pregunta LIKE ? 
            ORDER BY categoria, tipo
        ");
        $stmt->execute(['%' . $keyword . '%']);
        return $stmt->fetchAll();
    }
    
    public function importFromArray($questions) {
        $this->db->beginTransaction();
        
        try {
            $imported = 0;
            $duplicates = 0;
            
            foreach ($questions as $question) {
                // Check for duplicates
                $stmt = $this->db->prepare("
                    SELECT id FROM {$this->table} 
                    WHERE pregunta = ? AND categoria = ? AND tipo = ?
                ");
                $stmt->execute([$question['pregunta'], $question['categoria'], $question['tipo']]);
                
                if ($stmt->fetch()) {
                    $duplicates++;
                    continue;
                }
                
                // Insert question
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
    
    public function deleteMultiple($ids) {
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