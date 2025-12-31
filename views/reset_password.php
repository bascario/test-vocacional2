<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= APP_NAME ?> - Restablecer Contraseña</title>
    <link rel="stylesheet" href="/test-vocacional/assets/css/styles.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-brand">
                Test Vocacional
            </div>
            <div class="nav-menu">
                <a href="/test-vocacional/login" class="btn btn-sm btn-outline">Volver al Login</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="login-card" style="max-width: 500px; margin: 40px auto;">
            <div class="login-logo" style="text-align: center; margin-bottom: 30px;">
                <h2 style="color: #2c3e50; margin: 0;">Restablecer Contraseña</h2>
                <p style="color: #666; margin: 10px 0 0 0; font-size: 0.95rem;">
                    Ingresa tu nueva contraseña
                </p>
            </div>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-error">
                    <?= htmlspecialchars($_SESSION['error']) ?>
                    <?php unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($tokenData)): ?>
            <form method="POST" action="/test-vocacional/reset-password?token=<?= urlencode($token) ?>" class="form-group">
                <div class="form-field">
                    <label for="password">Nueva Contraseña *</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        required
                        placeholder="Ingresa tu nueva contraseña"
                        style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 1rem; margin-top: 8px;"
                    >
                    <small style="color: #666; margin-top: 5px; display: block;">
                        Mínimo <?= PASSWORD_MIN_LENGTH ?> caracteres
                    </small>
                </div>

                <div class="form-field">
                    <label for="confirm_password">Confirmar Contraseña *</label>
                    <input 
                        type="password" 
                        id="confirm_password" 
                        name="confirm_password" 
                        required
                        placeholder="Confirma tu nueva contraseña"
                        style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 1rem; margin-top: 8px;"
                    >
                </div>

                <button type="submit" class="btn btn-primary btn-lg" style="width: 100%; margin-top: 20px;">
                    Restablecer Contraseña
                </button>

                <div style="text-align: center; margin-top: 15px;">
                    <a href="/test-vocacional/login" style="color: #3498db; text-decoration: none;">Volver al Login</a>
                </div>
            </form>
            <?php else: ?>
                <div class="alert alert-error">
                    Token de recuperación no válido o expirado.
                </div>
                <div style="text-align: center; margin-top: 15px;">
                    <a href="/test-vocacional/recover-password" class="btn btn-primary" style="display: inline-block; padding: 10px 20px;">
                        Solicitar Nuevo Enlace
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <style>
        .form-field {
            margin-bottom: 20px;
        }

        .form-field label {
            display: block;
            margin-bottom: 5px;
            color: #2c3e50;
            font-weight: 600;
        }

        .alert {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 1rem;
        }

        .alert-error {
            background-color: #fee;
            border: 1px solid #f99;
            color: #c33;
        }

        .alert-success {
            background-color: #efe;
            border: 1px solid #9f9;
            color: #3c3;
        }
    </style>

    <?php require 'views/layout/footer.php'; ?>
</body>
</html>
