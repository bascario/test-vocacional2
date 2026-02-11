<?php
$pageTitle = APP_NAME . ' - Panel Administrativo';
require 'views/layout/header.php';
?>


<div class="admin-container">
    <?php require 'views/layout/sidebar.php'; ?>

    <main class="admin-main">
        <div class="admin-header">
            <h1>Dashboard Estadístico</h1>
            <p>Resumen general del sistema de test vocacional</p>
        </div>

        <!-- Filters Section -->
        <form method="GET" action="/test-vocacional/admin" class="filters-form">
            <div class="filter-group">
                <label for="zona">Zona</label>
                <select name="zona" id="zona" class="form-control" onchange="this.form.submit()">
                    <option value="">Todas las Zonas</option>
                    <?php foreach ($zonas as $z): ?>
                        <option value="<?= htmlspecialchars($z) ?>" <?= ($filters['zona'] == $z) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($z) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <?php if (!empty($filters['zona'])): ?>
                <div class="filter-group">
                    <label for="distrito">Distrito</label>
                    <select name="distrito" id="distrito" class="form-control" onchange="this.form.submit()">
                        <option value="">Todos los Distritos</option>
                        <?php if (!empty($distritos)): ?>
                            <?php foreach ($distritos as $d): ?>
                                <option value="<?= htmlspecialchars($d) ?>" <?= ($filters['distrito'] == $d) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($d) ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
            <?php endif; ?>

            <?php if (!empty($filters['zona'])): ?>
                <div class="filter-group">
                    <label for="institucion_id">Institución</label>
                    <select name="institucion_id" id="institucion_id" class="form-control" onchange="this.form.submit()">
                        <option value="">Todas las Instituciones</option>
                        <?php if (!empty($instituciones)): ?>
                            <?php foreach ($instituciones as $i): ?>
                                <option value="<?= $i['id'] ?>" <?= ($filters['institucion_id'] == $i['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($i['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
            <?php endif; ?>

            <div class="filter-group">
                <label for="amie">Código AMIE</label>
                <input type="text" name="amie" id="amie" class="form-control" placeholder="Buscar por AMIE..."
                    value="<?= htmlspecialchars($filters['amie'] ?? '') ?>" onchange="this.form.submit()">
            </div>

            <div class="filter-group actions">
                <a href="/test-vocacional/admin" class="btn btn-sm btn-secondary">Limpiar</a>
            </div>
        </form>
        <!-- </div>-->
        <div> <br><br> </div>

        <!-- Statistics Cards (Modified to remove student list count if not available/relevant, or assume it's filtered if method updated) -->
        <!-- For now, we keep totals from stats which are filtered -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">👥</div>
                <div class="stat-content">
                    <h3><?= $stats['total_tests'] ?></h3>
                    <p>Tests Realizados</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">🎯</div>
                <div class="stat-content">
                    <h3><?= isset($stats['average_scores']['Realista']) ? round($stats['average_scores']['Realista'], 1) : 0 ?>%
                    </h3>
                    <p>Promedio Realista</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">🔬</div>
                <div class="stat-content">
                    <h3><?= isset($stats['average_scores']['Investigador']) ? round($stats['average_scores']['Investigador'], 1) : 0 ?>%
                    </h3>
                    <p>Promedio Investigador</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">🎨</div>
                <div class="stat-content">
                    <h3><?= isset($stats['average_scores']['Artístico']) ? round($stats['average_scores']['Artístico'], 1) : 0 ?>%
                    </h3>
                    <p>Promedio Artístico</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">🤝</div>
                <div class="stat-content">
                    <h3><?= isset($stats['average_scores']['Social']) ? round($stats['average_scores']['Social'], 1) : 0 ?>%
                    </h3>
                    <p>Promedio Social</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">💼</div>
                <div class="stat-content">
                    <h3><?= isset($stats['average_scores']['Emprendedor']) ? round($stats['average_scores']['Emprendedor'], 1) : 0 ?>%
                    </h3>
                    <p>Promedio Emprendedor</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">📁</div>
                <div class="stat-content">
                    <h3><?= isset($stats['average_scores']['Convencional']) ? round($stats['average_scores']['Convencional'], 1) : 0 ?>%
                    </h3>
                    <p>Promedio Convencional</p>
                </div>
            </div>
        </div>

        <!-- Charts and CINE Table -->
        <?php
        // Ensure average_scores has numeric values
        $avgRaw = $stats['average_scores'] ?? [];
        $avgScores = [
            'Realista' => isset($avgRaw['Realista']) ? round((float) $avgRaw['Realista'], 1) : 0.0,
            'Investigador' => isset($avgRaw['Investigador']) ? round((float) $avgRaw['Investigador'], 1) : 0.0,
            'Artístico' => isset($avgRaw['Artístico']) ? round((float) $avgRaw['Artístico'], 1) : 0.0,
            'Social' => isset($avgRaw['Social']) ? round((float) $avgRaw['Social'], 1) : 0.0,
            'Emprendedor' => isset($avgRaw['Emprendedor']) ? round((float) $avgRaw['Emprendedor'], 1) : 0.0,
            'Convencional' => isset($avgRaw['Convencional']) ? round((float) $avgRaw['Convencional'], 1) : 0.0,
        ];
        ?>

        <div class="dashboard-grid">
            <!-- Radar Chart -->
            <div class="chart-card">
                <h3>Perfil Vocacional Promedio</h3>
                <div style="height: 350px; position: relative;">
                    <canvas id="radarChart"></canvas>
                </div>
            </div>

            <!-- CINE Table Summary -->
            <div class="chart-card" style="overflow-y: auto; max-height: 440px;">
                <h3>Clasificación CINE (Referencia)</h3>
                <table class="admin-table" style="font-size: 0.85em;">

                    <thead>
                        <tr>
                            <th>Tipo Vocacional</th>
                            <th>Definición</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>Realista</strong></td>
                            <td>Personas que prefieren trabajar con objetos, máquinas, herramientas, plantas o animales.
                                Prefieren actividades manuales, mecánicas y técnicas.</td>
                        </tr>
                        <tr>
                            <td><strong>Investigador</strong></td>
                            <td>Personas que prefieren observar, aprender, investigar, analizar, evaluar o resolver
                                problemas. Valoran la ciencia, la investigación y el aprendizaje.</td>
                        </tr>
                        <tr>
                            <td><strong>Artístico</strong></td>
                            <td>Personas que tienen habilidades artísticas, innovadoras o intuitivas y les gusta
                                trabajar en situaciones no estructuradas usando su imaginación y creatividad.</td>
                        </tr>
                        <tr>
                            <td><strong>Social</strong></td>
                            <td>Personas que les gusta trabajar con gente para informar, iluminar, ayudar, entrenar,
                                sanar o curar. Tienen habilidades con las palabras y la enseñanza.</td>
                        </tr>
                        <tr>
                            <td><strong>Emprendedor</strong></td>
                            <td>Personas que les gusta trabajar con gente, influenciar, persuadir, realizar, liderar o
                                administrar metas organizacionales o ganancias económicas.</td>
                        </tr>
                        <tr>
                            <td><strong>Convencional</strong></td>
                            <td>Personas que les gusta trabajar con datos, tienen habilidades administrativas, numéricas
                                o de oficina, realizan tareas en detalle y siguen instrucciones u órdenes.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Recent Tests -->
        <div class="recent-tests-section">
            <h3>Últimos Tests Realizados</h3>
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Estudiante</th>
                            <th>Curso</th>
                            <th>Tiempo</th>
                            <th>Área Principal</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentTests as $test):
                            $scores = json_decode($test['puntajes_json'], true);

                            // Include RIASEC helper if not already included
                            require_once 'utils/riasec_helpers.php';

                            // Determine main area
                            $mainArea = 'N/A';
                            if (is_array($scores) && !empty($scores)) {
                                $maxPct = -INF;
                                $mainCategory = '';
                                foreach ($scores as $areaKey => $areaData) {
                                    $pct = 0;
                                    if (is_array($areaData) && isset($areaData['porcentaje'])) {
                                        $pct = (float) $areaData['porcentaje'];
                                    } elseif (is_numeric($areaData)) {
                                        $pct = (float) $areaData;
                                    }
                                    // Standardize key for label
                                    if ($pct > $maxPct) {
                                        $maxPct = $pct;
                                        $mainCategory = $areaKey;
                                    }
                                }
                                if ($mainCategory) {
                                    $mainArea = getCategoryLabel($mainCategory);
                                }
                            }

                            $studentName = trim((string) ($test['nombre'] ?? '') . ' ' . (string) ($test['apellido'] ?? ''));
                            if ($studentName === '')
                                $studentName = '—';
                            $curso = (string) ($test['curso'] ?? '—');
                            ?>
                            <tr>
                                <td><?= date('d/m/Y H:i', strtotime($test['fecha_test'])) ?></td>
                                <td><?= htmlspecialchars($studentName) ?></td>
                                <td><?= htmlspecialchars($curso) ?></td>
                                <td>
                                    <?php
                                    $dur = $test['duration_seconds'] ?? null;
                                    if ($dur === null || $dur === '' || $dur == 0) {
                                        echo '—';
                                    } else {
                                        $h = floor($dur / 3600);
                                        $m = floor(($dur % 3600) / 60);
                                        $s = $dur % 60;
                                        if ($h > 0) {
                                            echo sprintf('%d:%02d:%02d', $h, $m, $s);
                                        } else {
                                            echo sprintf('%02d:%02d', $m, $s);
                                        }
                                    }
                                    ?>
                                </td>
                                <td><strong><?= htmlspecialchars($mainArea) ?></strong></td>
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
                <a href="/test-vocacional/admin/reports/group?format=pdf&zona=<?= urlencode($filters['zona'] ?? '') ?>&distrito=<?= urlencode($filters['distrito'] ?? '') ?>&institucion_id=<?= $filters['institucion_id'] ?>"
                    class="btn btn-primary">
                    📄 Generar Reporte Grupal PDF
                </a>
                <a href="/test-vocacional/admin/reports/group?format=excel&zona=<?= urlencode($filters['zona'] ?? '') ?>&distrito=<?= urlencode($filters['distrito'] ?? '') ?>&institucion_id=<?= $filters['institucion_id'] ?>"
                    class="btn btn-secondary">
                    📊 Generar Reporte Grupal Excel
                </a>
            </div>
        </div>
    </main>
</div>

<script>
    // Radar Chart Configuration
    const radarCtx = document.getElementById('radarChart').getContext('2d');
    new Chart(radarCtx, {
        type: 'radar',
        data: {
            labels: ['Realista', 'Investigador', 'Artístico', 'Social', 'Emprendedor', 'Convencional'],
            datasets: [{
                label: 'Promedio del Grupo',
                data: [
                    <?= $avgScores['Realista'] ?>,
                    <?= $avgScores['Investigador'] ?>,
                    <?= $avgScores['Artístico'] ?>,
                    <?= $avgScores['Social'] ?>,
                    <?= $avgScores['Emprendedor'] ?>,
                    <?= $avgScores['Convencional'] ?>
                ],
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                borderColor: 'rgba(54, 162, 235, 1)',
                pointBackgroundColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                r: {
                    angleLines: { display: true },
                    suggestedMin: 0,
                    suggestedMax: 100,
                    ticks: { stepSize: 20 }
                }
            }
        }
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
</body>

</html>