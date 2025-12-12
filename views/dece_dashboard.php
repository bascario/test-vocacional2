<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= APP_NAME ?> - Dashboard DECE</title>
    <link rel="stylesheet" href="/test-vocacional/assets/css/styles.css">
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/test-vocacional/assets/img/logoTUVN.ico">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .filter-section {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .filter-row {
            display: flex;
            gap: 15px;
            align-items: end;
            flex-wrap: wrap;
        }

        .filter-group {
            flex: 1;
            min-width: 200px;
        }

        .filter-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #333;
        }

        .filter-group select {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        .institution-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .institution-header h2 {
            margin: 0 0 10px 0;
            font-size: 28px;
        }

        .institution-header p {
            margin: 0;
            opacity: 0.9;
        }

        .chart-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .students-table-section {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-top: 20px;
        }

        .no-data {
            text-align: center;
            padding: 40px;
            color: #999;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
    </style>
</head>

<body>
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-brand">
                <img src="/test-vocacional/assets/img/logoTUVN.png" alt="Logo TUVN"
                    style="height: 40px; margin-right: 10px; vertical-align: middle;">
                Dashboard DECE
            </div>
            <div class="nav-menu">
                <span><?= htmlspecialchars($_SESSION['user_name']) ?> (DECE)</span>
                <a href="/test-vocacional/logout" class="btn btn-sm btn-outline">Cerrar Sesión</a>
            </div>
        </div>
    </nav>

    <div class="admin-container">
        <aside class="admin-sidebar">
            <ul class="sidebar-menu">
                <li><a href="/test-vocacional/admin/dece" class="active">📊 Dashboard DECE</a></li>
                <li><a href="/test-vocacional/admin/questions">❓ Gestión de Preguntas</a></li>
                <li><a href="/test-vocacional/admin/institutions">🏫 Mi Institución</a></li>
            </ul>
        </aside>

        <main class="admin-main">
            <!-- Institution Header -->
            <div class="institution-header">
                <h2><?= htmlspecialchars($institucion['nombre']) ?></h2>
                <p>Código: <?= htmlspecialchars($institucion['codigo']) ?> | Tipo:
                    <?= htmlspecialchars($institucion['tipo']) ?></p>
            </div>

            <!-- Filters -->
            <div class="filter-section">
                <h3>Filtros</h3>
                <form method="GET" action="/test-vocacional/admin/dece" id="filterForm">
                    <div class="filter-row">
                        <div class="filter-group">
                            <label for="curso">Curso</label>
                            <select name="curso" id="curso">
                                <option value="">Todos los cursos</option>
                                <?php foreach ($courses as $c): ?>
                                    <option value="<?= htmlspecialchars($c) ?>" <?= $curso === $c ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($c) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label for="paralelo">Paralelo</label>
                            <select name="paralelo" id="paralelo" <?= empty($curso) ? 'disabled' : '' ?>>
                                <option value="">Todos los paralelos</option>
                                <?php foreach ($paralelos as $p): ?>
                                    <option value="<?= htmlspecialchars($p) ?>" <?= $paralelo === $p ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($p) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="filter-group">
                            <button type="submit" class="btn btn-primary">Aplicar Filtros</button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">👥</div>
                    <div class="stat-content">
                        <h3><?= $stats['total_students'] ?? 0 ?></h3>
                        <p>Estudiantes Registrados</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">📝</div>
                    <div class="stat-content">
                        <h3><?= $stats['total_tests'] ?? 0 ?></h3>
                        <p>Tests Completados</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">📈</div>
                    <div class="stat-content">
                        <?php
                        $avgScores = $stats['average_scores'] ?? [];
                        $topArea = 'N/A';
                        $topScore = 0;
                        foreach ($avgScores as $area => $score) {
                            if ($score > $topScore) {
                                $topScore = $score;
                                $topArea = ucfirst($area);
                            }
                        }
                        ?>
                        <h3><?= $topArea ?></h3>
                        <p>Área Predominante (<?= round($topScore, 1) ?>%)</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">🎯</div>
                    <div class="stat-content">
                        <?php
                        $completionRate = $stats['total_students'] > 0
                            ? round(($stats['total_tests'] / $stats['total_students']) * 100, 1)
                            : 0;
                        ?>
                        <h3><?= $completionRate ?>%</h3>
                        <p>Tasa de Completación</p>
                    </div>
                </div>
            </div>

            <!-- Charts -->
            <?php
            $avgRaw = $stats['average_scores'] ?? [];
            $avgScores = [
                'ciencias' => isset($avgRaw['ciencias']) ? round((float) $avgRaw['ciencias'], 1) : 0.0,
                'tecnologia' => isset($avgRaw['tecnologia']) ? round((float) $avgRaw['tecnologia'], 1) : 0.0,
                'humanidades' => isset($avgRaw['humanidades']) ? round((float) $avgRaw['humanidades'], 1) : 0.0,
                'artes' => isset($avgRaw['artes']) ? round((float) $avgRaw['artes'], 1) : 0.0,
                'salud' => isset($avgRaw['salud']) ? round((float) $avgRaw['salud'], 1) : 0.0,
                'negocios' => isset($avgRaw['negocios']) ? round((float) $avgRaw['negocios'], 1) : 0.0,
            ];
            ?>
            <div class="chart-grid">
                <div class="chart-container">
                    <h3>Tests Realizados por Mes</h3>
                    <canvas id="monthlyChart"></canvas>
                </div>

                <div class="chart-container">
                    <h3>Promedio por Área Vocacional</h3>
                    <canvas id="areasChart"></canvas>
                </div>
            </div>

            <?php if (!empty($performanceByCourse)): ?>
                <div class="chart-grid">
                    <div class="chart-container">
                        <h3>Rendimiento por Curso</h3>
                        <canvas id="courseChart"></canvas>
                    </div>

                    <?php if (!empty($performanceByParalelo)): ?>
                        <div class="chart-container">
                            <h3>Rendimiento por Paralelo (<?= htmlspecialchars($curso) ?>)</h3>
                            <canvas id="paraleloChart"></canvas>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- Student Results Table -->
            <div class="students-table-section">
                <h3>Resultados de Estudiantes</h3>
                <?php if (!empty($studentResults)): ?>
                    <div class="table-responsive">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Estudiante</th>
                                    <th>Curso</th>
                                    <th>Paralelo</th>
                                    <th>Bachillerato</th>
                                    <th>Fecha Test</th>
                                    <th>Área Principal</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($studentResults as $student): ?>
                                    <?php
                                    $studentName = trim($student['nombre'] . ' ' . $student['apellido']);
                                    $mainArea = 'Pendiente';
                                    $testDate = '—';

                                    if (!empty($student['puntajes_json'])) {
                                        $scores = json_decode($student['puntajes_json'], true);
                                        $maxPct = -INF;
                                        foreach ($scores as $areaKey => $areaData) {
                                            $pct = is_array($areaData) && isset($areaData['porcentaje'])
                                                ? (float) $areaData['porcentaje']
                                                : 0;
                                            if ($pct > $maxPct) {
                                                $maxPct = $pct;
                                                $mainArea = ucfirst($areaKey);
                                            }
                                        }
                                        $testDate = date('d/m/Y', strtotime($student['fecha_test']));
                                    }
                                    ?>
                                    <tr>
                                        <td><?= htmlspecialchars($studentName) ?></td>
                                        <td><?= htmlspecialchars($student['curso'] ?? '—') ?></td>
                                        <td><?= htmlspecialchars($student['paralelo'] ?? '—') ?></td>
                                        <td><?= htmlspecialchars($student['bachillerato'] ?? '—') ?></td>
                                        <td><?= $testDate ?></td>
                                        <td><?= htmlspecialchars($mainArea) ?></td>
                                        <td>
                                            <?php if (!empty($student['test_id'])): ?>
                                                <a href="/test-vocacional/admin/reports/individual?student_id=<?= $student['id'] ?>"
                                                    class="btn btn-sm btn-primary" target="_blank">Ver PDF</a>
                                            <?php else: ?>
                                                <span style="color: #999;">Sin test</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="no-data">
                        <p>No hay estudiantes registrados con los filtros seleccionados.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Action Buttons -->
            <div class="action-buttons">
                <?php
                $reportUrl = '/test-vocacional/admin/dece/reports/institution';
                $exportUrl = '/test-vocacional/admin/dece/export';
                if ($curso) {
                    $reportUrl .= '?curso=' . urlencode($curso);
                    $exportUrl .= '?curso=' . urlencode($curso);
                }
                if ($paralelo) {
                    $reportUrl .= '&paralelo=' . urlencode($paralelo);
                    $exportUrl .= '&paralelo=' . urlencode($paralelo);
                }
                ?>
                <a href="<?= $reportUrl ?>" class="btn btn-primary" target="_blank">
                    📄 Generar Reporte PDF
                </a>
                <a href="<?= $exportUrl ?>" class="btn btn-secondary">
                    📊 Exportar a Excel
                </a>
            </div>
        </main>
    </div>

    <script>
        // Monthly Chart
        const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
        const monthlyData = <?= json_encode($stats['tests_by_month'] ?? []) ?>;

        new Chart(monthlyCtx, {
            type: 'line',
            data: {
                labels: monthlyData.map(item => item.mes),
                datasets: [{
                    label: 'Tests realizados',
                    data: monthlyData.map(item => parseInt(item.cantidad)),
                    borderColor: 'rgba(102, 126, 234, 1)',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
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
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        <?php if (!empty($performanceByCourse)): ?>
            // Course Performance Chart
            const courseCtx = document.getElementById('courseChart').getContext('2d');
            const courseData = <?= json_encode($performanceByCourse) ?>;

            new Chart(courseCtx, {
                type: 'bar',
                data: {
                    labels: courseData.map(item => item.curso),
                    datasets: [
                        {
                            label: 'Ciencias',
                            data: courseData.map(item => parseFloat(item.ciencias || 0)),
                            backgroundColor: 'rgba(255, 99, 132, 0.8)'
                        },
                        {
                            label: 'Tecnología',
                            data: courseData.map(item => parseFloat(item.tecnologia || 0)),
                            backgroundColor: 'rgba(54, 162, 235, 0.8)'
                        },
                        {
                            label: 'Humanidades',
                            data: courseData.map(item => parseFloat(item.humanidades || 0)),
                            backgroundColor: 'rgba(255, 206, 86, 0.8)'
                        },
                        {
                            label: 'Artes',
                            data: courseData.map(item => parseFloat(item.artes || 0)),
                            backgroundColor: 'rgba(75, 192, 192, 0.8)'
                        },
                        {
                            label: 'Salud',
                            data: courseData.map(item => parseFloat(item.salud || 0)),
                            backgroundColor: 'rgba(153, 102, 255, 0.8)'
                        },
                        {
                            label: 'Negocios',
                            data: courseData.map(item => parseFloat(item.negocios || 0)),
                            backgroundColor: 'rgba(255, 159, 64, 0.8)'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100
                        }
                    }
                }
            });
        <?php endif; ?>

        <?php if (!empty($performanceByParalelo)): ?>
            // Paralelo Performance Chart
            const paraleloCtx = document.getElementById('paraleloChart').getContext('2d');
            const paraleloData = <?= json_encode($performanceByParalelo) ?>;

            new Chart(paraleloCtx, {
                type: 'bar',
                data: {
                    labels: paraleloData.map(item => item.paralelo),
                    datasets: [
                        {
                            label: 'Ciencias',
                            data: paraleloData.map(item => parseFloat(item.ciencias || 0)),
                            backgroundColor: 'rgba(255, 99, 132, 0.8)'
                        },
                        {
                            label: 'Tecnología',
                            data: paraleloData.map(item => parseFloat(item.tecnologia || 0)),
                            backgroundColor: 'rgba(54, 162, 235, 0.8)'
                        },
                        {
                            label: 'Humanidades',
                            data: paraleloData.map(item => parseFloat(item.humanidades || 0)),
                            backgroundColor: 'rgba(255, 206, 86, 0.8)'
                        },
                        {
                            label: 'Artes',
                            data: paraleloData.map(item => parseFloat(item.artes || 0)),
                            backgroundColor: 'rgba(75, 192, 192, 0.8)'
                        },
                        {
                            label: 'Salud',
                            data: paraleloData.map(item => parseFloat(item.salud || 0)),
                            backgroundColor: 'rgba(153, 102, 255, 0.8)'
                        },
                        {
                            label: 'Negocios',
                            data: paraleloData.map(item => parseFloat(item.negocios || 0)),
                            backgroundColor: 'rgba(255, 159, 64, 0.8)'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100
                        }
                    }
                }
            });
        <?php endif; ?>

        // Filter handling
        const cursoSelect = document.getElementById('curso');
        const paraleloSelect = document.getElementById('paralelo');

        cursoSelect.addEventListener('change', function () {
            if (this.value) {
                // Enable paralelo select and fetch paralelos
                paraleloSelect.disabled = false;
                fetchParalelos(this.value);
            } else {
                paraleloSelect.disabled = true;
                paraleloSelect.innerHTML = '<option value="">Todos los paralelos</option>';
            }
        });

        function fetchParalelos(curso) {
            fetch(`/test-vocacional/admin/dece/paralelos?curso=${encodeURIComponent(curso)}`)
                .then(response => response.json())
                .then(data => {
                    paraleloSelect.innerHTML = '<option value="">Todos los paralelos</option>';
                    if (data.paralelos && data.paralelos.length > 0) {
                        data.paralelos.forEach(p => {
                            const option = document.createElement('option');
                            option.value = p;
                            option.textContent = p;
                            paraleloSelect.appendChild(option);
                        });
                    }
                })
                .catch(error => console.error('Error fetching paralelos:', error));
        }
    </script>
</body>

</html>