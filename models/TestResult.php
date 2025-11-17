<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= APP_NAME ?> - Mis Resultados</title>
    <link rel="stylesheet" href="/test-vocacional/assets/css/styles.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-brand">
                🎯 Mis Resultados
            </div>
            <div class="nav-menu">
                <a href="/test-vocacional/test" class="btn btn-sm btn-outline">Nuevo Test</a>
                <a href="/test-vocacional/logout" class="btn btn-sm btn-outline">Cerrar Sesión</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="results-header">
            <h1>Mi Perfil Vocacional</h1>
            <p>Test realizado el <?= date('d/m/Y H:i', strtotime($latestResult['fecha_test'])) ?></p>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?= htmlspecialchars($_SESSION['success']) ?>
                <?php unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <!-- Results Cards -->
        <div class="results-grid">
            <?php foreach ($scores as $area => $data): ?>
                <?php 
                $colorClass = '';
                $icon = '';
                switch ($data['estado']) {
                    case 'APTO':
                        $colorClass = 'result-apto';
                        $icon = '✅';
                        break;
                    case 'POTENCIAL':
                        $colorClass = 'result-potencial';
                        $icon = '⚠️';
                        break;
                    case 'POR REFORZAR':
                        $colorClass = 'result-por-reforzar';
                        $icon = '❗';
                        break;
                }
                ?>
                <div class="result-card <?= $colorClass ?>">
                    <div class="result-header">
                        <h3><?= ucfirst($area) ?></h3>
                        <span class="result-icon"><?= $icon ?></span>
                    </div>
                    <div class="result-score">
                        <span class="score-percentage"><?= $data['porcentaje'] ?>%</span>
                        <span class="score-label"><?= $data['estado'] ?></span>
                    </div>
                    <div class="result-details">
                        <p>Promedio: <?= round($data['promedio'], 2) ?>/5</p>
                        <p>Preguntas: <?= $data['conteo'] ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Charts -->
        <div class="charts-container">
            <div class="chart-wrapper">
                <h3>Perfil Vocacional - Gráfico de Radar</h3>
                <canvas id="radarChart"></canvas>
            </div>
            
            <div class="chart-wrapper">
                <h3>Ranking de Áreas - Gráfico de Barras</h3>
                <canvas id="barChart"></canvas>
            </div>
        </div>

        <!-- Recommendations -->
        <div class="recommendations-section">
            <h2>Recomendaciones Personalizadas</h2>
            <div class="recommendations-grid">
                <?php foreach ($scores as $area => $data): ?>
                    <div class="recommendation-card">
                        <h4><?= ucfirst($area) ?></h4>
                        <p><?= getRecommendationText($area, $data['estado']) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="action-buttons">
            <a href="/test-vocacional/test" class="btn btn-primary">Realizar Nuevo Test</a>
            <a href="/test-vocacional/admin/reports/individual?student_id=<?= $_SESSION['user_id'] ?>" 
               class="btn btn-secondary" target="_blank">Descargar PDF</a>
        </div>
    </div>

    <script>
        // Radar Chart
        const radarCtx = document.getElementById('radarChart').getContext('2d');
        const radarData = {
            labels: <?= json_encode(array_map('ucfirst', array_keys($scores))) ?>,
            datasets: [{
                label: 'Mi Perfil Vocacional',
                data: <?= json_encode(array_column($scores, 'porcentaje')) ?>,
                backgroundColor: 'rgba(102, 126, 234, 0.2)',
                borderColor: 'rgba(102, 126, 234, 1)',
                borderWidth: 2,
                pointBackgroundColor: 'rgba(102, 126, 234, 1)',
                pointBorderColor: '#fff',
                pointHoverBackgroundColor: '#fff',
                pointHoverBorderColor: 'rgba(102, 126, 234, 1)'
            }]
        };

        new Chart(radarCtx, {
            type: 'radar',
            data: radarData,
            options: {
                responsive: true,
                scales: {
                    r: {
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            stepSize: 20
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });

        // Bar Chart
        const barCtx = document.getElementById('barChart').getContext('2d');
        const areas = <?= json_encode(array_map('ucfirst', array_keys($scores))) ?>;
        const percentages = <?= json_encode(array_column($scores, 'porcentaje')) ?>;
        const colors = [
            'rgba(255, 99, 132, 0.8)',
            'rgba(54, 162, 235, 0.8)',
            'rgba(255, 206, 86, 0.8)',
            'rgba(75, 192, 192, 0.8)',
            'rgba(153, 102, 255, 0.8)',
            'rgba(255, 159, 64, 0.8)'
        ];

        new Chart(barCtx, {
            type: 'bar',
            data: {
                labels: areas,
                datasets: [{
                    label: 'Porcentaje',
                    data: percentages,
                    backgroundColor: colors,
                    borderColor: colors.map(color => color.replace('0.8', '1')),
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    </script>
</body>
</html>

<?php
function getRecommendationText($area, $estado) {
    $recommendations = [
        'ciencias' => [
            'APTO' => "Excelente aptitud para ciencias. Considera carreras STEM como Medicina, Ingeniería, Biología. Tu perfil indica habilidades analíticas y pensamiento lógico desarrollado.",
            'POTENCIAL' => "Buen potencial en ciencias. Participa en ferias científicas y proyectos de investigación para fortalecer esta área.",
            'POR REFORZAR' => "Área por reforzar. Participa en laboratorios y busca tutorías en ciencias básicas."
        ],
        'tecnologia' => [
            'APTO' => "Alto potencial tecnológico. Carreras: Ingeniería de Sistemas, Desarrollo de Software, Robótica. Considera participar en hackatones.",
            'POTENCIAL' => "Buenas habilidades tecnológicas. Toma cursos de programación y talleres de robótica.",
            'POR REFORZAR' => "Empieza con cursos básicos de computación y explora aplicaciones educativas."
        ],
        'humanidades' => [
            'APTO' => "Excelente perfil humanístico. Considera: Derecho, Psicología, Sociología, Historia.",
            'POTENCIAL' => "Buen potencial en humanidades. Lee literatura clásica y participa en debates.",
            'POR REFORZAR' => "Lee más libros de diferentes géneros y busca comprender diferentes perspectivas."
        ],
        'artes' => [
            'APTO' => "Gran talento artístico. Carreras: Diseño Gráfico, Música, Artes Plásticas, Danza.",
            'POTENCIAL' => "Buena sensibilidad artística. Toma clases de arte y visita galerías.",
            'POR REFORZAR' => "Experimenta con diferentes formas de arte y asiste a talleres creativos."
        ],
        'salud' => [
            'APTO' => "Excelente vocación para salud. Carreras: Medicina, Enfermería, Psicología Clínica.",
            'POTENCIAL' => "Buen potencial en salud. Voluntariado en hospitales y cursos de primeros auxilios.",
            'POR REFORZAR' => "Aprende sobre anatomía básica y participa en campañas de salud comunitaria."
        ],
        'negocios' => [
            'APTO' => "Gran perfil empresarial. Carreras: Administración de Empresas, Contabilidad, Marketing.",
            'POTENCIAL' => "Buenas habilidades de negocios. Participa en simulaciones empresariales.",
            'POR REFORZAR' => "Aprende sobre finanzas personales y observa cómo funcionan los negocios locales."
        ]
    ];
    
    return $recommendations[$area][$estado] ?? "Continúa explorando esta área para descubrir tu potencial.";
}
?>