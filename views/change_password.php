<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= APP_NAME ?> - Cambiar Contraseña</title>
    <link rel="stylesheet" href="/test-vocacional/assets/css/styles.css">
</head>

<body>
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-brand">
                Test Vocacional
            </div>
            <div class="nav-menu">
                <span>Bienvenido, <?= htmlspecialchars($_SESSION['user_name']) ?></span>
                <a href="/test-vocacional/logout" class="btn btn-sm btn-outline">Cerrar Sesión</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="login-card" style="max-width: 500px; margin: 40px auto;">
            <div class="login-logo" style="text-align: center; margin-bottom: 30px;">
                <h2 style="color: #2c3e50; margin: 0;">Cambiar Contraseña</h2>
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

            <form method="POST" action="/test-vocacional/auth/changePassword" class="form-group">
                <div class="form-field">
                    <label for="current_password">Contraseña Actual *</label>
                    <input type="password" id="current_password" name="current_password" required
                        placeholder="Ingresa tu contraseña actual"
                        style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 1rem; margin-top: 8px;">
                </div>

                <div class="form-field">
                    <label for="new_password">Nueva Contraseña *</label>
                    <input type="password" id="new_password" name="new_password" required
                        placeholder="Ingresa tu nueva contraseña"
                        style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 1rem; margin-top: 8px;">
                    <small style="color: #666; margin-top: 5px; display: block;">
                        Mínimo <?= PASSWORD_MIN_LENGTH ?> caracteres
                    </small>
                </div>

                <div class="form-field">
                    <label for="confirm_password">Confirmar Nueva Contraseña *</label>
                    <input type="password" id="confirm_password" name="confirm_password" required
                        placeholder="Confirma tu nueva contraseña"
                        style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 1rem; margin-top: 8px;">
                </div>

                <button type="submit" class="btn btn-primary btn-lg" style="width: 100%; margin-top: 20px;">
                    Cambiar Contraseña
                </button>

                <div style="text-align: center; margin-top: 15px;">
                    <?php
                    $backUrl = '/test-vocacional/test';
                    $backLabel = 'Volver al Test';

                    if (isset($_SESSION['user_role'])) {
                        switch ($_SESSION['user_role']) {
                            case 'administrador':
                                $backUrl = '/test-vocacional/admin';
                                $backLabel = 'Volver al Panel';
                                break;
                            case 'zonal':
                                $backUrl = '/test-vocacional/admin/zona';
                                $backLabel = 'Volver al Panel';
                                break;
                            case 'dece':
                                $backUrl = '/test-vocacional/admin/dece';
                                $backLabel = 'Volver al Panel';
                                break;
                        }
                    }
                    ?>
                    <a href="<?= $backUrl ?>" style="color: #3498db; text-decoration: none;"><?= $backLabel ?></a>
                </div>
            </form>
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