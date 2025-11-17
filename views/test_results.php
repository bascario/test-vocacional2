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