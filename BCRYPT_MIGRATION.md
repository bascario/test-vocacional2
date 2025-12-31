# Migración a bcrypt - Guía de Implementación

## Cambios realizados

Se ha actualizado el sistema para usar **bcrypt** en lugar de **MD5** para el hash de contraseñas. Bcrypt es significativamente más seguro.

### Archivos modificados

1. **models/User.php**
   - `createUser()`: Ahora usa `password_hash()` con bcrypt (cost 12)
   - `authenticate()`: Usa `password_verify()` para verificación segura
   - `updatePassword()`: Hash con bcrypt
   - `verifyPassword()`: Verificación con bcrypt

2. **migrate_passwords.php** (NUEVO)
   - Script para migrar contraseñas existentes de MD5 a bcrypt
   - Ejecutar: `php migrate_passwords.php`

## Pasos de migración

### Paso 1: Actualizar código
- Los cambios ya están aplicados en `models/User.php`

### Paso 2: Migrar contraseñas existentes
```bash
cd /ruta/a/test-vocacional
php migrate_passwords.php
```

Este script:
- Identifica usuarios con contraseñas en MD5
- Genera contraseñas temporales seguras
- Actualiza el hash a bcrypt
- Muestra un resumen de usuarios migrados

### Paso 3: Notificar usuarios
Los usuarios mirados deberán:
1. Intentar iniciar sesión con contraseña temporal (proporcionada en salida del script)
2. Acceder a "Cambiar Contraseña" en el perfil
3. Establecer su propia contraseña segura

## Ventajas de bcrypt

| Aspecto | MD5 | bcrypt |
|--------|-----|--------|
| **Seguridad** | ❌ Débil (rainbow tables) | ✅ Muy fuerte |
| **Salt** | No incluido | Incluido automáticamente |
| **Costo adaptable** | Fijo | Configurable (cost=12) |
| **Vulnerabilidad** | Hash rápido (malo para contraseñas) | Hash lento (bueno para contraseñas) |
| **Longitud hash** | 32 caracteres | 60 caracteres |

## Verificación después de migración

```bash
# Ejecutar en MySQL
SELECT 
    COUNT(*) as total_usuarios,
    SUM(CASE WHEN LENGTH(password) = 32 THEN 1 ELSE 0 END) as md5_count,
    SUM(CASE WHEN LENGTH(password) = 60 THEN 1 ELSE 0 END) as bcrypt_count
FROM usuarios;
```

**Resultado esperado:**
- md5_count = 0
- bcrypt_count = número total de usuarios

## Detalles técnicos

### Parámetros bcrypt
```php
password_hash($password, PASSWORD_BCRYPT, ['cost' => 12])
```

- **Algoritmo**: PASSWORD_BCRYPT
- **Cost**: 12 (equilibrio entre seguridad y velocidad)
  - Cost < 10: Demasiado rápido, menos seguro
  - Cost = 12: Recomendado (actual)
  - Cost > 14: Muy lento

### Cambios en funciones

#### Antes (MD5):
```php
$data['password'] = md5($data['password']);
if ($user['password'] === md5($password)) { /* OK */ }
```

#### Después (bcrypt):
```php
$data['password'] = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]);
if (password_verify($password, $user['password'])) { /* OK */ }
```

## Retrocompatibilidad

- ❌ No hay retrocompatibilidad automática
- Usuarios con contraseñas MD5 necesitan migración manual via `migrate_passwords.php`
- Nuevas contraseñas (registro, cambio) usan bcrypt automáticamente

## Troubleshooting

### Problema: El script no encuentra usuarios
```
Solución: Las contraseñas ya están en bcrypt. Sistema migrado correctamente.
```

### Problema: Error en migración
```bash
# Verificar permisos
# Verificar conexión a BD
# Revisar logs de error
tail -f php_errors.log
```

### Problema: Usuario no puede iniciar sesión
```
Solución: Cambiar contraseña desde "Cambiar Contraseña" en perfil
```

## Próximos pasos (Recomendado)

1. ✅ Ejecutar migración
2. ✅ Notificar usuarios de passwords temporales
3. ⏳ Implementar recuperación por email (TODO en `recoverPassword()`)
4. ⏳ Añadir validación de fuerza de contraseña (requisitos mínimos)
5. ⏳ Implementar autenticación de dos factores (2FA)

## Referencias

- [PHP password_hash() documentation](https://www.php.net/manual/es/function.password-hash.php)
- [OWASP Password Storage Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Password_Storage_Cheat_Sheet.html)
- [bcrypt Explained](https://blog.filippo.io/the-scrypt-parameters/)
