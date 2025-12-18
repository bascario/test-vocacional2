<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= APP_NAME ?> - Encuesta Inicial</title>
    <link rel="stylesheet" href="/test-vocacional/assets/css/styles.css">
    <style>
        .survey-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 40px;
            box-shadow: var(--box-shadow);
            max-width: 900px;
            margin: 30px auto;
            border-top: 5px solid var(--secondary-color);
        }

        .survey-intro {
            text-align: center;
            margin-bottom: 40px;
        }

        .survey-intro h1 {
            color: var(--secondary-color);
            font-size: 2.2rem;
            margin-bottom: 10px;
        }

        .question-block {
            background: #f8f9fa;
            border-radius: var(--border-radius);
            padding: 25px;
            margin-bottom: 30px;
            border-left: 4px solid var(--primary-color);
            transition: var(--transition);
        }

        .question-block:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .question-title {
            color: var(--secondary-color);
            font-size: 1.1rem;
            font-weight: 700;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .question-number {
            background: var(--primary-color);
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
            flex-shrink: 0;
        }

        .input-group-row {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
        }

        .input-number {
            color: #999;
            font-weight: bold;
            width: 25px;
        }

        .sub-label {
            display: block;
            margin-bottom: 8px;
            color: var(--dark-color);
            font-weight: 500;
        }

        .sub-group {
            background: white;
            padding: 15px;
            border-radius: var(--border-radius);
            border: 1px solid #e9ecef;
            margin-bottom: 15px;
        }
    </style>
</head>

<body>
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-brand">
                Test Vocacional
            </div>
            <div class="nav-menu">
                <span>Hola, <?= htmlspecialchars($_SESSION['user_name']) ?></span>
                <a href="/test-vocacional/logout" class="btn btn-sm btn-outline"
                    style="border-color: white; color: white;">Cerrar Sesión</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="survey-card">
            <div class="survey-intro">
                <h1>Encuesta Inicial</h1>
                <p>Para brindarte una mejor orientación, necesitamos conocer un poco más sobre ti.</p>
            </div>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-error">
                    <?= $_SESSION['error'] ?>
                    <?php unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>

            <form action="/test-vocacional/test/survey" method="POST">

                <!-- PREGUNTA 1 -->
                <div class="question-block">
                    <div class="question-title">
                        <span class="question-number">1</span>
                        ¿Cuáles son tus tres profesiones de mayor preferencia?
                    </div>
                    <div class="form-group">
                        <div class="input-group-row">
                            <span class="input-number">1)</span>
                            <input type="text" name="preferencia_mayor[]" class="form-control"
                                placeholder="Escribe tu primera opción..." required>
                        </div>
                        <div class="input-group-row">
                            <span class="input-number">2)</span>
                            <input type="text" name="preferencia_mayor[]" class="form-control"
                                placeholder="Escribe tu segunda opción..." required>
                        </div>
                        <div class="input-group-row">
                            <span class="input-number">3)</span>
                            <input type="text" name="preferencia_mayor[]" class="form-control"
                                placeholder="Escribe tu tercera opción..." required>
                        </div>
                    </div>
                </div>

                <!-- PREGUNTA 2 -->
                <div class="question-block">
                    <div class="question-title">
                        <span class="question-number">2</span>
                        ¿Cuáles son las tres profesiones de menor preferencia?
                    </div>
                    <div class="form-group">
                        <div class="input-group-row">
                            <span class="input-number">1)</span>
                            <input type="text" name="preferencia_menor[]" class="form-control"
                                placeholder="Menor preferencia 1..." required>
                        </div>
                        <div class="input-group-row">
                            <span class="input-number">2)</span>
                            <input type="text" name="preferencia_menor[]" class="form-control"
                                placeholder="Menor preferencia 2..." required>
                        </div>
                        <div class="input-group-row">
                            <span class="input-number">3)</span>
                            <input type="text" name="preferencia_menor[]" class="form-control"
                                placeholder="Menor preferencia 3..." required>
                        </div>
                    </div>
                </div>

                <!-- PREGUNTA 3 -->
                <div class="question-block">
                    <div class="question-title">
                        <span class="question-number">3</span>
                        Datos de la madre o cuidadora principal
                    </div>
                    <div class="sub-group">
                        <label class="sub-label">¿Cuál es el nivel de estudios?</label>
                        <input type="text" name="madre_estudios" class="form-control"
                            placeholder="Ej: Primaria, Secundaria, Universidad" required>
                    </div>
                    <div class="sub-group">
                        <label class="sub-label">¿Qué profesión ejerce actualmente?</label>
                        <input type="text" name="madre_profesion" class="form-control"
                            placeholder="Ej: Abogada, Ama de casa, Comerciante" required>
                    </div>
                </div>

                <!-- PREGUNTA 4 -->
                <div class="question-block">
                    <div class="question-title">
                        <span class="question-number">4</span>
                        Datos del padre o cuidador principal
                    </div>
                    <div class="sub-group">
                        <label class="sub-label">¿Cuál es el nivel de estudios?</label>
                        <input type="text" name="padre_estudios" class="form-control"
                            placeholder="Ej: Primaria, Secundaria, Universidad" required>
                    </div>
                    <div class="sub-group">
                        <label class="sub-label">¿Qué profesión ejerce actualmente?</label>
                        <input type="text" name="padre_profesion" class="form-control"
                            placeholder="Ej: Ingeniero, Mecánico, Docente" required>
                    </div>
                </div>

                <!-- PREGUNTA 5 -->
                <div class="question-block">
                    <div class="question-title">
                        <span class="question-number">5</span>
                        Actividades de Tiempo Libre
                    </div>
                    <p style="margin-bottom: 15px; color:#666; font-size:0.9rem;">Escribe tres actividades que realizas
                        en tu tiempo libre en orden de preferencia:</p>
                    <div class="form-group">
                        <div class="input-group-row">
                            <span class="input-number">1)</span>
                            <input type="text" name="tiempo_libre[]" class="form-control"
                                placeholder="Actividad favorita..." required>
                        </div>
                        <div class="input-group-row">
                            <span class="input-number">2)</span>
                            <input type="text" name="tiempo_libre[]" class="form-control"
                                placeholder="Segunda actividad..." required>
                        </div>
                        <div class="input-group-row">
                            <span class="input-number">3)</span>
                            <input type="text" name="tiempo_libre[]" class="form-control"
                                placeholder="Tercera actividad..." required>
                        </div>
                    </div>
                </div>

                <!-- PREGUNTA 6 -->
                <div class="question-block">
                    <div class="question-title">
                        <span class="question-number">6</span>
                        Definición de Éxito Profesional
                    </div>
                    <p style="margin-bottom: 15px; color:#666; font-size:0.9rem;">Escribe tres características de una
                        persona que consideras exitosa en su vida laboral:</p>
                    <div class="form-group">
                        <div class="input-group-row">
                            <span class="input-number">1)</span>
                            <input type="text" name="exito_profesional[]" class="form-control"
                                placeholder="Característica 1" required>
                        </div>
                        <div class="input-group-row">
                            <span class="input-number">2)</span>
                            <input type="text" name="exito_profesional[]" class="form-control"
                                placeholder="Característica 2" required>
                        </div>
                        <div class="input-group-row">
                            <span class="input-number">3)</span>
                            <input type="text" name="exito_profesional[]" class="form-control"
                                placeholder="Característica 3" required>
                        </div>
                    </div>
                </div>

                <!-- PREGUNTA 7 -->
                <div class="question-block">
                    <div class="question-title">
                        <span class="question-number">7</span>
                        Importancia del Éxito
                    </div>
                    <div class="form-group">
                        <label class="sub-label">¿Qué tan importante es para ti lograr el éxito en tus estudios y vida
                            laboral? ¿Por qué?</label>
                        <textarea name="importancia_exito" class="form-control" rows="4"
                            placeholder="Comparte tu opinión aquí..." required
                            style="width:100%; padding:12px; border:1px solid #ddd; border-radius:8px;"></textarea>
                    </div>
                </div>

                <div style="text-align: center; margin-top: 40px;">
                    <button type="submit" class="btn btn-primary btn-lg" style="min-width: 300px;">
                        Continuar al Test Vocacional <span style="font-size:1.2em">➜</span>
                    </button>
                    <p style="margin-top:15px; font-size:0.9rem; color:#888;">Tus respuestas son confidenciales</p>
                </div>

            </form>
        </div>
    </div>
</body>

</html>