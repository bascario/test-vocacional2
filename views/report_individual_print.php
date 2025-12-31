<?php
// views/report_individual_print.php
/** @var array $result */
/** @var array $normalizedScores */
/** @var array $stats */
/** @var array $differentiation */
/** @var array $competence */
/** @var array $topAreas */

// Helper to get color for cell background
function getLevelColor($level)
{
    if ($level === 'Bajo')
        return '#ffcccc'; // Light Red
    if ($level === 'Medio')
        return '#ffedad'; // Light Yellow
    if ($level === 'Alto')
        return '#d4edda'; // Light Green
    return '#ffffff';
}

$date = date('d/m/Y', strtotime($result['fecha_test']));
// Calculate age in years from fecha_nacimiento if available
$age_display = '-';
if (!empty($result['fecha_nacimiento'])) {
    $dob = DateTime::createFromFormat('Y-m-d', $result['fecha_nacimiento']);
    if ($dob) {
        $now = new DateTime();
        $age_display = $now->diff($dob)->y;
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Informe Vocacional - <?= htmlspecialchars($result['nombre'] . ' ' . $result['apellido']) ?></title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;700&display=swap');

        body {
            font-family: 'Roboto', sans-serif;
            margin: 0;
            padding: 20px;
            background: #f5f5f5;
            color: #333;
            font-size: 12px;
        }

        .page {
            background: white;
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto;
            padding: 15mm;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            position: relative;
            box-sizing: border-box;
        }

        @media only screen and (max-width: 210mm) {
            .page {
                width: 100%;
                padding: 5mm;
            }

            .analysis-container {
                grid-template-columns: 1fr;
            }

            .chart-area {
                height: 300px;
            }
        }

        @media print {
            body {
                background: white;
                margin: 0;
                padding: 0;
            }

            .page {
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
                size: auto;
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
                content: "Página " counter(page) " de 2";
            }

            .qr-code-print {
                display: block !important;
            }

            .qr-code-print img {
                width: 60px;
                height: 60px;
            }
        }

        .print-footer {
            text-align: center;
            font-size: 12px;
            color: #666;
            margin-top: 20px;
            padding: 10px;
            border-top: 1px solid #ddd;
        }

        .qr-code-print {
            text-align: center;
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

        /* Header */
        .header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 2px solid #00aeef;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }

        .header img {
            height: 50px;
        }

        .header-title {
            text-align: right;
            text-transform: uppercase;
            color: #00aeef;
            font-weight: bold;
            font-size: 18px;
        }

        /* Section Titles */
        .section-title {
            background: #cceeff;
            color: #000;
            font-weight: bold;
            text-transform: uppercase;
            padding: 5px 10px;
            margin: 10px 0;
            text-align: center;
            font-size: 14px;
        }

        /* Tables - Compact */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            font-size: 11px;
        }

        th,
        td {
            border: 1px solid #ccc;
            padding: 4px 8px;
            text-align: left;
        }

        th {
            background: #e0e0e0;
            text-align: center;
            font-weight: bold;
        }

        .text-center {
            text-align: center;
        }

        .bg-blue-light {
            background-color: #e6f7ff;
        }

        /* Personal Data */
        .personal-data-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 10px;
        }

        .data-row {
            display: flex;
            align-items: baseline;
        }

        .data-label {
            font-weight: bold;
            width: 100px;
        }

        .data-value {
            flex: 1;
            border-bottom: 1px solid #999;
            padding-left: 5px;
        }

        /* Type Vocational Box */
        .survey-results {
            display: flex;
            justify-content: space-between;
        }

        .career-list {
            width: 60%;
        }

        .vocational-type {
            width: 35%;
            border: 2px solid #ccc;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            background: #f9f9f9;
            flex-direction: column;
        }

        /* Analysis Section Layout */
        .analysis-container {
            display: grid;
            grid-template-columns: 35% 65%;
            gap: 20px;
            margin-bottom: 10px;
        }

        .score-table th {
            background-color: #999;
            color: white;
        }

        .score-table tr.total-row {
            background-color: #ddd;
            font-weight: bold;
        }

        /* Radar Chart Area */
        .chart-area {
            height: 250px;
            position: relative;
        }

        /* Differentiation Table */
        .differentiation-table th {
            font-size: 9px;
        }

        .differentiation-table td {
            font-size: 9px;
            text-align: center;
        }

        /* Competence & Recommendations */
        .comp-rec-container {
            display: grid;
            grid-template-columns: 30% 68%;
            gap: 2%;
        }

        /* Lines for writing */
        .write-lines {
            border-bottom: 1px solid #999;
            height: 18px;
            margin-bottom: 5px;
        }

        /* Footer / Signatures */
        .signatures {
            margin-top: 30px;
            display: flex;
            justify-content: space-between;
            text-align: center;
        }

        .sig-box {
            width: 30%;
            border-top: 1px solid #000;
            padding-top: 5px;
            font-weight: bold;
            font-size: 10px;
        }

        .qr-data-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 5px;
        }

        .dece-info {
            width: 75%;
            background-color: #f2f2f2;
            padding: 10px;
            border: 1px solid #ccc;
            font-weight: bold;
            font-size: 11px;
        }

        .dece-box {
            background-color: #ddd;
            padding: 10px;
            text-align: center;
            font-weight: bold;
            margin-bottom: 20px;
        }

        /* Page 2 - Definitions */
        .def-table th {
            background-color: #87CEEB;
            color: black;
            border: 1px solid #000;
        }

        .def-table td {
            vertical-align: top;
            padding: 8px;
            border: 1px solid #000;
        }

        .cat-realista {
            background-color: #b3e5fc;
        }

        .cat-investigadora {
            background-color: #ffe0b2;
        }

        /* Orange tint */
        .cat-artistica {
            background-color: #e1bee7;
        }

        /* Purple tint */
        .cat-social {
            background-color: #c8e6c9;
        }

        /* Green tint */
        .cat-emprendedora {
            background-color: #f0f4c3;
        }

        /* Lime tint */
        .cat-convencional {
            background-color: #cfd8dc;
        }

        /* Grey tint */
    </style>
</head>

<body>

    <!-- CONTROL BAR -->
    <div class="no-print"
        style="margin-bottom: 20px; text-align: center; background: #333; padding: 10px; color: white; border-radius: 5px;">
        <strong>Vista Previa del Informe</strong>
        <button onclick="window.print()"
            style="margin-left: 20px; padding: 8px 16px; background: #00aeef; color: white; border: none; cursor: pointer; border-radius: 4px; font-weight: bold;">🖨️
            Imprimir / Guardar como PDF</button>
        <a href="/test-vocacional/admin/users" style="margin-left: 10px; color: #ccc; text-decoration: none;">&larr;
            Volver</a>
    </div>

    <!-- PAGE 1 -->
    <div class="page">
        <!-- HEADER -->
        <div class="header">
            <!-- Replace with actual logo path or base64 if needed, using relative path for now -->
            <img src="/test-vocacional/assets/img/mined.png" alt="Logo">
            <div class="header-title">RESULTADOS</div>
        </div>

        <div class="section-title">I. DATOS PERSONALES</div>
        <div class="personal-data-grid">
            <div class="data-row"><span class="data-label">NOMBRES:</span>
                <div class="data-value"><?= htmlspecialchars($result['nombre'] . ' ' . $result['apellido']) ?></div>
            </div>
            <div class="data-row"><span class="data-label">CURSO:</span>
                <div class="data-value"><?= htmlspecialchars($result['curso'] ?? '') ?></div>
            </div>
            <div class="data-row"><span class="data-label">FECHA:</span>
                <div class="data-value"><?= $date ?></div>
            </div>
            <div class="data-row"><span class="data-label">EDAD:</span>
                <div class="data-value"><?= htmlspecialchars($age_display) ?></div>
            </div>
        </div>

        <div class="section-title">II. ENCUESTA</div>
        <div class="survey-results">
            <div class="career-list">
                <strong>CARRERAS PREFERIDAS:</strong>
                <ol>
                    <?php foreach ($topAreas as $cat => $data): ?>
                        <li><?= ucfirst($cat) ?> (<?= $data['porcentaje'] ?>%)</li>
                    <?php endforeach; ?>
                </ol>
            </div>
            <div class="vocational-type">
                <span>TIPO VOCACIONAL</span>
                <span style="font-size: 1.5em; color: #00aeef;"><?= strtoupper(array_key_first($topAreas)) ?></span>
            </div>
        </div>

        <div class="section-title">III. ANALISIS GLOBAL DE RESULTADOS DEL IPPJ</div>

        <div class="analysis-container">
            <!-- Scores Left -->
            <div>
                <table class="score-table">
                    <thead>
                        <tr>
                            <th colspan="2">PUNTAJE</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($normalizedScores as $cat => $data): ?>
                            <tr>
                                <td><?= strtoupper($cat) ?></td>
                                <td class="text-center"><?= round($data['promedio'], 0) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div style="margin-top: 10px; background: #ddd; padding: 5px; text-align: center; font-weight: bold;">
                    INTIMIDAD</div>
                <div style="display: flex; margin-top: 5px;">
                    <div style="width: 50%; border: 1px solid #ccc; padding: 5px;">NIVEL</div>
                    <div style="width: 50%; border: 1px solid #ccc; padding: 5px; text-align: center;">Bajo</div>
                </div>
            </div>

            <!-- Chart Right -->
            <div class="chart-area">
                <h3 style="text-align: center; margin: 0 0 10px 0;">Resultados del Test</h3>
                <canvas id="radarChart"></canvas>
            </div>
        </div>

        <!-- Diferenciacion -->
        <div style="background: #ddd; text-align: center; font-weight: bold; padding: 5px; margin-bottom: 5px;">
            DIFERENCIACIÓN</div>
        <table class="differentiation-table">
            <thead>
                <tr>
                    <th></th>
                    <?php foreach ($differentiation as $cat => $row): ?>
                        <th><?= strtoupper(substr($cat, 0, 4)) ?>.</th> <!-- Shorten headers -->
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($differentiation as $rowCat => $cols): ?>
                    <tr>
                        <td style="font-weight: bold; background: #eee;"><?= strtoupper($rowCat) ?></td>
                        <?php foreach ($cols as $colCat => $val): ?>
                            <td style="background-color: <?= ($val === '-') ? '#eee' : getLevelColor($val) ?>;">
                                <?= $val ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Competencia vs Recomendaciones -->
        <div class="comp-rec-container">
            <!-- Competencia -->
            <div>
                <div style="background: #ddd; text-align: center; font-weight: bold; padding: 5px;">COMPETENCIA</div>
                <table>
                    <?php foreach ($competence as $pair => $level): ?>
                        <tr>
                            <td style="font-size: 9px;"><?= strtoupper($pair) ?></td>
                            <td style="font-size: 9px; background-color: <?= getLevelColor($level) ?>;"><?= $level ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>

                <div style="background: #ddd; text-align: center; font-weight: bold; padding: 5px; margin-top: 5px;">
                    CONGRUENCIA</div>
                <div style="border: 1px solid #ccc; padding: 5px; font-size: 10px;">
                    CARRERA PREFERIDA 1: <?= ucfirst(array_keys($topAreas)[0] ?? '-') ?><br>
                    CARRERA PREFERIDA 2: <?= ucfirst(array_keys($topAreas)[1] ?? '-') ?>
                </div>
            </div>

            <!-- Recomendaciones -->
            <div>
                <div style="background: #ddd; text-align: center; font-weight: bold; padding: 5px;">RECOMENDACIONES
                </div>
                <div style="border: 1px solid #ccc; padding: 10px; height: 165px;">
                    <?php
                    // Auto-generate some text based on top area
                    $topCat = array_key_first($topAreas);
                    echo "<strong>Perfil predominante: " . ucfirst($topCat) . "</strong><br><br>";

                    // Call the new helper method
                    require_once __DIR__ . '/../utils/ReportHelper.php';
                    echo ReportHelper::getEvaluationText($topCat);
                    ?>
                    <div class="write-lines"></div>
                    <div class="write-lines"></div>
                    <div class="write-lines"></div>
                    <div class="write-lines"></div>
                    <div class="write-lines"></div>
                    <div class="write-lines"></div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="section-title">IV. DATOS DEL ESTUDIANTE Y PROFESIONAL DECE</div>
        <div class="qr-data-container">
            <div class="dece-info">
                ESTUDIANTE: <?= htmlspecialchars($result['nombre'] . ' ' . $result['apellido']) ?> -
                <?= htmlspecialchars($result['curso'] ?? '-') ?><br>
                <?= htmlspecialchars($deceUser ? ($deceUser['nombre'] . ' ' . $deceUser['apellido']) : 'No asignado') ?><br>
                <?= htmlspecialchars($institution['nombre'] ?? 'Ecuador') ?>, <?= $date ?>
            </div>

            <?php if (isset($qrCodeBase64) && !empty($qrCodeBase64)): ?>
                <div class="qr-code-print">
                    <img src="<?= $qrCodeBase64 ?>" alt="QR Validación">
                    <div class="qr-label">VALIDACIÓN QR</div>
                </div>
            <?php endif; ?>
        </div>

        <div class="signatures">
            <div class="sig-box">
                ESTUDIANTE
            </div>
            <div class="sig-box">
                REPRESENTANTE LEGAL
            </div>
            <div class="sig-box">
                PROFESIONAL DECE / RECTOR(A)
            </div>
        </div>
    </div>

    <!-- PAGE 2: Definitions Table -->
    <div class="page">
        <div class="section-title">V. TABLA: TIPO PROFESIONAL Y CLASIFICACION INTERNACIONAL NORMALIZADA DE LA EDUCACION
            (CINE)</div>

        <table class="def-table">
            <thead>
                <tr>
                    <th width="15%">TIPO VOCACIONAL</th>
                    <th width="25%">DEFINICIÓN</th>
                    <th width="60%">CAMPOS DE EDUCACIÓN</th>
                </tr>
            </thead>
            <tbody>
                <tr class="cat-realista">
                    <td><strong>REALISTA</strong></td>
                    <td>Persona que prefieren las actividades manuales, con herramientas, máquinas y cosas; personas
                        prácticas, realistas y les gusta la naturaleza y plantas.</td>
                    <td>Ingeniería y profesiones afines (Electricidad, electrónica, mecánica, automotriz, ingeniería
                        civil, topografía, telecomunicaciones, transporte energético y químico, mantenimiento de
                        vehículos, transporte, industrias y producción, indumentaria y calzado, textiles, confección,
                        minería, petróleo, materiales (maclerna, papel, plástico vidrio, artes visuales, industrias
                        extractivas).<br>Arquitectura y construcción (arquitectura y urbanismo, arquitectura
                        paisajística, planificación comunitaria, cartografía, edificación, construcción, ingeniería
                        civil).<br>Agricultura, silvicultura y pesca (producción agrícola, pecuaria, agronomía,
                        ganadería, horticultura y jardinería, silvicultura y técnicas forestales, parques naturales,
                        flora y fauna, ciencia y tecnología pesquera).</td>
                </tr>
                <tr class="cat-investigadora">
                    <td><strong>INVESTIGADORA</strong></td>
                    <td>Persona que valora mucho la teoría y la información; analíticos, intelectuales y científicos.
                    </td>
                    <td>Educación (programas básicos de educación preescolar, elemental, primaria, secundaria,
                        etc).<br>Artes y Humanidades (humanidades, religión y teología, lenguas y culturas extranjeras,
                        literatura, historia, arqueología, filosofía, ética).<br>Ciencias sociales, periodismo e
                        información (economía, ciencias políticas, sociología, psicología, geografía,
                        periodismo).<br>Ciencias naturales, matemáticas y estadística (biología, medio ambiente, física,
                        química, geología, matemáticas, estadística).<br>Tecnologías de la información y la comunicación
                        (TIC) (diseño y administración de base de datos y redes, desarrollo de software).</td>
                </tr>
                <tr class="cat-artistica">
                    <td><strong>ARTÍSTICA</strong></td>
                    <td>Persona que prefiere las relaciones espaciales; es original e independiente.</td>
                    <td>Bellas artes (música, teatro y danza, dibujo, pintura, escultura).<br>Artes visuales
                        (fotografía, artesanía, producción de medios, diseño gráfico).<br>Diseño industrial, de modas e
                        interiores.</td>
                </tr>
                <tr class="cat-social">
                    <td><strong>SOCIAL</strong></td>
                    <td>Persona que desea trabajar en sociedad, le gusta ayudar a personas; cooperativos, educadores y
                        comprensión.</td>
                    <td>Educación (formación de personal docente, ciencias de la educación).<br>Servicios personales
                        (hotelería y restaurantes, turismo, deportes, servicios de transporte, servicio
                        postal).<br>Salud y bienestar (medicina, enfermería y partería, tecnología de diagnóstico y
                        tratamiento médico, terapia y rehabilitación, farmacia).<br>Servicios de seguridad (protección
                        de personas y bienes: policía, militar, protección contra incendios, seguridad civil).</td>
                </tr>
                <tr class="cat-emprendedora">
                    <td><strong>EMPRENDEDORA</strong></td>
                    <td>Persona que trabaja en entornos competitivos, le gusta liderar, persuadir, vender, gestionar
                        proyectos.</td>
                    <td>Administración de empresas y derecho (contabilidad, auditoría, finanzas, banca, seguros,
                        marketing y publicidad).<br>Gestión y administración.</td>
                </tr>
                <tr class="cat-convencional">
                    <td><strong>CONVENCIONAL</strong></td>
                    <td>Persona muy ordenada, precisa, que valida con atención a los detalles. Se enfocan en el orden y
                        la organización.</td>
                    <td>Administración de empresas y derecho (secretaría y trabajo de oficina,
                        contabilidad).<br>Bibliotecología, información y archivos.</td>
                </tr>
            </tbody>
        </table>

        <?php if (isset($qrCodeBase64) && !empty($qrCodeBase64)): ?>
            <div class="qr-code-print">
                <img src="<?= $qrCodeBase64 ?>" alt="QR Validación">
                <div class="qr-label">VALIDACIÓN QR</div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Branding Footer (Global or End of Report) -->
    <div class="no-print" style="text-align: center; margin-top: 20px; font-size: 10px; color: #666;">
        Tecnológico Universitario Vida Nueva | admisiones@istvidanueva.edu.ec | www.vidanueva.edu.ec
    </div>

    <style>
        @media print {
            .branding-footer {
                position: fixed;
                bottom: 10px;
                left: 0;
                right: 0;
                text-align: center;
                font-size: 9px;
                color: #666;
                border-top: 1px solid #ddd;
                padding-top: 5px;
                background: white;
            }
        }
    </style>
    <div class="branding-footer"
        style="text-align: center; margin-top: 30px; font-size: 10px; border-top: 1px solid #ccc; padding-top: 5px;">
        © Tecnológico Universitario Vida Nueva <br>
        admisiones@istvidanueva.edu.ec | www.vidanueva.edu.ec
    </div>

    <script>
        // Chart Configuration
        const ctx = document.getElementById('radarChart').getContext('2d');
        const labels = <?= json_encode(array_map('strtoupper', array_keys($normalizedScores))) ?>;
        const dataValues = <?= json_encode(array_column($normalizedScores, 'promedio')) ?>;

        new Chart(ctx, {
            type: 'radar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Perfil',
                    data: dataValues,
                    backgroundColor: 'rgba(0, 174, 239, 0.2)',
                    borderColor: '#00aeef',
                    borderWidth: 2,
                    pointBackgroundColor: '#00aeef',
                    pointBorderColor: '#fff',
                    pointHoverBackgroundColor: '#fff',
                    pointHoverBorderColor: '#00aeef'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    r: {
                        beginAtZero: true,
                        max: 5,
                        min: 0,
                        ticks: { stepSize: 1, display: false }, // Hide axis numbers to look cleaner like image
                        pointLabels: {
                            font: { size: 9, weight: 'bold' } // Smaller labels
                        }
                    }
                },
                plugins: {
                    legend: { display: false }
                }
            }
        });
    </script>
    <div class="print-footer"></div>
</body>

</html>