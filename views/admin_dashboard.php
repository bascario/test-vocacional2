<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= APP_NAME ?> - Panel Administrativo</title>
    <link rel="stylesheet" href="/test-vocacional/assets/css/styles.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-brand">
                Panel Administrativo
            </div>
            <div class="nav-menu">
                <span><?= htmlspecialchars($_SESSION['user_name']) ?> (<?= $_SESSION['user_role'] ?>)</span>
                <a href="/test-vocacional/logout" class="btn btn-sm btn-outline">Cerrar Sesión</a>
            </div>
        </div>
    </nav>

    <div class="admin-container">
        <aside class="admin-sidebar">
            <ul class="sidebar-menu">
                <li><a href="/test-vocacional/admin" class="active">📊 Dashboard</a></li>
                <li><a href="/test-vocacional/admin/questions">❓ Gestión de Preguntas</a></li>
                <?php if (in_array($_SESSION['user_role'], ['administrador','dece'])): ?>
                    <?php if ($_SESSION['user_role'] === 'administrador'): ?>
                        <li><a href="/test-vocacional/admin/questions/import">📥 Importar Preguntas</a></li>
                    <?php endif; ?>
                    <li><a href="/test-vocacional/admin/institutions">🏫 Instituciones</a></li>
                <?php endif; ?>
                <li><a href="/test-vocacional/admin/reports/group">📋 Reportes Grupales</a></li>
            </ul>
        </aside>

        <main class="admin-main">
            <div class="admin-header">
                <h1>Dashboard Estadístico</h1>
                <p>Resumen general del sistema de test vocacional</p>
            </div>

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">👥</div>
                    <div class="stat-content">
                        <h3><?= $stats['total_tests'] ?></h3>
                        <p>Tests Completados</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">📝</div>
                    <div class="stat-content">
                        <h3><?= count($students) ?></h3>
                        <p>Estudiantes Registrados</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">📈</div>
                    <div class="stat-content">
                        <h3><?= round($stats['average_scores']['ciencias']?? 0, 1) ?>%</h3>
                        <p>Promedio en Ciencias</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">🎯</div>
                    <div class="stat-content">
                        <h3><?= round($stats['average_scores']['tecnologia']?? 0, 1) ?>%</h3>
                        <p>Promedio en Tecnología</p>
                    </div>
                </div>
            </div>

            <!-- Charts -->
            <?php
                // Ensure average_scores has numeric values for all areas
                $avgRaw = $stats['average_scores'] ?? [];
                $avgScores = [
                    'ciencias' => isset($avgRaw['ciencias']) ? round((float)$avgRaw['ciencias'], 1) : 0.0,
                    'tecnologia' => isset($avgRaw['tecnologia']) ? round((float)$avgRaw['tecnologia'], 1) : 0.0,
                    'humanidades' => isset($avgRaw['humanidades']) ? round((float)$avgRaw['humanidades'], 1) : 0.0,
                    'artes' => isset($avgRaw['artes']) ? round((float)$avgRaw['artes'], 1) : 0.0,
                    'salud' => isset($avgRaw['salud']) ? round((float)$avgRaw['salud'], 1) : 0.0,
                    'negocios' => isset($avgRaw['negocios']) ? round((float)$avgRaw['negocios'], 1) : 0.0,
                ];
            ?>
            <div class="charts-section">
                <div class="chart-container">
                    <h3>Tests Realizados por Mes</h3>
                    <canvas id="monthlyChart"></canvas>
                </div>
                
                <div class="chart-container">
                    <h3>Promedio General por Área</h3>
                    <canvas id="areasChart"></canvas>
                </div>
            </div>

            <!-- Recent Tests -->
            <div class="recent-tests-section">
                <h3>Tests Recientes</h3>
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Estudiante</th>
                                <th>Curso</th>
                                <th>Área Principal</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentTests as $test):
                                $scores = json_decode($test['puntajes_json'], true);

                                // Determine main area by porcentaje if available, otherwise fallback
                                $mainArea = 'N/A';
                                if (is_array($scores) && !empty($scores)) {
                                    $maxPct = -INF;
                                    foreach ($scores as $areaKey => $areaData) {
                                        $pct = 0;
                                        if (is_array($areaData) && isset($areaData['porcentaje'])) {
                                            $pct = (float)$areaData['porcentaje'];
                                        } elseif (is_numeric($areaData)) {
                                            $pct = (float)$areaData;
                                        }
                                        if ($pct > $maxPct) {
                                            $maxPct = $pct;
                                            $mainArea = $areaKey;
                                        }
                                    }
                                }

                                // Safe student name and course (avoid undefined index warnings)
                                $studentName = trim((string)($test['nombre'] ?? '') . ' ' . (string)($test['apellido'] ?? ''));
                                if ($studentName === '') $studentName = '—';
                                $curso = (string)($test['curso'] ?? '—');
                            ?>
                            <tr>
                                <td><?= date('d/m/Y H:i', strtotime($test['fecha_test'])) ?></td>
                                <td><?= htmlspecialchars($studentName) ?></td>
                                <td><?= htmlspecialchars($curso) ?></td>
                                <td><?= htmlspecialchars(ucfirst((string)$mainArea)) ?></td>
                                <td>
                                    <a href="/test-vocacional/admin/reports/individual?student_id=<?= htmlspecialchars($test['usuario_id']) ?>" 
                                       class="btn btn-sm btn-primary" target="_blank">Ver PDF</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="quick-actions">
                <h3>Acciones Rápidas</h3>
                <div class="action-buttons">
                    <a href="/test-vocacional/admin/reports/group?format=pdf" class="btn btn-primary">
                        📄 Generar Reporte Grupal PDF
                    </a>
                    <a href="/test-vocacional/admin/reports/group?format=excel" class="btn btn-secondary">
                        📊 Generar Reporte Grupal Excel
                    </a>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Monthly Chart
        const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
        const monthlyData = <?= json_encode($stats['tests_by_month']) ?>;
        
        new Chart(monthlyCtx, {
            type: 'line',
            data: {
                labels: monthlyData.map(item => item.mes),
                datasets: [{
                    label: 'Tests realizados',
                    data: monthlyData.map(item => parseInt(item.cantidad)),
                    borderColor: 'rgba(102, 126, 234, 1)',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });

        // Areas Chart
        const areasCtx = document.getElementById('areasChart').getContext('2d');
        new Chart(areasCtx, {
            type: 'doughnut',
            data: {
                labels: ['Ciencias', 'Tecnología', 'Humanidades', 'Artes', 'Salud', 'Negocios'],
                datasets: [{
                    data: [
                            <?= $avgScores['ciencias'] ?>,
                            <?= $avgScores['tecnologia'] ?>,
                            <?= $avgScores['humanidades'] ?>,
                            <?= $avgScores['artes'] ?>,
                            <?= $avgScores['salud'] ?>,
                            <?= $avgScores['negocios'] ?>
                        ],
                    backgroundColor: [
                        'rgba(255, 99, 132, 0.8)',
                        'rgba(54, 162, 235, 0.8)',
                        'rgba(255, 206, 86, 0.8)',
                        'rgba(75, 192, 192, 0.8)',
                        'rgba(153, 102, 255, 0.8)',
                        'rgba(255, 159, 64, 0.8)'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    </script>
</body>
</html>