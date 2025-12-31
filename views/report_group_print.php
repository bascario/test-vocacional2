<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte Grupal Vocacional</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap');

        :root {
            --primary-color: #2c3e50;
            --secondary-color: #34495e;
            --accent-color: #3498db;
            --success-color: #27ae60;
            --warning-color: #f39c12;
            --text-color: #2c3e50;
            --light-bg: #f8f9fa;
            --border-color: #dee2e6;
        }

        body {
            font-family: 'Roboto', sans-serif;
            color: var(--text-color);
            line-height: 1.5;
            margin: 0;
            padding: 0;
            background: white;
            font-size: 11pt;
            /* Slightly smaller for lists */
        }

        .report-page {
            max-width: 210mm;
            /* A4 width */
            margin: 0 auto;
            background: white;
            padding: 15mm;
            min-height: 297mm;
            position: relative;
            box-sizing: border-box;
        }

        @media only screen and (max-width: 210mm) {
            .report-page {
                padding: 10mm;
            }

            .summary-section {
                flex-direction: column;
            }

            .chart-container {
                height: 250px;
                width: 100%;
            }
        }

        @media print {
            body {
                background: white;
                margin: 0;
                padding: 0;
            }

            .report-page {
                width: 100%;
                margin: 0;
                box-shadow: none;
                border: none;
                page-break-after: always;
            }

            .no-print {
                display: none !important;
            }

            /* Force background colors */
            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            @page {
                size: portrait;
                margin: 15mm;
            }

            .print-footer {
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                height: 10mm;
                display: block !important;
                text-align: center;
                font-size: 10px;
                color: #666;
            }

            .print-footer::after {
                content: "Página " counter(page);
            }

            .qr-code-print {
                position: fixed;
                bottom: 10mm;
                right: 15mm;
                display: block !important;
            }

            .qr-code-print img {
                width: 60px;
                height: 60px;
            }
        }

        .qr-code-print {
            display: none;
        }

        .print-footer {
            text-align: center;
            font-size: 10px;
            color: #666;
            margin-top: 10px;
        }

        .qr-code-print {
            text-align: center;
            margin: 10px auto;
            padding: 5px;
            background: #fff;
            border: 1px dashed #00aeef;
            width: fit-content;
        }

        .qr-code-print img {
            width: 80px;
            height: 80px;
        }

        .qr-label {
            font-size: 8px;
            color: #00aeef;
            font-weight: bold;
            margin-top: 2px;
        }

        /* Header and report title bar */
        .header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 6px;
        }

        .report-title-bar {
            background: linear-gradient(90deg, #00aeea, #8fd8ff);
            color: #fff;
            text-align: center;
            padding: 10px 12px;
            border-radius: 2px;
            margin-bottom: 12px;
        }

        .report-title-bar h1 {
            margin: 0;
            font-size: 20px;
            letter-spacing: 1px;
            font-weight: 700;
        }

        .header-logo img {
            height: 64px;
        }

        .report-subtitle {
            background: #dff6ff;
            padding: 6px 10px;
            border-left: 6px solid #00aeea;
            margin-bottom: 14px;
            font-weight: 600;
            color: #002b36;
        }

        /* Info Grid */
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-bottom: 25px;
            background: var(--light-bg);
            padding: 15px;
            border-radius: 5px;
            border: 1px solid var(--border-color);
        }

        .info-item strong {
            display: block;
            font-size: 9pt;
            color: var(--secondary-color);
            text-transform: uppercase;
            margin-bottom: 2px;
        }

        .info-item span {
            font-size: 11pt;
            font-weight: 500;
        }

        /* Group Summary Section - two column layout like sample */
        .group-grid {
            display: grid;
            grid-template-columns: 42% 58%;
            gap: 18px;
            margin-bottom: 18px;
            page-break-inside: avoid;
        }

        .left-cards {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .small-table {
            background: #e9f9ff;
            border: 1px solid #cfeefc;
            padding: 8px;
            border-radius: 4px;
        }

        .small-table h4 {
            margin: 0 0 6px 0;
            background: #cceeff;
            padding: 6px 8px;
            font-size: 12px;
            border: 1px solid #00aeea;
            text-transform: uppercase;
        }

        .small-table table {
            width: 100%;
            border-collapse: collapse;
        }

        .small-table td,
        .nsmall-table th,
        .small-table th {
            padding: 6px;
            border-bottom: 1px solid #d7f0fa;
            font-size: 11pt;
        }

        .small-table .label {
            color: #004b59;
            font-weight: 600;
            width: 65%;
        }

        .small-table .val {
            text-align: right;
            font-weight: 700;
            color: #003644;
        }

        .right-panel {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .results-chart-box {
            background: #fff;
            padding: 10px;
            border: 1px solid #cfeefc;
            border-radius: 4px;
        }

        .recommendations {
            background: #fff;
            border: 1px solid #e6eef6;
            padding: 12px;
            border-radius: 4px;
            min-height: 140px;
        }

        /* Students Table */
        .students-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            font-size: 10pt;
        }

        .students-table th,
        .students-table td {
            padding: 8px 10px;
            border: 1px solid var(--border-color);
            text-align: left;
        }

        .students-table th {
            background-color: var(--primary-color);
            color: white;
            font-weight: 500;
        }

        .students-table tr:nth-child(even) {
            background-color: #f8f9fa;
        }

        .area-pill {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.85em;
            font-weight: 500;
            color: white;
            background-color: var(--secondary-color);
        }

        /* Footer */
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 9pt;
            color: #7f8c8d;
            border-top: 1px solid var(--border-color);
            padding-top: 10px;
        }

        @media print {
            body {
                background: white;
                -webkit-print-color-adjust: exact;
            }

            .report-page {
                box-shadow: none;
                margin: 0;
                width: 100%;
                max-width: none;
                height: auto;
                min-height: 0;
            }

            .no-print {
                display: none !important;
            }

            .page-break {
                page-break-before: always;
            }
        }

        .print-controls {
            position: fixed;
            top: 20px;
            right: 20px;
            background: white;
            padding: 10px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            z-index: 1000;
        }

        .btn {
            background: var(--accent-color);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            text-decoration: none;
            display: inline-block;
        }

        .btn:hover {
            opacity: 0.9;
        }

        /* DECE Footer Styles */
        .section-title {
            background: #cceeff;
            color: #000;
            font-weight: bold;
            text-transform: uppercase;
            padding: 5px 10px;
            margin: 20px 0 10px;
            text-align: center;
            font-size: 14px;
            border: 1px solid #00aeef;
        }

        .dece-box {
            background-color: #ddd;
            padding: 15px;
            text-align: center;
            font-weight: bold;
            margin-top: 10px;
            border: 1px solid #999;
        }

        /* Branding Footer */
        .branding-footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 5px;
        }

        @media print {
            .branding-footer {
                position: fixed;
                bottom: 10px;
                left: 0;
                right: 0;
            }
        }
    </style>
</head>

<body>
    <div class="print-controls no-print">
        <button onclick="window.print()" class="btn">🖨️ Imprimir / Guardar PDF</button>
        <a href="/test-vocacional/admin" class="btn" style="background: #95a5a6; margin-left: 10px;">Volver al
            Dashboard</a>
    </div>

    <div class="report-page">
        <!-- Header and Title -->
        <div class="header">
            <div class="header-logo" style="display:flex; align-items:center; gap:8px;">
                <img src="/test-vocacional/assets/img/mined.png" alt="MINED" style="height:48px;">
                <img src="/test-vocacional/assets/img/logoTUVN.png" alt="Logo TUVN" style="height:64px;">
            </div>
            <div style="flex:1; text-align:center;">
                <div class="report-title-bar">
                    <h1>RESULTADOS</h1>
                </div>
                <div class="report-subtitle">I. DATOS PERSONALES</div>
            </div>
            <div style="width:120px; text-align:right; font-size:12px; color:#004b59;">
                <?= date('d/m/Y') ?><br>
                <?= htmlspecialchars($filterInfo['institution'] ?? '') ?>
            </div>
        </div>

        <!-- Filter Info (compact) -->
        <div class="info-grid">
            <div class="info-item">
                <strong>Grupo</strong>
                <span><?= htmlspecialchars($filterInfo['group_name'] ?? ($filterInfo['institution'] ?? 'Todas')) ?></span>
            </div>
            <div class="info-item">
                <strong>Total Evaluados</strong>
                <span><?= count($results) ?> Estudiantes</span>
            </div>
        </div>

        <!-- Main grid: left small tables and right radar + recommendations -->
        <div class="group-grid">
            <div class="left-cards">
                <div class="small-table">
                    <h4>PUNTAJE</h4>
                    <table>
                        <tbody>
                            <tr>
                                <td class="label">REALISTA</td>
                                <td class="val"><?= round($groupAverages['Realista'] ?? 0, 1) ?></td>
                            </tr>
                            <tr>
                                <td class="label">INVESTIGADORA</td>
                                <td class="val"><?= round($groupAverages['Investigador'] ?? 0, 1) ?></td>
                            </tr>
                            <tr>
                                <td class="label">ARTÍSTICA</td>
                                <td class="val"><?= round($groupAverages['Artístico'] ?? 0, 1) ?></td>
                            </tr>
                            <tr>
                                <td class="label">SOCIAL</td>
                                <td class="val"><?= round($groupAverages['Social'] ?? 0, 1) ?></td>
                            </tr>
                            <tr>
                                <td class="label">EMPRENDEDORA</td>
                                <td class="val"><?= round($groupAverages['Emprendedor'] ?? 0, 1) ?></td>
                            </tr>
                            <tr>
                                <td class="label">CONVENCIONAL</td>
                                <td class="val"><?= round($groupAverages['Convencional'] ?? 0, 1) ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="small-table">
                    <h4>INTENSIDAD</h4>
                    <table>
                        <tbody>
                            <?php
                            $max = 0;
                            foreach ($groupAverages as $v) {
                                if ($v > $max)
                                    $max = $v;
                            }
                            $nivel = $max >= 60 ? 'Alta' : ($max >= 40 ? 'Media' : 'Baja');
                            ?>
                            <tr>
                                <td class="label">NIVEL</td>
                                <td class="val"><?= $nivel ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="small-table">
                    <h4>CONSISTENCIA</h4>
                    <table>
                        <tbody>
                            <?php
                            // simple pairwise consistency: compare top two averages
                            $copies = $groupAverages;
                            arsort($copies);
                            $keys = array_keys($copies);
                            $first = $keys[0] ?? null;
                            $second = $keys[1] ?? null;
                            $diff = ($copies[$first] ?? 0) - ($copies[$second] ?? 0);
                            $cons = abs($diff) <= 10 ? 'Alta' : 'Media';
                            ?>
                            <tr>
                                <td class="label"><?= htmlspecialchars(($first ? strtoupper($first) : '-')) ?> -
                                    <?= htmlspecialchars(($second ? strtoupper($second) : '-')) ?>
                                </td>
                                <td class="val"><?= $cons ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="right-panel">
                <div class="results-chart-box">
                    <h3 style="margin:6px 0 8px 0; text-align:center;">Resultados del Test</h3>
                    <div style="height:300px;">
                        <canvas id="groupRadarChart"></canvas>
                    </div>
                </div>

                <div class="recommendations">
                    <h4 style="margin:0 0 8px 0;">RECOMENDACIONES</h4>
                    <p style="margin:0;">&nbsp;</p>
                    <ol>
                        <li>Analizar áreas con mayor puntuación para orientación grupal.</li>
                        <li>Promover talleres y visitas relacionadas con las áreas predominantes.</li>
                        <li>Monitorear estudiantes con alta consistencia para consejería personalizada.</li>
                    </ol>
                </div>
            </div>
        </div>

        <!-- CINE Legend (Compact) -->
        <div style="margin-bottom: 20px; font-size: 0.85em; background: #f8f9fa; padding: 10px; border-radius: 4px;">
            <strong>Nota:</strong> Los resultados se basan en la clasificación RIASEC:
            Realista (Tecnología), Investigador (Ciencia), Artístico (Artes), Social (Servicio), Emprendedor (Negocios),
            Convencional (Organización).
        </div>

        <!-- Students List -->
        <h3>Resultados Individuales</h3>
        <table class="students-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Estudiante</th>
                    <th>Curso</th>
                    <th>Área Principal (1ra Opción)</th>
                    <th>Área Secundaria (2da Opción)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($results as $index => $row):
                    $scores = json_decode($row['puntajes_json'], true);
                    $topAreas = []; // Calculate per user
                    if (is_array($scores)) {
                        // Sort desc
                        arsort($scores);
                        $topAreas = array_slice(array_keys($scores), 0, 2);
                    }
                    $area1 = $topAreas[0] ?? '-';
                    $area2 = $topAreas[1] ?? '-';
                    ?>
                    <tr>
                        <td><?= $index + 1 ?></td>
                        <td><?= htmlspecialchars($row['nombre'] . ' ' . $row['apellido']) ?></td>
                        <td><?= htmlspecialchars($row['curso'] ?? '-') ?></td>
                        <td><span class="area-pill"
                                style="background-color: var(--accent-color);"><?= htmlspecialchars($area1) ?></span></td>
                        <td><span class="area-pill"
                                style="background-color: #95a5a6;"><?= htmlspecialchars($area2) ?></span></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- DECE Footer -->
        <div class="section-title">IV. DATOS PROFESIONAL DECE</div>
        <div class="dece-box">
            <?php
            $footerName = 'No asignado';
            if (!empty($deceUser)) {
                $footerName = $deceUser['nombre'] . ' ' . $deceUser['apellido'];
            }

            $footerPlace = 'Ecuador';
            if (!empty($institution['nombre'])) {
                $footerPlace = $institution['nombre'];
            } elseif (!empty($filterInfo['institution']) && $filterInfo['institution'] !== 'Todas') {
                $footerPlace = $filterInfo['institution'];
            }
            ?>
            <?= htmlspecialchars($footerName) ?><br>
            <?= htmlspecialchars($footerPlace) ?>, <?= date('d/m/Y') ?><br>
            FIRMA DE RESPONSABILIDAD
        </div>

        <!-- Branding Footer -->
        <div class="branding-footer">
            <p>© Tecnológico Universitario Vida Nueva<br>
                www.vidanueva.edu.ec | admisiones@istvidanueva.edu.ec</p>
        </div>

        <?php if (isset($qrCodeBase64) && !empty($qrCodeBase64)): ?>
            <div class="qr-code-print">
                <img src="<?= $qrCodeBase64 ?>" alt="QR Validación">
                <div class="qr-label">VALIDACIÓN QR</div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Data for Radar Chart
        const labels = ['Realista', 'Investigador', 'Artístico', 'Social', 'Emprendedor', 'Convencional'];
        // Use PHP to inject group averages
        const data = [
            <?= $groupAverages['Realista'] ?? 0 ?>,
            <?= $groupAverages['Investigador'] ?? 0 ?>,
            <?= $groupAverages['Artístico'] ?? 0 ?>,
            <?= $groupAverages['Social'] ?? 0 ?>,
            <?= $groupAverages['Emprendedor'] ?? 0 ?>,
            <?= $groupAverages['Convencional'] ?? 0 ?>
        ];

        const ctx = document.getElementById('groupRadarChart').getContext('2d');
        new Chart(ctx, {
            type: 'radar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Promedio Grupal',
                    data: data,
                    backgroundColor: 'rgba(52, 152, 219, 0.2)',
                    borderColor: 'rgba(52, 152, 219, 1)',
                    pointBackgroundColor: 'rgba(52, 152, 219, 1)',
                    pointBorderColor: '#fff',
                    pointHoverBackgroundColor: '#fff',
                    pointHoverBorderColor: 'rgba(52, 152, 219, 1)'
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
    </script>
    <div class="print-footer"></div>
</body>

</html>