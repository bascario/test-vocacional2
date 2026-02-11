<?php
$pageTitle = APP_NAME . ' - Dashboard DECE';
require 'views/layout/header.php';
?>


<div class="admin-container">
    <?php require 'views/layout/sidebar.php'; ?>

    <main class="admin-main">
        <!-- Institution Header -->
        <div class="institution-header">
            <h2><?= htmlspecialchars($institucion['nombre']) ?></h2>
            <p>Código: <?= htmlspecialchars($institucion['codigo']) ?> | Tipo:
                <?= htmlspecialchars($institucion['tipo']) ?>
            </p>
        </div>

        <!-- Filters -->
        <div class="filters-section">
            <form method="GET" action="/test-vocacional/admin/dece" id="filterForm" class="filters-form">
                <div class="filter-group">
                    <label for="curso">Curso</label>
                    <select name="curso" id="curso" onchange="this.form.submit()">
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
                    <label for="amie">AMIE</label>
                    <input type="text" name="amie" id="amie" value="<?= htmlspecialchars($amie ?? '') ?>"
                        placeholder="AMIE..." onchange="this.form.submit()">
                </div>
                <div class="filter-group actions">
                    <a href="/test-vocacional/admin/dece" class="btn btn-sm btn-secondary">Limpiar</a>
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
        $avgRaw = $stats['average_scores'] ?? [];
        $avgScores = [
            'Investigador' => isset($avgRaw['Investigador']) ? round((float) $avgRaw['Investigador'], 1) : 0.0,
            'Realista' => isset($avgRaw['Realista']) ? round((float) $avgRaw['Realista'], 1) : 0.0,
            'Social' => isset($avgRaw['Social']) ? round((float) $avgRaw['Social'], 1) : 0.0,
            'Artístico' => isset($avgRaw['Artístico']) ? round((float) $avgRaw['Artístico'], 1) : 0.0,
            'Emprendedor' => isset($avgRaw['Emprendedor']) ? round((float) $avgRaw['Emprendedor'], 1) : 0.0,
            'Convencional' => isset($avgRaw['Convencional']) ? round((float) $avgRaw['Convencional'], 1) : 0.0,
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
                                <th>Fecha Test</th>
                                <th>Área Principal</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($studentResults as $student): ?>
                                <?php
                                require_once 'utils/riasec_helpers.php';
                                $studentName = trim($student['nombre'] . ' ' . $student['apellido']);
                                $mainArea = 'Pendiente';
                                $testDate = '—';

                                if (!empty($student['puntajes_json'])) {
                                    $scores = json_decode($student['puntajes_json'], true);
                                    $maxPct = -INF;
                                    $mainCategory = '';
                                    foreach ($scores as $areaKey => $areaData) {
                                        $pct = is_array($areaData) && isset($areaData['porcentaje'])
                                            ? (float) $areaData['porcentaje']
                                            : 0;
                                        if ($pct > $maxPct) {
                                            $maxPct = $pct;
                                            $mainCategory = $areaKey;
                                        }
                                    }
                                    if ($mainCategory) {
                                        $mainArea = getCategoryLabel($mainCategory);
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
            if (!empty($amie)) {
                $reportUrl .= '&amie=' . urlencode($amie);
                $exportUrl .= '&amie=' . urlencode($amie);
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

    // Areas Chart with RIASEC labels
    const areasCtx = document.getElementById('areasChart').getContext('2d');
    new Chart(areasCtx, {
        type: 'doughnut',
        data: {
            labels: ['Investigador', 'Realista', 'Artístico', 'Social', 'Emprendedor', 'Convencional'],
            datasets: [{
                data: [
                    <?= $avgScores['Investigador'] ?>,
                    <?= $avgScores['Realista'] ?>,
                    <?= $avgScores['Artístico'] ?>,
                    <?= $avgScores['Social'] ?>,
                    <?= $avgScores['Emprendedor'] ?>,
                    <?= $avgScores['Convencional'] ?>
                ],
                backgroundColor: [
                    'rgba(72, 187, 120, 0.8)',  // Investigador
                    'rgba(102, 126, 234, 0.8)', // Realista
                    'rgba(237, 137, 54, 0.8)',  // Artístico
                    'rgba(245, 101, 101, 0.8)', // Social
                    'rgba(159, 122, 234, 0.8)',  // Emprendedor
                    'rgba(56, 178, 172, 0.8)'   // Convencional
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
                        label: 'INVESTIGADOR',
                        data: courseData.map(item => parseFloat(item.Investigador || 0)),
                        backgroundColor: 'rgba(72, 187, 120, 0.8)'
                    },
                    {
                        label: 'REALISTA',
                        data: courseData.map(item => parseFloat(item.Realista || 0)),
                        backgroundColor: 'rgba(102, 126, 234, 0.8)'
                    },
                    {
                        label: 'SOCIAL',
                        data: courseData.map(item => parseFloat(item.Social || 0)),
                        backgroundColor: 'rgba(245, 101, 101, 0.8)'
                    },
                    {
                        label: 'ARTÍSTICO',
                        data: courseData.map(item => parseFloat(item.Artístico || 0)),
                        backgroundColor: 'rgba(237, 137, 54, 0.8)'
                    },
                    {
                        label: 'EMPRENDEDOR',
                        data: courseData.map(item => parseFloat(item.Emprendedor || 0)),
                        backgroundColor: 'rgba(159, 122, 234, 0.8)'
                    },
                    {
                        label: 'CONVENCIONAL',
                        data: courseData.map(item => parseFloat(item.Convencional || 0)),
                        backgroundColor: 'rgba(56, 178, 172, 0.8)'
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
                        label: 'Investigador',
                        data: paraleloData.map(item => parseFloat(item.Investigador || 0)),
                        backgroundColor: 'rgba(72, 187, 120, 0.8)'
                    },
                    {
                        label: 'Realista',
                        data: paraleloData.map(item => parseFloat(item.Realista || 0)),
                        backgroundColor: 'rgba(102, 126, 234, 0.8)'
                    },
                    {
                        label: 'Social',
                        data: paraleloData.map(item => parseFloat(item.Social || 0)),
                        backgroundColor: 'rgba(245, 101, 101, 0.8)'
                    },
                    {
                        label: 'Artístico',
                        data: paraleloData.map(item => parseFloat(item.Artístico || 0)),
                        backgroundColor: 'rgba(237, 137, 54, 0.8)'
                    },
                    {
                        label: 'Emprendedor',
                        data: paraleloData.map(item => parseFloat(item.Emprendedor || 0)),
                        backgroundColor: 'rgba(159, 122, 234, 0.8)'
                    },
                    {
                        label: 'Convencional',
                        data: paraleloData.map(item => parseFloat(item.Convencional || 0)),
                        backgroundColor: 'rgba(56, 178, 172, 0.8)'
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