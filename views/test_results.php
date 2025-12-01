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
        <div class="nav-brand">Mis Resultados</div>
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
        <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
    <?php endif; ?>

    <!-- Tarjetas de resultados -->
    <div class="results-grid">
        <?php foreach ($scores as $area => $data): ?>
            <?php
            $colorClass = match($data['estado']) {
                'APTO' => 'result-apto',
                'POTENCIAL' => 'result-potencial',
                'POR REFORZAR' => 'result-por-reforzar',
                default => ''
            };
            $icon = match($data['estado']) {
                'APTO' => '✅',
                'POTENCIAL' => '⚠️',
                'POR REFORZAR' => '❗',
                default => '🔍'
            };
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

    <!-- Gráficos -->
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

    <!-- Recomendaciones personalizadas -->
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

    <!-- Carreras del Tecnológico Vida Nueva (filtradas por perfil) -->
    <div class="careers-section">
        <h2>Carreras Ofertadas - Tecnológico Universitario Vida Nueva</h2>
        <p class="careers-intro">Explora las carreras disponibles en el Tecnológico Universitario Vida Nueva que podrían ser de tu interés según tu perfil vocacional.</p>

        <?php
        // Mapeo de categorías con palabras clave para identificar coincidencias con las áreas del test
        $careerMap = [
            'salud' => [
                'title' => '🏥 Carreras de Salud',
                'keywords' => ['salud','enfermeria','enfermería'],
                'items' => [
                    ['label' => 'Enfermería', 'url' => 'https://vidanueva.edu.ec/enfermeria/']
                ]
            ],
            'tecnicas' => [
                'title' => '🔧 Carreras Técnicas',
                'keywords' => ['mecanica','mecánica','electromecanica','automotriz','industrial','automatizacion','instrumentacion'],
                'items' => [
                    ['label' => 'Electromecánica', 'url' => 'https://vidanueva.edu.ec/electromecanica/'],
                    ['label' => 'Mecánica Automotriz', 'url' => 'https://vidanueva.edu.ec/mecanica-automotriz/'],
                    ['label' => 'Electromecánica Automotriz', 'url' => 'https://vidanueva.edu.ec/electromecanica-automotriz/'],
                    ['label' => 'Enderezada y Pintura Automotriz', 'url' => 'https://vidanueva.edu.ec/enderezada-y-pintura-automotriz-2/'],
                    ['label' => 'Tecnología Superior en Mecánica Industrial', 'url' => 'https://vidanueva.edu.ec/tecnologia-superior-universitaria-en-mecanica-industrial/'],
                    ['label' => 'Automatización e Instrumentación', 'url' => 'https://vidanueva.edu.ec/automatizacion-e-instrumentacion-3/'],
                    ['label' => 'Mecánica Industrial', 'url' => 'https://vidanueva.edu.ec/mecanica-industrial/']
                ]
            ],
            'educacion' => [
                'title' => '📚 Carreras de Educación',
                'keywords' => ['educacion','docencia','educación'],
                'items' => [
                    ['label' => 'Educación Inicial', 'url' => 'https://vidanueva.edu.ec/educacion-inicial/'],
                    ['label' => 'Educación Básica', 'url' => 'https://vidanueva.edu.ec/educacion-basica/'],
                    ['label' => 'Docencia e Innovación Educativa', 'url' => 'https://vidanueva.edu.ec/docencia-e-innovacion-educativa-3/']
                ]
            ],
            'servicios' => [
                'title' => '💇 Carreras de Servicios',
                'keywords' => ['servicio','servicios','estetica','estética','deportivo','entrenamiento'],
                'items' => [
                    ['label' => 'Estética Integral', 'url' => 'https://vidanueva.edu.ec/estetica-integral/'],
                    ['label' => 'Tecnología Superior en Entrenamiento Deportivo', 'url' => 'https://vidanueva.edu.ec/tecnologia-superior-en-entrenamiento-deportivo/']
                ]
            ],
            'negocios' => [
                'title' => '💼 Carreras de Negocios y Finanzas',
                'keywords' => ['administracion','contabilidad','marketing','negocio','negocios','finanza','finanzas'],
                'items' => [
                    ['label' => 'Tecnología Superior Universitaria en Administración', 'url' => 'https://vidanueva.edu.ec/tecnologia-superior-universitaria-en-administracion/'],
                    ['label' => 'Contabilidad', 'url' => 'https://vidanueva.edu.ec/contabilidad/'],
                    ['label' => 'Contabilidad y Asesoría Tributaria', 'url' => 'https://vidanueva.edu.ec/contabilidad-y-asesoria-tributaria/'],
                    ['label' => 'Marketing Digital y Comercio Electrónico', 'url' => 'https://vidanueva.edu.ec/marketing-digital-y-comercio-electronico/'],
                    ['label' => 'Administración', 'url' => 'https://vidanueva.edu.ec/administracion/']
                ]
            ],
            'tic' => [
                'title' => '💻 Carreras de TIC',
                'keywords' => ['software','desarrollo','ensamblaje','computo','cómputo','tic','informatic'],
                'items' => [
                    ['label' => 'Desarrollo de Software', 'url' => 'https://vidanueva.edu.ec/desarrollo-de-software/'],
                    ['label' => 'Ensamblaje y Mantenimiento de Equipos de Cómputo', 'url' => 'https://vidanueva.edu.ec/ensamblaje-y-mantenimiento-de-equipos-de-computo-2/']
                ]
            ]
        ];

        // Detectar categorías que coinciden con áreas del perfil (estado APTO o POTENCIAL)
        $matchedCategories = [];
        foreach ($scores as $area => $data) {
            $estado = strtoupper($data['estado'] ?? '');
            if (!in_array($estado, ['APTO', 'POTENCIAL'])) continue;
            $name = strtolower($area);
            foreach ($careerMap as $key => $cat) {
                foreach ($cat['keywords'] as $kw) {
                    if (strpos($name, $kw) !== false) {
                        $matchedCategories[$key] = true;
                    }
                }
            }
        }

        // Si no hay coincidencias directas, elegir la categoría relacionada con el área de mayor porcentaje
        if (empty($matchedCategories)) {
            $maxArea = null; $maxPct = -1;
            foreach ($scores as $area => $data) {
                $pct = isset($data['porcentaje']) ? (float)$data['porcentaje'] : 0;
                if ($pct > $maxPct) { $maxPct = $pct; $maxArea = $area; }
            }
            if ($maxArea) {
                $name = strtolower($maxArea);
                foreach ($careerMap as $key => $cat) {
                    foreach ($cat['keywords'] as $kw) {
                        if (strpos($name, $kw) !== false) { $matchedCategories[$key] = true; }
                    }
                }
            }
        }

        // Renderizar las categorías coincidentes
        $anyShown = false;
        echo '<div class="careers-grid">';
        foreach ($careerMap as $key => $cat) {
            if (!empty($matchedCategories) && empty($matchedCategories[$key])) continue;
            $anyShown = true;
            echo '<div class="career-category">';
            echo '<h3>' . htmlspecialchars($cat['title']) . '</h3>';
            echo '<ul>';
            foreach ($cat['items'] as $item) {
                echo '<li><a href="' . htmlspecialchars($item['url']) . '" target="_blank">' . htmlspecialchars($item['label']) . '</a></li>';
            }
            echo '</ul>';
            echo '</div>';
        }
        echo '</div>'; // end careers-grid

        if (!$anyShown) {
            echo '<p><em>No se encontraron coincidencias claras con tu perfil. Aquí tienes la oferta completa en el sitio del Tecnológico Vida Nueva:</em> <a href="https://vidanueva.edu.ec/oferta-academica/" target="_blank">Ver oferta académica completa</a></p>';
        }
        ?>

        <div class="careers-contact">
            <h4>¿Necesitas más información?</h4>
            <p>Contacta al Tecnológico Universitario Vida Nueva:</p>
            <ul>
                <li>📞 <a href="https://wa.me/message/54EBII4JMBI7M1" target="_blank">WhatsApp: 098 769 0886</a></li>
                <li>📧 <a href="mailto:admisiones@istvidanueva.edu.ec">Email: admisiones@istvidanueva.edu.ec</a></li>
                <li>🌐 <a href="https://vidanueva.edu.ec/" target="_blank">Sitio Web: www.vidanueva.edu.ec</a></li>
            </ul>
        </div>
    </div>

    <!-- Acciones -->
    <div class="action-buttons">
        <a href="/test-vocacional/test" class="btn btn-primary">Realizar Nuevo Test</a>
        <a href="/test-vocacional/admin/reports/individual?student_id=<?= $_SESSION['user_id'] ?>" 
           class="btn btn-secondary" target="_blank">Descargar PDF</a>
    </div>
</div>

<script>
    // Gráfico de Radar
    const radarCtx = document.getElementById('radarChart').getContext('2d');
    new Chart(radarCtx, {
        type: 'radar',
        data: {
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
        },
        options: {
            responsive: true,
            scales: {
                r: {
                    beginAtZero: true,
                    max: 100,
                    ticks: { stepSize: 20 }
                }
            },
            plugins: { legend: { display: false } }
        }
    });

    // Gráfico de Barras
    const barCtx = document.getElementById('barChart').getContext('2d');
    new Chart(barCtx, {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_map('ucfirst', array_keys($scores))) ?>,
            datasets: [{
                label: 'Porcentaje',
                data: <?= json_encode(array_column($scores, 'porcentaje')) ?>,
                backgroundColor: [
                    'rgba(255, 99, 132, 0.8)',
                    'rgba(54, 162, 235, 0.8)',
                    'rgba(255, 206, 86, 0.8)',
                    'rgba(75, 192, 192, 0.8)',
                    'rgba(153, 102, 255, 0.8)',
                    'rgba(255, 159, 64, 0.8)'
                ],
                borderColor: [
                    'rgba(255, 99, 132, 1)',
                    'rgba(54, 162, 235, 1)',
                    'rgba(255, 206, 86, 1)',
                    'rgba(75, 192, 192, 1)',
                    'rgba(153, 102, 255, 1)',
                    'rgba(255, 159, 64, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: { beginAtZero: true, max: 100 }
            },
            plugins: { legend: { display: false } }
        }
    });
</script>

</body>
</html>