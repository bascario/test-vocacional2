<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= APP_NAME ?> - Dashboard Zonal</title>
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

        .zona-header {
            background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
            color: white;
            padding: 30px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .zona-header h2 {
            margin: 0 0 10px 0;
            font-size: 28px;
        }

        .zona-header p {
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
                Dashboard Zonal
            </div>
            <div class="nav-menu">
                <span><?= htmlspecialchars($_SESSION['user_name']) ?> (Zonal)</span>
                <a href="/test-vocacional/logout" class="btn btn-sm btn-outline">Cerrar Sesión</a>
            </div>
        </div>
    </nav>

    <div class="admin-container">
        <aside class="admin-sidebar">
            <ul class="sidebar-menu">
                <li><a href="/test-vocacional/admin/zona" class="active">📊 Dashboard Zonal</a></li>
            </ul>
        </aside>

        <main class="admin-main">
            <!-- Zona Header -->
            <div class="zona-header">
                <h2>Zona: <?= htmlspecialchars($zona) ?></h2>
                <p>Instituciones Asignadas: <?= $totalInstitutions ?> | Estudiantes Totales: <?= $totalStudents ?></p>
            </div>

            <!-- Filters -->
            <div class="filter-section">
                <h3>Filtros</h3>
                <form method="GET" action="/test-vocacional/admin/zona" id="filterForm">
                    <div class="filter-row">
                        <div class="filter-group">
                            <label for="institucion">Institución</label>
                            <select name="institucion" id="institucion">
                                <option value="">Todas las instituciones</option>
                                <?php foreach ($institutions as $inst): ?>
                                    <option value="<?= htmlspecialchars($inst['id']) ?>" <?= ($institucionId == $inst['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($inst['nombre']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label for="curso">Curso</label>
                            <select name="curso" id="curso" <?= empty($institucionId) ? 'disabled' : '' ?>>
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
                        // Use helper for RIASEC label
                        require_once 'utils/riasec_helpers.php';
                        $avgScores = $stats['average_scores'] ?? [];
                        $topArea = 'N/A';
                        $topScore = 0;
                        $topCategory = '';
                        foreach ($avgScores as $area => $score) {
                            if ($score > $topScore) {
                                $topScore = $score;
                                $topCategory = $area;
                            }
                        }
                        if ($topCategory) {
                            // This handles both old keys (ciencias) and new keys (Investigador)
                            $topArea = getCategoryLabel($topCategory);
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
            // Prepare data for RIASEC charts
            $avgRaw = $stats['average_scores'] ?? [];
            // Map raw scores to RIASEC categories securely
            $riasecScores = convertToRIASEC($avgRaw);
            ?>
            <div class="chart-grid">
                <div class="chart-container">
                    <h3>Tests Realizados por Mes</h3>
                    <canvas id="monthlyChart"></canvas>
                </div>

                <div class="chart-container">
                    <h3>Promedio por Área Vocacional (RIASEC)</h3>
                    <canvas id="areasChart"></canvas>
                </div>
            </div>

            <?php if (!empty($performanceByInstitution)): ?>
                <div class="chart-container" style="margin-bottom: 20px;">
                    <h3>Rendimiento por Institución</h3>
                    <canvas id="institutionChart"></canvas>
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
                                    <th>Institución</th>
                                    <th>Curso</th>
                                    <th>Paralelo</th>
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
                                        // Use helper to determine dominant RIASEC type
                                        $mainCategory = getDominantRIASEC($scores);
                                        if ($mainCategory) {
                                            $mainArea = $mainCategory; // Already a label like 'REALISTA'
                                        }
                                        $testDate = date('d/m/Y', strtotime($student['fecha_test']));
                                    }
                                    ?>
                                    <tr>
                                        <td><?= htmlspecialchars($studentName) ?></td>
                                        <td><?= htmlspecialchars($student['institucion_nombre'] ?? '—') ?></td>
                                        <td><?= htmlspecialchars($student['curso'] ?? '—') ?></td>
                                        <td><?= htmlspecialchars($student['paralelo'] ?? '—') ?></td>
                                        <td><?= $testDate ?></td>
                                        <td><strong><?= htmlspecialchars($mainArea) ?></strong></td>
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
                $reportUrl = '/test-vocacional/admin/zona/reports/zona';
                $exportUrl = '/test-vocacional/admin/zona/export';
                $queryParams = [];
                if ($institucionId)
                    $queryParams[] = 'institucion=' . urlencode($institucionId);
                if ($curso)
                    $queryParams[] = 'curso=' . urlencode($curso);
                if ($paralelo)
                    $queryParams[] = 'paralelo=' . urlencode($paralelo);

                if (!empty($queryParams)) {
                    $queryString = '?' . implode('&', $queryParams);
                    $reportUrl .= $queryString;
                    $exportUrl .= $queryString;
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
                labels: ['REALISTA', 'INVESTIGADORA', 'ARTÍSTICA', 'SOCIAL', 'EMPRENDEDORA', 'CONVENCIONAL'],
                datasets: [{
                    data: [
                        <?= $riasecScores['REALISTA'] ?? 0 ?>,
                        <?= $riasecScores['INVESTIGADORA'] ?? 0 ?>,
                        <?= $riasecScores['ARTÍSTICA'] ?? 0 ?>,
                        <?= $riasecScores['SOCIAL'] ?? 0 ?>,
                        <?= $riasecScores['EMPRENDEDORA'] ?? 0 ?>,
                        <?= $riasecScores['CONVENCIONAL'] ?? 0 ?>
                    ],
                    backgroundColor: [
                        '<?= getRIASECColor('REALISTA') ?>',
                        '<?= getRIASECColor('INVESTIGADORA') ?>',
                        '<?= getRIASECColor('ARTÍSTICA') ?>',
                        '<?= getRIASECColor('SOCIAL') ?>',
                        '<?= getRIASECColor('EMPRENDEDORA') ?>',
                        '<?= getRIASECColor('CONVENCIONAL') ?>'
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

        <?php if (!empty($performanceByInstitution)): ?>
            // Institution Performance Chart
            const instCtx = document.getElementById('institutionChart').getContext('2d');
            const instData = <?= json_encode($performanceByInstitution) ?>;

            // Transform data for chart
            // We need datasets for each RIASEC category
            const categories = ['REALISTA', 'INVESTIGADORA', 'ARTÍSTICA', 'SOCIAL', 'EMPRENDEDORA'];
            const labels = instData.map(item => item.nombre);

            const datasets = categories.map(cat => ({
                label: cat,
                data: instData.map(item => {
                    // Item keys might be old (ciencias) or new (Investigador). 
                    // Need to map somewhat manually or ensure backend returns consistent keys.
                    // Assuming backend returns consistent keys matching what convertToRIASEC expects or direct map.
                    // For simplicity, let's try to find key.
                    // Actually, let's handle this in PHP above to be safe next time. 
                    // For now, let's assume keys are standard.
                    // Let's rely on standard keys:
                    // If backend sends 'ciencias', we need to display it as INVESTIGADORA.

                    // Quick map based on what we see in dece_dashboard (ciencias, tecnologia, etc)
                    if (cat === 'INVESTIGADORA') return item.ciencias || item.Investigador || 0;
                    if (cat === 'REALISTA') return item.tecnologia || item.Realista || 0;
                    if (cat === 'ARTÍSTICA') return item.artes || item.Artístico || 0;
                    if (cat === 'SOCIAL') return item.humanidades || item.Social || 0;
                    if (cat === 'EMPRENDEDORA') return item.negocios || item.Emprendedor || 0;
                    return 0;
                }),
                backgroundColor: '<?= getRIASECColor("'+cat+'") ?>'.replace("'+cat+'", cat) // Simple hack for Color injection
            }));

            // Fix colors manually since JS injection above is tricky
            const colors = {
                'REALISTA': '<?= getRIASECColor('REALISTA') ?>',
                'INVESTIGADORA': '<?= getRIASECColor('INVESTIGADORA') ?>',
                'ARTÍSTICA': '<?= getRIASECColor('ARTÍSTICA') ?>',
                'SOCIAL': '<?= getRIASECColor('SOCIAL') ?>',
                'EMPRENDEDORA': '<?= getRIASECColor('EMPRENDEDORA') ?>',
                'CONVENCIONAL': '<?= getRIASECColor('CONVENCIONAL') ?>'
            };

            datasets.forEach(ds => {
                ds.backgroundColor = colors[ds.label] || '#ccc';
            });

            new Chart(instCtx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: datasets
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

        // Dynamic Filtering
        const institucionSelect = document.getElementById('institucion');
        const cursoSelect = document.getElementById('curso');
        const paraleloSelect = document.getElementById('paralelo');

        institucionSelect.addEventListener('change', function () {
            const instId = this.value;
            cursoSelect.innerHTML = '<option value="">Cargando...</option>';
            paraleloSelect.innerHTML = '<option value="">Todos los paralelos</option>';
            paraleloSelect.disabled = true;

            if (!instId) {
                cursoSelect.innerHTML = '<option value="">Todos los cursos</option>';
                cursoSelect.disabled = true;
                return;
            }

            cursoSelect.disabled = false;

            // Fetch courses
            fetch(`/test-vocacional/admin/zona/courses?institucion=${instId}`)
                .then(res => res.json())
                .then(data => {
                    cursoSelect.innerHTML = '<option value="">Todos los cursos</option>';
                    if (data.courses) {
                        data.courses.forEach(c => {
                            const opt = document.createElement('option');
                            opt.value = c;
                            opt.textContent = c;
                            cursoSelect.appendChild(opt);
                        });
                    }
                })
                .catch(err => {
                    console.error(err);
                    cursoSelect.innerHTML = '<option value="">Error al cargar</option>';
                });
        });

        cursoSelect.addEventListener('change', function () {
            const curso = this.value;
            const instId = institucionSelect.value;

            paraleloSelect.innerHTML = '<option value="">Cargando...</option>';

            if (!curso) {
                paraleloSelect.innerHTML = '<option value="">Todos los paralelos</option>';
                paraleloSelect.disabled = true;
                return;
            }

            paraleloSelect.disabled = false;

            // Fetch paralelos
            fetch(`/test-vocacional/admin/zona/paralelos?institucion=${instId}&curso=${encodeURIComponent(curso)}`)
                .then(res => res.json())
                .then(data => {
                    paraleloSelect.innerHTML = '<option value="">Todos los paralelos</option>';
                    if (data.paralelos) {
                        data.paralelos.forEach(p => {
                            const opt = document.createElement('option');
                            opt.value = p;
                            opt.textContent = p;
                            paraleloSelect.appendChild(opt);
                        });
                    }
                })
                .catch(err => {
                    console.error(err);
                    paraleloSelect.innerHTML = '<option value="">Error al cargar</option>';
                });
        });
    </script>
</body>

</html>