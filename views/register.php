<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= APP_NAME ?> - Registro</title>
    <link rel="stylesheet" href="/test-vocacional/assets/css/styles.css">
    <!-- CSS de Select2 -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

    <!-- jQuery (requerido por Select2) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- JS de Select2 -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
</head>

<body>
    <div class="container">
        <div class="login-container">
            <div class="login-card">
                <div class="login-header">
                    <h1>Test Vocacional</h1>
                    <p>Registro de Estudiantes</p>
                </div>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-error">
                        <?= htmlspecialchars($_SESSION['error']) ?>
                        <?php unset($_SESSION['error']); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="/test-vocacional/register" class="login-form">
                    <div class="form-group">
                        <label for="username">Usuario</label>
                        <input type="text" id="username" name="username" required
                            placeholder="Ingresa un nombre de usuario">
                    </div>

                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" required placeholder="Ingresa tu email">
                    </div>

                    <div class="form-group">
                        <label for="nombre">Nombre</label>
                        <input type="text" id="nombre" name="nombre" required placeholder="Ingresa tu nombre">
                    </div>

                    <div class="form-group">
                        <label for="apellido">Apellido</label>
                        <input type="text" id="apellido" name="apellido" required placeholder="Ingresa tu apellido">
                    </div>

                    <div class="form-group">
                        <label for="fecha_nacimiento">Fecha de Nacimiento</label>
                        <input type="date" id="fecha_nacimiento" name="fecha_nacimiento"
                            max="<?= date('Y-m-d') ?>" placeholder="AAAA-MM-DD">
                    </div>

                    <div class="form-group">
                        <label for="institucion_id">Nombre del Colegio</label>
                        <select id="institucion_id" name="institucion_id" required style="width: 100%;">

                        </select>
                    </div>

                    <div class="form-group">
                        <label for="telefono">Número de Celular (Representante)</label>
                        <input type="tel" id="telefono" name="telefono" placeholder="Ej: 0987654321" pattern="[0-9]{10}"
                            title="Ingresa un número de 10 dígitos">
                    </div>

                    <div class="form-group">
                        <label for="curso">Curso (Opcional)</label>
                        <select id="curso" name="curso">
                            <option value="">Selecciona un curso</option>
                            <option value="1ero BGU">1ero BGU</option>
                            <option value="2do BGU">2do BGU</option>
                            <option value="3ero BGU">3ero BGU</option>
                            <option value="1ero BT">1ero BT</option>
                            <option value="2do BT">2do BT</option>
                            <option value="3ero BT">3ero BT</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="paralelo">Paralelo (Opcional)</label>
                        <select id="paralelo" name="paralelo">
                            <option value="">Selecciona un paralelo</option>
                            <option value="A">A</option>
                            <option value="B">B</option>
                            <option value="C">C</option>
                            <option value="D">D</option>
                            <option value="E">E</option>
                            <option value="Otro">Otro</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="password">Contraseña</label>
                        <input type="password" id="password" name="password" required
                            placeholder="Ingresa tu contraseña (mínimo 6 caracteres)">
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">
                        Registrarse
                    </button>
                </form>

                <div class="login-footer">
                    <p>¿Ya tienes cuenta? <a href="/test-vocacional/login">Inicia sesión aquí</a></p>
                </div>
            </div>
        </div>
    </div>

    <script src="/test-vocacional/assets/js/main.js"></script>
</body>

</html>

<script>
    $(document).ready(function () {
        $('#institucion_id').select2({
            placeholder: "Busca tu institución por nombre o código...",
            allowClear: true,
            width: '100%',
            minimumInputLength: 2,
            ajax: {
                url: '/test-vocacional/institutions/search',
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return { q: params.term };
                },
                processResults: function (data) {
                    return { results: data.results };
                },
                cache: true
            },
            language: {
                inputTooShort: function (args) {
                    var remaining = args.minimum - args.input.length;
                    return 'Por favor escribe ' + args.minimum + ' o más caracteres';
                },
                noResults: function () {
                    return 'No se encontraron resultados';
                },
                searching: function () {
                    return 'Buscando…';
                }
            },
            dropdownParent: $('#institucion_id').parent()
        });
    });
</script>