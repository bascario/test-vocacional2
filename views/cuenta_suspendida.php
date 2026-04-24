<?php require 'views/layout/header.php'; ?>

<div class="login-container" style="padding: 40px 20px; max-width: 600px; margin: 60px auto;">
    <div class="login-card" style="text-align: center; padding: 40px;">
        <div class="login-logo" aria-hidden="true"><span class="logo-mark">⚠️</span></div>
        <h2>Cuenta Suspendida</h2>
        <p style="margin: 1.5rem 0; color: #555; font-size: 1rem;">
            Tu cuenta está suspendida porque el pago no ha sido registrado. Si crees que se trata de un error, contacta con el administrador o con soporte.
        </p>
        <?php if (!empty($_SESSION['error'])): ?>
            <div class="alert alert-error" style="margin-bottom: 20px;">
                <?= htmlspecialchars($_SESSION['error']) ?>
                <?php unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>
        <?php if (!empty($_SESSION['user_id'])): ?>
            <a href="/test-vocacional/logout" class="btn btn-primary">Cerrar sesión</a>
        <?php else: ?>
            <a href="/test-vocacional/login" class="btn btn-primary">Volver al inicio de sesión</a>
        <?php endif; ?>
    </div>
</div>

<?php require 'views/layout/footer.php'; ?>