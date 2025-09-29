<?php
class VocationalTest extends BaseModel {
    protected $table = 'resultados_test';
    
    public function createTest($usuarioId, $respuestas) {
        // Calculate scores
        $puntajes = $this->calculateScores($respuestas);
        
        // Create test result
        $testId = $this->create([
            'usuario_id' => $usuarioId,
            'puntajes_json' => json_encode($puntajes)
        ]);
        
        // Store detailed answers
        $this->storeDetailedAnswers($testId, $respuestas);
        
        return $testId;
    }
    
    private function calculateScores($respuestas) {
        $puntajes = [];
        $conteos = [];
        
        // Initialize arrays
        foreach (TEST_CATEGORIES as $category) {
            $puntajes[$category] = 0;
            $conteos[$category] = 0;
        }
        
        // Get question details
        $questionModel = new Question();
        
        foreach ($respuestas as $preguntaId => $respuesta) {
            $pregunta = $questionModel->find($preguntaId);
            
            if (!$pregunta) {
                continue;
            }
            
            $categoria = $pregunta['categoria'];
            $peso = $pregunta['peso'] ?? 1;
            
            $puntajes[$categoria] += ($respuesta * $peso);
            $conteos[$categoria]++;
        }
        
        // Calculate percentages and states
        $resultados = [];
        foreach (TEST_CATEGORIES as $category) {
            if ($conteos[$category] > 0) {
                $promedio = $puntajes[$category] / $conteos[$category];
                $porcentaje = ($promedio / 5) * 100;
                
                if ($porcentaje >= APTO_THRESHOLD) {
                    $estado = 'APTO';
                } elseif ($porcentaje >= POTENCIAL_THRESHOLD) {
                    $estado = 'POTENCIAL';
                } else {
                    $estado = 'POR REFORZAR';
                }
                
                $resultados[$category] = [
                    'puntaje' => $puntajes[$category],
                    'conteo' => $conteos[$category],
                    'promedio' => $promedio,
                    'porcentaje' => round($porcentaje, 2),
                    'estado' => $estado
                ];
            } else {
                $resultados[$category] = [
                    'puntaje' => 0,
                    'conteo' => 0,
                    'promedio' => 0,
                    'porcentaje' => 0,
                    'estado' => 'POR REFORZAR'
                ];
            }
        }
        
        return $resultados;
    }
    
    private function storeDetailedAnswers($testId, $respuestas) {
        $stmt = $this->db->prepare("
            INSERT INTO respuestas_detalle (test_id, pregunta_id, respuesta) 
            VALUES (?, ?, ?)
        ");
        
        foreach ($respuestas as $preguntaId => $respuesta) {
            $stmt->execute([$testId, $preguntaId, $respuesta]);
        }
    }
    
    public function getResultsByUser($usuarioId) {
        $stmt = $this->db->prepare("
            SELECT rt.*, u.nombre, u.apellido, u.curso, u.email
            FROM {$this->table} rt
            JOIN usuarios u ON rt.usuario_id = u.id
            WHERE rt.usuario_id = ?
            ORDER BY rt.fecha_test DESC
        ");
        $stmt->execute([$usuarioId]);
        return $stmt->fetchAll();
    }
    
    public function getResultsByCourse($curso = null) {
        $sql = "
            SELECT rt.*, u.nombre, u.apellido, u.curso, u.email
            FROM {$this->table} rt
            JOIN usuarios u ON rt.usuario_id = u.id
            WHERE u.rol = 'estudiante'
        ";
        $params = [];
        
        if ($curso) {
            $sql .= " AND u.curso = ?";
            $params[] = $curso;
        }
        
        $sql .= " ORDER BY u.curso, u.apellido, u.nombre";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    public function getStatistics() {
        $stats = [];
        
        // Total tests
        $stmt = $this->db->query("SELECT COUNT(*) as total FROM {$this->table}");
        $stats['total_tests'] = $stmt->fetch()['total'];
        
        // Tests by month
        $stmt = $this->db->query("
            SELECT DATE_FORMAT(fecha_test, '%Y-%m') as mes, COUNT(*) as cantidad
            FROM {$this->table}
            WHERE fecha_test >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
            GROUP BY mes
            ORDER BY mes
        ");
        $stats['tests_by_month'] = $stmt->fetchAll();
        
        // Average scores by area
        $stmt = $this->db->query("
            SELECT 
                AVG(JSON_EXTRACT(puntajes_json, '$.ciencias.porcentaje')) as ciencias,
                AVG(JSON_EXTRACT(puntajes_json, '$.tecnologia.porcentaje')) as tecnologia,
                AVG(JSON_EXTRACT(puntajes_json, '$.humanidades.porcentaje')) as humanidades,
                AVG(JSON_EXTRACT(puntajes_json, '$.artes.porcentaje')) as artes,
                AVG(JSON_EXTRACT(puntajes_json, '$.salud.porcentaje')) as salud,
                AVG(JSON_EXTRACT(puntajes_json, '$.negocios.porcentaje')) as negocios
            FROM {$this->table}
        ");
        $stats['average_scores'] = $stmt->fetch();
        
        return $stats;
    }
}
?>