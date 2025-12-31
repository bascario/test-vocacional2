<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= APP_NAME ?> - Iniciar Sesión</title>
    <link rel="stylesheet" href="/test-vocacional/assets/css/styles.css">
</head>

<body class="auth-page">
    <div class="container">
        <div class="login-container">
            <div class="login-card">
                <div class="login-header">
                    <div class="login-logo" aria-hidden="true"><span class="logo-mark">🎯</span></div>
                    <h1 class="brand-title">Test Vocacional</h1>
                    <p>Sistema de Orientación Vocacional</p>
                </div>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-error">
                        <?= htmlspecialchars($_SESSION['error']) ?>
                        <?php unset($_SESSION['error']); ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success">
                        <?= htmlspecialchars($_SESSION['success']) ?>
                        <?php unset($_SESSION['success']); ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['info'])): ?>
                    <div class="alert alert-info">
                        <?= htmlspecialchars($_SESSION['info']) ?>
                        <?php unset($_SESSION['info']); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="/test-vocacional/login" class="login-form">
                    <div class="form-group">
                        <label for="username">Usuario</label>
                        <input type="text" id="username" name="username" required placeholder="Ingresa tu usuario">
                    </div>

                    <div class="form-group">
                        <label for="password">Contraseña</label>
                        <input type="password" id="password" name="password" required
                            placeholder="Ingresa tu contraseña">
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">
                        Iniciar Sesión
                    </button>
                </form>

                <div class="login-footer">
                    <p>¿No tienes cuenta? <a href="/test-vocacional/register">Regístrate aquí</a></p>
                    <p><a href="/test-vocacional/recover-password" style="color: #3498db;">Recuperar contraseña</a></p>
                    <p class="text-small">
                        &copy; 2024 Derechos reservados Instituto Tecnológico Superior Vida nueva
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script src="/test-vocacional/assets/js/main.js"></script>
</body>

</html>