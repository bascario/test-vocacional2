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
            <a href="/test-vocacional/test?new=1" class="btn btn-sm btn-outline">Nuevo Test</a>
            <a href="/test-vocacional/auth/changePassword" class="btn btn-sm btn-outline">Cambiar Contraseña</a>
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

    <!-- Perfil - edición rápida -->
    <div class="category-section" style="margin-bottom:20px;">
        <h3>Mi Perfil</h3>
        <form method="POST" action="/test-vocacional/profile/update" style="display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end;">
            <div style="flex:1;min-width:200px;">
                <label>Nombre</label>
                <input type="text" name="nombre" value="<?= htmlspecialchars($user['nombre'] ?? '') ?>" class="form-control">
            </div>
            <div style="flex:1;min-width:200px;">
                <label>Apellido</label>
                <input type="text" name="apellido" value="<?= htmlspecialchars($user['apellido'] ?? '') ?>" class="form-control">
            </div>
            <div style="flex:1;min-width:220px;">
                <label>Email</label>
                <input type="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" class="form-control">
            </div>
            <div style="flex:1;min-width:140px;">
                <label>Curso</label>
                <input type="text" name="curso" value="<?= htmlspecialchars($user['curso'] ?? '') ?>" class="form-control">
            </div>
            <div style="flex:1;min-width:140px;">
                <label>Paralelo</label>
                <input type="text" name="paralelo" value="<?= htmlspecialchars($user['paralelo'] ?? '') ?>" class="form-control">
            </div>
            <div style="flex:1;min-width:160px;">
                <label>Teléfono</label>
                <input type="text" name="telefono" value="<?= htmlspecialchars($user['telefono'] ?? '') ?>" class="form-control">
            </div>
            <div style="flex:0 0 auto;">
                <button type="submit" class="btn btn-success">Guardar Perfil</button>
            </div>
        </form>
    </div>

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
            <div class="public-career-card result-card <?= $colorClass ?>">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:10px;">
                    <div>
                        <h4 style="margin:0 0 6px 0;"><?= ucfirst($area) ?></h4>
                        <p style="margin:0;color:#666;">Promedio: <?= round($data['promedio'], 2) ?>/5 • Preguntas: <?= $data['conteo'] ?></p>
                    </div>
                    <div style="text-align:right;">
                        <div class="result-icon" style="font-size:1.6rem;"><?= $icon ?></div>
                        <div style="margin-top:8px;">
                            <span class="score-percentage" style="font-size:1.4rem;color:var(--primary-color);font-weight:700;"><?= $data['porcentaje'] ?>%</span>
                            <div class="score-label" style="font-weight:700;margin-top:4px;color:#555;"><?= $data['estado'] ?></div>
                        </div>
                    </div>
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

    <!-- Carreras en Universidades Públicas (CES) -->
    <div class="public-careers-section">
        <h2>Carreras Sugeridas en Universidades Públicas</h2>
        <p>Basado en tu perfil, estas son algunas opciones en universidades públicas del país (Fuente: CES - Oferta Académica Vigente):</p>
        
        <?php
        $publicUniversityMap = [
            'Realista' => [
                ['name' => 'Ingeniería Civil', 'university' => 'Universidad Central del Ecuador (UCE)'],
                ['name' => 'Ingeniería Agronómica', 'university' => 'Universidad Central del Ecuador (UCE)'],
                ['name' => 'Ingeniería Ambiental', 'university' => 'Univ. Nacional de Chimborazo (UNACH)'],
                ['name' => 'Ing. en Manejo y Conservación del Medio Ambiente', 'university' => 'Univ. Nacional de Loja (UNL)']
            ],
            'Investigador' => [
                ['name' => 'Medicina', 'university' => 'Universidad Central del Ecuador (UCE)'],
                ['name' => 'Biología', 'university' => 'Universidad Central del Ecuador (UCE)'],
                ['name' => 'Física', 'university' => 'Escuela Politécnica Nacional (EPN)'],
                ['name' => 'Psicología Clínica', 'university' => 'Universidad Central del Ecuador (UCE)']
            ],
            'Artístico' => [
                ['name' => 'Artes Plásticas', 'university' => 'Universidad Nacional de Loja (UNL)'],
                ['name' => 'Música', 'university' => 'Universidad Central del Ecuador (UCE)'],
                ['name' => 'Artes Escénicas', 'university' => 'Universidad Central del Ecuador (UCE)'],
                ['name' => 'Diseño Gráfico', 'university' => 'Universidad Técnica de Ambato (UTA)']
            ],
            'Social' => [
                ['name' => 'Psicología Infantil y Psicorrehabilitación', 'university' => 'Univ. Central del Ecuador (UCE)'],
                ['name' => 'Psicología Educativa y Orientación', 'university' => 'Univ. Central del Ecuador (UCE)'],
                ['name' => 'Trabajo Social', 'university' => 'Universidad Central del Ecuador (UCE)'],
                ['name' => 'Enfermería', 'university' => 'Universidad Central del Ecuador (UCE)']
            ],
            'Emprendedor' => [
                ['name' => 'Derecho', 'university' => 'Universidad Central del Ecuador (UCE)'],
                ['name' => 'Administración de Empresas', 'university' => 'Universidad Central del Ecuador (UCE)'],
                ['name' => 'Comunicación', 'university' => 'Universidad Central del Ecuador (UCE)'],
                ['name' => 'Turismo', 'university' => 'Universidad Central del Ecuador (UCE)']
            ],
            'Convencional' => [
                ['name' => 'Contabilidad y Auditoría', 'university' => 'Universidad Central del Ecuador (UCE)'],
                ['name' => 'Finanzas', 'university' => 'Universidad Central del Ecuador (UCE)'],
                ['name' => 'Economía', 'university' => 'Universidad Central del Ecuador (UCE)'],
                ['name' => 'Bibliotecología', 'university' => 'Universidad Nacional de Loja (UNL)']
            ]
        ];

        // Obtener las 2 áreas principales del estudiante
        $userScores = [];
        foreach ($scores as $area => $data) {
            $userScores[$area] = (float)$data['porcentaje'];
        }
        arsort($userScores);
        $topTwoAreas = array_slice(array_keys($userScores), 0, 2);

        echo '<div class="public-careers-grid">';
        foreach ($topTwoAreas as $area) {
            $areaKey = ucfirst(strtolower($area));
            if (isset($publicUniversityMap[$areaKey])) {
                foreach ($publicUniversityMap[$areaKey] as $career) {
                    echo '<div class="public-career-card">';
                    echo '<div>';
                    echo '<h4>' . htmlspecialchars($career['name']) . '</h4>';
                    echo '<p class="university">' . htmlspecialchars($career['university']) . '</p>';
                    echo '</div>';
                    echo '<span class="area-tag">' . htmlspecialchars($areaKey) . '</span>';
                    echo '</div>';
                }
            }
        }
        echo '</div>';
        ?>
    </div>

    <!-- Carreras del Tecnológico Vida Nueva (filtradas por perfil) -->
    <div class="public-careers-section">
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

        // Renderizar las categorías coincidentes como tarjetas (reutiliza estilos de public-career-card)
        $anyShown = false;
        echo '<div class="public-careers-grid">';
        foreach ($careerMap as $key => $cat) {
            if (!empty($matchedCategories) && empty($matchedCategories[$key])) continue;
            $anyShown = true;
            foreach ($cat['items'] as $item) {
                $url = !empty($item['url']) ? $item['url'] : '#';
                echo '<a href="' . htmlspecialchars($url) . '" target="_blank" style="display:block;color:inherit;text-decoration:none;">';
                echo '<div class="public-career-card">';
                echo '<div>';
                echo '<h4>' . htmlspecialchars($item['label']) . '</h4>';
                echo '<p class="university">' . htmlspecialchars($cat['title']) . '</p>';
                echo '</div>';
                echo '<span class="area-tag">' . htmlspecialchars(strtoupper($key)) . '</span>';
                echo '</div>';
                echo '</a>';
            }
        }
        echo '</div>'; // end public-careers-grid

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
        <a href="/test-vocacional/test?new=1" class="btn btn-primary">Realizar Nuevo Test</a>
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