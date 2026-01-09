<?php
// Simple profile page to update user data
if (empty($user)) {
    header('Location: /test-vocacional/test');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= APP_NAME ?> - Mi Perfil</title>
    <link rel="stylesheet" href="/test-vocacional/assets/css/styles.css">
</head>

<body>

    <?php require 'views/layout/header.php'; ?>

    <div class="container">
        <div class="category-section">
            <h2>Mi Perfil</h2>
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']);
                unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-error"><?= htmlspecialchars($_SESSION['error']);
                unset($_SESSION['error']); ?></div>
            <?php endif; ?>

            <form method="POST" action="/test-vocacional/profile/update">
                <div class="form-row">
                    <div class="form-group">
                        <label>Nombre del Estudiante</label>
                        <input type="text" name="nombre" value="<?= htmlspecialchars($user['nombre'] ?? '') ?>"
                            class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Nombre del Representante</label>
                        <input type="text" name="apellido" value="<?= htmlspecialchars($user['apellido'] ?? '') ?>"
                            class="form-control">
                    </div>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>"
                        class="form-control">
                </div>
                <div class="form-group">
                    <label>Curso</label>
                    <input type="text" name="curso" value="<?= htmlspecialchars($user['curso'] ?? '') ?>"
                        class="form-control">
                </div>
                <div class="form-group">
                    <label>Paralelo</label>
                    <input type="text" name="paralelo" value="<?= htmlspecialchars($user['paralelo'] ?? '') ?>"
                        class="form-control">
                </div>
                <div class="form-group">
                    <label>Teléfono</label>
                    <input type="text" name="telefono" value="<?= htmlspecialchars($user['telefono'] ?? '') ?>"
                        class="form-control">
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Guardar</button>
                    <a href="/test-vocacional/auth/changePassword" class="btn btn-secondary">Cambiar Contraseña</a>
                </div>
            </form>
        </div>
    </div>

    <?php require 'views/layout/footer.php'; ?>
</body>

</html>