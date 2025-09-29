<?php
require_once 'vendor/autoload.php';

class PDFGenerator {
    private $pdf;
    
    public function __construct() {
        $this->pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $this->setupPDF();
    }
    
    private function setupPDF() {
        // Set document information
        $this->pdf->SetCreator(PDF_CREATOR);
        $this->pdf->SetAuthor('Sistema de Test Vocacional');
        $this->pdf->SetTitle('Reporte de Test Vocacional');
        $this->pdf->SetSubject('Resultados del Test Vocacional');
        
        // Set default header data
        $this->pdf->SetHeaderData('', 0, 'Sistema de Test Vocacional', 'Reporte de Resultados');
        
        // Set header and footer fonts
        $this->pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
        $this->pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
        
        // Set margins
        $this->pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $this->pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
        $this->pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
        
        // Set auto page breaks
        $this->pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
        
        // Set image scale factor
        $this->pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
        
        // Set font
        $this->pdf->SetFont('helvetica', '', 10);
    }
    
    public function generateIndividualReport($result, $scores) {
        $this->pdf->AddPage();
        
        // Title
        $this->pdf->SetFont('helvetica', 'B', 16);
        $this->pdf->Cell(0, 10, 'REPORTE INDIVIDUAL DE TEST VOCACIONAL', 0, 1, 'C');
        $this->pdf->Ln(10);
        
        // Student information
        $this->pdf->SetFont('helvetica', 'B', 12);
        $this->pdf->Cell(0, 8, 'INFORMACIÓN DEL ESTUDIANTE', 0, 1, 'L');
        $this->pdf->SetFont('helvetica', '', 10);
        
        $html = '
        <table border="1" cellpadding="5">
            <tr>
                <td><strong>Nombre:</strong></td>
                <td>' . htmlspecialchars($result['nombre'] . ' ' . $result['apellido']) . '</td>
                <td><strong>Email:</strong></td>
                <td>' . htmlspecialchars($result['email']) . '</td>
            </tr>
            <tr>
                <td><strong>Curso:</strong></td>
                <td>' . htmlspecialchars($result['curso']) . '</td>
                <td><strong>Fecha del Test:</strong></td>
                <td>' . date('d/m/Y H:i', strtotime($result['fecha_test'])) . '</td>
            </tr>
        </table>
        ';
        
        $this->pdf->writeHTML($html, true, false, true, false, '');
        $this->pdf->Ln(10);
        
        // Results table
        $this->pdf->SetFont('helvetica', 'B', 12);
        $this->pdf->Cell(0, 8, 'RESULTADOS POR ÁREA VOCACIONAL', 0, 1, 'L');
        
        $html = '
        <table border="1" cellpadding="5">
            <thead>
                <tr style="background-color: #f0f0f0;">
                    <th><strong>Área</strong></th>
                    <th><strong>Puntaje</strong></th>
                    <th><strong>Porcentaje</strong></th>
                    <th><strong>Estado</strong></th>
                </tr>
            </thead>
            <tbody>
        ';
        
        foreach ($scores as $area => $data) {
            $color = $this->getStateColor($data['estado']);
            $html .= '
                <tr>
                    <td>' . ucfirst($area) . '</td>
                    <td>' . round($data['promedio'], 2) . '/5</td>
                    <td>' . $data['porcentaje'] . '%</td>
                    <td style="color: ' . $color . '; font-weight: bold;">' . $data['estado'] . '</td>
                </tr>
            ';
        }
        
        $html .= '</tbody></table>';
        $this->pdf->writeHTML($html, true, false, true, false, '');
        
        // Recommendations
        $this->pdf->Ln(10);
        $this->pdf->SetFont('helvetica', 'B', 12);
        $this->pdf->Cell(0, 8, 'RECOMENDACIONES PERSONALIZADAS', 0, 1, 'L');
        $this->pdf->SetFont('helvetica', '', 10);
        
        $recommendations = $this->getRecommendations($scores);
        foreach ($recommendations as $area => $rec) {
            $this->pdf->Ln(5);
            $this->pdf->SetFont('helvetica', 'B', 10);
            $this->pdf->Cell(0, 6, ucfirst($area) . ':', 0, 1, 'L');
            $this->pdf->SetFont('helvetica', '', 10);
            $this->pdf->MultiCell(0, 5, $rec);
        }
        
        return $this->pdf->Output('reporte_individual.pdf', 'S');
    }
    
    public function generateGroupReport($results, $course = null) {
        $this->pdf->AddPage();
        
        // Title
        $this->pdf->SetFont('helvetica', 'B', 16);
        $title = 'REPORTE GRUPAL DE TEST VOCACIONAL';
        if ($course) {
            $title .= ' - CURSO: ' . strtoupper($course);
        }
        $this->pdf->Cell(0, 10, $title, 0, 1, 'C');
        $this->pdf->Ln(10);
        
        // Summary statistics
        $this->pdf->SetFont('helvetica', 'B', 12);
        $this->pdf->Cell(0, 8, 'RESUMEN ESTADÍSTICO', 0, 1, 'L');
        $this->pdf->SetFont('helvetica', '', 10);
        
        $totalStudents = count($results);
        $this->pdf->Cell(0, 6, 'Total de estudiantes evaluados: ' . $totalStudents, 0, 1, 'L');
        $this->pdf->Cell(0, 6, 'Fecha de generación: ' . date('d/m/Y H:i'), 0, 1, 'L');
        $this->pdf->Ln(10);
        
        // Detailed results table
        $this->pdf->SetFont('helvetica', 'B', 12);
        $this->pdf->Cell(0, 8, 'RESULTADOS DETALLADOS', 0, 1, 'L');
        
        $html = '
        <table border="1" cellpadding="4">
            <thead>
                <tr style="background-color: #f0f0f0;">
                    <th><strong>Estudiante</strong></th>
                    <th><strong>Curso</strong></th>
                    <th><strong>Ciencias</strong></th>
                    <th><strong>Tecnología</strong></th>
                    <th><strong>Humanidades</strong></th>
                    <th><strong>Artes</strong></th>
                    <th><strong>Salud</strong></th>
                    <th><strong>Negocios</strong></th>
                </tr>
            </thead>
            <tbody>
        ';
        
        foreach ($results as $result) {
            $scores = json_decode($result['puntajes_json'], true);
            $html .= '
                <tr>
                    <td>' . htmlspecialchars($result['apellido'] . ', ' . $result['nombre']) . '</td>
                    <td>' . htmlspecialchars($result['curso']) . '</td>
            ';
            
            foreach (TEST_CATEGORIES as $category) {
                $percentage = $scores[$category]['porcentaje'] ?? 0;
                $color = $this->getStateColor($scores[$category]['estado'] ?? 'POR REFORZAR');
                $html .= '<td style="color: ' . $color . ';">' . round($percentage, 1) . '%</td>';
            }
            
            $html .= '</tr>';
        }
        
        $html .= '</tbody></table>';
        $this->pdf->writeHTML($html, true, false, true, false, '');
        
        return $this->pdf->Output('reporte_grupal.pdf', 'S');
    }
    
    private function getStateColor($state) {
        switch ($state) {
            case 'APTO':
                return '#28a745';
            case 'POTENCIAL':
                return '#ffc107';
            case 'POR REFORZAR':
                return '#dc3545';
            default:
                return '#6c757d';
        }
    }
    
    private function getRecommendations($scores) {
        $recommendations = [];
        
        foreach ($scores as $area => $data) {
            switch ($area) {
                case 'ciencias':
                    if ($data['estado'] === 'APTO') {
                        $recommendations[$area] = "Excelente aptitud para ciencias. Considera carreras como: Medicina, Ingeniería, Biología, Química, Física. Tu perfil indica habilidades analíticas y pensamiento lógico desarrollado.";
                    } elseif ($data['estado'] === 'POTENCIAL') {
                        $recommendations[$area] = "Buen potencial en ciencias. Te recomendamos participar en ferias científicas, clubes de ciencias y realizar proyectos de investigación para fortalecer esta área.";
                    } else {
                        $recommendations[$area] = "Área por reforzar. Participa en laboratorios, visita museos de ciencias, y busca tutorías en matemáticas y ciencias básicas.";
                    }
                    break;
                    
                case 'tecnologia':
                    if ($data['estado'] === 'APTO') {
                        $recommendations[$area] = "Alto potencial tecnológico. Carreras recomendadas: Ingeniería de Sistemas, Desarrollo de Software, Robótica, Inteligencia Artificial. Considera participar en hackatones y competencias de programación.";
                    } elseif ($data['estado'] === 'POTENCIAL') {
                        $recommendations[$area] = "Buenas habilidades tecnológicas. Toma cursos de programación, participa en talleres de robótica y mantente actualizado con las últimas tendencias tecnológicas.";
                    } else {
                        $recommendations[$area] = "Necesitas reforzar esta área. Empieza con cursos básicos de computación, aprende a usar herramientas digitales y explora aplicaciones educativas.";
                    }
                    break;
                    
                case 'humanidades':
                    if ($data['estado'] === 'APTO') {
                        $recommendations[$area] = "Excelente perfil humanístico. Considera: Derecho, Psicología, Sociología, Historia, Filosofía. Tu capacidad de análisis crítico y comprensión de contextos sociales es destacada.";
                    } elseif ($data['estado'] === 'POTENCIAL') {
                        $recommendations[$area] = "Buen potencial en humanidades. Lee literatura clásica, participa en debates y seminarios, y desarrolla tu escritura y oratoria.";
                    } else {
                        $recommendations[$area] = "Área por fortalecer. Lee más libros de diferentes géneros, participa en conversatorios y busca comprender diferentes perspectivas culturales.";
                    }
                    break;
                    
                case 'artes':
                    if ($data['estado'] === 'APTO') {
                        $recommendations[$area] = "Gran talento artístico. Carreras sugeridas: Diseño Gráfico, Música, Artes Plásticas, Danza, Teatro. Considera crear un portafolio de tus trabajos y participar en concursos artísticos.";
                    } elseif ($data['estado'] === 'POTENCIAL') {
                        $recommendations[$area] = "Buena sensibilidad artística. Toma clases de arte, música o teatro, visita galerías y museos, y practica técnicas artísticas regularmente.";
                    } else {
                        $recommendations[$area] = "Desarrolla tu lado artístico. Experimenta con diferentes formas de arte, asiste a talleres creativos y no temas expresarte artísticamente.";
                    }
                    break;
                    
                case 'salud':
                    if ($data['estado'] === 'APTO') {
                        $recommendations[$area] = "Excelente vocación para salud. Carreras ideales: Medicina, Enfermería, Psicología Clínica, Nutrición, Fisioterapia. Tu empatía y vocación de servicio son fundamentales.";
                    } elseif ($data['estado'] === 'POTENCIAL') {
                        $recommendations[$area] = "Buen potencial en salud. Voluntariado en hospitales, primeros auxilios, y cursos de anatomía y fisiología te ayudarán a confirmar tu vocación.";
                    } else {
                        $recommendations[$area] = "Explora el área de salud. Aprende sobre anatomía básica, participa en campañas de salud comunitaria y desarrolla tu sentido de empatía y cuidado.";
                    }
                    break;
                    
                case 'negocios':
                    if ($data['estado'] === 'APTO') {
                        $recommendations[$area] = "Gran perfil empresarial. Considera: Administración de Empresas, Contabilidad, Marketing, Economía, Emprendimiento. Tu visión estratégica y habilidades de liderazgo son destacadas.";
                    } elseif ($data['estado'] === 'POTENCIAL') {
                        $recommendations[$area] = "Buenas habilidades de negocios. Participa en simulaciones empresariales, lee sobre casos de negocios exitosos y desarrolla proyectos escolares con enfoque comercial.";
                    } else {
                        $recommendations[$area] = "Fortalece tus habilidades empresariales. Aprende sobre finanzas personales, observa cómo funcionan los negocios locales y desarrolla proyectos pequeños de venta o servicio.";
                    }
                    break;
            }
        }
        
        return $recommendations;
    }
}
?>