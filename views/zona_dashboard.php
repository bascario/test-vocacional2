<?php
$pageTitle = APP_NAME . ' - Dashboard Zonal';
require 'views/layout/header.php';
?>


<div class="admin-container">
    <?php require 'views/layout/sidebar.php'; ?>

    <main class="admin-main">
        <!-- Zona Header -->
        <div class="zona-header">
            <h2>Zona: <?= htmlspecialchars($zona) ?></h2>
            <p>Instituciones Asignadas: <?= $totalInstitutions ?> | Estudiantes Totales: <?= $totalStudents ?></p>
        </div>

        <!-- Filters -->
        <div class="filters-section">
            <form method="GET" action="/test-vocacional/admin/zona" id="filterForm" class="filters-form">
                <div class="filter-group">
                    <label for="institucion">Institución</label>
                    <select name="institucion" id="institucion" onchange="this.form.submit()">
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
                    <select name="curso" id="curso" <?= empty($institucionId) ? 'disabled' : '' ?>
                        onchange="this.form.submit()">
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
                    <select name="paralelo" id="paralelo" <?= empty($curso) ? 'disabled' : '' ?>
                        onchange="this.form.submit()">
                        <option value="">Todos los paralelos</option>
                        <?php foreach ($paralelos as $p): ?>
                            <option value="<?= htmlspecialchars($p) ?>" <?= $paralelo === $p ? 'selected' : '' ?>>
                                <?= htmlspecialchars($p) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="amie">Código AMIE</label>
                    <input type="text" name="amie" id="amie" value="<?= htmlspecialchars($amie ?? '') ?>"
                        placeholder="AMIE..." onchange="this.form.submit()">
                </div>
                <div class="filter-group actions">
                    <a href="/test-vocacional/admin/zona" class="btn btn-sm btn-secondary">Limpiar</a>
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
                                    <td>
                                        <div><strong>Est:</strong> <?= htmlspecialchars($student['nombre']) ?></div>
                                        <div style="font-size: 0.8em; color: #666;"><strong>Rep:</strong>
                                            <?= htmlspecialchars($student['apellido']) ?></div>
                                    </td>
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
            if (!empty($amie))
                $queryParams[] = 'amie=' . urlencode($amie);

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
            labels: ['Realista', 'Investigador', 'Artístico', 'Social', 'Emprendedor', 'Convencional'],
            datasets: [{
                data: [
                    <?= $riasecScores['Realista'] ?? 0 ?>,
                    <?= $riasecScores['Investigador'] ?? 0 ?>,
                    <?= $riasecScores['Artístico'] ?? 0 ?>,
                    <?= $riasecScores['Social'] ?? 0 ?>,
                    <?= $riasecScores['Emprendedor'] ?? 0 ?>,
                    <?= $riasecScores['Convencional'] ?? 0 ?>
                ],
                backgroundColor: [
                    '<?= getRIASECColor('Realista') ?>',
                    '<?= getRIASECColor('Investigador') ?>',
                    '<?= getRIASECColor('Artístico') ?>',
                    '<?= getRIASECColor('Social') ?>',
                    '<?= getRIASECColor('Emprendedor') ?>',
                    '<?= getRIASECColor('Convencional') ?>'
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
        const categories = ['Realista', 'Investigador', 'Artístico', 'Social', 'Emprendedor', 'Convencional'];
        const labels = instData.map(item => item.institucion_nombre);

        const datasets = categories.map(cat => ({
            label: cat,
            data: instData.map(item => {
                return parseFloat(item[cat] || 0);
            }),
            backgroundColor: '<?= getRIASECColor("'+cat+'") ?>'.replace("'+cat+'", cat)
        }));

        // Fix colors manually since JS injection above is tricky
        const colors = {
            'Realista': '<?= getRIASECColor('Realista') ?>',
            'Investigador': '<?= getRIASECColor('Investigador') ?>',
            'Artístico': '<?= getRIASECColor('Artístico') ?>',
            'Social': '<?= getRIASECColor('Social') ?>',
            'Emprendedor': '<?= getRIASECColor('Emprendedor') ?>',
            'Convencional': '<?= getRIASECColor('Convencional') ?>'
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

    // Initialize Choices.js for searchable filters
    document.querySelectorAll('.filters-form select').forEach(el => {
        new Choices(el, {
            searchEnabled: true,
            itemSelectText: '',
            noResultsText: 'No se encontraron resultados',
            searchPlaceholderValue: 'Buscar...',
            placeholder: true,
            shouldSort: false
        });
    });
</script>
<?php require 'views/layout/footer.php'; ?>