<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= APP_NAME ?> - Recuperar Contraseña</title>
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
                <h2 style="color: #2c3e50; margin: 0;">Recuperar Contraseña</h2>
                <p style="color: #666; margin: 10px 0 0 0; font-size: 0.95rem;">
                    Ingresa tu email para recibir un enlace de recuperación
                </p>
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
                <div class="alert alert-info" style="background-color: #e3f2fd; border: 1px solid #90caf9; color: #1565c0;">
                    <?= htmlspecialchars($_SESSION['info']) ?>
                    <?php unset($_SESSION['info']); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="/test-vocacional/auth/recoverPassword" class="form-group">
                <div class="form-field">
                    <label for="email">Email *</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        required
                        placeholder="Ingresa tu email registrado"
                        style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 1rem; margin-top: 8px;"
                    >
                </div>

                <button type="submit" class="btn btn-primary btn-lg" style="width: 100%; margin-top: 20px;">
                    Enviar Enlace de Recuperación
                </button>

                <div style="text-align: center; margin-top: 15px;">
                    <a href="/test-vocacional/login" style="color: #3498db; text-decoration: none;">Volver al Login</a>
                </div>
            </form>

            <div style="background: #f9f9f9; border: 1px solid #eee; border-radius: 6px; padding: 15px; margin-top: 25px;">
                <p style="margin: 0; color: #666; font-size: 0.9rem;">
                    <strong>¿No tienes cuenta?</strong> 
                    <a href="/test-vocacional/register" style="color: #3498db; text-decoration: none;">Regístrate aquí</a>
                </p>
            </div>
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
