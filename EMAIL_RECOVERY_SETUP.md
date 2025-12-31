# Guía de configuración - Recuperación de Contraseña por Email

## 📧 Sistema de Recuperación de Contraseña Implementado

Se ha implementado un sistema completo de recuperación de contraseña por email con:
- Generación segura de tokens (64 caracteres)
- Enlaces con expiración (1 hora por defecto)
- Envío de emails HTML con plantillas profesionales
- Soporte para SMTP y función mail() de PHP

## 🚀 Flujo de recuperación

1. Usuario ingresa email en `/recover-password`
2. Sistema genera token criptográfico seguro
3. Email se envía con enlace `reset-password?token=XXX`
4. Usuario hace clic en el enlace dentro de 1 hora
5. Ingresa nueva contraseña en `/reset-password`
6. Contraseña se actualiza con bcrypt y token se marca como usado

## ⚙️ Configuración SMTP (config/config.php)

### Opción 1: Gmail con contraseña de aplicación (RECOMENDADO)

```php
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'tu-email@gmail.com');
define('SMTP_PASS', 'tu-app-password');  // NO tu contraseña de Gmail
define('SMTP_FROM_NAME', 'Test Vocacional');
define('SMTP_FROM_EMAIL', 'noreply@test-vocacional.com');
```

**Pasos para Gmail:**
1. Habilitar autenticación de dos factores en cuenta Google
2. Ir a: https://myaccount.google.com/apppasswords
3. Crear "contraseña de aplicación" (será de 16 caracteres)
4. Copiar esa contraseña en SMTP_PASS

### Opción 2: Servidor SMTP personalizado

```php
define('SMTP_HOST', 'mail.tudominio.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'usuario@tudominio.com');
define('SMTP_PASS', 'password');
define('SMTP_FROM_NAME', 'Test Vocacional');
define('SMTP_FROM_EMAIL', 'noreply@tudominio.com');
```

### Opción 3: Sin configuración SMTP (usa mail() de PHP)

Si SMTP_HOST y SMTP_USER están vacíos, se usará la función `mail()` de PHP:

```php
define('SMTP_HOST', '');
define('SMTP_USER', '');
// ... resto vacío
```

**Requisitos:**
- Servidor con PHP configurado para envío de emails
- Postfix/Sendmail en Linux o IIS SMTP en Windows

## 📁 Archivos creados/modificados

### Nuevos archivos:
- `utils/EmailSender.php` - Clase para envío de emails
- `models/User.php` - Métodos para tokens
- `views/reset_password.php` - Formulario de reset
- `migrations/005_create_password_reset_tokens.sql` - Tabla de tokens
- `create_tokens_table.php` - Script para crear tabla

### Modificados:
- `config/config.php` - Configuración SMTP
- `controllers/AuthController.php` - Métodos recoverPassword() y resetPassword()
- `views/recover_password.php` - Mejorado con soporte a email
- `index.php` - Nueva ruta `/reset-password`

## 🔐 Seguridad implementada

✅ **Tokens seguros**
- 64 caracteres de datos aleatorios generados con `random_bytes()`
- Almacenados con hash SHA256 en BD
- Expiran después de 1 hora

✅ **Email sanitizado**
- Validación de formato email
- No revelamos si el email existe (previene enumeration attacks)
- Mensaje genérico para ambos casos

✅ **Protección contra fuerza bruta**
- Tokens de un solo uso
- Registra IP del usuario
- Tokens expirados se limpian automáticamente

✅ **Contraseñas**
- Hash con bcrypt (cost=12)
- Mínimo de caracteres requerido
- Validación en servidor

## 🧪 Prueba local (sin SMTP)

Para probar sin servidor SMTP:

1. Configurar `SMTP_HOST` vacío en `config/config.php`
2. Usar `mail()` de PHP (requiere Postfix en Linux o configuración SMTP en Windows)
3. O revisar logs en `php_errors.log`

### Alternativa: Ver emails en archivo

Editar `utils/EmailSender.php` para guardar emails en archivo:

```php
// Temporalmente, en lugar de enviar:
file_put_contents(
    '/tmp/reset_email_' . time() . '.html',
    $this->getEmailTemplate($userName, $resetLink)
);
return true;
```

## 📊 Base de datos

### Tabla password_reset_tokens

```
id              INT PRIMARY KEY (auto-increment)
user_id         INT FOREIGN KEY -> usuarios(id)
token           VARCHAR(64) - Token en texto plano enviado al email
token_hash      VARCHAR(255) - Hash SHA256 almacenado en BD
email           VARCHAR(255) - Email del usuario
created_at      TIMESTAMP - Cuándo se creó el token
expires_at      TIMESTAMP - Cuándo expira
used_at         TIMESTAMP - Cuándo fue usado (NULL si no usado)
ip_address      VARCHAR(45) - IP que solicitó el reset
```

### Limpieza automática de tokens expirados

Ejecutar periódicamente (cada hora):

```bash
php -r "require_once 'config/config.php'; require_once 'models/BaseModel.php'; require_once 'models/User.php'; (new User())->cleanupExpiredTokens();"
```

O agregar a cron:

```
0 * * * * /usr/bin/php /ruta/test-vocacional/cleanup_tokens.php
```

## 🐛 Troubleshooting

### Problema: Email no se envía
```
Solución 1: Verificar SMTP_HOST y SMTP_USER en config.php
Solución 2: Revisar logs: tail -f php_errors.log
Solución 3: Usar mail() de PHP si SMTP no está disponible
Solución 4: Verificar credenciales SMTP (especialmente app password de Gmail)
```

### Problema: Token expirado inmediatamente
```
Solución: Verificar timezone del servidor
date_default_timezone_set('America/Guayaquil'); // En config.php
```

### Problema: Usuario no recibe email
```
Verificar:
1. Email está registrado en el sistema
2. SMTP está configurado correctamente
3. Revisar carpeta Spam/Junk
4. Verificar logs: php_errors.log
```

## 📝 Próximas mejoras (Opcional)

1. **Limpieza automática**
   - Agregar cron job para limpiar tokens expirados

2. **Rate limiting**
   - Limitar intentos por IP
   - Máximo X solicitudes por hora

3. **Notificaciones**
   - Email cuando se cambia contraseña
   - Alertas de intentos fallidos

4. **Autenticación 2FA**
   - Código enviado por SMS
   - Google Authenticator

## ✅ Verificación post-instalación

```bash
# 1. Crear tabla
php create_tokens_table.php

# 2. Probar flujo:
# - Ir a /recover-password
# - Ingresar email
# - Ver que se muestre mensaje genérico
# - Revisar BD: SELECT * FROM password_reset_tokens

# 3. Probar con token válido:
# - Generar token manualmente en BD
# - Acceder a /reset-password?token=...
# - Cambiar contraseña
```

## 📞 Soporte

Para problemas con:
- **SMTP Gmail**: https://myaccount.google.com/apppasswords
- **PHPMailer**: https://github.com/PHPMailer/PHPMailer
- **Email validation**: https://www.php.net/manual/es/filter.examples.email.php
